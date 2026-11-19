<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ErdModelsAndArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_reference_data_and_head_librarian(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(9, Department::count());
        $head = User::where('username', 'headlibrarian')->firstOrFail();
        $this->assertNotNull($head->personnel);
        $this->assertEquals('Head Librarian', $head->fullName());
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
        LibraryMember::factory()->create(['reference_number' => '136800000001']);

        $this->expectException(QueryException::class);
        LibraryMember::factory()->create(['reference_number' => '136800000001']);
    }

    public function test_member_code_generator_increments_and_skips_archived_codes(): void
    {
        $year = 2026;
        $first = LibraryMember::generateMemberCode($year);
        $this->assertEquals('UBNHS-2026-0001', $first);

        $member = LibraryMember::factory()->create(['member_code' => $first]);
        $member->delete(); // archive

        $this->assertEquals('UBNHS-2026-0002', LibraryMember::generateMemberCode($year));
    }

    public function test_activity_log_records_member_changes_and_prune_purges_after_six_months(): void
    {
        $oldArchived = LibraryMember::factory()->create();
        $recentArchived = LibraryMember::factory()->create();

        $oldArchived->delete();
        $recentArchived->delete();

        // Backdate oldArchived to 7 months ago.
        LibraryMember::onlyTrashed()
            ->where('member_id', $oldArchived->member_id)
            ->update(['deleted_at' => now()->subMonths(7)]);

        $this->assertGreaterThan(0, Activity::where('log_name', 'library_member')->count());

        $this->artisan('model:prune', ['--model' => [LibraryMember::class]])
            ->assertExitCode(0);

        $this->assertNull(LibraryMember::withTrashed()->find($oldArchived->member_id));
        $this->assertNotNull(LibraryMember::withTrashed()->find($recentArchived->member_id));
    }
}
