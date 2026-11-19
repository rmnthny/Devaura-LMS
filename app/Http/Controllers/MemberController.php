<?php

namespace App\Http\Controllers;

use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\LibraryMember;
use App\Models\User;
use App\Services\QrCodeService;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function show(LibraryMember $member)
    {
        return new MemberResource($member);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $roleFilter = $request->query('role');

        $members = LibraryMember::with(['user.roles', 'department'])
            ->when(in_array($roleFilter, [LibraryMember::ROLE_STUDENT, LibraryMember::ROLE_FACULTY], true),
                fn ($q) => $q->where('member_role', $roleFilter))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%")
                ->orWhere('member_code', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->latest('member_id')
            ->paginate(15)
            ->withQueryString();

        return MemberResource::collection($members);
    }


    public function store(StoreMemberRequest $request, QrCodeService $qrService)
    {
        $data = $request->validated();

        $member = DB::transaction(function () use ($data, $qrService): LibraryMember {
            $memberCode = LibraryMember::generateMemberCode();

            $user = User::create([
                'username' => str()->uuid()->toString(),
                'email' => $data['email'],
                'password' => $data['password'],
                'account_status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'user_qr_value' => $memberCode,
            ]);

            $spatieRole = $data['member_role'] === LibraryMember::ROLE_FACULTY
                ? Rbac::FACULTY
                : Rbac::STUDENT;
            $user->assignRole($spatieRole);

            $member = LibraryMember::create([
                'account_id' => $user->id,
                'department_id' => $data['member_role'] === LibraryMember::ROLE_FACULTY ? ($data['department_id'] ?? null) : null,
                'member_role' => $data['member_role'],
                'reference_number' => $data['reference_number'],
                'member_code' => $memberCode,
                'last_name' => $data['last_name'],
                'first_name' => $data['first_name'],
                'middle_initial' => $data['middle_initial'] ?? null,
            ]);

            $qrService->generateMemberQrPng($memberCode);

            return $member;
        });

        return response()->json(['message' => "Member {$member->fullName()} ({$member->member_code}) created with QR code."], 201);
    }


    public function update(UpdateMemberRequest $request, LibraryMember $member)
    {
        $data = $request->validated();

        DB::transaction(function () use ($member, $data): void {
            $member->update([
                'member_role' => $data['member_role'],
                'department_id' => $data['member_role'] === LibraryMember::ROLE_FACULTY ? ($data['department_id'] ?? null) : null,
                'reference_number' => $data['reference_number'],
                'last_name' => $data['last_name'],
                'first_name' => $data['first_name'],
                'middle_initial' => $data['middle_initial'] ?? null,
            ]);

            $user = $member->user;
            if ($user) {
                $user->email = $data['email'];
                if (! empty($data['account_status']) && ! $user->hasRole(Rbac::SUPERADMIN)) {
                    $user->account_status = $data['account_status'];
                }
                $user->save();

                // Sync Spatie role only if the user currently holds a base member role.
                $currentRole = $user->getRoleNames()->first();
                if (in_array($currentRole, Rbac::MEMBER_ROLES, true)) {
                    $user->syncRoles([strtolower($data['member_role'])]);
                }
            }
        });

        return response()->json(['message' => "Updated member {$member->fullName()}."]);
    }

    public function destroy(Request $request, LibraryMember $member)
    {
        $user = $member->user;

        if ($user && ($user->hasRole(Rbac::SUPERADMIN) || $request->user()->is($user))) {
            throw ValidationException::withMessages(['member' => 'You cannot archive the Head Librarian or your own account.']);
        }

        DB::transaction(function () use ($request, $member, $user): void {
            if ($user) {
                $user->forceFill(['account_status' => User::STATUS_INACTIVE])->save();
                $user->delete();
            }
            $member->delete();

            activity('archive')
                ->causedBy($request->user())
                ->performedOn($member)
                ->log("Archived member {$member->fullName()} ({$member->member_code})");
        });

        return response()->json(['message' => "{$member->fullName()} was moved to the Archive Bin (retained for 6 months)."]);
    }

    public function archive(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $archived = LibraryMember::onlyTrashed()
            ->with(['user' => fn ($q) => $q->withTrashed()])
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%")
                ->orWhere('member_code', 'like', "%{$search}%")))
            ->latest('deleted_at')
            ->paginate(15)
            ->withQueryString();

        return MemberResource::collection($archived);
    }

    public function restore(Request $request, int $memberId)
    {
        $member = LibraryMember::onlyTrashed()->findOrFail($memberId);

        DB::transaction(function () use ($request, $member): void {
            $member->restore();

            $user = User::withTrashed()->find($member->account_id);
            if ($user) {
                $user->restore();
                $user->forceFill(['account_status' => User::STATUS_ACTIVE])->save();
            }

            activity('archive')
                ->causedBy($request->user())
                ->performedOn($member)
                ->log("Restored member {$member->fullName()} ({$member->member_code}) from archive");
        });

        return response()->json(['message' => "{$member->fullName()} has been restored."]);
    }

    public function qrCard(Request $request, LibraryMember $member, QrCodeService $qrService)
    {
        $actor = $request->user();
        abort_unless($actor->can('members.manage') || $member->account_id === $actor->id, 403);

        $member->load('user');
        $code = $this->ensureMemberQr($member, $qrService);

        return response()->json([
            'member_code' => $code,
            'qrSvg' => $qrService->getQrSvg($code, 6),
        ]);
    }

    public function myQrCard(Request $request, QrCodeService $qrService)
    {
        $member = $request->user()->member;

        if (! $member) {
            throw ValidationException::withMessages(['qr' => 'No library member profile is linked to this account.']);
        }

        return $this->qrCard($request, $member, $qrService);
    }

    private function ensureMemberQr(LibraryMember $member, QrCodeService $qrService): string
    {
        if (! $member->member_code) {
            $member->forceFill(['member_code' => LibraryMember::generateMemberCode()])->save();
        }

        if ($member->user && $member->user->user_qr_value !== $member->member_code) {
            $member->user->forceFill(['user_qr_value' => $member->member_code])->save();
        }

        $qrService->generateMemberQrPng($member->member_code);

        return $member->member_code;
    }
}
