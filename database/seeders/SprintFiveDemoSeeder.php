<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Author;
use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\Department;
use App\Models\Genre;
use App\Models\Publisher;
use App\Models\User;
use App\Models\WalkInVisitor;
use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SprintFiveDemoSeeder extends Seeder
{
    private const DATABASE = 'devaura_lms_demo';

    private const PASSWORD = 'Sprint5-Demo!Only';

    private const ACCOUNTS = [
        Rbac::SUPERADMIN => ['defense.head@example.com', '9900001', 'DEMO-HEAD', 'Librarian'],
        Rbac::ADMIN => ['defense.admin@example.com', '9900002', 'DEMO-TEACHER', 'Teacher'],
        'student' => ['defense.student@example.com', '990000000001', 'DEMO-MEMBER-01', 'Student'],
        'overdue' => ['defense.overdue@example.com', '990000000002', 'DEMO-MEMBER-02', 'Borrower'],
    ];

    public function run(): void
    {
        $connection = DB::connection();
        if (! app()->environment('local') || config('mail.default') !== 'array'
            || $connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== self::DATABASE
            || $connection->selectOne('SELECT DATABASE() AS selected_schema')->selected_schema !== self::DATABASE) {
            throw new RuntimeException('Demo records require the local MySQL devaura_lms_demo schema and the array mailer.');
        }

        $connection->transaction(function (): void {
            if (User::withTrashed()->whereNotIn('email', array_column(self::ACCOUNTS, 0))->exists()) {
                throw new RuntimeException('The demo schema contains an account outside the demo fixtures.');
            }

            $this->call(DatabaseSeeder::class);

            $department = Department::where('department_name', 'English')->firstOrFail();
            $accounts = [];
            foreach (self::ACCOUNTS as $key => [$email, $reference, $qr, $lastName]) {
                $account = User::firstOrCreate(['email' => $email], [
                    'username' => 'defense_'.$key,
                    'reference_number' => $reference,
                    'user_qr_value' => $qr,
                    'password_hash' => self::PASSWORD,
                    'account_status' => User::STATUS_ACTIVE,
                ]);
                $name = ['first_name' => 'Demo', 'last_name' => $lastName];
                $account->member()->firstOrCreate([], $name);
                $personnel = in_array($key, [Rbac::SUPERADMIN, Rbac::ADMIN], true);
                if ($personnel) {
                    $account->personnel()->firstOrCreate([], $name + ['department_id' => $department->getKey()]);
                }
                if ($account->wasRecentlyCreated) {
                    $account->assignRole($personnel ? $key : Rbac::STUDENT);
                }
                $accounts[$key] = $account;
            }

            $genre = Genre::where('genre_name', 'Reference')->firstOrFail();
            $author = Author::firstOrCreate(['first_name' => 'Sample', 'last_name' => 'Author']);
            $publisher = Publisher::firstOrCreate(['publisher_name' => 'Defense Sample Press'], ['publisher_type' => 'Organization']);
            $books = [];
            foreach ([
                'BK-DM01-AA' => 'Demo Atlas',
                'BK-DM02-AA' => 'Demo Field Guide',
                'BK-DM03-AA' => 'Demo Almanac',
                'BK-DM04-AA' => 'Demo Loan Primer',
                'BK-DM05-AA' => 'Demo Local History',
            ] as $qr => $title) {
                $book = Book::firstOrCreate(['book_qr_value' => $qr], [
                    'book_title' => $title,
                    'genre_id' => $genre->getKey(),
                    'book_status' => $qr === 'BK-DM04-AA' ? 'Borrowed' : 'Available',
                ]);
                $book->authors()->syncWithoutDetaching([$author->getKey()]);
                $book->publishers()->syncWithoutDetaching([$publisher->getKey()]);
                $books[$qr] = $book;
            }

            $head = $accounts[Rbac::SUPERADMIN];
            // The initial seed date keeps rehearsal history stable across subsequent runs.
            $day = $head->created_at->toImmutable()->setTimezone(config('attendance.timezone'))->startOfDay();
            $student = $accounts['student'];
            $visitor = WalkInVisitor::firstOrCreate(['email' => 'defense.history@example.com'], [
                'first_name' => 'Demo',
                'last_name' => 'Reader',
            ]);

            $studentVisit = $day->subDays(2)->setTime(9, 0)->utc();
            AttendanceLog::firstOrCreate(['account_id' => $student->getKey(), 'time_in_at' => $studentVisit], [
                'user_role' => Rbac::STUDENT,
                'time_out_at' => $studentVisit->addMinutes(30),
            ]);
            $visitorVisit = $day->subDay()->setTime(10, 0)->utc();
            AttendanceLog::firstOrCreate(['visitor_id' => $visitor->getKey(), 'time_in_at' => $visitorVisit], [
                'user_role' => Rbac::VISITOR,
                'time_out_at' => $visitorVisit->addMinutes(45),
                'visit_purpose' => 'Read local history',
            ]);

            BorrowTransaction::firstOrCreate(['transaction_code' => 'TRX-'.$day->subDays(3)->format('Ymd').'-DM01'], [
                'book_id' => $books['BK-DM01-AA']->getKey(),
                'borrower_id' => $student->getKey(),
                'borrow_processed_by' => $head->getKey(),
                'return_processed_by' => $head->getKey(),
                'borrow_date' => $day->subDays(3)->toDateString(),
                'due_date' => $day->toDateString(),
                'return_date' => $day->subDay()->toDateString(),
                'transaction_status' => BorrowTransaction::RETURNED,
            ]);
            BorrowTransaction::firstOrCreate(['transaction_code' => 'TRX-'.$day->subDays(5)->format('Ymd').'-DM04'], [
                'book_id' => $books['BK-DM04-AA']->getKey(),
                'borrower_id' => $accounts['overdue']->getKey(),
                'borrow_processed_by' => $head->getKey(),
                'borrow_date' => $day->subDays(5)->toDateString(),
                'due_date' => $day->subDays(2)->toDateString(),
                'transaction_status' => BorrowTransaction::BORROWED,
            ]);
        });
    }
}
