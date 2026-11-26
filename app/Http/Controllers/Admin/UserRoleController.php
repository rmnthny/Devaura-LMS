<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\LibraryAlerts;
use App\Services\RoleAssignmentService;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->query('q');
        $search = is_string($query) ? trim($query) : '';
        $roleFilter = $request->query('role');
        $isHead = $request->user()->hasRole(Rbac::SUPERADMIN);
        $filterableRoles = $isHead ? RoleAssignmentService::ASSIGNABLE : [Rbac::STUDENT, Rbac::STAFF];
        if ($isHead) {
            $filterableRoles[] = Rbac::SUPERADMIN;
        }

        $users = User::with(['roles', 'member', 'personnel.department'])
            ->when(! $isHead, fn ($q) => $q->role($filterableRoles)->whereHas('member')->has('roles', '=', 1)
                ->where('reference_number', 'like', str_repeat('_', 12))
                ->whereDoesntHave('roles', fn ($roles) => $roles->whereNotIn('name', $filterableRoles)))
            ->when(in_array($roleFilter, $filterableRoles, true), fn ($q) => $q->role($roleFilter))
            ->when($search !== '', function ($q) use ($search, $isHead) {
                foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
                    $q->where(fn ($w) => $w
                        ->when($isHead, fn ($fields) => $fields->where('email', 'like', "%{$term}%")
                            ->orWhere('reference_number', 'like', "%{$term}%")
                            ->orWhere('user_qr_value', 'like', "%{$term}%"))
                        ->orWhereHas('member', fn ($m) => $m
                            ->where('last_name', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%"))
                        ->orWhereHas('personnel', fn ($p) => $p
                            ->where('last_name', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%")));
                }
            })
            ->orderBy('email')
            ->paginate(15)
            ->withQueryString();

        if ($request->expectsJson()) {
            return $isHead ? UserResource::collection($users) : response()->json($users->through(fn (User $account) => [
                'account_id' => $account->getKey(),
                'name' => $account->fullName(),
                'role' => $account->getRoleNames()->first(),
                'status' => $account->account_status,
            ]));
        }

        return view('members.accounts', compact('users', 'search', 'roleFilter', 'filterableRoles', 'isHead'));
    }

    public function updateRole(Request $request, User $user, RoleAssignmentService $service)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(RoleAssignmentService::ASSIGNABLE)],
            'confirm' => ['accepted'],
            'current_password' => ['required', 'current_password'],
        ]);

        $service->assign($request->user(), $user, $data['role']);

        $message = "{$user->fullName()} is now ".ucfirst($data['role']).'.';

        return $request->expectsJson() ? response()->json(['message' => $message])
            : to_route('accounts.index')->with('status', $message);
    }

    public function transfer(Request $request, User $user, RoleAssignmentService $service)
    {
        $request->validate([
            'confirm' => ['accepted'],
            'current_password' => ['required', 'current_password'],
        ]);

        $service->transferSuperadmin($request->user(), $user);

        $message = "Head Librarian privilege transferred to {$user->fullName()}. Your account is now Faculty.";

        return $request->expectsJson() ? response()->json(['message' => $message])
            : to_route('profile.edit')->with('status', $message);
    }

    public function updateStatus(Request $request, User $user, LibraryAlerts $alerts)
    {
        $data = $request->validate(['account_status' => ['required', Rule::in([
            User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_LOCKED,
        ])]]);

        abort_if($request->user()->is($user), 422, 'You cannot change your own account status.');
        DB::transaction(function () use ($user, $data, $alerts): void {
            $account = User::lockForUpdate()->findOrFail($user->getKey());
            abort_if($account->account_status === User::STATUS_PENDING, 422, 'The member must verify their email first.');
            $account->update($data);
            if ($account->wasChanged('account_status')) {
                $alerts->accountUpdated($account, 'Your library account status changed to '.$account->account_status.'.');
            }
        });
        $message = "{$user->fullName()} is now {$data['account_status']}.";

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : to_route('accounts.index')->with('status', $message);
    }
}
