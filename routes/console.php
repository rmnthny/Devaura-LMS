<?php

use App\Models\Book;
use App\Models\LibraryMember;
use App\Models\LibraryPersonnel;
use App\Models\User;
use App\Services\ImportRows;
use App\Services\LibraryAlerts;
use App\Services\RoleAssignmentService;
use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

Artisan::command('library:check-database', function (): int {
    $connection = \Illuminate\Support\Facades\DB::connection();

    try {
        $connection->selectOne('SELECT 1');
    } catch (\Illuminate\Database\QueryException $error) {
        $this->error('Database unavailable at '.$connection->getConfig('host').':'.$connection->getConfig('port').'. Start the configured MySQL instance before running composer dev.');

        return 1;
    }

    $this->info('Library database connection is ready.');

    return 0;
})->purpose('Check MySQL before starting local services');

DevCommands::artisan('queue:work member-mail --sleep=1', 'welcome-mail');
DevCommands::artisan('serve --host=127.0.0.1 --port=8001 --tries=1', 'kiosk');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('library:bootstrap-head {reference : Faculty DepEd Employee Number} {--from= : One-row official Faculty spreadsheet for initial setup}', function (RoleAssignmentService $roles, ImportRows $imports): int {
    $reference = (string) $this->argument('reference');
    if (! preg_match('/^[0-9]{7}$/D', $reference)) {
        $this->error('Enter a seven-digit DepEd Employee Number.');

        return 1;
    }

    if (! $this->confirm('Make this verified faculty account the first Head Librarian?')) {
        return 1;
    }

    try {
        $row = null;
        if ($file = $this->option('from')) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (! is_file($file) || ! in_array($extension, ['csv', 'xlsx', 'xls'], true)
                || filesize($file) > 5 * 1024 * 1024) {
                $this->error('Provide a readable CSV, XLSX, or XLS file no larger than 5 MB.');
                return 1;
            }
            $rows = $imports->validate(new UploadedFile($file, basename($file), null, null, true), 'members');
            if (count($rows) !== 1 || $rows[0]['member_role'] !== 'Faculty' || $rows[0]['reference_number'] !== $reference) {
                $this->error('The spreadsheet must contain exactly one Faculty row matching the requested reference number.');
                return 1;
            }
            $row = $rows[0];
        }
        $roles->bootstrapSuperadmin($reference, $row);
    } catch (ValidationException $error) {
        $this->error($error->getMessage());

        return 1;
    }

    $this->info('The first Head Librarian was assigned.');

    return 0;
})->purpose('Appoint the first Head Librarian from an eligible faculty account or import file');

Artisan::command('library:circulation-notifications', function (LibraryAlerts $alerts): void {
    $alerts->remind();
})->purpose('Flag overdue loans and queue due and overdue notifications');

Schedule::command('library:circulation-notifications')->everyMinute()->withoutOverlapping();

Schedule::command('model:prune', [
    '--model' => [
        Book::class,
        LibraryMember::class,
        LibraryPersonnel::class,
        User::class,
    ],
])->daily();
