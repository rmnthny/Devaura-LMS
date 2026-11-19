<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Route::middleware(['web', 'secure', 'permission:roles.manage'])->get('/_test/roles', fn () => 'ok');
        Route::middleware(['web', 'secure', 'permission:circulation.process'])->get('/_test/circulation', fn () => 'ok');
        Route::middleware(['web', 'secure'])->get('/_test/home', fn () => 'ok');
    }

    public function test_seeder_creates_all_seven_roles(): void
    {
        $this->assertEqualsCanonicalizing(array_keys(Rbac::rolePermissions()), Role::pluck('name')->all());
        $this->assertCount(7, Role::all());
    }

    public function test_superadmin_has_every_permission(): void
    {
        $user = User::factory()->withRole(Rbac::SUPERADMIN)->create();

        foreach (Rbac::PERMISSIONS as $permission) {
            $this->assertTrue($user->can($permission), "superadmin missing {$permission}");
        }
    }

    public function test_members_cannot_access_personnel_permissions(): void
    {
        foreach (Rbac::MEMBER_ROLES as $role) {
            $user = User::factory()->withRole($role)->create();

            $this->assertTrue($user->can('qr.own'));
            $this->assertFalse($user->can('circulation.process'));
            $this->assertFalse($user->can('roles.manage'));
        }
    }

    public function test_only_superadmin_manages_roles(): void
    {
        foreach ([Rbac::ADMIN, Rbac::STAFF, Rbac::MAINTENANCE, Rbac::STUDENT, Rbac::FACULTY] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create())
                ->getJson('/_test/roles')->assertForbidden();
        }

        $this->actingAs(User::factory()->withRole(Rbac::SUPERADMIN)->create())
            ->getJson('/_test/roles')->assertOk();
    }

    public function test_admin_and_staff_can_process_circulation_but_members_cannot(): void
    {
        $this->actingAs(User::factory()->withRole(Rbac::ADMIN)->create())->getJson('/_test/circulation')->assertOk();
        $this->actingAs(User::factory()->withRole(Rbac::STAFF)->create())->getJson('/_test/circulation')->assertOk();
        $this->actingAs(User::factory()->withRole(Rbac::STUDENT)->create())->getJson('/_test/circulation')->assertForbidden();
    }

    public function test_staff_cannot_archive_master_records(): void
    {
        $staff = User::factory()->withRole(Rbac::STAFF)->create();

        $this->assertFalse($staff->can('members.delete'));
        $this->assertFalse($staff->can('books.delete'));
        $this->assertFalse($staff->can('personnel.delete'));
        $this->assertFalse($staff->can('audit.view'));
    }

    public function test_admin_has_member_report_and_audit_permissions_but_not_role_management(): void
    {
        $admin = User::factory()->withRole(Rbac::ADMIN)->create();

        $this->assertTrue($admin->can('members.manage'));
        $this->assertTrue($admin->can('members.delete'));
        $this->assertTrue($admin->can('reports.generate'));
        $this->assertTrue($admin->can('inventory.audit'));
        $this->assertTrue($admin->can('audit.view'));

        $this->assertFalse($admin->can('roles.manage'));
        $this->assertFalse($admin->can('books.delete'));
        $this->assertFalse($admin->can('personnel.delete'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->getJson('/_test/home')->assertUnauthorized();
    }

    public function test_inactive_account_is_logged_out(): void
    {
        $user = User::factory()->inactive()->withRole(Rbac::STUDENT)->create();

        $this->actingAs($user)->getJson('/_test/home')->assertForbidden();
        $this->assertGuest();
    }

    public function test_authenticated_pages_send_no_cache_headers(): void
    {
        $response = $this->actingAs(User::factory()->withRole(Rbac::STUDENT)->create())->getJson('/_test/home');

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
