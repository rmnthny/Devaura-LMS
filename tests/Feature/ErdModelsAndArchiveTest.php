<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ErdModelsAndArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_reference_data_without_demo_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(9, Department::count());
        $this->assertSame(0, User::count());
    }

    public function test_member_and_personnel_relationships_work(): void
    {
        $this->seed(DatabaseSeeder::class);

        $member = LibraryMember::factory()->student()->create([
            'first_name' => 'Juan',
            'middle_initial' => 'D',
            'last_name' => 'Dela Cruz',
        ]);
        $this->assertEquals('Juan D. Dela Cruz', $member->user->fullName());

        $personnel = LibraryPersonnel::factory()->create();
        $this->assertNotNull($personnel->user);
    }

    public function test_duplicate_reference_number_is_rejected_by_database(): void
    {
        User::factory()->create(['reference_number' => '136800000001']);

        $this->expectException(QueryException::class);
        User::factory()->create(['reference_number' => '136800000001']);
    }

    public function test_member_code_generator_increments_and_skips_archived_codes(): void
    {
        $first = User::factory()->create();
        $first->assignMemberQr();
        $first->delete();
        $next = User::factory()->create();
        $next->assignMemberQr();

        $this->assertNotSame($first->user_qr_value, $next->user_qr_value);
        $this->assertSame(sprintf('UBNHS-%d-%04d', now()->year, $next->getKey()), $next->user_qr_value);
    }

    public function test_activity_log_records_member_changes_and_prune_purges_after_six_months(): void
    {
        $this->seed(DatabaseSeeder::class);
        $oldArchived = LibraryMember::factory()->create();
        $recentArchived = LibraryMember::factory()->create();

        $oldArchived->delete();
        $recentArchived->delete();

        LibraryMember::onlyTrashed()
            ->where('member_id', $oldArchived->member_id)
            ->update(['deleted_at' => now()->subMonths(7)]);

        $this->assertGreaterThan(0, Activity::where('log_name', 'library_member')->count());

        $this->artisan('model:prune', ['--model' => [LibraryMember::class]])
            ->assertExitCode(0);

        $this->assertNull(LibraryMember::withTrashed()->find($oldArchived->member_id));
        $this->assertNotNull(LibraryMember::withTrashed()->find($recentArchived->member_id));
    }

    public function test_archive_cleanup_keeps_profiles_connected_to_library_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $member = LibraryMember::factory()->create();
        $personnel = LibraryPersonnel::factory()->create();
        DB::table('attendance_log')->insert([
            'account_id' => $member->account_id, 'user_role' => 'Student', 'time_in_at' => now(),
        ]);
        $schedule = [
            'personnel_id' => $personnel->getKey(),
            'day_id' => DB::table('schedule_day')->value('day_id'),
            'period_id' => DB::table('schedule_period')->value('period_id'),
        ];
        DB::table('personnel_schedule')->insert($schedule);

        $this->travelTo(now()->subMonthsNoOverflow(7), function () use ($member, $personnel): void {
            $member->delete();
            $member->user()->firstOrFail()->delete();
            $personnel->delete();
            $personnel->user()->firstOrFail()->delete();
        });

        $this->artisan('model:prune', ['--model' => [LibraryMember::class, LibraryPersonnel::class, User::class]])
            ->assertSuccessful();
        $this->assertSoftDeleted('library_member', ['member_id' => $member->getKey()]);
        $this->assertSoftDeleted('library_personnel', ['personnel_id' => $personnel->getKey()]);
        $this->assertDatabaseHas('personnel_schedule', $schedule);
    }
}
