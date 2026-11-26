<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRepair;
use App\Models\InventoryAuditSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinalDiagramWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-11-26 10:00', 'Asia/Manila')->utc());
    }

    public function test_head_librarian_approves_a_verified_worklist_and_admin_cannot_access_it(): void
    {
        $book = Book::factory()->create();
        $borrowed = Book::factory()->create();
        $loan = $this->checkout(User::factory()->staff()->create(), User::factory()->withMemberProfile()->create(), $borrowed)
            ->assertCreated()->json('transaction_code');
        $admin = User::factory()->admin()->create();
        $head = $this->headLibrarian();

        $this->actingAs($admin)->postJson(route('inventory.sessions.store'))->assertForbidden();
        $this->actingAs($head)->post(route('inventory.sessions.store'), ['remarks' => 'Shelf review'])->assertRedirect();
        $session = InventoryAuditSession::sole();
        $item = $session->items()->where('book_id', $book->getKey())->sole();
        $this->assertSame($book->getKey(), $item->book_id);
        $this->get(route('inventory.sessions.show', $session))->assertOk()->assertSee($book->book_title);

        $this->patchJson(route('inventory.sessions.finish', $session), ['outcome' => 'approve'])
            ->assertUnprocessable()->assertJsonValidationErrors('audit');
        $this->patch(route('inventory.sessions.items.update', [$session, $item->getKey()]), [
            'verification_status' => 'Missing', 'remarks' => 'Recounted shelf',
        ])->assertRedirect();
        $this->get(route('inventory.sessions.show', ['session' => $session, 'filter' => 'discrepancies']))
            ->assertOk()->assertSee('Recounted shelf');
        $this->patch(route('inventory.sessions.finish', $session), ['outcome' => 'approve'])->assertRedirect();
        $this->assertSame('Completed', $session->refresh()->audit_status);
        $this->assertSame('Missing', $item->refresh()->discrepancy_type);
        $this->assertStringContainsString($loan, $session->items()->where('book_id', $borrowed->getKey())->sole()->remarks);
        $this->patchJson(route('inventory.sessions.items.update', [$session, $item->getKey()]), [
            'verification_status' => 'Present', 'observed_condition' => 'Good',
        ])->assertUnprocessable();
        $this->assertDatabaseHas('activity_log', ['event' => 'approved', 'causer_id' => $head->getKey()]);
    }

    public function test_member_history_is_private_and_admin_can_filter_and_export_reports(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();
        $member = User::factory()->withMemberProfile()->create();
        $other = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create(['book_title' => 'Member History Guide']);
        $otherBook = Book::factory()->create(['book_title' => 'Private Other Title']);
        $this->checkout($staff, $member, $book)->assertCreated();
        $this->checkout($staff, $other, $otherBook)->assertCreated();
        $this->actingAs($member)->get(route('dashboard.index'))->assertOk()->assertSee('My open loans')->assertDontSee('Available copies');
        $this->get(route('history.index'))->assertOk()->assertSee($book->book_title)->assertDontSee($otherBook->book_title);
        $this->getJson(route('reports.index'))->assertForbidden();
        $this->getJson(route('audit.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('dashboard.index'))->assertOk()->assertSee('Available copies')->assertSee('Last seven days in Manila');
        $filters = ['type' => 'circulation', 'from' => '2026-11-26', 'through' => '2026-11-26'];
        $this->get(route('reports.index', $filters))->assertOk()->assertSee($book->book_title)->assertSee($otherBook->book_title);
        $this->get(route('reports.index', [...$filters, 'format' => 'pdf']))->assertDownload('circulation.pdf');
        $this->get(route('reports.index', [...$filters, 'format' => 'xlsx']))->assertDownload('circulation.xlsx');
        $this->get(route('audit.index', ['area' => 'circulation']))->assertOk()->assertSee('Checked out a library book');
    }

    public function test_damaged_return_can_schedule_repair_and_replay_does_not_duplicate_it(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();
        $loan = $this->checkout($staff, $member, $book)->assertCreated()->json('transaction_code');
        $payload = ['book_id' => $book->getKey(), 'transaction_code' => $loan, 'condition' => 'Damaged', 'repair_on' => '2026-11-28'];

        $this->postJson(route('circulation.return'), $payload)->assertOk();
        $this->postJson(route('circulation.return'), $payload)->assertOk();
        $repair = BookRepair::sole();
        $this->assertSame('Damaged', $book->refresh()->book_status);
        $this->get(route('circulation.repairs.index'))->assertOk()->assertSee($book->book_title);
        $this->actingAs($member)->patchJson(route('circulation.repairs.complete', $repair), ['outcome' => 'Available'])->assertForbidden();
        $this->actingAs($staff)->patchJson(route('circulation.repairs.complete', $repair), ['outcome' => 'Available'])->assertNoContent();
        $this->assertSame('Available', $book->refresh()->book_status);
        $this->assertNotNull($repair->refresh()->completed_at);
        $this->assertDatabaseHas('activity_log', ['event' => 'repair_completed', 'causer_id' => $staff->getKey()]);
    }

    private function checkout(User $staff, User $member, Book $book)
    {
        return $this->actingAs($staff)->postJson(route('circulation.borrow'), [
            'book_id' => $book->getKey(), 'borrower_account_id' => $member->getKey(), 'due_date' => '2026-11-26',
            'request_id' => (string) Str::uuid(),
        ]);
    }
}
