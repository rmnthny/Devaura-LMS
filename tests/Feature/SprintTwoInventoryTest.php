<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\Publisher;
use App\Models\SchedulePeriod;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SprintTwoInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_catalog_edits_and_schedules_enforce_role_permissions(): void
    {
        $book = Book::factory()->create();
        $this->actingAs(User::factory()->staff()->create())
            ->deleteJson(route('books.destroy', $book))->assertForbidden();
        $this->getJson(route('books.create'))->assertForbidden();
        $this->postJson(route('books.store'))->assertForbidden();
        $this->getJson(route('archive.index'))->assertForbidden();
        $this->actingAs(User::factory()->student()->create())
            ->getJson(route('books.index'))->assertOk();
        $this->postJson(route('books.store'))->assertForbidden();
        $this->postJson(route('schedules.store'))->assertForbidden();
        $this->get(route('books.label', $book))->assertForbidden();
        $this->postJson(route('inventory.store'))->assertForbidden();
    }

    public function test_book_copy_crud_search_validation_and_qr_label(): void
    {
        $genre = Genre::factory()->create();
        $author = Author::factory()->create();
        $publisher = Publisher::factory()->create();
        $data = [
            'genre_id' => $genre->getKey(),
            'book_title' => 'Local History <script>',
            'isbn' => '9781234567897',
            'book_status' => 'Available',
            'author_ids' => [$author->getKey()],
            'publisher_ids' => [$publisher->getKey()],
            'date_published' => '2005-01-01',
        ];

        $this->actingAs($this->headLibrarian());
        $this->get(route('books.create'))->assertOk();
        $this->postJson(route('books.store'), [...$data, 'book_status' => 'Missing', 'author_ids' => [99999]])
            ->assertUnprocessable()->assertJsonValidationErrors(['book_status', 'author_ids.0']);
        $first = $this->postJson(route('books.store'), $data)->assertCreated()->json();
        $second = $this->postJson(route('books.store'), $data)->assertCreated()->json();
        $this->assertMatchesRegularExpression('/^BK-[A-Z0-9]{4}-[A-Z0-9]{2}$/', $first['book_qr_value']);
        $this->assertNotSame($first['book_qr_value'], $second['book_qr_value']);

        $this->getJson(route('books.index', [
            'title' => 'History', 'isbn' => '97812', 'genre_id' => $genre->getKey(),
            'author_id' => $author->getKey(), 'book_status' => 'Available',
        ]))->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.copies_count', 2);
        $this->get(route('books.index'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
        $this->get(route('books.edit', $first['book_id']))->assertOk();
        $this->get(route('books.label', $first['book_id']))->assertOk()
            ->assertSee('<svg', false)->assertSee('@media print')->assertSee($first['book_qr_value']);
        $this->get(route('books.png', $first['book_id']))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->putJson(route('books.update', $first['book_id']), [
            ...$data, 'book_status' => 'Damaged', 'author_ids' => [], 'publisher_ids' => [],
        ])->assertOk()->assertJsonPath('book_qr_value', $first['book_qr_value'])
            ->assertJsonPath('book_status', 'Damaged')->assertJsonCount(0, 'authors');
        $this->assertDatabaseMissing('book_publisher', ['book_id' => $first['book_id']]);

        Book::findOrFail($second['book_id'])->delete();
        $this->postJson(route('books.qr-sheet'), ['book_ids' => [$first['book_id'], $second['book_id']]])
            ->assertUnprocessable()->assertJsonValidationErrors('book_ids');
        $this->assertDatabaseMissing('activity_log', ['event' => 'qr_sheet_generated']);
        $this->post(route('books.qr-sheet'), ['book_ids' => [$first['book_id']]])
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'book', 'event' => 'qr_sheet_generated', 'causer_id' => auth()->id(),
        ]);
    }

    public function test_lookups_create_update_search_delete_and_protect_linked_records(): void
    {
        $this->actingAs($this->headLibrarian());
        foreach ([
            'genres' => ['genre', 'genre_id', ['genre_name' => 'Local history'], ['genre_name' => 'Philippine history'], ['genre_name' => '']],
            'authors' => ['author', 'author_id', ['first_name' => 'Apolinario'], ['last_name' => 'Mabini'], []],
            'publishers' => ['publisher', 'publisher_id', ['publisher_name' => 'School Press', 'publisher_type' => 'Organization'],
                ['publisher_name' => 'Community Press', 'publisher_type' => 'Individual'], ['publisher_type' => 'Invalid']],
        ] as $type => [$table, $key, $input, $change, $bad]) {
            $this->postJson(route('catalog.store', $type), $bad)->assertUnprocessable();
            $item = $this->postJson(route('catalog.store', $type), $input)->assertCreated()->json();
            $this->putJson(route('catalog.update', [$type, $item[$key]]), $change)->assertOk();
            $this->getJson(route('catalog.index', [$type, 'q' => reset($change)]))->assertOk()->assertJsonCount(1, 'data');
            $this->get(route('catalog.index', $type))->assertOk();
            $this->deleteJson(route('catalog.destroy', [$type, $item[$key]]))->assertNoContent();
            $this->assertDatabaseMissing($table, [$key => $item[$key]]);
        }

        $book = Book::factory()->create();
        $book->delete();
        $this->deleteJson(route('catalog.destroy', ['genres', $book->genre_id]))
            ->assertUnprocessable()->assertJsonValidationErrors('lookup');
    }

    public function test_archive_bin_restores_all_four_record_types(): void
    {
        $head = $this->headLibrarian();
        $book = Book::factory()->create();
        $member = LibraryMember::factory()->create();
        $personnel = LibraryPersonnel::factory()->create();
        $account = User::factory()->pending()->create();

        $this->actingAs($head)->deleteJson(route('books.destroy', $book))->assertNoContent();
        $this->deleteJson(route('api.v1.members.destroy', $member))->assertOk();
        $this->deleteJson(route('archive.personnel', $personnel))->assertNoContent();
        $this->deleteJson(route('archive.accounts', $account))->assertNoContent();
        $this->assertSoftDeleted($book);
        $this->assertSoftDeleted($member);
        $this->assertSoftDeleted($personnel);
        $this->assertSoftDeleted($account);
        $this->get(route('archive.index'))->assertOk()->assertSee($book->book_title);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->getJson(route('archive.index'))->assertForbidden();
        $this->postJson(route('archive.restore', ['books', $book]))->assertForbidden();
        $this->postJson(route('api.v1.members.restore', $member))->assertForbidden();

        $this->actingAs($head);
        $this->postJson(route('api.v1.members.restore', $member))->assertOk();
        $this->postJson(route('archive.restore', ['accounts', $personnel->account_id]))
            ->assertUnprocessable()->assertJsonValidationErrors('account');
        foreach (['books' => $book, 'personnel' => $personnel, 'accounts' => $account] as $type => $model) {
            $this->postJson(route('archive.restore', [$type, $model]))->assertNoContent();
            $this->assertNotSoftDeleted($model);
        }
        $this->assertNotSoftDeleted($member);
        $this->assertNotSoftDeleted('user_account', ['account_id' => $personnel->account_id]);
        $this->assertSame(User::STATUS_PENDING, $account->refresh()->account_status);
    }

    public function test_pruning_removes_expired_book_copies_and_keeps_circulation_history(): void
    {
        $old = Book::factory()->create();
        $recent = Book::factory()->create();
        $referenced = Book::factory()->create();
        $author = Author::factory()->create();
        $old->authors()->attach($author);
        $borrower = User::factory()->student()->create();
        $processor = User::factory()->staff()->create();
        DB::table('borrow_transaction')->insert([
            'borrower_id' => $borrower->getKey(), 'borrow_processed_by' => $processor->getKey(),
            'book_id' => $referenced->getKey(), 'borrow_date' => today(), 'due_date' => today(),
            'transaction_code' => 'TRX-TEST-0001', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([$old, $recent, $referenced] as $book) {
            $book->delete();
        }
        Book::onlyTrashed()->whereKey([$old->getKey(), $referenced->getKey()])
            ->update(['deleted_at' => now()->subMonths(7)]);

        $this->artisan('model:prune', ['--model' => [Book::class]])->assertSuccessful();
        $this->assertModelMissing($old);
        $this->assertDatabaseMissing('book_author', ['book_id' => $old->getKey()]);
        $this->assertSoftDeleted($recent);
        $this->assertSoftDeleted($referenced);
    }

    public function test_inventory_audit_updates_condition_and_logs_actor_and_previous_state(): void
    {
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create();
        $this->actingAs($admin)->get(route('inventory.index'))->assertForbidden();
        $head = $this->headLibrarian();
        $this->actingAs($head)->get(route('inventory.index'))->assertOk()
            ->assertSee('name="book_qr_value"', false)
            ->assertDontSee('html5-qrcode.min.js')
            ->assertDontSee('Scan with camera');
        $this->postJson(route('inventory.store'), ['book_qr_value' => 'unknown', 'condition' => 'Lost'])
            ->assertUnprocessable()->assertJsonValidationErrors('book_qr_value');
        $this->postJson(route('inventory.store'), ['book_qr_value' => $book->book_qr_value, 'condition' => 'Borrowed'])
            ->assertUnprocessable()->assertJsonValidationErrors('condition');
        $this->postJson(route('inventory.store'), ['book_qr_value' => $book->book_qr_value, 'condition' => 'Damaged'])
            ->assertOk()->assertJsonPath('book_status', 'Damaged');
        $log = Activity::inLog('inventory')->forSubject($book)->sole();
        $this->assertTrue($log->causer->is($head));
        $this->assertSame('Available', $log->getExtraProperty('previous_status'));
        $this->assertSame('Damaged', $book->refresh()->book_status);
    }

    public function test_open_loan_protects_a_copy_even_when_its_catalog_status_is_stale(): void
    {
        $head = $this->headLibrarian();
        $borrower = User::factory()->student()->create();
        $book = Book::factory()->create(['book_status' => 'Available']);
        $transaction = [
            'borrower_id' => $borrower->getKey(), 'borrow_processed_by' => $head->getKey(),
            'book_id' => $book->getKey(), 'borrow_date' => today(), 'due_date' => today(),
            'transaction_code' => 'TRX-ACTIVE-0001', 'transaction_status' => 'Overdue',
            'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('borrow_transaction')->insert($transaction);

        $this->actingAs($head)->postJson(route('inventory.store'), [
            'book_qr_value' => $book->book_qr_value, 'condition' => 'Damaged',
        ])->assertUnprocessable()->assertJsonValidationErrors('book_qr_value');
        $this->putJson(route('books.update', $book), [
            'book_title' => $book->book_title, 'genre_id' => $book->genre_id, 'book_status' => 'Lost',
        ])->assertUnprocessable()->assertJsonValidationErrors('book_status');
        $this->deleteJson(route('books.destroy', $book))->assertUnprocessable()->assertJsonValidationErrors('book');
        $this->assertNotSoftDeleted($book);
        $this->assertSame('Available', $book->refresh()->book_status);
        $this->assertDatabaseMissing('activity_log', ['log_name' => 'inventory', 'subject_id' => $book->getKey()]);

        DB::table('borrow_transaction')->where('transaction_code', $transaction['transaction_code'])
            ->update(['transaction_status' => 'Returned', 'return_processed_by' => $head->getKey(), 'return_date' => today()]);
        $this->deleteJson(route('books.destroy', $book))->assertNoContent();
        $this->assertSoftDeleted($book);
    }

    public function test_personnel_schedule_crud_uses_entire_composite_key(): void
    {
        $actor = User::factory()->admin()->create();
        $personnel = LibraryPersonnel::factory()->create();
        $period = SchedulePeriod::firstOrFail();
        $schedule = ['personnel_id' => $personnel->getKey(), 'day_id' => 1, 'period_id' => $period->getKey()];

        $this->actingAs($actor)->postJson(route('schedules.store'), $schedule)->assertCreated();
        $this->postJson(route('schedules.store'), $schedule)->assertUnprocessable()->assertJsonValidationErrors('period_id');
        $this->postJson(route('schedules.store'), [...$schedule, 'day_id' => 99])->assertUnprocessable();
        $this->get(route('schedules.index'))->assertOk()->assertSee($personnel->fullName());
        $this->putJson(route('schedules.update', array_values($schedule)), [...$schedule, 'day_id' => 2])->assertOk();
        $this->assertDatabaseMissing('personnel_schedule', $schedule);
        $schedule['day_id'] = 2;
        $this->assertDatabaseHas('personnel_schedule', $schedule);
        $this->deleteJson(route('schedules.destroy', array_values($schedule)))->assertNoContent();
        $this->assertDatabaseMissing('personnel_schedule', $schedule);
    }
}
