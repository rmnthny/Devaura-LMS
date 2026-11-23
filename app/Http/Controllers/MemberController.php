<?php

namespace App\Http\Controllers;

use App\Http\Requests\Members\SaveMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Department;
use App\Models\LibraryMember;
use App\Models\User;
use App\Services\ArchiveBin;
use App\Services\ImportedMember;
use App\Services\QrCodeService;
use App\Services\MemberProfileService;
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
        $query = $request->query('q');
        $search = is_string($query) ? trim($query) : '';
        $roleFilter = $request->query('role');
        $statusFilter = $request->query('status');
        $allowedStatuses = [User::STATUS_ACTIVE, User::STATUS_PENDING, User::STATUS_INACTIVE, User::STATUS_LOCKED];

        $members = LibraryMember::with(['user.roles', 'user.personnel.department'])->whereHas('user')
            ->when(in_array($roleFilter, [LibraryMember::ROLE_STUDENT, LibraryMember::ROLE_FACULTY], true),
                fn ($q) => $q->whereHas('user', fn ($u) => $u->where('reference_number', 'like',
                    str_repeat('_', $roleFilter === LibraryMember::ROLE_FACULTY ? 7 : 12))))
            ->when(in_array($statusFilter, $allowedStatuses, true),
                fn ($q) => $q->whereHas('user', fn ($u) => $u->where('account_status', $statusFilter)))
            ->when($search !== '', function ($q) use ($search) {
                foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
                    $pattern = '%'.strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                    $q->where(function ($member) use ($term, $pattern) {
                        $member->whereRaw("last_name LIKE ? ESCAPE '!'", [$pattern])
                            ->orWhereRaw("first_name LIKE ? ESCAPE '!'", [$pattern])
                            ->orWhereHas('user', fn ($user) => $user
                                ->whereRaw("username LIKE ? ESCAPE '!'", [$pattern])
                                ->orWhereRaw("reference_number LIKE ? ESCAPE '!'", [$pattern])
                                ->orWhereRaw("user_qr_value LIKE ? ESCAPE '!'", [$pattern])
                                ->orWhereRaw("email LIKE ? ESCAPE '!'", [$pattern]));

                        if (preg_match('/\A\p{L}\p{M}*\.?\z/u', $term)) {
                            $initial = rtrim($term, '.');
                            $member->orWhereIn('middle_initial', [$initial, $initial.'.']);
                        }
                    });
                }
            })
            ->latest('member_id')
            ->paginate(15)
            ->withQueryString();

        return $request->expectsJson() ? MemberResource::collection($members)
            : view('members.index', compact('members', 'search', 'roleFilter', 'statusFilter', 'allowedStatuses'));
    }

    public function edit(Request $request, LibraryMember $member)
    {
        $this->authorizeMemberEdit($request, $member);

        return $this->form($member);
    }

    public function create()
    {
        return $this->form(new LibraryMember);
    }

    private function form(LibraryMember $member)
    {
        return view('members.form', [
            'member' => $member,
            'departments' => Department::orderBy('department_name')->get(),
        ]);
    }

    public function store(SaveMemberRequest $request, ImportedMember $members, MemberProfileService $profiles)
    {
        $data = $request->validated() + ['department_id' => null, 'middle_initial' => null];

        DB::transaction(function () use ($request, $members, $profiles, $data): void {
            if (! $members->create($data, $profiles)) {
                throw ValidationException::withMessages([
                    'email' => 'The email address or reference number already belongs to an existing or archived account.',
                ]);
            }

            $account = User::where('email', $data['email'])->firstOrFail();
            activity('members')->causedBy($request->user())->performedOn($account)
                ->event('created')->log('Created a member account manually');
        });

        $message = 'Member account created. The welcome email is queued.';

        return $request->expectsJson() ? response()->json(['message' => $message], 201)
            : to_route('members.index')->with('status', $message);
    }

    public function update(SaveMemberRequest $request, LibraryMember $member, MemberProfileService $profiles)
    {
        $this->authorizeMemberEdit($request, $member);
        $data = $request->validated();
        if ($member->user->hasAnyRole(Rbac::PERSONNEL_ROLES) && $member->member_role !== $data['member_role']) {
            throw ValidationException::withMessages([
                'member_role' => 'Remove the personnel role before changing the membership type.',
            ]);
        }

        DB::transaction(function () use ($member, $data, $profiles): void {
            $user = $member->user;
            if ($user) {
                $user->email = $data['email'];
                $user->reference_number = $data['reference_number'];
                if (! empty($data['account_status']) && $user->account_status !== User::STATUS_PENDING
                    && ! $user->hasRole(Rbac::SUPERADMIN)) {
                    $user->account_status = $data['account_status'];
                }
                $user->save();
                $profiles->save($user, $data);

                // Updating membership must not remove a staff or admin assignment.
                $currentRole = $user->getRoleNames()->first();
                if (in_array($currentRole, Rbac::MEMBER_ROLES, true)) {
                    $user->syncRoles([strtolower($data['member_role'])]);
                }
            }
        });

        return $request->expectsJson() ? response()->json(['message' => "Updated member {$member->refresh()->fullName()}."])
            : to_route('members.index')->with('status', 'Member updated.');
    }

    public function destroy(Request $request, LibraryMember $member, ArchiveBin $archive)
    {
        $archive->archiveMember($request->user(), $member);

        return $request->expectsJson()
            ? response()->json(['message' => "{$member->fullName()} was moved to the Archive Bin (retained for 6 months)."])
            : to_route('members.index')->with('status', 'Member archived.');
    }

    public function archive(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $archived = LibraryMember::onlyTrashed()
            ->with(['user' => fn ($q) => $q->withTrashed()])
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('last_name', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->withTrashed()
                    ->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('user_qr_value', 'like', "%{$search}%"))))
            ->latest('deleted_at')
            ->paginate(15)
            ->withQueryString();

        return MemberResource::collection($archived);
    }

    public function restore(Request $request, int $memberId, ArchiveBin $archive)
    {
        $member = $archive->restore($request->user(), LibraryMember::class, $memberId);

        return $request->expectsJson()
            ? response()->json(['message' => "{$member->fullName()} has been restored."])
            : to_route('archive.index', ['section' => 'members'])->with('status', 'Member restored.');
    }

    public function qrSheet(Request $request, QrCodeService $qrService)
    {
        $validated = $request->validate([
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $members = LibraryMember::with('user.personnel.department')
            ->whereIn('member_id', $validated['member_ids'])
            ->whereHas('user', fn ($user) => $user->whereNotNull('user_qr_value')->where('user_qr_value', '<>', ''))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        if ($members->count() !== count($validated['member_ids'])) {
            throw ValidationException::withMessages([
                'member_ids' => 'One or more selected QR cards are unavailable. Refresh Member Management and select the cards again.',
            ]);
        }

        $items = $members->map(function (LibraryMember $member) use ($qrService) {
            $dept = $member->user?->personnel?->department?->department_name;
            $idLabel = $member->member_role === LibraryMember::ROLE_FACULTY ? 'DepEd ID' : 'LRN';

            return [
                'title' => $member->fullName(),
                'subtitle' => $dept ? "{$member->member_role} · {$dept}" : $member->member_role,
                'code' => $member->member_code,
                'footer' => "{$idLabel}: {$member->reference_number}",
                'png' => $qrService->getQrPngBase64($member->member_code, 6),
            ];
        })->values()->all();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.qr-sheet', [
            'title' => 'Member QR Cards',
            'columns' => 2,
            'items' => $items,
        ])->setPaper('a4')->stream('member-qr-cards.pdf');
    }

    public function qrCard(Request $request, LibraryMember $member, QrCodeService $qrService)
    {
        $actor = $request->user();
        abort_unless($actor->can('members.manage') || $member->account_id === $actor->getKey(), 403);

        $member->load('user.personnel.department');
        $code = $member->member_code;
        abort_if($code === null, 404);

        if (! $request->expectsJson()) {
            $png = $qrService->getQrPngBase64($code);

            return view('members.qr', compact('member', 'code', 'png'));
        }

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

    private function authorizeMemberEdit(Request $request, LibraryMember $member): void
    {
        abort_unless($member->user, 404);
        abort_if($member->user->hasRole(Rbac::SUPERADMIN)
            || ($member->user->hasAnyRole(Rbac::PERSONNEL_ROLES) && $request->user()->cannot('roles.manage')), 403);
    }
}
