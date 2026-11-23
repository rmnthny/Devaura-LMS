<?php

namespace Tests\Feature;

use App\Mail\WelcomeMemberMail;
use App\Models\Department;
use App\Models\LibraryMember;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $head;

    public function test_only_the_head_librarian_can_add_a_member_manually(): void
    {
        Mail::fake();
        $data = [
            'member_role' => 'Student',
            'reference_number' => '000123456789',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'email' => 'manual@example.test',
        ];

        foreach ([Rbac::ADMIN, Rbac::STAFF] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create());
            $this->getJson(route('members.create'))->assertForbidden();
            $this->postJson(route('api.v1.members.store'), $data)->assertForbidden();
        }

        $this->actingAs($this->head)->get(route('members.create'))->assertOk()->assertSee('Add member');
        $this->postJson(route('api.v1.members.store'), [...$data, 'reference_number' => '1234567'])
            ->assertUnprocessable()->assertJsonValidationErrors('reference_number');
        $this->postJson(route('api.v1.members.store'), $data)->assertCreated();
        $member = User::where('email', $data['email'])->sole();
        $this->assertSame($data['reference_number'], $member->reference_number);
        $this->assertTrue($member->hasRole(Rbac::STUDENT));
        $this->postJson(route('api.v1.members.store'), $data)->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'reference_number']);
        Mail::assertQueued(WelcomeMemberMail::class, fn (WelcomeMemberMail $mail) => $mail->hasTo($member->email));
        Mail::assertQueuedCount(1);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->head = $this->headLibrarian();
    }


    public function test_duplicate_reference_number_is_rejected_on_update(): void
    {
        $existing = User::factory()->student()->create(['reference_number' => '136855556666']);
        $other = LibraryMember::factory()->student()->create();

        $this->actingAs($this->head)->putJson(route('api.v1.members.update', $other), [
            'member_role' => 'Student',
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
        $code = $member->member_code;

        $this->actingAs($this->head)
            ->deleteJson(route('api.v1.members.destroy', $member))
            ->assertSuccessful();

        $this->assertSoftDeleted('library_member', ['member_id' => $member->member_id]);
        $this->assertSoftDeleted('user_account', ['account_id' => $member->account_id]);

        $this->actingAs($this->head)
            ->getJson(route('api.v1.members.archive'))
            ->assertOk()
            ->assertJsonFragment(['member_code' => $code]);

        $this->actingAs($this->head)
            ->postJson(route('api.v1.members.restore', $member->member_id))
            ->assertOk();

        $this->assertNotSoftDeleted('library_member', ['member_id' => $member->member_id]);
        $this->assertNotSoftDeleted('user_account', ['account_id' => $member->account_id]);
        $this->assertEquals(User::STATUS_ACTIVE, User::find($member->account_id)->account_status);
    }

    public function test_directory_handles_full_names_and_literal_search_characters(): void
    {
        $member = LibraryMember::factory()->student()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_initial' => 'M.',
        ]);
        $this->actingAs($this->head)
            ->getJson(route('api.v1.members.index', ['q' => $member->fullName()]))
            ->assertOk()->assertJsonCount(1, 'data')->assertSee($member->member_code);
        $this->get(route('members.index', ['q' => '%']))
            ->assertOk()->assertSee('No members match these filters.')->assertDontSee($member->member_code);
        $this->get(route('members.index', ['page' => 999]))
            ->assertOk()->assertSee('Back to first page');
    }

    public function test_qr_sheet_rejects_an_archived_card_in_the_selection(): void
    {
        Storage::fake('local');
        $current = LibraryMember::factory()->student()->create();
        $archived = LibraryMember::factory()->student()->create();
        $archived->delete();

        $this->actingAs($this->head)->postJson(route('members.qr-sheet'), [
            'member_ids' => [$current->getKey(), $archived->getKey()],
        ])->assertUnprocessable()->assertJsonValidationErrors('member_ids');
        $this->post(route('members.qr-sheet'), ['member_ids' => [$current->getKey()]])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_head_librarian_can_permanently_delete_a_confirmed_archived_member(): void
    {
        $member = LibraryMember::factory()->student()->create();
        $member->user->assignRole(Rbac::STUDENT);
        $route = route('archive.members.purge', $member);

        $this->actingAs($this->head)->deleteJson($route, ['confirmation' => 'DELETE'])->assertNotFound();
        $this->deleteJson(route('api.v1.members.destroy', $member))->assertSuccessful();
        $this->get(route('archive.index', ['section' => 'members']))->assertOk()
            ->assertSee('Archived members')->assertSee('Delete permanently')->assertDontSee('Type DELETE to confirm');
        $review = route('archive.members.confirm-purge', $member);
        $this->get($review)->assertOk()->assertSee($member->fullName())->assertSee('Type DELETE to confirm');
        $this->deleteJson($route, ['confirmation' => 'Delete'])->assertJsonValidationErrors('confirmation');
        $this->actingAs(User::factory()->admin()->create())->deleteJson($route, ['confirmation' => 'DELETE'])->assertForbidden();
        $this->get($review)->assertForbidden();
        $this->actingAs($this->head)->deleteJson($route, ['confirmation' => 'DELETE'])->assertNoContent();
        $this->assertDatabaseMissing('library_member', ['member_id' => $member->getKey()]);
        $this->assertDatabaseMissing('user_account', ['account_id' => $member->account_id]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'archive', 'event' => 'purged', 'subject_id' => $member->getKey()]);
    }

    public function test_permanent_delete_preserves_a_member_with_attendance_history(): void
    {
        $member = LibraryMember::factory()->student()->create();
        DB::table('attendance_log')->insert([
            'account_id' => $member->account_id, 'user_role' => 'Student', 'time_in_at' => now(),
        ]);
        $this->actingAs($this->head)->deleteJson(route('api.v1.members.destroy', $member))->assertSuccessful();
        $this->get(route('archive.members.confirm-purge', $member))->assertOk()
            ->assertSee('linked to library history')->assertDontSee('Type DELETE to confirm');
        $this->deleteJson(route('archive.members.purge', $member), ['confirmation' => 'DELETE'])
            ->assertUnprocessable()->assertJsonValidationErrors('record');
        $this->assertSoftDeleted('library_member', ['member_id' => $member->getKey()]);
        $this->assertSoftDeleted('user_account', ['account_id' => $member->account_id]);
    }

    public function test_stale_welcome_email_is_discarded_for_an_archived_member(): void
    {
        $member = LibraryMember::factory()->student()->create();
        $mail = new \App\Mail\WelcomeMemberMail(
            $member->fullName(), $member->user->email, 'Student', null,
            '••••0000', $member->member_code, 'password',
        );
        $this->actingAs($this->head)->deleteJson(route('api.v1.members.destroy', $member))->assertSuccessful();
        $mailer = \Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class);
        $mailer->shouldNotReceive('send');
        $this->assertNull($mail->send($mailer));
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
        $this->actingAs($student)->postJson(route('members.import'), [])->assertForbidden();
    }

    public function test_printable_qr_id_card_access_for_superadmin_and_owner_only(): void
    {
        Storage::fake('local');

        $memberA = LibraryMember::factory()->student()->create();
        $memberA->user->assignRole(Rbac::STUDENT);

        $memberB = LibraryMember::factory()->student()->create();
        $memberB->user->assignRole(Rbac::STUDENT);

        $this->actingAs($this->head)
            ->getJson(route('api.v1.members.qr-card', $memberA))
            ->assertOk()
            ->assertJsonStructure(['member_code','qrSvg'])
            ->assertSee($memberA->member_code);

        $this->actingAs($memberA->user)
            ->getJson(route('api.v1.members.my-qr-card'))
            ->assertOk()
            ->assertSee($memberA->member_code);

        $this->actingAs($memberA->user)
            ->getJson(route('api.v1.members.qr-card', $memberB))
            ->assertForbidden();
    }
}
