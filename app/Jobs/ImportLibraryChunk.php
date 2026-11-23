<?php

namespace App\Jobs;

use App\Models\Author;
use App\Models\Genre;
use App\Models\Publisher;
use App\Services\ImportedMember;
use App\Services\BookCatalog;
use App\Services\MemberProfileService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportLibraryChunk implements ShouldQueue, ShouldBeEncrypted
{
    use Batchable, Queueable;

    public int $timeout = 240;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(
        private string $type,
        private ?string $path,
        private int $index,
        private ?array $rows = null,
    ) {}

    public function handle(MemberProfileService $profiles, BookCatalog $catalog, ImportedMember $members): void
    {
        try {
            $batch = $this->batch();
            if (! $batch || $batch->cancelled()) {
                return;
            }

            $cache = Cache::store('database');
            $cache->add("library-import:{$batch->id}:started", true, now()->addWeek());
            $key = self::resultKey($batch->id, $this->index);
            $cache->lock($key.':lock', 275)->block(5, function () use ($cache, $key, $profiles, $catalog, $members): void {
                if ($cache->has($key)) {
                    return;
                }
                $rows = $this->rows ?? $this->readLegacyRows();

                DB::transaction(function () use ($rows, $cache, $key, $profiles, $catalog, $members): void {
                    $counts = ['imported' => 0, 'skipped' => 0, 'copies' => 0];
                    foreach ($rows as $row) {
                        if ($this->type === 'books') {
                            $counts['copies'] += $this->importBooks($row, $catalog);
                            $counts['imported']++;
                        } elseif ($members->create($row, $profiles)) {
                            $counts['imported']++;
                        } else {
                            $counts['skipped']++;
                        }
                    }
                    // Save the result with the records so a retry cannot add the same copies again.
                    $cache->put($key, $counts, now()->addWeek());
                });
            });
        } catch (\Throwable) {
            // Import errors can contain member details.
            throw new \RuntimeException('Some import rows could not be saved.');
        }
    }

    private function readLegacyRows(): array
    {
        // Older queued imports still read their rows from an encrypted file.
        $serialized = Storage::disk('local')->get($this->path);
        $rows = json_decode(Crypt::decryptString($serialized), true, flags: JSON_THROW_ON_ERROR);

        return array_slice($rows, $this->index * 100, 100);
    }

    public static function resultKey(string $batch, int $index): string
    {
        return "library-import:{$batch}:{$index}";
    }
    private function importBooks(array $row, BookCatalog $catalog): int
    {
        $genre = Genre::firstOrCreate(['genre_name' => $row['genre']]);
        $authors = collect($row['authors'])->map(function (string $name) {
            [$first, $last] = \App\Services\ImportRows::splitAuthor($name);
            return Author::firstOrCreate(['first_name' => $first, 'last_name' => $last])->getKey();
        })->unique()->values()->all();
        $publishers = collect($row['publishers'])->map(fn ($name) =>
            Publisher::firstOrCreate(['publisher_name' => $name])->getKey())->unique()->values()->all();

        return $catalog->createCopies([
            'book_title' => $row['book_title'], 'isbn' => $row['isbn'], 'genre_id' => $genre->getKey(),
            'book_status' => 'Available', 'date_published' => $row['date_published'],
            'author_ids' => $authors, 'publisher_ids' => $publishers,
        ], (int) $row['copies'])->count();
    }
}
