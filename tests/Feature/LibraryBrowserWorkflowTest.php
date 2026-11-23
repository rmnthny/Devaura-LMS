<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\LibraryPersonnel;
use App\Models\SchedulePeriod;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryBrowserWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }


    public function test_staff_can_view_the_catalog_and_their_schedule_but_cannot_add_or_edit_books(): void
    {
        $staff = LibraryPersonnel::factory()->create();
        $other = LibraryPersonnel::factory()->create(['last_name' => 'Someone Else']);
        $period = SchedulePeriod::firstOrFail();
        $head = $this->headLibrarian();
        foreach ([$staff, $other] as $person) {
            $this->actingAs($head)->postJson(route('schedules.store'), [
                'personnel_id' => $person->getKey(), 'day_id' => 1, 'period_id' => $period->getKey(),
            ])->assertCreated();
        }
        $this->actingAs($staff->user)->get(route('schedules.index'))->assertOk()
            ->assertSee($staff->fullName())->assertDontSee('Someone Else')->assertDontSee('Add schedule');
        $this->get(route('books.index'))->assertOk()->assertDontSee(route('books.create'), false);
        $this->get(route('books.create'))->assertForbidden();
        $this->get(route('books.edit', Book::factory()->create()))->assertForbidden();
        $this->postJson(route('schedules.store'))->assertForbidden();
    }

    public function test_admin_cannot_schedule_another_admin_and_head_can_open_member_and_role_pages(): void
    {
        $admin = LibraryPersonnel::factory()->admin()->create();
        $other = LibraryPersonnel::factory()->admin()->create();
        $period = SchedulePeriod::firstOrFail();
        $schedule = ['personnel_id' => $other->getKey(), 'day_id' => 1, 'period_id' => $period->getKey()];
        $head = $this->headLibrarian();
        $this->actingAs($admin->user)->get(route('books.create'))->assertOk();
        $this->actingAs($admin->user)->postJson(route('schedules.store'), $schedule)->assertForbidden();
        $this->actingAs($head)->postJson(route('schedules.store'), $schedule)->assertCreated();
        $this->get(route('members.index'))->assertOk();
        $this->get(route('accounts.index'))->assertOk();
    }

    public function test_maintenance_sign_in_lands_on_profile_without_catalog_access(): void
    {
        $maintainer = User::factory()->withRole(Rbac::MAINTENANCE)->create(['password_hash' => 'Library123!']);
        $this->post(route('api.v1.login.attempt'), ['login' => $maintainer->email, 'password' => 'Library123!'])
            ->assertRedirect(route('profile.edit'));
        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('books.index'))->assertForbidden();
    }



}
