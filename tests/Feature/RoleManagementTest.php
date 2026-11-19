<?php

namespace Tests\Feature;

use App\Models\LibraryMember;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $head;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->head = User::where('username', 'headlibrarian')->firstOrFail();
    }

    private function member(string $type): User
    {
        $member = LibraryMember::factory()->{strtolower($type)}()->create();
        $member->user->assignRole(strtolower($type));

        return $member->user->fresh();
    }

    public function test_superadmin_promotes_faculty_to_admin_and_student_to_staff(): void
    {
        $faculty = $this->member('Faculty');
        $student = $this->member('Student');

        // Missing strict confirmation or wrong password is rejected
        $this->actingAs($this->head)->patchJson(route('api.v1.admin.users.role', $faculty), [
            'role' => 'admin',
        ])->assertJsonValidationErrors(['confirm', 'current_password']);

        $this->actingAs($this->head)->patchJson(route('api.v1.admin.users.role', $faculty), [
            'role' => 'admin',
            'confirm' => 1,
            'current_password' => 'password',
        ])->assertSuccessful();

        $this->actingAs($this->head)->patchJson(route('api.v1.admin.users.role', $student), [
            'role' => 'staff',
            'confirm' => 1,
            'current_password' => 'password',
        ])->assertSuccessful();

        $this->assertTrue($faculty->fresh()->hasRole(Rbac::ADMIN));
        $this->assertNotNull($faculty->fresh()->personnel);
        $this->assertTrue($student->fresh()->hasRole(Rbac::STAFF));
    }

    public function test_cross_type_promotion_is_rejected(): void
    {
        $student = $this->member('Student');
        $faculty = $this->member('Faculty');

        $this->actingAs($this->head)->patchJson(route('api.v1.admin.users.role', $student), [
            'role' => 'admin', 'confirm' => 1, 'current_password' => 'password',
        ])->assertJsonValidationErrors('role');

        $this->actingAs($this->head)->patchJson(route('api.v1.admin.users.role', $faculty), [
            'role' => 'staff', 'confirm' => 1, 'current_password' => 'password',
        ])->assertJsonValidationErrors('role');
    }

    public function test_demoting_staff_back_to_student_archives_personnel_record(): void
    {
        $student = $this->member('Student');
        $this->actingAs($this->head)->patchJson(route('api.v1.admin.users.role', $student), [
            'role' => 'staff', 'confirm' => 1, 'current_password' => 'password',
        ]);
        $this->actingAs($this->head)->patchJson(route('api.v1.admin.users.role', $student), [
            'role' => 'student', 'confirm' => 1, 'current_password' => 'password',
        ]);

        $student->refresh();
        $this->assertTrue($student->hasRole(Rbac::STUDENT));
        $this->assertNull($student->personnel);
    }

    public function test_admin_and_members_cannot_manage_roles(): void
    {
        $admin = $this->member('Faculty');
        $admin->syncRoles([Rbac::ADMIN]);
        $target = $this->member('Student');

        $this->actingAs($admin)->getJson(route('api.v1.admin.users.index'))->assertForbidden();
        $this->actingAs($admin)->patchJson(route('api.v1.admin.users.role', $target), ['role' => 'staff'])->assertForbidden();
        $this->actingAs($target)->getJson(route('api.v1.admin.users.index'))->assertForbidden();
    }

    public function test_superadmin_transfer_leaves_exactly_one_superadmin(): void
    {
        $newHead = $this->member('Faculty');

        $this->actingAs($this->head)
            ->postJson(route('api.v1.admin.users.transfer', $newHead), ['confirm' => 1, 'current_password' => 'password'])
            ->assertOk();

        $this->assertTrue($newHead->fresh()->hasRole(Rbac::SUPERADMIN));
        $this->assertTrue($this->head->fresh()->hasRole(Rbac::FACULTY));
        $this->assertEquals(1, User::role(Rbac::SUPERADMIN)->count());
    }

    public function test_superadmin_cannot_be_transferred_to_a_student(): void
    {
        $student = $this->member('Student');

        $this->actingAs($this->head)
            ->postJson(route('api.v1.admin.users.transfer', $student), ['confirm' => 1, 'current_password' => 'password'])
            ->assertJsonValidationErrors('role');

        $this->assertTrue($this->head->fresh()->hasRole(Rbac::SUPERADMIN));
    }

    public function test_management_pages_render_for_superadmin(): void
    {
        $this->member('Faculty');

        $this->actingAs($this->head)->getJson(route('api.v1.admin.users.index'))->assertOk()->assertJsonStructure(['data']);
        $this->actingAs($this->head)->getJson(route('api.v1.me'))->assertOk();
    }

    public function test_user_updates_own_personal_info_and_password(): void
    {
        $student = $this->member('Student');

        $this->actingAs($student)->putJson(route('api.v1.profile.update'), [
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'middle_initial' => 'L',
            'current_password' => 'password',
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertSuccessful();

        $student->refresh();
        $this->assertEquals('Santos', $student->member->last_name);
        $this->assertTrue(\Hash::check('newpassword1', $student->password));
    }
}
