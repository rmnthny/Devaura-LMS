<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AttendanceLog;
use App\Models\Book;
use App\Models\BorrowTransaction;
use Carbon\CarbonImmutable;

class LibraryReports
{
    public const TYPES = [
        'attendance' => 'Attendance',
        'circulation' => 'Circulation',
        'inventory' => 'Inventory checks',
        'activity' => 'Audit trail',
    ];

    public function build(string $type, array $filters): array
    {
        $timezone = config('attendance.timezone');
        $start = CarbonImmutable::parse($filters['from'], $timezone)->startOfDay();
        $end = CarbonImmutable::parse($filters['through'], $timezone)->addDay()->startOfDay();

        [$query, $dateColumn, $headings, $row] = match ($type) {
            'attendance' => [
                AttendanceLog::query(), 'time_in_at',
                ['Visit #', 'Role', 'Time in (Manila)', 'Time out (Manila)'],
                fn (AttendanceLog $visit) => [
                    $visit->getKey(), $visit->user_role, $this->time($visit->time_in_at), $this->time($visit->time_out_at),
                ],
            ],
            'circulation' => [
                BorrowTransaction::with('book'), 'borrow_date',
                ['Reference', 'Copy #', 'Book', 'Checked out', 'Due', 'Returned', 'Status'],
                fn (BorrowTransaction $loan) => [
                    $loan->transaction_code, $loan->book_id, $loan->book->book_title, $loan->borrow_date->toDateString(),
                    $loan->due_date->toDateString(), $loan->return_date?->toDateString(), $loan->transaction_status,
                ],
            ],
            'inventory' => [
                Activity::where('log_name', 'inventory'), 'created_at',
                ['When (Manila)', 'Audit #', 'Copy #', 'Event', 'Previous status', 'Condition', 'Discrepancy'],
                fn (Activity $entry) => [
                    $this->time($entry->created_at), data_get($entry->properties, 'audit_id'),
                    data_get($entry->properties, 'book_id', $entry->subject_type === Book::class ? $entry->subject_id : null),
                    $entry->description, data_get($entry->properties, 'previous_status'),
                    data_get($entry->properties, 'condition'), data_get($entry->properties, 'discrepancy'),
                ],
            ],
            'activity' => [
                Activity::query()->when($filters['area'] ?? null, fn ($query, $area) => $query->where('log_name', $area)),
                'created_at',
                ['When (Manila)', 'Area', 'Event', 'Actor account #', 'Description'],
                fn (Activity $entry) => [
                    $this->time($entry->created_at), $entry->log_name, $entry->event, $entry->causer_id, $entry->description,
                ],
            ],
        };

        if ($type === 'circulation') {
            $query->where($dateColumn, '>=', $start->toDateString())->where($dateColumn, '<', $end->toDateString());
        } else {
            $query->where($dateColumn, '>=', $start->utc())->where($dateColumn, '<', $end->utc());
        }

        return [
            $query->orderByDesc($dateColumn)->orderByDesc($query->getModel()->getQualifiedKeyName()),
            $headings,
            $row,
        ];
    }

    private function time(?\DateTimeInterface $value): ?string
    {
        return $value ? CarbonImmutable::instance($value)->setTimezone(config('attendance.timezone'))->format('Y-m-d H:i') : null;
    }
}
