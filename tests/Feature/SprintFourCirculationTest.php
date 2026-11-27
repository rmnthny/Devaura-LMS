<?php

namespace Tests\Feature;

use App\Jobs\SendLibraryAlert;
use App\Mail\LibraryAlertMail;
use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SprintFourCirculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->travelTo(Carbon::parse('2026-11-26 10:00', 'Asia/Manila')->utc());
        Mail::fake();
    }

    public function test_staff_check_out_and_return_a_copy_with_independent_processor_records(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();

        $this->actingAs($staff)->get(route('circulation.borrow.form'))->assertOk();
        $this->postJson(route('circulation.borrow'), $this->checkout($member, $book))->assertCreated();
        $loan = BorrowTransaction::sole();
        $this->assertMatchesRegularExpression('/^TRX-20261126-[A-Z0-9]{4}$/', $loan->transaction_code);
        $this->assertSame($member->getKey(), $loan->borrower_id);
        $this->assertSame($staff->getKey(), $loan->borrow_processed_by);
        $this->assertSame('Borrowed', $book->refresh()->book_status);
        $this->assertSame('loan.pending', $member->notifications()->sole()->data['event']);
        $this->assertDatabaseHas('jobs', ['queue' => 'welcome-mail']);
        Mail::assertNothingOutgoing();

        $this->actingAs($admin)->get(route('circulation.return.form', ['book_qr_value' => $book->book_qr_value]))
            ->assertOk()->assertSee($loan->transaction_code);
        $this->postJson(route('circulation.return'), $this->checkIn($loan, $book))
            ->assertOk()->assertJsonPath('transaction_status', 'Returned');
        $this->assertSame($admin->getKey(), $loan->refresh()->return_processed_by);
        $this->assertSame('2026-11-26', $loan->return_date->toDateString());
        $this->assertSame('Available', $book->refresh()->book_status);
        $this->assertDatabaseCount('borrow_transaction', 1);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'circulation', 'event' => 'returned', 'causer_id' => $admin->getKey()]);
    }

    public function test_manual_member_and_copy_numbers_use_the_same_checkout_and_return_guards(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();
        $other = Book::factory()->create(['book_title' => $book->book_title]);
        $checkout = $this->checkout($member, $book);
        $manual = [
            ...$checkout,
            'borrower_identifier' => $member->reference_number,
            'book_id' => $book->getKey(),
        ];
        unset($manual['book_qr_value']);

        $this->actingAs($staff)->postJson(route('circulation.borrow'), [...$manual, 'book_qr_value' => $other->book_qr_value])
            ->assertUnprocessable()->assertJsonValidationErrors('book_qr_value');
        $this->postJson(route('circulation.borrow'), $manual)->assertCreated();
        $loan = BorrowTransaction::sole();
        $this->assertSame($book->getKey(), $loan->book_id);
        $this->assertSame('Available', $other->refresh()->book_status);
        $this->postJson(route('circulation.borrow'), $checkout)->assertOk()
            ->assertJsonPath('transaction_code', $loan->transaction_code);
        $this->get(route('circulation.return.form', ['book_id' => $book->getKey()]))
            ->assertOk()->assertSee($loan->transaction_code);
        $return = ['book_id' => $book->getKey(), 'transaction_code' => $loan->transaction_code, 'condition' => 'Available'];
        $this->postJson(route('circulation.return'), [...$return, 'book_id' => $other->getKey()])
            ->assertUnprocessable()->assertJsonValidationErrors('transaction_code');
        $this->postJson(route('circulation.return'), $return)->assertOk()
            ->assertJsonPath('transaction_status', 'Returned');
        $this->assertSame('Available', $book->refresh()->book_status);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_circulation_staff_can_search_and_select_a_member_and_a_book_copy(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $member->member()->update(['first_name' => 'Circulation Tester', 'last_name' => 'Juniper']);
        $locked = User::factory()->withMemberProfile()->create(['account_status' => 'Locked']);
        $locked->member()->update(['first_name' => 'Locked Reader', 'last_name' => 'Juniper']);
        $book = Book::factory()->create(['book_title' => 'Foundry Field Guide']);
        $otherCopy = Book::factory()->create(['book_title' => $book->book_title]);
        $members = route('circulation.borrow.form', ['member_query' => 'Juniper']);
        $books = route('circulation.borrow.form', ['book_query' => 'Foundry']);

        $this->getJson($members)->assertUnauthorized();
        $this->actingAs($member)->getJson($members)->assertForbidden();
        $this->getJson($books)->assertForbidden();
        $this->getJson(route('circulation.return.form', ['loan_query' => 'Foundry']))->assertForbidden();

        $this->actingAs($staff)->get($members)->assertOk()->assertSee('Circulation Tester')->assertDontSee('Locked Reader');
        $this->get($books)->assertOk()->assertSee($book->book_title);
        $this->get(route('circulation.borrow.form', [
            'member' => $member->getKey(),
            'copy' => $otherCopy->getKey(),
        ]))->assertOk()->assertSee('Circulation Tester')->assertSee($otherCopy->book_title);

        $qrCheckout = $this->checkout($member, $otherCopy);
        $selected = [
            'borrower_account_id' => $member->getKey(),
            'book_id' => $otherCopy->getKey(),
            'request_id' => $qrCheckout['request_id'],
        ];
        $this->postJson(route('circulation.borrow'), [...$selected, 'borrower_account_id' => $locked->getKey()])
            ->assertUnprocessable()->assertJsonValidationErrors('borrower_account_id');
        $this->postJson(route('circulation.borrow'), $selected)->assertCreated();
        $loan = BorrowTransaction::sole();
        $this->assertSame($member->getKey(), $loan->borrower_id);
        $this->assertSame($otherCopy->getKey(), $loan->book_id);
        $this->assertSame('Available', $book->refresh()->book_status);
        $this->postJson(route('circulation.borrow'), $qrCheckout)
            ->assertOk()->assertJsonPath('transaction_code', $loan->transaction_code);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_failed_return_lookup_stays_on_the_form_and_preserves_json_validation(): void
    {
        $staff = User::factory()->staff()->create();
        $url = route('circulation.return.form', ['book_qr_value' => 'BK-UNKNOWN']);

        $this->actingAs($staff)->get($url)->assertOk()
            ->assertSee('No open loan matches that QR value or transaction reference.')
            ->assertSee('Search open loans')->assertDontSee('Record outcome');
        $this->getJson($url)->assertUnprocessable()->assertJsonValidationErrors('book_qr_value');
    }

    public function test_return_search_excludes_closed_loans_and_audits_a_late_damaged_return(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create(['book_title' => 'Foundry Field Guide']);
        $closedCopy = Book::factory()->create(['book_title' => $book->book_title]);
        $this->actingAs($staff)->postJson(route('circulation.borrow'), $this->checkout($member, $book))->assertCreated();
        $this->postJson(route('circulation.borrow'), $this->checkout($member, $closedCopy))->assertCreated();
        $loan = BorrowTransaction::where('book_id', $book->getKey())->sole();
        $closedLoan = BorrowTransaction::where('book_id', $closedCopy->getKey())->sole();
        $this->postJson(route('circulation.return'), $this->checkIn($closedLoan, $closedCopy))->assertOk();

        $this->get(route('circulation.return.form', ['loan_query' => 'Foundry']))
            ->assertOk()->assertSee($loan->transaction_code)->assertDontSee($closedLoan->transaction_code)
            ->assertSee(route('circulation.return.form', ['transaction_code' => $loan->transaction_code, 'loan_query' => 'Foundry']));
        $this->get(route('circulation.return.form', ['transaction_code' => $loan->transaction_code]))
            ->assertOk()->assertSee($loan->transaction_code)->assertSee($book->book_title);

        $this->travelTo(Carbon::parse('2026-11-30 00:00:00', 'Asia/Manila')->utc());
        $this->assertSame('Borrowed', $loan->refresh()->transaction_status);
        $this->postJson(route('circulation.return'), [
            'book_id' => $book->getKey(),
            'transaction_code' => $loan->transaction_code,
            'condition' => 'Damaged',
        ])->assertOk()->assertJsonPath('transaction_status', 'Returned');
        $this->assertSame('Damaged', $book->refresh()->book_status);
        $properties = DB::table('activity_log')->where([
            'log_name' => 'circulation',
            'event' => 'returned',
            'subject_id' => $loan->getKey(),
            'causer_id' => $staff->getKey(),
        ])->value('properties');
        $audit = json_decode($properties, true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue($audit['was_overdue']);
        $this->assertSame('Damaged', $audit['copy_status']);
    }

    public function test_permissions_and_server_side_eligibility_leave_records_unchanged(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $locked = User::factory()->withMemberProfile()->create(['account_status' => 'Locked']);
        $archived = User::factory()->withMemberProfile()->create();
        $archived->member()->delete();
        $book = Book::factory()->create();
        $damaged = Book::factory()->create(['book_status' => 'Damaged']);

        $this->postJson(route('circulation.borrow'), $this->checkout($member, $book))->assertUnauthorized();
        $this->actingAs($member)->postJson(route('circulation.borrow'), $this->checkout($member, $book))->assertForbidden();
        $this->postJson(route('circulation.return'), [])->assertForbidden();

        $this->actingAs($staff);
        foreach ([$locked, $archived] as $ineligible) {
            $this->postJson(route('circulation.borrow'), $this->checkout($ineligible, $book))
                ->assertUnprocessable()->assertJsonValidationErrors('borrower_identifier');
        }
        $this->postJson(route('circulation.borrow'), $this->checkout($member, $damaged))
            ->assertUnprocessable()->assertJsonValidationErrors('book_qr_value');
        $this->assertDatabaseCount('borrow_transaction', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_lost_item_is_audited_and_leaves_no_active_loan_or_stale_email(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();
        $this->actingAs($staff)->postJson(route('circulation.borrow'), $this->checkout($member, $book))->assertCreated();
        $loan = BorrowTransaction::sole();
        $outcome = [...$this->checkIn($loan, $book), 'condition' => 'Lost'];

        $this->postJson(route('circulation.return'), $outcome)->assertOk()->assertJsonPath('transaction_status', 'Lost');
        $this->postJson(route('circulation.return'), $outcome)->assertOk()->assertJsonPath('return_date', null);
        $this->postJson(route('circulation.return'), $this->checkIn($loan, $book))
            ->assertUnprocessable()->assertJsonValidationErrors('condition');
        $this->assertSame('Lost', $book->refresh()->book_status);
        $this->assertSame(0, BorrowTransaction::open()->count());
        $this->assertDatabaseHas('activity_log', ['log_name' => 'circulation', 'event' => 'lost', 'causer_id' => $staff->getKey()]);
        $this->artisan('queue:work', ['connection' => 'member-mail', '--once' => true])->assertSuccessful();
        Mail::assertNothingOutgoing();
    }

    public function test_replays_preserve_the_original_checkout_and_cannot_return_a_later_loan(): void
    {
        $staff = User::factory()->staff()->create();
        $first = User::factory()->withMemberProfile()->create();
        $second = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();
        $payload = $this->checkout($first, $book);

        $this->actingAs($staff)->postJson(route('circulation.borrow'), $payload)->assertCreated();
        $loan = BorrowTransaction::sole();
        $this->postJson(route('circulation.borrow'), $payload)->assertOk()
            ->assertJsonPath('transaction_code', $loan->transaction_code);
        $this->postJson(route('circulation.borrow'), $this->checkout($second, $book))
            ->assertUnprocessable()->assertJsonValidationErrors('book_qr_value');
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('jobs', 1);

        $return = $this->checkIn($loan, $book);
        $this->postJson(route('circulation.return'), $return)->assertOk();
        $this->postJson(route('circulation.borrow'), $this->checkout($second, $book))->assertCreated();
        $secondLoan = BorrowTransaction::open()->sole();
        $this->postJson(route('circulation.return'), $return)->assertOk()->assertJsonPath('transaction_status', 'Returned');
        $this->postJson(route('circulation.borrow'), $payload)->assertOk()->assertJsonPath('transaction_status', 'Returned');
        $this->assertSame('Borrowed', $secondLoan->refresh()->transaction_status);
        $this->assertSame('Borrowed', $book->refresh()->book_status);
        $this->assertDatabaseCount('borrow_transaction', 2);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_checkout_assigns_three_calendar_days_and_replays_keep_the_original_date(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();
        $payload = [...$this->checkout($member, $book), 'due_date' => '2026-12-31'];
        $this->actingAs($staff)->postJson(route('circulation.borrow'), $payload)
            ->assertCreated()->assertJsonPath('due_date', '2026-11-29');

        $this->travelTo(Carbon::parse('2026-11-27 00:00:00', 'Asia/Manila')->utc());
        $this->postJson(route('circulation.borrow'), $payload)->assertOk()
            ->assertJsonPath('due_date', '2026-11-29');
        $this->assertDatabaseCount('borrow_transaction', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_due_date_is_inclusive_in_manila_and_return_clears_the_overdue_block(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $first = Book::factory()->create();
        $second = Book::factory()->create();
        $this->actingAs($staff)->postJson(route('circulation.borrow'), $this->checkout($member, $first))->assertCreated();
        $loan = BorrowTransaction::sole();

        $this->travelTo(Carbon::parse('2026-11-29 23:59:59', 'Asia/Manila')->utc());
        $this->artisan('library:circulation-notifications')->assertSuccessful();
        $this->artisan('library:circulation-notifications')->assertSuccessful();
        $this->assertSame('Borrowed', $loan->refresh()->transaction_status);
        $this->assertEqualsCanonicalizing(['loan.pending', 'loan.due'], $this->eventsFor($member));
        $dueNotice = $member->notifications()->get()->firstWhere('data.event', 'loan.due');
        (new SendLibraryAlert($dueNotice->id))->handle();
        Mail::assertSent(LibraryAlertMail::class, function (LibraryAlertMail $mail): bool {
            $mail->assertSeeInHtml('is due today');
            $mail->assertSeeInText('circulation desk today');

            return $mail->event === 'loan.due';
        });

        $this->travelTo(Carbon::parse('2026-11-30 00:00:00', 'Asia/Manila')->utc());
        $next = $this->checkout($member, $second);
        $this->postJson(route('circulation.borrow'), $next)
            ->assertUnprocessable()->assertJsonValidationErrors('borrower_identifier');
        $this->assertSame('Borrowed', $loan->refresh()->transaction_status);
        $this->assertEqualsCanonicalizing(['loan.pending', 'loan.due', 'loan.overdue'], $this->eventsFor($member));

        $this->artisan('library:circulation-notifications')->assertSuccessful();
        $this->assertSame('Overdue', $loan->refresh()->transaction_status);
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->artisan('library:circulation-notifications')->assertSuccessful();
            $this->assertFalse(collect(DB::getQueryLog())->contains(
                fn (array $query) => str_contains(strtolower($query['query']), 'for update'),
            ));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $this->assertSame('Borrowed', $first->refresh()->book_status);
        $this->assertEqualsCanonicalizing(['loan.pending', 'loan.due', 'loan.overdue'], $this->eventsFor($member));
        $overdueNotice = $member->notifications()->get()->firstWhere('data.event', 'loan.overdue');
        (new SendLibraryAlert($overdueNotice->id))->handle();
        Mail::assertSent(LibraryAlertMail::class, function (LibraryAlertMail $mail): bool {
            if ($mail->event !== 'loan.overdue') {
                return false;
            }

            $mail->assertSeeInHtml('all overdue items have been returned');
            $mail->assertSeeInText('November 29, 2026 (Manila time)');

            return true;
        });

        $this->postJson(route('circulation.return'), $this->checkIn($loan, $first))->assertOk();
        $this->postJson(route('circulation.borrow'), $next)->assertCreated();
    }

    public function test_a_delayed_due_email_is_skipped_after_the_due_date(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();
        $this->actingAs($staff)->postJson(route('circulation.borrow'), $this->checkout($member, $book))->assertCreated();

        $this->travelTo(Carbon::parse('2026-11-29 23:59:59', 'Asia/Manila')->utc());
        $this->artisan('library:circulation-notifications')->assertSuccessful();
        $dueNotice = $member->notifications()->get()->firstWhere('data.event', 'loan.due');

        $this->travelTo(Carbon::parse('2026-11-30 00:00:00', 'Asia/Manila')->utc());
        (new SendLibraryAlert($dueNotice->id))->handle();
        Mail::assertNothingOutgoing();
        $this->assertNull($dueNotice->refresh()->email_attempted_at);

        $this->artisan('library:circulation-notifications')->assertSuccessful();
        $overdueNotice = $member->notifications()->get()->firstWhere('data.event', 'loan.overdue');
        (new SendLibraryAlert($overdueNotice->id))->handle();
        Mail::assertSent(LibraryAlertMail::class, fn (LibraryAlertMail $mail) => $mail->event === 'loan.overdue');
        Mail::assertSentCount(1);
    }

    public function test_checkout_queue_and_notification_commit_together_and_the_worker_sends_once(): void
    {
        $staff = User::factory()->staff()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create();
        $payload = $this->checkout($member, $book);
        $this->actingAs($staff);

        DB::beginTransaction();
        try {
            $this->postJson(route('circulation.borrow'), $payload)->assertCreated();
            $this->assertDatabaseCount('jobs', 1);
        } finally {
            DB::rollBack();
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('borrow_transaction', 0);
        $this->assertSame('Available', $book->refresh()->book_status);
        Mail::assertNothingOutgoing();

        $this->postJson(route('circulation.borrow'), $payload)->assertCreated();
        $notification = $member->notifications()->sole();
        $this->artisan('queue:work', ['connection' => 'member-mail', '--once' => true])->assertSuccessful();
        (new SendLibraryAlert($notification->id))->handle();
        Mail::assertSent(LibraryAlertMail::class, function (LibraryAlertMail $mail) use ($member, $book): bool {
            $mail->assertHasSubject('UBNHS Library — Library book checked out');
            $mail->assertSeeInHtml($book->book_title);
            $mail->assertSeeInText(BorrowTransaction::sole()->transaction_code);
            $mail->assertSeeInHtml('November 29, 2026 (Manila time)');

            return $mail->hasTo($member->email);
        });
        Mail::assertSentCount(1);
        $this->assertNotNull($notification->refresh()->email_sent_at);

        $this->actingAs($staff)->post(route('notifications.read', $notification))->assertNotFound();
        $this->actingAs($member)->get(route('notifications.index'))->assertOk()->assertSee('Library book checked out');
        $this->post(route('notifications.read', $notification))->assertRedirect(route('notifications.index'));
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_an_account_change_creates_one_notification_when_the_request_is_repeated(): void
    {
        $head = $this->headLibrarian();
        $member = User::factory()->withMemberProfile()->create();
        $url = route('api.v1.admin.users.status', $member);
        $this->actingAs($head)->patchJson($url, ['account_status' => 'Locked'])->assertOk();
        $this->patchJson($url, ['account_status' => 'Locked'])->assertOk();
        $notification = $member->notifications()->sole();
        $this->assertSame('account.updated', $notification->data['event']);
        (new SendLibraryAlert($notification->id))->handle();
        (new SendLibraryAlert($notification->id))->handle();
        Mail::assertSentCount(1);
        Mail::assertSent(LibraryAlertMail::class, function (LibraryAlertMail $mail): bool {
            $mail->assertSeeInHtml('Account Services');
            $mail->assertSeeInText('Your library account status changed to Locked.');

            return true;
        });

        app(\App\Services\LibraryAlerts::class)->accountUpdated($member, 'Your password was changed.', true);
        $securityNotice = $member->notifications()->get()->firstWhere('data.event', 'account.password_changed');
        (new SendLibraryAlert($securityNotice->id))->handle();
        Mail::assertSent(\App\Mail\PasswordChangedMail::class, function ($mail): bool {
            $mail->assertSeeInHtml('Account Security');
            $mail->assertSeeInText('please contact the Head Librarian immediately');

            return true;
        });
    }

    private function checkout(User $member, Book $book): array
    {
        return [
            'book_qr_value' => $book->book_qr_value,
            'borrower_identifier' => $member->user_qr_value,
            'request_id' => (string) Str::uuid(),
        ];
    }

    private function checkIn(BorrowTransaction $loan, Book $book): array
    {
        return [
            'book_qr_value' => $book->book_qr_value,
            'transaction_code' => $loan->transaction_code,
            'condition' => 'Available',
        ];
    }

    private function eventsFor(User $member): array
    {
        return $member->notifications()->get()->map(fn (DatabaseNotification $notification) => $notification->data['event'])->all();
    }
}
