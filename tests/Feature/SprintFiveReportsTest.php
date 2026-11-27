<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AttendanceLog;
use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SprintFiveReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->travelTo(Carbon::parse('2026-11-27 10:00', 'Asia/Manila')->utc());
    }

    public function test_report_preview_and_exports_share_manila_date_and_recorded_role_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $faculty = User::factory()->faculty()->create();
        $expected = $this->visit($student, 'student', '2026-11-25 16:00:00');
        $this->visit($student, 'student', '2026-11-25 15:59:59');
        $this->visit($student, 'student', '2026-11-26 16:00:00');
        $this->visit($faculty, 'faculty', '2026-11-26 15:59:59');
        $filters = ['from' => '2026-11-26', 'through' => '2026-11-26', 'role' => 'student'];

        $preview = $this->actingAs($admin)->get(route('reports.index', $filters))
            ->assertOk()->assertSee('Time in (Manila)')->assertSee('2026-11-26 00:00:00')
            ->assertDontSee('2026-11-26 23:59:59');
        $this->assertSame(1, $preview->viewData('summary')['Matching records']);
        $this->assertSame([[$expected->getKey(), 'Student', '2026-11-26 00:00:00', null]], $preview->viewData('rows')->all());

        $pdf = $this->get(route('reports.index', $filters + ['format' => 'pdf']))->assertDownload('attendance.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringContainsString('DejaVuSans', $pdf->getContent());

        $xlsx = $this->get(route('reports.index', $filters + ['format' => 'xlsx']))->assertDownload('attendance.xlsx');
        $this->openWorkbook($xlsx, function ($sheet) use ($preview): void {
            $this->assertStringContainsString('Attendance Report', $sheet->getCell('A1')->getValue());
            $this->assertFalse($sheet->getShowGridlines());
            $this->assertSame('A7:D8', $sheet->getAutoFilter()->getRange());
            $this->assertSame('A1:D8', $sheet->getPageSetup()->getPrintArea());
            $this->assertSame([7, 7], $sheet->getPageSetup()->getRowsToRepeatAtTop());
            $this->assertSame('2026-11-26', $sheet->getCell('B2')->getValue());
            $this->assertSame('2026-11-26', $sheet->getCell('B3')->getValue());
            $this->assertSame(1, $sheet->getCell('B4')->getValue());
            $this->assertSame('Student', $sheet->getCell('B5')->getValue());
            $this->assertSame('A8', $sheet->getFreezePane());
            $this->assertSame([['Visit #', 'Role at time in', 'Time in', 'Time out']], $sheet->rangeToArray('A7:D7', calculateFormulas: false, formatData: false));
            $expectedRow = array_map(fn ($value) => $value ?? '', $preview->viewData('rows')->first());
            $this->assertSame([$expectedRow], $sheet->rangeToArray('A8:D8', calculateFormulas: false, formatData: false));
        });

        $emptyFilters = ['from' => '2026-11-24', 'through' => '2026-11-24'];
        $empty = $this->get(route('reports.index', $emptyFilters))
            ->assertOk()->assertSee('No records match the filters.');
        $this->assertSame(0, $empty->viewData('summary')['Matching records']);
        $this->get(route('reports.index', $emptyFilters + ['format' => 'pdf']))->assertDownload('attendance.pdf');
        $emptyWorkbook = $this->get(route('reports.index', $emptyFilters + ['format' => 'xlsx']))->assertDownload('attendance.xlsx');
        $this->openWorkbook($emptyWorkbook, function ($sheet): void {
            $this->assertSame(0, $sheet->getCell('B4')->getValue());
            $this->assertSame('Visit #', $sheet->getCell('A7')->getValue());
            $this->assertSame('No records match the filters.', $sheet->getCell('A8')->getValue());
        });
    }

    public function test_circulation_reports_use_activity_dates_and_keep_worksheet_text_literal(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->withMemberProfile()->create();
        $book = Book::factory()->create(['book_title' => '=1+2']);
        BorrowTransaction::create([
            'borrower_id' => $member->getKey(), 'borrow_processed_by' => $admin->getKey(),
            'return_processed_by' => $admin->getKey(), 'book_id' => $book->getKey(),
            'borrow_date' => '2026-11-22', 'due_date' => '2026-11-25', 'return_date' => '2026-11-26',
            'transaction_status' => BorrowTransaction::RETURNED, 'transaction_code' => 'TRX-QA-RETURN',
        ]);
        $overdueBook = Book::factory()->create(['book_title' => 'Overdue study guide', 'book_status' => 'Borrowed']);
        BorrowTransaction::create([
            'borrower_id' => $member->getKey(), 'borrow_processed_by' => $admin->getKey(), 'book_id' => $overdueBook->getKey(),
            'borrow_date' => '2026-11-23', 'due_date' => '2026-11-26',
            'transaction_status' => BorrowTransaction::BORROWED, 'transaction_code' => 'TRX-QA-OVERDUE',
        ]);
        $filters = ['type' => 'circulation', 'from' => '2026-11-26', 'through' => '2026-11-26'];

        $this->actingAs($admin)->get(route('reports.index', $filters))->assertOk()
            ->assertSee('No records match the filters.')->assertDontSee('TRX-QA-RETURN');
        $returnedFilters = $filters + ['circulation_activity' => 'return'];
        $preview = $this->get(route('reports.index', $returnedFilters))->assertOk()
            ->assertSee('TRX-QA-RETURN')->assertDontSee('TRX-QA-OVERDUE');
        $xlsx = $this->get(route('reports.index', $returnedFilters + ['format' => 'xlsx']))->assertDownload('circulation.xlsx');
        $this->openWorkbook($xlsx, function ($sheet) use ($preview): void {
            $this->assertSame('Returns by return date', $sheet->getCell('B5')->getValue());
            $this->assertSame($preview->viewData('rows')->all(), $sheet->rangeToArray('A8:G8', calculateFormulas: false, formatData: false));
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C8')->getDataType());
            $this->assertSame('=1+2', $sheet->getCell('C8')->getValue());
        });
        $this->get(route('reports.index', $returnedFilters + ['format' => 'pdf']))->assertDownload('circulation.pdf');

        $overdue = $this->get(route('reports.index', $filters + ['circulation_activity' => 'overdue']))
            ->assertOk()->assertSee('TRX-QA-OVERDUE')->assertDontSee('TRX-QA-RETURN');
        $this->assertSame('Overdue', $overdue->viewData('rows')->first()[6]);
        $this->get(route('reports.index', $filters + ['circulation_activity' => 'active']))->assertOk()
            ->assertSee('Snapshot (Manila)')->assertSee('TRX-QA-OVERDUE')->assertDontSee('TRX-QA-RETURN');
        $this->assertDatabaseHas('borrow_transaction', [
            'transaction_code' => 'TRX-QA-OVERDUE', 'transaction_status' => BorrowTransaction::BORROWED,
        ]);
    }

    public function test_inventory_report_combines_authorized_recorded_checks_with_current_catalog_status(): void
    {
        $head = $this->headLibrarian();
        $admin = User::factory()->admin()->create();
        $book = Book::factory()->create(['book_title' => 'Inventory Practice Copy', 'date_published' => '2020-08-15']);
        Book::factory()->create();
        $this->actingAs($head)->postJson(route('inventory.store'), [
            'book_qr_value' => $book->book_qr_value, 'condition' => 'Damaged',
        ])->assertOk();

        $filters = ['type' => 'inventory', 'from' => '2026-11-27', 'through' => '2026-11-27'];
        $preview = $this->get(route('reports.index', $filters))->assertOk()
            ->assertSee('Inventory Practice Copy')->assertSee('Discrepancy')->assertSee('2 copies (1 available, 0 borrowed, 0 lost, 1 damaged)');
        $this->assertSame(1, $preview->viewData('summary')['Matching records']);
        $this->assertSame(['Available', 'Damaged', 'Damaged'], array_slice($preview->viewData('rows')->first(), 3));
        $this->get(route('reports.index', $filters + ['format' => 'pdf']))->assertDownload('inventory.pdf');
        $xlsx = $this->get(route('reports.index', $filters + ['format' => 'xlsx']))->assertDownload('inventory.xlsx');
        $this->openWorkbook($xlsx, function ($sheet) use ($preview): void {
            $this->assertSame($preview->viewData('summary')['Current active catalog'], $sheet->getCell('B5')->getValue());
            $this->assertSame($preview->viewData('rows')->all(), $sheet->rangeToArray('A8:F8', calculateFormulas: false, formatData: false));
        });

        $options = $preview->viewData('operationalFilterOptions');
        $this->assertContains('Inventory Practice Copy', $options['book_title']);
        $this->assertContains('2020-08-15', $options['published_on']);
        $this->assertArrayHasKey($book->genre_id, $options['genre_id']);

        $selected = $filters + ['book_title' => 'Practice', 'genre_id' => $book->genre_id];
        $this->get(route('reports.index', $selected))->assertOk()
            ->assertSee('Inventory Practice Copy')->assertSee('1 copies (0 available, 0 borrowed, 0 lost, 1 damaged)');
        $this->get(route('reports.index', $selected + ['published_on' => '1900-01-01']))->assertOk()
            ->assertSee('No records match the filters.');

        $adminPreview = $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertDontSee('Inventory Checks');
        $adminOptions = $adminPreview->viewData('operationalFilterOptions');
        $this->assertArrayNotHasKey('inventory', $adminOptions['type']);
        $this->assertSame('Student', $adminOptions['role']['student']);
        $this->assertArrayNotHasKey('book_title', $adminOptions);
        foreach ([[], ['format' => 'pdf'], ['format' => 'xlsx']] as $format) {
            $this->getJson(route('reports.index', $filters + $format))->assertForbidden();
        }
    }

    public function test_audit_returns_chronological_actions_with_actor_ids_and_manila_timestamps(): void
    {
        $admin = User::factory()->admin()->create();
        $actor = User::factory()->staff()->create(['username' => 'archived-audit-actor']);
        $actor->delete();
        Activity::create([
            'log_name' => 'circulation', 'event' => 'returned', 'description' => 'Recorded an older return',
            'causer_type' => $actor->getMorphClass(), 'causer_id' => $actor->getKey(),
            'created_at' => '2026-11-25 16:00:00',
        ]);
        Activity::create([
            'log_name' => 'circulation', 'event' => 'checked_out', 'description' => 'Recorded a newer checkout',
            'created_at' => '2026-11-26 15:59:59',
        ]);
        Activity::create([
            'log_name' => 'catalog', 'event' => 'updated', 'description' => 'Different area update',
            'created_at' => '2026-11-26 15:59:59',
        ]);
        Activity::create([
            'log_name' => 'circulation', 'event' => 'returned', 'description' => 'Next day return',
            'created_at' => '2026-11-26 16:00:00',
        ]);
        $filters = ['from' => '2026-11-26', 'through' => '2026-11-26', 'area' => 'circulation'];

        $audit = $this->actingAs($admin)->get(route('audit.index', $filters))->assertOk()
            ->assertSeeInOrder(['Recorded a newer checkout', 'Recorded an older return'])
            ->assertDontSee('Next day return')->assertDontSee('Different area update')->assertDontSee('(Manila)')
            ->assertSee(route('audit.index', ['area' => 'circulation'] + $filters + ['format' => 'pdf']))
            ->assertSee(route('audit.index', ['area' => 'circulation'] + $filters + ['format' => 'xlsx']))
            ->assertSee('Print current page');
        $this->assertSame([
            ['2026-11-26 23:59:59', 'circulation', 'System', 'checked_out', 'Recorded a newer checkout', null],
            ['2026-11-26 00:00:00', 'circulation', 'archived-audit-actor (#'.$actor->getKey().')', 'returned', 'Recorded an older return', null],
        ], $audit->viewData('rows')->all());

        $this->assertSame(['checked_out', 'returned', 'updated'], $audit->viewData('auditFilterOptions')['event']);

        $pdf = $this->get(route('audit.index', ['area' => 'circulation'] + $filters + ['format' => 'pdf']))->assertDownload('audit-trail.pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $xlsx = $this->get(route('audit.index', ['area' => 'circulation'] + $filters + ['format' => 'xlsx']))->assertDownload('audit-trail.xlsx');
        $this->openWorkbook($xlsx, function ($sheet) use ($audit): void {
            $this->assertSame('circulation', $sheet->getCell('B5')->getValue());
            $this->assertSame(2, $sheet->getCell('B4')->getValue());
            $this->assertSame([$audit->viewData('headings')], $sheet->rangeToArray('A7:F7'));
            $rows = $audit->viewData('rows')->map(fn (array $row) => array_map(fn ($value) => $value ?? '', $row));
            $this->assertSame($rows->all(), $sheet->rangeToArray('A8:F9'));
        });
    }

    public function test_audit_filters_combine_and_match_the_exported_output(): void
    {
        $admin = User::factory()->admin()->create();
        foreach ([
            ['returned', 'Copy returned 100%', 'TRX-QA-1'],
            ['checked_out', 'Copy returned 100%', 'TRX-QA-1'],
            ['returned', 'Copy returned 100%', 'TRX-QA-10'],
            ['returned', 'Copy returned 1000', 'TRX-QA-1'],
        ] as [$event, $action, $reference]) {
            Activity::create([
                'log_name' => 'circulation', 'event' => $event, 'description' => $action,
                'properties' => ['reference' => $reference],
            ]);
        }
        $filters = [
            'area' => 'circulation', 'event' => 'returned', 'action' => '100%', 'transaction' => 'trx-qa-1',
            'from' => '2026-11-27', 'through' => '2026-11-27',
        ];

        $audit = $this->actingAs($admin)->get(route('audit.index', $filters))->assertOk()
            ->assertSee('Print current page')->assertSee('Copy returned 100%')
            ->assertSee(route('audit.index', $filters + ['format' => 'xlsx']));
        $this->assertSame(1, $audit->viewData('summary')['Matching records']);
        $this->assertSame('TRX-QA-1', $audit->viewData('rows')->sole()[5]);
        $options = $audit->viewData('auditFilterOptions');
        $this->assertContains('circulation', $options['area']);
        $this->assertSame(['checked_out', 'created', 'returned'], $options['event']);
        $this->assertContains('Copy returned 1000', $options['action']);
        $this->assertSame(['TRX-QA-1', 'TRX-QA-10'], $options['transaction']);

        $this->get(route('audit.index', $filters + ['format' => 'pdf']))->assertDownload('audit-trail.pdf');
        $file = $this->get(route('audit.index', $filters + ['format' => 'xlsx']))->assertDownload('audit-trail.xlsx');
        $this->openWorkbook($file, function ($sheet) use ($audit): void {
            $this->assertSame('returned', $sheet->getCell('B6')->getValue());
            $this->assertSame('100%', $sheet->getCell('B7')->getValue());
            $this->assertSame('TRX-QA-1', $sheet->getCell('B8')->getValue());
            $this->assertSame($audit->viewData('rows')->all(), $sheet->rangeToArray('A11:F11'));
        });
    }

    public function test_report_and_audit_direct_urls_require_the_same_roles_as_navigation(): void
    {
        $reports = ['/reports', '/reports?format=pdf', '/reports?format=xlsx'];
        $auditUrls = ['/audit-trail', '/audit-trail?format=pdf', '/audit-trail?format=xlsx'];
        foreach ($auditUrls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->get('/reports?format=pdf')->assertRedirect(route('login'));

        foreach ([User::factory()->staff()->create(), User::factory()->withMemberProfile()->create()] as $account) {
            $this->actingAs($account);
            foreach ($auditUrls as $url) {
                $this->get($url)->assertForbidden();
            }
            foreach ($reports as $url) {
                $this->get($url)->assertForbidden();
            }
        }

        $this->actingAs($this->headLibrarian())->get('/audit-trail')->assertOk();
        $this->get('/audit-trail?format=pdf')->assertDownload('audit-trail.pdf');
        $this->get('/audit-trail?format=xlsx')->assertDownload('audit-trail.xlsx');
        $this->getJson('/audit-trail?format=csv')->assertUnprocessable()->assertJsonValidationErrors('format');
        $this->get('/reports')->assertOk();
        $this->getJson('/reports?from=2026-11-27&through=2026-11-26&format=xlsx')
            ->assertUnprocessable()->assertJsonValidationErrors('through');
    }

    private function visit(User $account, string $role, string $utcTime): AttendanceLog
    {
        return AttendanceLog::create(['account_id' => $account->getKey(), 'user_role' => $role, 'time_in_at' => $utcTime]);
    }

    private function openWorkbook(TestResponse $response, callable $inspect): void
    {
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $workbook = IOFactory::load($path);
            try {
                $inspect($workbook->getActiveSheet());
            } finally {
                $workbook->disconnectWorksheets();
            }
        } finally {
            unlink($path);
        }
    }
}
