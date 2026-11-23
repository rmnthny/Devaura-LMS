<?php

namespace App\Services;

use App\Jobs\ImportLibraryChunk;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class LibraryImports
{
    private const INLINE_LIMIT = 20;

    private const BOOK_COPIES_PER_JOB = 100;

    private const MEMBERS_PER_JOB = 100;

    public function queue(string $type, array $rows, User $actor): Batch
    {
        $units = $type === 'books' ? array_sum(array_column($rows, 'copies')) : count($rows);
        $chunks = $type === 'books' ? $this->chunkBooks($rows) : array_chunk($rows, self::MEMBERS_PER_JOB);
        $actorId = $actor->getKey();
        $batch = Bus::batch(array_map(
            fn (array $chunk, int $index) => new ImportLibraryChunk($type, null, $index, $chunk),
            $chunks, array_keys($chunks),
        ))->name("Library {$type} import")
            ->allowFailures()
            ->withOption('import_rows', count($rows))
            ->finally(static function (Batch $batch) use ($actorId, $type): void {
                $actor = User::withTrashed()->find($actorId);
                $log = activity('imports')->withProperties([
                    ...LibraryImports::counts($batch),
                    'failed_jobs' => $batch->failedJobs,
                ]);
                if ($actor) {
                    $log->causedBy($actor);
                }
                $log->log("Finished processing {$type} import");
            });

        if ($units <= self::INLINE_LIMIT) {
            $batch->onConnection('sync');
        }

        return $batch->dispatch();
    }

    private function chunkBooks(array $rows): array
    {
        $chunks = [];
        $chunk = [];
        $copies = 0;
        foreach ($rows as $row) {
            if ($copies + $row['copies'] > self::BOOK_COPIES_PER_JOB) {
                $chunks[] = $chunk;
                $chunk = [];
                $copies = 0;
            }
            $chunk[] = $row;
            $copies += $row['copies'];
        }
        if ($chunk !== []) {
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    public static function counts(Batch $batch): array
    {
        $counts = ['imported' => 0, 'skipped' => 0, 'copies' => 0];
        $cache = Cache::store('database');
        for ($index = 0; $index < $batch->totalJobs; $index++) {
            foreach ($cache->get(ImportLibraryChunk::resultKey($batch->id, $index), []) as $key => $value) {
                $counts[$key] += $value;
            }
        }
        return $counts;
    }

    public static function started(Batch $batch): bool
    {
        return Cache::store('database')->has("library-import:{$batch->id}:started");
    }
}
