<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LibraryMember;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $head;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->head = User::where('username', 'headlibrarian')->firstOrFail();
    }

    public function test_superadmin_can_create_member_with_auto_member_code_and_qr(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->head)->postJson(route('api.v1.members.store'), [
            'member_role' => 'Student',
            'reference_number' => '136811112222',
            'last_name' => 'Reyes',
            'first_name' => 'Ana',
            'middle_initial' => 'B',
            'username' => 'anareyes',
            'email' => 'ana@ubnhs.test',
            'password' => 'password123',
        ]);

        $response->assertSuccessful();

        $member = LibraryMember::where('reference_number', '136811112222')->firstOrFail();
        $this->assertMatchesRegularExpression('/^UBNHS-\d{4}-\d{4}$/', $member->member_code);
        $this->assertEquals($member->member_code, $member->user->user_qr_value);
        $this->assertTrue($member->user->hasRole(Rbac::STUDENT));
        $this->assertNotNull($member->user->email_verified_at);
    }

    public function test_duplicate_reference_number_is_rejected_on_create_and_update(): void
    {
        $existing = LibraryMember::factory()->student()->create(['reference_number' => '136855556666']);
        $other = LibraryMember::factory()->faculty()->create(['reference_number' => '2026002']);

        // Duplicate on create
        $this->actingAs($this->head)->postJson(route('api.v1.members.store'), [
            'member_role' => 'Student',
            'reference_number' => '136855556666',
            'last_name' => 'Copy',
            'first_name' => 'Cat',
            'username' => 'copycat',
            'email' => 'copy@ubnhs.test',
            'password' => 'password123',
        ])->assertJsonValidationErrors('reference_number');

        // Duplicate on update
        $this->actingAs($this->head)->putJson(route('api.v1.members.update', $other), [
            'member_role' => 'Faculty',
            'reference_number' => $existing->reference_number,
            'last_name' => $other->last_name,
            'first_name' => $other->first_name,
            'username' => $other->user->username,
            'email' => $other->user->email,
        ])->assertJsonValidationErrors('reference_number');
    }

    public function test_superadmin_can_list_filter_and_update_member(): void
    {
        $student = LibraryMember::factory()->student()->create([
            'first_name' => 'Carlo',
            'last_name' => 'Mendoza',
        ]);
        $student->user->assignRole(Rbac::STUDENT);

        $this->actingAs($this->head)
            ->getJson(route('api.v1.members.index', ['q' => 'Mendoza', 'role' => 'Student']))
            ->assertOk()
            ->assertSee('Carlo')
            ->assertSee($student->member_code);

        $dept = Department::firstOrFail();

        $this->actingAs($this->head)->putJson(route('api.v1.members.update', $student), [
            'member_role' => 'Faculty',
            'department_id' => $dept->department_id,
            'reference_number' => '7654321',
            'last_name' => 'Mendoza',
            'first_name' => 'Carlos',
            'middle_initial' => 'R',
            'username' => $student->user->username,
            'email' => $student->user->email,
            'account_status' => User::STATUS_ACTIVE,
        ])->assertSuccessful();

        $student->refresh();
        $this->assertEquals('Carlos', $student->first_name);
        $this->assertEquals('Faculty', $student->member_role);
        $this->assertTrue($student->user->fresh()->hasRole(Rbac::FACULTY));
    }

    public function test_archiving_soft_deletes_member_and_restore_brings_it_back(): void
    {
        $member = LibraryMember::factory()->student()->create();
        $member->user->assignRole(Rbac::STUDENT);

        // Archive
        $this->actingAs($this->head)
            ->deleteJson(route('api.v1.members.destroy', $member))
            ->assertSuccessful();

        $this->assertSoftDeleted('library_member', ['member_id' => $member->member_id]);
        $this->assertSoftDeleted('users', ['id' => $member->account_id]);
        $this->assertEquals(User::STATUS_INACTIVE, User::withTrashed()->find($member->account_id)->account_status);

        // Visible in Archive Bin
        $this->actingAs($this->head)
            ->getJson(route('api.v1.members.archive'))
            ->assertOk()
            ->assertJsonFragment(['member_code'=>$member->member_code]);

        // Restore
        $this->actingAs($this->head)
            ->postJson(route('api.v1.members.restore', $member->member_id))
            ->assertOk();

        $this->assertNotSoftDeleted('library_member', ['member_id' => $member->member_id]);
        $this->assertNotSoftDeleted('users', ['id' => $member->account_id]);
        $this->assertEquals(User::STATUS_ACTIVE, User::find($member->account_id)->account_status);
    }

    public function test_staff_and_students_cannot_manage_or_archive_members(): void
    {
        $member = LibraryMember::factory()->student()->create();
        $staff = User::factory()->withRole(Rbac::STAFF)->create();
        $student = User::factory()->withRole(Rbac::STUDENT)->create();

        $this->actingAs($staff)->getJson(route('api.v1.members.index'))->assertForbidden();
        $this->actingAs($staff)->deleteJson(route('api.v1.members.destroy', $member))->assertForbidden();
        $this->actingAs($staff)->getJson(route('api.v1.members.archive'))->assertForbidden();

        $this->actingAs($student)->getJson(route('api.v1.members.index'))->assertForbidden();
        $this->actingAs($student)->postJson(route('api.v1.members.store'), [])->assertForbidden();
    }

    public function test_printable_qr_id_card_access_for_superadmin_and_owner_only(): void
    {
        Storage::fake('public');

        $memberA = LibraryMember::factory()->student()->create();
        $memberA->user->assignRole(Rbac::STUDENT);

        $memberB = LibraryMember::factory()->student()->create();
        $memberB->user->assignRole(Rbac::STUDENT);

        // Superadmin can view any member's QR ID card
        $this->actingAs($this->head)
            ->getJson(route('api.v1.members.qr-card', $memberA))
            ->assertOk()
            ->assertJsonStructure(['member_code','qrSvg'])
            ->assertSee($memberA->member_code);

        // Member A can view their own QR ID card via /my-qr-card and /members/{id}/qr-card
        $this->actingAs($memberA->user)
            ->getJson(route('api.v1.members.my-qr-card'))
            ->assertOk()
            ->assertSee($memberA->member_code);

        // Member A cannot view Member B's QR ID card
        $this->actingAs($memberA->user)
            ->getJson(route('api.v1.members.qr-card', $memberB))
            ->assertForbidden();
    }
}
