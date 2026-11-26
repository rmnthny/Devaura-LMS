<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\ScheduleDay;
use App\Models\SchedulePeriod;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SprintThreeAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private ?int $staffHealthStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();

        $healthUrl = rtrim(config('app.url'), '/').'/up';
        Http::preventStrayRequests()->fake([
            $healthUrl => fn () => $this->staffHealthStatus === null
                ? Http::failedConnection()
                : Http::response(status: $this->staffHealthStatus),
        ]);
    }

    public function test_the_kiosk_rejects_entries_when_the_staff_app_is_down_and_recovers(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->headLibrarian())
            ->postJson($this->staffRoute('attendance.own'), ['action' => 'in'])->assertOk();
        $member = User::factory()->student()->create();
        $kiosk = config('attendance.kiosk_url').'/kiosk';
        $entry = ['type' => 'account', 'action' => 'in', 'identifier' => $member->reference_number];
        $this->get($kiosk)->assertOk()->assertSee('Library member');

        foreach ([null, 503, 302] as $unhealthyStatus) {
            $this->staffHealthStatus = $unhealthyStatus;
            $this->get($kiosk)->assertServiceUnavailable()
                ->assertSee('Public attendance is temporarily unavailable.')
                ->assertDontSee('Library member')->assertDontSee('Walk-in visitor');
            $this->post($kiosk, $entry)->assertServiceUnavailable()
                ->assertSee('Try again');
            $this->postJson($kiosk, $entry)->assertServiceUnavailable()
                ->assertJsonPath('message', 'Public attendance is temporarily unavailable.');
        }

        $this->assertDatabaseMissing('attendance_log', ['account_id' => $member->getKey()]);
        $this->staffHealthStatus = 200;
        $this->get($kiosk)->assertOk()->assertSee('Library member');
        $this->postJson($kiosk, $entry)->assertOk()->assertJsonPath('message', 'Time In recorded.');
    }

    public function test_the_public_origin_only_serves_the_kiosk_even_with_staff_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);
        $kiosk = config('attendance.kiosk_url').'/kiosk';
        $origin = config('attendance.kiosk_url');

        $this->actingAs($this->headLibrarian());
        $this->get('/kiosk')->assertRedirect($kiosk);
        $this->post('/kiosk')->assertNotFound();

        foreach (['/attendance', '/login', '/books', '/api/v1/me'] as $path) {
            $this->get($origin.$path)->assertRedirect($kiosk);
        }
        $this->get('http://localhost:8001/attendance')->assertRedirect($kiosk);
        $this->postJson($origin.'/attendance/own', ['action' => 'in'])->assertNotFound();
        $this->assertDatabaseCount('attendance_log', 0);

        $staffCookie = config('session.cookie');
        $response = $this->get($kiosk)->assertOk()->assertSee('Public Kiosk')
            ->assertSee('Kiosk closed')->assertDontSee('Personnel Management')
            ->assertCookie($staffCookie.'_kiosk')->assertCookieMissing($staffCookie)
            ->assertCookieMissing('XSRF-TOKEN');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_head_librarian_can_cover_the_kiosk_without_a_schedule_after_hours(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->travelTo(now('Asia/Manila')->setTime(21, 0)->utc());
        $kiosk = config('attendance.kiosk_url').'/kiosk';
        $head = $this->headLibrarian();
        $this->actingAs($head)->get($this->staffRoute('attendance.index'))->assertSee('disabled>Open attendance kiosk', false);
        $this->postJson($this->staffRoute('attendance.own'), ['action' => 'in'])->assertOk();
        $this->get($this->staffRoute('attendance.index'))->assertSee('href="'.$kiosk.'" target="_blank"', false);
        $member = User::factory()->student()->create();
        $response = $this->from($this->staffRoute('attendance.index'))->post($kiosk, [
            'type' => 'account', 'identifier' => $member->reference_number, 'action' => 'in',
        ])->assertRedirect($kiosk)->assertSessionHas('kiosk_status', 'Time In recorded.');
        $this->withCookie(config('session.cookie').'_kiosk', session()->getId())->get($response->headers->get('Location'))
            ->assertOk()->assertSee('Resume')->assertSee('href="'.$kiosk.'"', false)
            ->assertDontSee('href="'.$this->staffRoute('attendance.index').'"', false);
        $this->postJson($this->staffRoute('attendance.own'), ['action' => 'out'])->assertOk();
        $this->get($kiosk)->assertSee('Kiosk closed');
    }

    public function test_transferring_head_librarian_does_not_turn_an_open_faculty_visit_into_coverage(): void
    {
        $this->seed(DatabaseSeeder::class);
        $head = $this->headLibrarian();
        $successor = LibraryMember::factory()->faculty()->create()->user;
        LibraryPersonnel::factory()->forAccount($successor)->create();
        $kiosk = config('attendance.kiosk_url').'/kiosk';

        $this->actingAs($head)->postJson($this->staffRoute('attendance.record'), [
            'type' => 'account', 'action' => 'in', 'identifier' => $successor->reference_number,
        ])->assertOk();
        $this->get($kiosk)->assertSee('Kiosk closed');

        $this->postJson($this->staffRoute('api.v1.admin.users.transfer', $successor), [
            'confirm' => 1, 'current_password' => 'password',
        ])->assertOk();
        $this->get($kiosk)->assertSee('Kiosk closed');
        $this->assertDatabaseHas('attendance_log', [
            'account_id' => $successor->getKey(), 'user_role' => 'faculty', 'time_out_at' => null,
        ]);

        $this->actingAs($successor->fresh())->postJson($this->staffRoute('attendance.own'), ['action' => 'out'])->assertOk();
        $this->postJson($this->staffRoute('attendance.own'), ['action' => 'in'])->assertOk();
        $this->get($kiosk)->assertSee('Library member');
    }

    public function test_assigned_personnel_keep_the_kiosk_open_through_a_handover(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->travelTo(now('Asia/Manila')->setTime(9, 0)->utc());
        $member = User::factory()->student()->create();
        $kiosk = config('attendance.kiosk_url').'/kiosk';
        $entry = ['type' => 'account', 'identifier' => $member->reference_number, 'action' => 'in'];
        $this->get($kiosk)->assertOk()->assertSee('Kiosk closed');
        $this->postJson($kiosk, $entry)->assertUnprocessable();
        $this->getJson($this->staffRoute('attendance.index'))->assertUnauthorized();

        $first = LibraryPersonnel::factory()->admin()->create();
        $second = LibraryPersonnel::factory()->create();
        $this->actingAs($first->user)->postJson($this->staffRoute('attendance.own'), ['action' => 'in'])->assertOk();
        $this->get($kiosk)->assertSee('Kiosk closed');
        $this->get($this->staffRoute('attendance.index'))->assertSee('disabled>Open attendance kiosk', false)
            ->assertDontSee('href="'.$kiosk.'"', false);
        $day = ScheduleDay::where('day_name', now('Asia/Manila')->format('l'))->firstOrFail();
        $period = SchedulePeriod::where('period_name', 'Morning')->firstOrFail();
        foreach ([$first, $second] as $staff) {
            $staff->schedules()->insert(['personnel_id' => $staff->getKey(), 'day_id' => $day->getKey(), 'period_id' => $period->getKey()]);
        }
        $this->get($kiosk)->assertSee('Library member')->assertDontSee($first->user->email);
        $this->get($this->staffRoute('attendance.index'))->assertSee('href="'.$kiosk.'"', false);
        $this->get($this->staffRoute('attendance.index'))->assertSee('Timing out now will close the kiosk');
        $this->travelTo(now('Asia/Manila')->setTime(12, 0)->utc(), function () use ($kiosk): void {
            $this->get($kiosk)->assertSee('Kiosk closed');
        });
        $this->travelTo(now('Asia/Manila')->setTime(9, 0)->utc());
        $this->postJson($kiosk, ['type' => 'account', 'identifier' => $second->user->reference_number, 'action' => 'in'])
            ->assertUnprocessable();
        $this->postJson($kiosk, $entry + ['visit_purpose' => 'Visitor purpose only'])->assertOk();
        $this->assertDatabaseHas('attendance_log', ['account_id' => $member->getKey(), 'visit_purpose' => null]);
        $this->postJson($kiosk, $entry)->assertOk()->assertSee('Already timed in');
        $this->assertSame(1, \DB::table('attendance_log')->where('account_id', $member->getKey())->count());
        $member->update(['account_status' => User::STATUS_LOCKED]);
        $this->postJson($kiosk, [...$entry, 'action' => 'out'])->assertUnprocessable();
        $visitor = ['type' => 'visitor', 'email' => 'guest@example.test'];
        $this->postJson($kiosk, $visitor + ['action' => 'out'])->assertUnprocessable();
        $this->postJson($kiosk, $visitor + ['action' => 'in', 'first_name' => 'Juan',
            'last_name' => 'Dela Cruz', 'visit_purpose' => 'Reading'])->assertOk();
        $this->postJson($kiosk, $visitor + ['action' => 'out'])->assertOk();
        $this->actingAs($second->user)->postJson($this->staffRoute('attendance.own'), ['action' => 'in'])->assertOk();
        $this->get($this->staffRoute('attendance.index'))->assertDontSee('Timing out now will close the kiosk');
        $this->actingAs($first->user)->postJson($this->staffRoute('attendance.own'), ['action' => 'out'])->assertOk();
        $this->get($kiosk)->assertSee('Library member');
        $this->actingAs($second->user)->postJson($this->staffRoute('attendance.own'), ['action' => 'out'])->assertOk()
            ->assertSee('The kiosk is closed');
        $this->postJson($kiosk, [...$entry, 'action' => 'out'])->assertUnprocessable();
        $this->assertDatabaseHas('attendance_log', ['account_id' => $member->getKey(), 'time_out_at' => null]);
    }

    public function test_assisted_visitor_records_and_forced_time_out_are_audited(): void
    {
        $this->seed(DatabaseSeeder::class);
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();
        $entry = ['type' => 'visitor', 'action' => 'in', 'email' => 'visitor@example.test',
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'middle_initial' => 'm', 'visit_purpose' => 'Research'];
        $this->actingAs($staff)->postJson($this->staffRoute('attendance.record'), $entry)->assertOk();
        $this->assertDatabaseHas('walk_in_visitor', ['email' => 'visitor@example.test', 'middle_initial' => 'M.']);
        $visit = AttendanceLog::sole();
        $this->get($this->staffRoute('attendance.index', ['status' => 'open', 'role' => 'visitor']))
            ->assertOk()->assertSee('Research');
        $this->postJson($this->staffRoute('attendance.force', $visit), ['reason' => 'Forgot to time out'])->assertForbidden();
        $this->actingAs($admin)->postJson($this->staffRoute('attendance.force', $visit), [])->assertJsonValidationErrors('reason');
        $this->postJson($this->staffRoute('attendance.force', $visit), ['reason' => 'Forgot to time out'])->assertNoContent();
        $this->assertNotNull($visit->fresh()->time_out_at);
        $this->assertDatabaseHas('activity_log', ['event' => 'forced_time_out', 'causer_id' => $admin->getKey()]);
        $this->postJson($this->staffRoute('attendance.record'), [...$entry, 'visit_purpose' => 'Return a book'])->assertOk();
        $this->assertDatabaseCount('walk_in_visitor', 1);
        $this->assertDatabaseHas('attendance_log', ['visit_purpose' => 'Return a book']);
    }

    public function test_staff_can_filter_visits_through_a_past_manila_date_without_a_start_date(): void
    {
        $this->seed(DatabaseSeeder::class);
        $staff = User::factory()->staff()->create();
        $day = now('Asia/Manila')->subDay();
        $this->travelTo($day->copy()->setTime(23, 55)->utc(), function () use ($staff): void {
            $this->actingAs($staff)->postJson($this->staffRoute('attendance.record'), [
                'type' => 'visitor', 'action' => 'in', 'email' => 'history@example.test',
                'first_name' => 'Juan', 'last_name' => 'Reyes', 'visit_purpose' => 'Local history',
            ])->assertOk();
        });
        $this->get($this->staffRoute('attendance.index'))->assertOk()->assertDontSee('Local history');
        $this->get($this->staffRoute('attendance.index', ['through' => $day->toDateString()]))
            ->assertOk()->assertSee('Local history');
    }

    public function test_maintenance_records_own_attendance_and_returns_to_the_profile(): void
    {
        $this->seed(DatabaseSeeder::class);
        $maintenance = LibraryPersonnel::factory()->maintenance()->create()->user;
        $profile = $this->staffRoute('profile.edit');

        $this->actingAs($maintenance)->get($profile)->assertOk()
            ->assertSee('Time In')->assertSee('Time Out');
        $this->post($this->staffRoute('attendance.own'), ['action' => 'in'])
            ->assertRedirect($profile)->assertSessionHas('status', 'Time In recorded.');
        $this->assertDatabaseHas('attendance_log', [
            'account_id' => $maintenance->getKey(), 'user_role' => 'maintenance', 'time_out_at' => null,
        ]);
        $this->get(config('attendance.kiosk_url').'/kiosk')->assertSee('Kiosk closed');
        $this->get($this->staffRoute('attendance.index'))->assertForbidden();

        $this->post($this->staffRoute('attendance.own'), ['action' => 'out'])
            ->assertRedirect($profile)->assertSessionHas('status', 'Time Out recorded.');
        $this->assertDatabaseMissing('attendance_log', [
            'account_id' => $maintenance->getKey(), 'time_out_at' => null,
        ]);
    }

    public function test_public_member_lookup_accepts_qr_and_reference_and_rejects_archived_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $head = $this->headLibrarian();
        $this->actingAs($head)->postJson($this->staffRoute('attendance.own'), ['action' => 'in'])->assertOk();
        $kiosk = config('attendance.kiosk_url').'/kiosk';
        $faculty = User::factory()->faculty()->create();
        $archived = User::factory()->student()->create();
        $archived->delete();

        foreach (['not-a-member', $archived->reference_number] as $identifier) {
            $this->postJson($kiosk, ['type' => 'account', 'action' => 'in', 'identifier' => $identifier])
                ->assertJsonValidationErrors('attendance');
        }

        $this->postJson($kiosk, ['type' => 'account', 'action' => 'in', 'identifier' => $faculty->user_qr_value])
            ->assertOk()->assertJsonPath('message', 'Time In recorded.');
        $this->postJson($kiosk, ['type' => 'account', 'action' => 'out', 'identifier' => $faculty->reference_number])
            ->assertOk()->assertJsonPath('message', 'Time Out recorded.');
        $this->assertDatabaseHas('attendance_log', ['account_id' => $faculty->getKey(), 'user_role' => 'faculty']);
        $this->assertDatabaseMissing('attendance_log', ['account_id' => $faculty->getKey(), 'time_out_at' => null]);
        $this->assertDatabaseMissing('attendance_log', ['account_id' => $archived->getKey()]);
    }

    public function test_public_visitor_requires_time_in_details_and_times_out_by_email_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->headLibrarian())
            ->postJson($this->staffRoute('attendance.own'), ['action' => 'in'])->assertOk();
        $kiosk = config('attendance.kiosk_url').'/kiosk';

        $this->postJson($kiosk, ['type' => 'visitor', 'action' => 'in'])
            ->assertJsonValidationErrors(['email', 'first_name', 'last_name', 'visit_purpose']);
        $this->assertDatabaseCount('walk_in_visitor', 0);

        $visitor = ['type' => 'visitor', 'email' => 'Visitor@example.test'];
        $names = ['first_name' => 'Juan', 'last_name' => 'Reyes'];

        foreach (['Reading', 'Research'] as $purpose) {
            $this->postJson($kiosk, $visitor + $names + ['action' => 'in', 'visit_purpose' => $purpose])->assertOk();
            $this->postJson($kiosk, ['type' => 'visitor', 'action' => 'out', 'email' => 'visitor@example.test'])->assertOk();
        }

        $this->assertDatabaseCount('walk_in_visitor', 1);
        $this->assertDatabaseHas('attendance_log', ['user_role' => 'visitor', 'visit_purpose' => 'Reading']);
        $this->assertDatabaseHas('attendance_log', ['user_role' => 'visitor', 'visit_purpose' => 'Research']);
        $this->assertDatabaseMissing('attendance_log', ['user_role' => 'visitor', 'time_out_at' => null]);
    }

    private function staffRoute(string $name, mixed $parameters = []): string
    {
        return rtrim(config('app.url'), '/').route($name, $parameters, absolute: false);
    }
}
