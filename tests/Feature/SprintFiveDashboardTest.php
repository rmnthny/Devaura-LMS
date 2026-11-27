<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SprintFiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->travelTo(Carbon::parse('2026-11-27 00:05:00', 'Asia/Manila')->utc());
    }

    public function test_operational_metrics_and_charts_follow_manila_calendar_boundaries(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->withMemberProfile()->create();
        $archived = Book::factory()->create();
        $archived->delete();
        Book::factory()->create();
        Book::factory()->create(['book_status' => 'Damaged']);

        foreach (['2026-11-20 15:59:59', '2026-11-20 16:00:00', '2026-11-26 15:59:59', '2026-11-26 16:00:00', '2026-11-27 16:00:00'] as $utc) {
            AttendanceLog::create([
                'account_id' => $student->getKey(), 'user_role' => Rbac::STUDENT,
                'time_in_at' => $utc,
            ]);
        }

        BorrowTransaction::factory()->create([
            'borrow_date' => '2026-11-21', 'due_date' => '2026-11-24',
        ]);
        BorrowTransaction::factory()->create([
            'borrow_date' => '2026-11-24', 'due_date' => '2026-11-27',
        ]);
        BorrowTransaction::factory()->returned()->create([
            'borrow_date' => '2026-11-20', 'due_date' => '2026-11-23', 'return_date' => '2026-11-27',
        ]);
        BorrowTransaction::factory()->lost()->create([
            'borrow_date' => '2026-11-27', 'due_date' => '2026-11-30',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.index'))->assertOk()
            ->assertSee('Last seven days in Manila')->assertSee('Checkouts')->assertSee('Returns');
        $this->assertSame([
            'Available copies' => 2, 'Open loans' => 2, 'Overdue loans' => 1, 'Visits today' => 1,
        ], $response->viewData('operational'));

        $trend = $response->viewData('trend');
        $this->assertSame([
            '2026-11-21', '2026-11-22', '2026-11-23', '2026-11-24', '2026-11-25', '2026-11-26', '2026-11-27',
        ], array_column($trend, 'date'));
        $this->assertSame([1, 0, 0, 0, 0, 1, 1], array_column($trend, 'visits'));
        $this->assertSame([1, 0, 0, 1, 0, 0, 1], array_column($trend, 'checkouts'));
        $this->assertSame([0, 0, 0, 0, 0, 0, 1], array_column($trend, 'returns'));
    }

    public function test_empty_manila_period_keeps_zero_cards_and_all_seven_dates(): void
    {
        $response = $this->actingAs($this->headLibrarian())->get(route('dashboard.index'))->assertOk();
        $this->assertSame([0, 0, 0, 0], array_values($response->viewData('operational')));
        $trend = $response->viewData('trend');
        $this->assertCount(7, $trend);
        foreach (['visits', 'checkouts', 'returns'] as $key) {
            $this->assertSame(array_fill(0, 7, 0), array_column($trend, $key));
        }
        $response->assertSee('max="1" value="0"', false);
    }

    public function test_personal_dashboard_and_transactions_are_scoped_to_the_authenticated_borrower(): void
    {
        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
        $this->get(route('history.index'))->assertRedirect(route('login'));
        $member = User::factory()->withMemberProfile()->create();
        $own = Book::factory()->create(['book_title' => 'Personal Dashboard Book']);
        $other = Book::factory()->create(['book_title' => 'Other Dashboard Book']);
        BorrowTransaction::factory()->create([
            'book_id' => $own->getKey(), 'borrower_id' => $member->getKey(),
            'borrow_date' => '2026-11-22', 'due_date' => '2026-11-25',
        ]);
        $otherLoan = BorrowTransaction::factory()->create(['book_id' => $other->getKey()]);

        $response = $this->actingAs($member)->get(route('dashboard.index'))->assertOk()
            ->assertSee('My open loans')->assertDontSee('Library overview')->assertDontSee('Operational Reports');
        $this->assertSame(['My open loans' => 1, 'My overdue loans' => 1], $response->viewData('personal'));
        $this->get(route('history.index', ['borrower_id' => $otherLoan->borrower_id]))->assertOk()
            ->assertSee($own->book_title)->assertSee('Overdue')->assertDontSee($other->book_title);

        foreach ([Rbac::STAFF, Rbac::FACULTY] as $role) {
            $user = User::factory()->withRole($role)->create();
            $this->actingAs($user)->get(route('dashboard.index'))->assertOk()->assertDontSee('Available copies');
            $this->get(route('history.index'))->assertOk()->assertSee('No transactions recorded.');
        }

        foreach ([Rbac::MAINTENANCE, Rbac::VISITOR] as $role) {
            $user = User::factory()->withRole($role)->create();
            $this->actingAs($user)->get(route('dashboard.index'))->assertForbidden();
            $this->get(route('history.index'))->assertForbidden();
        }
    }
}
