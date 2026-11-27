<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AttendanceLog;
use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\Genre;
use App\Models\User;
use App\Support\Rbac;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LibraryReports
{
    public const TYPES = [
        'attendance' => 'Attendance',
        'circulation' => 'Circulation',
        'inventory' => 'Inventory Checks',
        'activity' => 'Audit Trail',
    ];

    public const ATTENDANCE_ROLES = [
        Rbac::STUDENT => 'Student',
        Rbac::FACULTY => 'Faculty',
        Rbac::VISITOR => 'Visitor',
        Rbac::SUPERADMIN => 'Head Librarian',
        Rbac::ADMIN => 'Admin',
        Rbac::STAFF => 'Staff',
        Rbac::MAINTENANCE => 'Maintenance',
    ];

    public const CIRCULATION_ACTIVITIES = [
        'checkout' => 'Checkouts by checkout date',
        'return' => 'Returns by return date',
        'overdue' => 'Open overdue loans by due date',
        'active' => 'All active loans (current snapshot)',
    ];

    public const EMPTY_MESSAGE = 'No records match the filters.';

    public function build(string $type, array $filters, string $today): array
    {
        $timezone = config('attendance.timezone');
        $start = CarbonImmutable::parse($filters['from'], $timezone)->startOfDay();
        $end = CarbonImmutable::parse($filters['through'], $timezone)->addDay()->startOfDay();

        [$query, $dateColumn, $headings, $row] = match ($type) {
            'attendance' => [
                AttendanceLog::query()->when($filters['role'] ?? null, fn ($query, $role) => $query->where('user_role', $role)),
                'time_in_at',
                ['Visit #', 'Role at time in', 'Time in (Manila)', 'Time out (Manila)'],
                fn (AttendanceLog $visit) => [
                    $visit->getKey(), self::ATTENDANCE_ROLES[$visit->user_role] ?? $visit->user_role,
                    $this->time($visit->time_in_at), $this->time($visit->time_out_at),
                ],
            ],
            'circulation' => $this->circulation($filters['circulation_activity'], $today),
            'inventory' => [
                Activity::where('log_name', 'inventory')->where('event', 'checked')
                    ->where('subject_type', (new Book)->getMorphClass())
                    ->when($this->hasBookFilters($filters), fn (Builder $query) => $query
                        ->whereHasMorph('subject', Book::class, fn (Builder $books) => $this->filterBooks($books->withTrashed(), $filters)))
                    ->with(['subject' => fn (MorphTo $subject) => $subject->withTrashed()]),
                'created_at',
                ['Checked at (Manila)', 'Copy #', 'Book', 'Previous status', 'Observed status', 'Discrepancy'],
                fn (Activity $entry) => [
                    $this->time($entry->created_at), $entry->subject_id, $entry->subject?->book_title,
                    data_get($entry->properties, 'previous_status'), data_get($entry->properties, 'condition'),
                    match (data_get($entry->properties, 'condition')) {
                        'Lost' => 'Lost',
                        'Damaged' => 'Damaged',
                        default => null,
                    },
                ],
            ],
            'activity' => [
                Activity::with(['causer' => fn (MorphTo $causer) => $causer->withTrashed()])
                    ->when(isset($filters['area']), fn ($query) => $query->where('log_name', $filters['area']))
                    ->when(isset($filters['event']), fn ($query) => $query->where('event', $filters['event']))
                    ->when(isset($filters['action']), fn ($query) => $query->whereRaw('LOCATE(?, description) > 0', [$filters['action']]))
                    ->when(isset($filters['transaction']), fn ($query) => $query
                        ->where('log_name', 'circulation')->where('properties->reference', strtoupper($filters['transaction']))),
                'created_at',
                ['Date and time', 'Area', 'Actor', 'Event', 'Action', 'Transaction'],
                fn (Activity $entry) => [
                    $this->time($entry->created_at), $entry->log_name, $this->actor($entry), $entry->event, $entry->description,
                    $entry->log_name === 'circulation' ? data_get($entry->properties, 'reference') : null,
                ],
            ],
        };

        if ($type === 'circulation') {
            if ($filters['circulation_activity'] !== 'active') {
                $query->where($dateColumn, '>=', $start->toDateString())->where($dateColumn, '<', $end->toDateString());
            }
        } else {
            $query->where($dateColumn, '>=', $start->utc())->where($dateColumn, '<', $end->utc());
        }

        return [
            $query->orderByDesc($dateColumn)->orderByDesc($query->getModel()->getQualifiedKeyName()),
            $headings,
            $row,
        ];
    }

    public function operationalFilterOptions(string $type, array $types, array $genres): array
    {
        $options = ['type' => $types];

        return $options + match ($type) {
            'attendance' => ['role' => self::ATTENDANCE_ROLES],
            'circulation' => ['circulation_activity' => self::CIRCULATION_ACTIVITIES],
            'inventory' => [
                'genre_id' => $genres,
                'book_title' => Book::withTrashed()->whereNotNull('book_title')->where('book_title', '<>', '')
                    ->distinct()->orderBy('book_title')->pluck('book_title')->all(),
                'published_on' => Book::withTrashed()->whereNotNull('date_published')
                    ->distinct()->orderByDesc('date_published')->pluck('date_published')
                    ->map(fn ($date) => $date->toDateString())->all(),
            ],
        };
    }

    public function auditFilterOptions(array $filters, string $today): array
    {
        [$query] = $this->build('activity', array_intersect_key($filters, ['from' => true, 'through' => true]), $today);
        $query->reorder()->distinct();
        $options = [];
        foreach (['area' => ['log_name', 80], 'event' => ['event', 80], 'action' => ['description', 255]] as $key => [$column, $max]) {
            $options[$key] = (clone $query)->whereNotNull($column)->where($column, '<>', '')
                ->whereRaw("CHAR_LENGTH({$column}) <= ?", [$max])->orderBy($column)->pluck($column)->all();
        }
        $options['transaction'] = (clone $query)->where('log_name', 'circulation')
            ->whereNotNull('properties->reference')->where('properties->reference', '<>', '')
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.reference')) as reference")
            ->orderBy('reference')->pluck('reference')->all();

        return $options;
    }

    public function summary(string $type, array $filters, int $total): array
    {
        $period = $type === 'circulation' && $filters['circulation_activity'] === 'active'
            ? ['Snapshot (Manila)' => now(config('attendance.timezone'))->toDateString()]
            : [
                ($type === 'activity' ? 'From' : 'From (Manila)') => $filters['from'],
                ($type === 'activity' ? 'Through' : 'Through (Manila)') => $filters['through'],
            ];

        return $period + ['Matching records' => $total] + match ($type) {
            'attendance' => ['Role at time in' => self::ATTENDANCE_ROLES[$filters['role'] ?? ''] ?? 'All roles'],
            'circulation' => ['Activity' => self::CIRCULATION_ACTIVITIES[$filters['circulation_activity']]],
            'inventory' => ['Current active catalog' => $this->catalogSummary($filters)] + array_filter([
                'Book title contains' => $filters['book_title'] ?? null,
                'Genre' => isset($filters['genre_id']) ? Genre::whereKey($filters['genre_id'])->value('genre_name') : null,
                'Published on' => $filters['published_on'] ?? null,
            ], fn ($value) => $value !== null),
            'activity' => ['Area' => $filters['area'] ?? 'All areas'] + array_filter([
                'Event' => $filters['event'] ?? null,
                'Action contains' => $filters['action'] ?? null,
                'Transaction' => isset($filters['transaction']) ? strtoupper($filters['transaction']) : null,
            ], fn ($value) => $value !== null),
        };
    }

    private function circulation(string $activity, string $today): array
    {
        $query = BorrowTransaction::with('book');
        $dateColumn = match ($activity) {
            'return' => 'return_date',
            'overdue' => 'due_date',
            default => 'borrow_date',
        };
        if ($activity === 'overdue') {
            $query->overdueOn($today);
        } elseif ($activity === 'active') {
            $query->open();
        }

        return [
            $query,
            $dateColumn,
            ['Reference', 'Copy #', 'Book', 'Checked out', 'Due', 'Returned', 'Current status'],
            fn (BorrowTransaction $loan) => [
                $loan->transaction_code, $loan->book_id, $loan->book?->book_title, $loan->borrow_date->toDateString(),
                $loan->due_date->toDateString(), $loan->return_date?->toDateString(),
                $loan->transaction_status === BorrowTransaction::BORROWED && $loan->return_date === null && $loan->isOverdueOn($today)
                    ? BorrowTransaction::OVERDUE
                    : $loan->transaction_status,
            ],
        ];
    }

    private function hasBookFilters(array $filters): bool
    {
        return isset($filters['book_title']) || isset($filters['genre_id']) || isset($filters['published_on']);
    }

    private function filterBooks(Builder $query, array $filters): Builder
    {
        return $query
            ->when(isset($filters['book_title']), fn ($books) => $books->whereRaw('LOCATE(?, book_title) > 0', [$filters['book_title']]))
            ->when($filters['genre_id'] ?? null, fn ($books, $genre) => $books->where('genre_id', $genre))
            ->when($filters['published_on'] ?? null, fn ($books, $date) => $books->where('date_published', $date));
    }

    private function catalogSummary(array $filters): string
    {
        $counts = $this->filterBooks(Book::query(), $filters)
            ->selectRaw('book_status, count(*) as total')->groupBy('book_status')->pluck('total', 'book_status');
        $statuses = array_map(fn (string $status) => ($counts[$status] ?? 0).' '.strtolower($status), Book::STATUSES);

        return $counts->sum().' copies ('.implode(', ', $statuses).')';
    }

    private function time(?DateTimeInterface $value): ?string
    {
        return $value
            ? CarbonImmutable::instance($value)->setTimezone(config('attendance.timezone'))->format('Y-m-d H:i:s')
            : null;
    }

    private function actor(Activity $entry): string
    {
        if ($entry->causer instanceof User) {
            return "{$entry->causer->username} (#{$entry->causer_id})";
        }

        return $entry->causer_id === null ? 'System' : "Account #{$entry->causer_id}";
    }
}
