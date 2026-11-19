<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Superadmin: assign personnel roles, transfer superadmin, activate/deactivate accounts. */
class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $users = User::with(['roles', 'member', 'personnel.department'])
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhereHas('member', fn ($m) => $m
                    ->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%"))))
            ->orderBy('username')
            ->paginate(15)
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function updateRole(Request $request, User $user, RoleAssignmentService $service)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(RoleAssignmentService::ASSIGNABLE)],
            'department_id' => ['nullable', 'exists:department,department_id'],
            'confirm' => ['accepted'],
            'current_password' => ['required', 'current_password'],
        ]);

        $service->assign($request->user(), $user, $data['role'], $data['department_id'] ?? null);

        return response()->json(['message' => "{$user->fullName()} is now {$data['role']}."]);
    }

    public function transfer(Request $request, User $user, RoleAssignmentService $service)
    {
        $request->validate([
            'confirm' => ['accepted'],
            'current_password' => ['required', 'current_password'],
        ]);

        $service->transferSuperadmin($request->user(), $user);

        return response()->json(['message' => "Superadmin privilege transferred to {$user->fullName()}. You are now a Faculty member."]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $data = $request->validate(['account_status' => ['required', Rule::in([
            User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_LOCKED,
        ])]]);

        abort_if($request->user()->is($user), 422, 'You cannot change your own account status.');

        $user->update($data);

        return response()->json(['message' => "{$user->fullName()} is now {$data['account_status']}."]);
    }
}
