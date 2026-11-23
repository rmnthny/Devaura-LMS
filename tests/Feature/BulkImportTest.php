<?php

namespace Tests\Feature;

use App\Mail\WelcomeMemberMail;
use App\Models\Book;
use App\Models\User;
use App\Services\ImportRows;
use App\Support\Rbac;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class BulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        Mail::fake();
    }

    private function csv(string $type, string $rows, bool $bom = false): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', ($bom ? "\xEF\xBB\xBF" : '')
            .implode(',', ImportRows::HEADERS[$type])."\n".$rows);
    }

    public function test_import_routes_use_distinct_role_guards_and_no_public_signup(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Sign Up');
        $this->get('/register')->assertNotFound();
        $this->postJson('/api/v1/register')->assertNotFound();
        foreach ([Rbac::ADMIN, Rbac::STAFF, Rbac::FACULTY, Rbac::STUDENT] as $role) {
            $this->actingAs(User::factory()->withRole($role)->create());
            $this->get(route('members.import-template'))->assertForbidden();
            $this->postJson(route('members.import'))->assertForbidden();
            if ($role === Rbac::ADMIN) {
                $this->get(route('books.import-template'))->assertDownload('books-import-template.csv');
            } else {
                $this->get(route('books.import-template'))->assertForbidden();
                $this->postJson(route('books.import'))->assertForbidden();
            }
        }
        $this->actingAs($this->headLibrarian())->get(route('members.import-template'))->assertDownload('members-import-template.csv');
    }

    public function test_members_are_imported_and_existing_accounts_skipped_with_masked_emails(): void
    {
        config(['queue.default' => 'database']);
        $existing = User::factory()->student()->create(['reference_number' => '999999999999']);
        $existing->delete();
        $this->actingAs($this->headLibrarian())->postJson(route('members.import'), [
            'file' => $this->csv('members', "Student,012345678901,Dela Cruz,Juan,M,,juan@example.test\n"
                ."Faculty,0123456,Reyes,Ana,,science,ana@example.test\n"
                ."Student,999999999999,Cruz,Pedro,,,skip@example.test", true),
        ])->assertAccepted()->assertJsonPath('rows', 3);

        $student = User::where('email', 'juan@example.test')->sole();
        $faculty = User::where('email', 'ana@example.test')->sole();
        $this->assertSame('012345678901', $student->reference_number);
        $this->assertTrue($student->hasRole(Rbac::STUDENT));
        $this->assertTrue($faculty->hasRole(Rbac::FACULTY));
        $this->assertSame('Science', $faculty->personnel->department->department_name);
        $this->assertMatchesRegularExpression('/^UBNHS-\d{4}-\d{4}$/', $student->user_qr_value);
        $this->assertDatabaseMissing('user_account', ['email' => 'skip@example.test']);
        Mail::assertQueued(WelcomeMemberMail::class, fn (WelcomeMemberMail $mail) =>
            $mail->hasTo($student->email) && $mail->maskedReference === '••••••••8901'
            && $mail->connection === 'member-mail'
            && \Hash::check($mail->temporaryPassword, $student->password_hash));
        Mail::assertQueuedCount(2);
        Mail::assertNothingSent();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_books_import_creates_individual_copies_and_links_metadata(): void
    {
        config(['queue.default' => 'database']);
        $this->actingAs(User::factory()->admin()->create())->postJson(route('books.import'), [
            'file' => $this->csv('books', 'Library Guide,978-1-234567-89-7,Reference,Juan Reyes;Ana Santos,School Press,2020-01-01,3'),
        ])->assertAccepted();

        $books = Book::with(['genre', 'authors', 'publishers'])->where('book_title', 'Library Guide')->get();
        $this->assertCount(3, $books);
        $this->assertCount(3, $books->pluck('book_qr_value')->unique());
        $this->assertSame('Reference', $books->first()->genre->genre_name);
        $this->assertCount(2, $books->first()->authors);
        $this->assertSame('School Press', $books->first()->publishers->sole()->publisher_name);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_welcome_mail_uses_its_own_worker_and_recovers_from_a_transport_failure(): void
    {
        $transport = new class extends \Illuminate\Mail\Transport\ArrayTransport
        {
            public int $attempts = 0;

            public function send(\Symfony\Component\Mime\RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): ?\Symfony\Component\Mailer\SentMessage
            {
                if (++$this->attempts === 1) {
                    throw new \Symfony\Component\Mailer\Exception\TransportException('SMTP unavailable in test');
                }

                return parent::send($message, $envelope);
            }
        };
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        Mail::extend('welcome-test', fn () => $transport);
        config([
            'mail.mailers.smtp' => ['transport' => 'welcome-test'],
            'mail.from' => ['address' => 'library@example.test', 'name' => 'UBNHS Library'],
        ]);

        $this->actingAs($this->headLibrarian());
        $file = fn () => $this->csv('members', 'Student,012345678901,Dela Cruz,Juan,M,,retry@example.test');
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $this->postJson(route('members.import'), ['file' => $file()])->assertAccepted();
            $this->assertDatabaseHas('jobs', ['queue' => 'welcome-mail']);
        } finally {
            \Illuminate\Support\Facades\DB::rollBack();
        }
        $this->assertDatabaseMissing('user_account', ['email' => 'retry@example.test']);
        $this->assertDatabaseMissing('jobs', ['queue' => 'welcome-mail']);
        $this->postJson(route('members.import'), [
            'file' => $file(),
        ])->assertAccepted();
        $this->assertDatabaseHas('user_account', ['email' => 'retry@example.test']);
        $this->assertSame(0, $transport->attempts);
        $job = \Illuminate\Support\Facades\DB::table('jobs')->where('queue', 'welcome-mail')->sole();
        $this->assertStringNotContainsString('retry@example.test', $job->payload);

        $worker = ['connection' => 'member-mail', '--once' => true];
        $this->artisan('queue:work', $worker)->assertSuccessful();
        $this->assertSame(1, $transport->attempts);
        $this->assertCount(0, $transport->messages());
        $this->assertDatabaseHas('jobs', ['queue' => 'welcome-mail', 'attempts' => 1]);

        $this->travel(31)->seconds();
        $this->artisan('queue:work', $worker)->assertSuccessful();
        $this->assertSame(2, $transport->attempts);
        $message = $transport->messages()->sole()->getOriginalMessage();
        $this->assertSame('retry@example.test', $message->getTo()[0]->getAddress());
        $this->assertStringContainsString('Student LRN', $message->getHtmlBody());
        $this->assertStringContainsString('Temporary password', $message->getTextBody());
        $this->assertStringNotContainsString('012345678901', $message->toString());
        $this->assertDatabaseMissing('jobs', ['queue' => 'welcome-mail']);
    }

    public function test_large_import_stays_queued_for_background_processing(): void
    {
        config(['queue.default' => 'database']);
        $response = $this->actingAs($this->headLibrarian())->postJson(route('books.import'), [
            'file' => $this->csv('books', 'Large Import,,Reference,,,,21'),
        ])->assertAccepted();

        $batch = Bus::findBatch($response->json('batch_id'));
        $this->assertFalse($batch->finished());
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseMissing('book', ['book_title' => 'Large Import']);
    }

    public function test_book_batches_limit_copies_and_store_rows_in_encrypted_jobs(): void
    {
        config(['queue.default' => 'database']);
        $response = $this->actingAs($this->headLibrarian())->postJson(route('books.import'), [
            'file' => $this->csv('books', "Private title A,,Reference,,,,50\nPrivate title B,,Reference,,,,50\nPrivate title C,,Reference,,,,50"),
        ])->assertAccepted();

        $batch = Bus::findBatch($response->json('batch_id'));
        $this->assertSame(2, $batch->totalJobs);
        $this->assertSame(3, $batch->options['import_rows']);
        $this->assertSame([], Storage::disk('local')->allFiles());
        foreach (\Illuminate\Support\Facades\DB::table('jobs')->pluck('payload') as $payload) {
            $this->assertStringNotContainsString('Private title', $payload);
        }

        $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();
        $this->assertSame(100, Book::where('book_title', 'like', 'Private title %')->count());
        $this->assertSame(1, $batch->fresh()->pendingJobs);

        $job = \Illuminate\Support\Facades\DB::table('jobs')->sole();
        // Simulate an exhausted attempt, then requeue the same encrypted payload.
        $batch->recordFailedJob(json_decode($job->payload, true, flags: JSON_THROW_ON_ERROR)['uuid'], new \RuntimeException('Test failure'));
        \Illuminate\Support\Facades\DB::table('jobs')->where('id', $job->id)->delete();
        $this->assertCount(1, $batch->fresh()->failedJobIds);
        \Illuminate\Support\Facades\Queue::connection('database')->pushRaw($job->payload, $job->queue);
        $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();
        $this->assertSame(150, Book::where('book_title', 'like', 'Private title %')->count());
        $this->assertTrue($batch->fresh()->finished());
        $this->assertSame([], $batch->fresh()->failedJobIds);
    }

    public function test_excel_import_accepts_text_identifiers_and_rejects_formulas(): void
    {
        $sheet = new Spreadsheet;
        $page = $sheet->getActiveSheet();
        $page->fromArray(ImportRows::HEADERS['members'], startCell: 'A1');
        $page->fromArray(['Student', null, 'Dela Cruz', 'Juan', 'M', null, 'excel@example.test'], startCell: 'A2');
        $page->setCellValueExplicit('B2', '001234567890', DataType::TYPE_STRING);
        $path = tempnam(sys_get_temp_dir(), 'library-xlsx');
        try {
            $this->actingAs($this->headLibrarian());
            $page->setCellValue('D2', '=1+1');
            (new Xlsx($sheet))->save($path);
            $this->postJson(route('members.import'), ['file' => new UploadedFile($path, 'official.xlsx', null, null, true)])
                ->assertUnprocessable()->assertSee('formula marker');
            $page->setCellValue('D2', 'Juan');
            (new Xlsx($sheet))->save($path);
            $this->postJson(route('members.import'), ['file' => new UploadedFile($path, 'official.xlsx', null, null, true)])
                ->assertAccepted();
            $this->assertDatabaseHas('user_account', ['email' => 'excel@example.test', 'reference_number' => '001234567890']);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            $sheet->disconnectWorksheets();
        }
    }

    public function test_first_head_can_be_provisioned_from_one_official_faculty_row_only_once(): void
    {
        $file = $this->csv('members', 'Faculty,0123456,Dela Cruz,Juan,M,Science,head@example.test');
        $args = ['reference' => '0123456', '--from' => $file->getPathname()];
        // The console validates the source extension and does not remove the operator's file.
        $path = $file->getPathname().'.csv';
        copy($file->getPathname(), $path);
        $args['--from'] = $path;
        try {
            $question = 'Make this verified faculty account the first Head Librarian?';
            $this->artisan('library:bootstrap-head', $args)->expectsConfirmation($question, 'yes')->assertExitCode(0);
            $this->artisan('library:bootstrap-head', $args)->expectsConfirmation($question, 'yes')->assertExitCode(1);
            $this->assertSame(1, User::role(Rbac::SUPERADMIN)->count());
            Mail::assertQueuedCount(1);
        } finally {
            unlink($path);
        }
    }

    public function test_legacy_excel_import_accepts_a_single_worksheet(): void
    {
        $workbook = new Spreadsheet;
        $workbook->getActiveSheet()->setCellValue('A1', 'Instructions');
        $workbook->createSheet()->setTitle('Official Members')->fromArray([
            ['Member Role', 'Reference Number', 'Last Name', 'First Name', 'Email'],
            ['student', '001234567890', 'Dela Cruz', 'Juan', 'legacy@example.test'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'library-xls-');
        try {
            $this->actingAs($this->headLibrarian());
            (new Xls($workbook))->save($path);
            $this->postJson(route('members.import'), ['file' => new UploadedFile($path, 'members.xls', test: true)])
                ->assertUnprocessable()->assertSee('multiple tabs');
            $workbook->removeSheetByIndex(0);
            (new Xls($workbook))->save($path);
            $this->postJson(route('members.import'), [
                'file' => new UploadedFile($path, 'members.xls', test: true),
            ])->assertAccepted();
            $this->assertDatabaseHas('user_account', ['email' => 'legacy@example.test', 'reference_number' => '001234567890']);
        } finally {
            $workbook->disconnectWorksheets();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_downloaded_templates_can_be_filled_in_and_imported(): void
    {
        $this->actingAs($this->headLibrarian());
        $path = tempnam(sys_get_temp_dir(), 'library-template-');
        try {
            $headers = [
                'members' => ['Member Role', 'Reference Number', 'Last Name', 'First Name', 'Middle Initial', 'Department', 'Email'],
                'books' => ['Book Title', 'ISBN', 'Genre', 'Authors', 'Publishers', 'Date Published', 'Copies'],
            ];
            foreach ($headers as $kind => $columns) {
                $csv = $this->get(route($kind.'.import-template'))->assertDownload("{$kind}-import-template.csv");
                $csvText = $csv->streamedContent();
                $this->assertStringStartsWith("\xEF\xBB\xBF", $csvText);
                $this->assertSame($columns, str_getcsv(rtrim(substr($csvText, 3)), ',', '"', ''));
                $csvRow = $kind === 'members'
                    ? 'Student,002234567890,Dela Cruz,Juan,M,,template-csv@example.test'
                    : 'CSV Template Book,,Reference,,,,1';
                $this->postJson(route($kind.'.import'), [
                    'file' => UploadedFile::fake()->createWithContent('template.csv', $csvText.$csvRow."\r\n"),
                ])->assertAccepted();
                $excel = $this->get(route($kind.'.import-template', ['format' => 'xlsx']))
                    ->assertDownload("{$kind}-import-template.xlsx");
                file_put_contents($path, $excel->streamedContent());
                $workbook = IOFactory::createReader('Xlsx')->load($path);
                try {
                    $this->assertSame(1, $workbook->getSheetCount());
                    $sheet = $workbook->getActiveSheet();
                    $this->assertSame([$columns], $sheet->rangeToArray('A1:G1'));
                    $this->assertSame('A2', $sheet->getFreezePane());
                    $this->assertSame([array_fill(0, 7, null)], $sheet->rangeToArray('A2:G2'));
                    $this->assertSame(NumberFormat::FORMAT_TEXT, $sheet->getColumnStyle('B')->getNumberFormat()->getFormatCode());
                    $this->assertNotEmpty($sheet->getComment('B1')->getText()->getPlainText());
                    if ($kind === 'members') {
                        $this->assertSame('"Student,Faculty"', $sheet->getDataValidation('A2')->getFormula1());
                        $this->assertStringContainsString('7,0', $sheet->getDataValidation('B2')->getFormula1());
                        $this->assertStringContainsString('Science', $sheet->getDataValidation('F2')->getFormula1());
                        $sheet->fromArray([
                            ['Student', '001234567890', 'Dela Cruz', 'Juan', 'M', null, 'template-student@example.test'],
                            ['Faculty', '0123456', 'Reyes', 'Ana', null, 'Science', 'template-faculty@example.test'],
                        ], startCell: 'A2');
                    } else {
                        $this->assertSame('50', $sheet->getDataValidation('G2')->getFormula2());
                        $sheet->fromArray(['Template Book', '0123456789', 'Reference', null, null, null, 2], startCell: 'A2');
                    }
                    (new Xlsx($workbook))->save($path);
                } finally {
                    $workbook->disconnectWorksheets();
                }
                $this->postJson(route($kind.'.import'), ['file' => new UploadedFile($path, 'template.xlsx', test: true)])
                    ->assertAccepted();
            }
            $this->assertDatabaseHas('user_account', ['email' => 'template-student@example.test', 'reference_number' => '001234567890']);
            $this->assertDatabaseHas('user_account', ['email' => 'template-faculty@example.test', 'reference_number' => '0123456']);
            $this->assertSame(2, Book::where('book_title', 'Template Book')->where('isbn', '0123456789')->count());
            $this->get(route('members.import-template', ['format' => 'pdf']))->assertNotFound();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_import_requires_a_file_and_rejects_unlisted_formats(): void
    {
        Bus::fake();
        $this->actingAs($this->headLibrarian());
        $this->postJson(route('members.import'), [])->assertJsonValidationErrors('file');
        $this->postJson(route('members.import'), ['sheet_url' => 'https://docs.google.com/spreadsheets/d/example/edit'])
            ->assertJsonValidationErrors('file');
        foreach (['tsv', 'xlsm', 'pdf'] as $extension) {
            $this->postJson(route('members.import'), ['file' => UploadedFile::fake()->create("import.{$extension}")])
                ->assertJsonValidationErrors('file')->assertSee('Upload a CSV');
        }
        $this->get(route('members.index'))->assertOk()
            ->assertSee('Download Excel template')->assertSee('CSV template')
            ->assertSee('Member roster file')->assertDontSee('How to fill out the template')
            ->assertDontSee('name="sheet_url"', false)->assertDontSee('name="worksheet"', false);
        Bus::assertNothingBatched();
        Mail::assertNothingOutgoing();
    }

    public function test_csv_uploads_accept_semicolons_and_excel_unicode_exports(): void
    {
        $this->actingAs($this->headLibrarian());
        $header = ImportRows::HEADERS['members'];
        $this->postJson(route('members.import'), [
            'file' => UploadedFile::fake()->createWithContent('members.csv', implode(';', $header)
                ."\nStudent;000123456789;Dela Cruz;Juan;M;;csv@example.test"),
        ])->assertAccepted();
        $tsv = implode("\t", $header)."\r\nStudent\t000123456790\tPeña\tJuan\tM\t\tunicode@example.test";
        $this->postJson(route('members.import'), [
            'file' => UploadedFile::fake()->createWithContent('members.csv', "\xFF\xFE".mb_convert_encoding($tsv, 'UTF-16LE', 'UTF-8')),
        ])->assertAccepted();
        $this->assertDatabaseHas('user_account', ['email' => 'unicode@example.test', 'reference_number' => '000123456790']);
    }

    public function test_browser_import_errors_appear_next_to_the_file_picker(): void
    {
        $this->actingAs($this->headLibrarian());
        $page = route('members.index');
        $this->from($page)->post(route('members.import'), [])->assertRedirect($page)
            ->assertSessionHasErrorsIn('libraryImport', ['file']);
        $this->from($page)->post(route('members.import'), [
            'file' => UploadedFile::fake()->createWithContent('members.csv', "email\nmember@example.test"),
        ])->assertRedirect($page)->assertSessionHasErrorsIn('libraryImport', ['file']);
        $this->withCookie(config('session.cookie'), session()->getId())->get($page)
            ->assertOk()->assertSee('id="import-members-errors"', false)->assertSee('Missing headers');
    }

    public function test_validation_is_all_or_nothing_and_does_not_dispatch(): void
    {
        Bus::fake();
        $this->actingAs($this->headLibrarian());
        $valid = "Student,012345678901,Dela Cruz,Juan,M,,juan@example.test\n";
        foreach ([
            'Student,123456789012,Cruz,John2,,,other@example.test',
            'Student,123456789012,Cruz,John,,,not-an-email',
            'Student,12345678901,Cruz,John,,,other@example.test',
            'Faculty,1234567,Cruz,John,,,other@example.test',
            'Student,012345678901,Cruz,John,,,other@example.test',
            'Student,123456789012,Cruz,John,,,juan@example.test',
            'Student,123456789012,Cruz,=Excel,,,other@example.test',
        ] as $invalid) {
            $this->postJson(route('members.import'), ['file' => $this->csv('members', $valid.$invalid)])
                ->assertUnprocessable()->assertJsonValidationErrors('file')->assertSee('Row 3');
        }
        $this->postJson(route('members.import'), ['file' => UploadedFile::fake()->createWithContent('missing.csv', "email\nmember@example.test")])
            ->assertUnprocessable()->assertSee('Missing headers');
        $this->postJson(route('members.import'), [
            'file' => $this->csv('members', "Student,12345678901,Cruz,John,,Science,other@example.test\n"
                .'Faculty,123456,Cruz,John,,Science,faculty@example.test'),
        ])->assertUnprocessable()->assertSee('12-digit student LRN')
            ->assertSee('Leave the department blank for Student')->assertSee('7-digit DepEd employee number');
        foreach (['Bad ISBN,9781234567890,Reference,,,2020-01-01,1', 'Future date,,Reference,,,2099-01-01,1'] as $invalid) {
            $this->postJson(route('books.import'), ['file' => $this->csv('books', $invalid)])->assertUnprocessable();
        }
        Bus::assertNothingBatched();
        Mail::assertNothingOutgoing();
        $this->assertDatabaseMissing('user_account', ['email' => 'juan@example.test']);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_limits_row_count_and_error_messages_and_batches_in_hundreds(): void
    {
        Bus::fake();
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson(route('books.import'), ['file' => $this->csv('books', '')])
            ->assertUnprocessable()->assertSee('no data rows');
        $this->postJson(route('books.import'), [
            'file' => $this->csv('books', str_repeat("Title,,Reference,,,,1\n", 2001)),
        ])->assertUnprocessable()->assertSee('2,000 data rows');
        $this->postJson(route('books.import'), [
            'file' => $this->csv('books', str_repeat("Title,1234567890,Reference,,,,1\n", 12)),
        ])->assertUnprocessable()->assertJsonCount(11, 'errors.file')->assertSee('...and 2 more errors');
        Bus::assertNothingBatched();

        $this->postJson(route('books.import'), [
            'file' => $this->csv('books', str_repeat("Title,,Reference,,,,1\n", 101)),
        ])->assertAccepted();
        Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 2);
    }

    public function test_member_import_batches_up_to_one_hundred_rows_per_job(): void
    {
        Bus::fake();
        $this->actingAs($this->headLibrarian());

        foreach ([100 => 1, 101 => 2] as $rowCount => $jobCount) {
            $rows = array_map(
                fn (int $index) => sprintf('Student,%012d,Dela Cruz,Juan,,,batch%d@example.test', $index, $index),
                range(1, $rowCount),
            );
            $this->postJson(route('members.import'), [
                'file' => $this->csv('members', implode("\n", $rows)),
            ])->assertAccepted()->assertJsonPath('rows', $rowCount);

            Bus::assertBatched(fn ($batch) => $batch->jobs->count() === $jobCount
                && $batch->options['import_rows'] === $rowCount
                && $batch->connection() !== 'sync');
        }
    }
}
