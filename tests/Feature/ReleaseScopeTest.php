<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReleaseScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_is_active_and_sessions_and_repairs_stay_deferred(): void
    {
        $this->seed();
        $this->actingAs($this->headLibrarian());

        foreach (['dashboard.index', 'history.index', 'reports.index', 'audit.index'] as $route) {
            $this->assertTrue(Route::has($route), $route);
            $this->get(route($route))->assertOk();
        }
        foreach (['inventory.sessions.index', 'circulation.repairs.index'] as $route) {
            $this->assertFalse(Route::has($route), $route);
        }
        foreach (['/inventory/audit/sessions', '/circulation/repairs'] as $path) {
            $this->getJson($path)->assertNotFound();
        }
        $this->postJson('/inventory/audit/sessions')->assertNotFound();
        $this->patchJson('/circulation/repairs/1')->assertNotFound();
        foreach (['inventory_audit', 'inventory_audit_item', 'book_repair'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }

        $this->get(route('books.index'))->assertOk()
            ->assertSee('Inventory Audits')->assertSee('Operational Reports')->assertSee('Audit Trail');
        $this->get(route('inventory.index'))->assertOk();
    }

    public function test_stale_checkout_selection_recovers_and_a_damaged_return_stays_available(): void
    {
        $this->seed();
        Mail::fake();
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();
        $due = now(config('attendance.timezone'))->toDateString();

        $checkout = $this->actingAs($staff)->postJson(route('circulation.borrow'), [
            'borrower_account_id' => $member->getKey(), 'book_id' => $book->getKey(),
            'request_id' => (string) Str::uuid(),
        ])->assertCreated();

        $this->get(route('circulation.borrow.form', ['copy' => $book->getKey(), 'member' => $member->getKey()]))
            ->assertOk()->assertSee('The selected copy is no longer available.')->assertSee('Selected member');

        $data = ['book_id' => $book->getKey(), 'transaction_code' => $checkout->json('transaction_code'), 'condition' => 'Damaged'];
        $this->postJson(route('circulation.return'), [...$data, 'repair_on' => $due])
            ->assertUnprocessable()->assertJsonValidationErrors('repair_on');
        $this->assertSame('Borrowed', $book->refresh()->book_status);
        $this->postJson(route('circulation.return'), $data)->assertOk()->assertJsonPath('transaction_status', 'Returned');
        $this->assertSame('Damaged', $book->refresh()->book_status);
    }
}
