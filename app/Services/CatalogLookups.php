<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Genre;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CatalogLookups
{
    public const TYPES = [
        'genres' => [Genre::class, ['genre_name' => 'Genre name']],
        'authors' => [Author::class, ['last_name' => 'Last name', 'first_name' => 'First name', 'middle_initial' => 'Middle initial']],
        'publishers' => [Publisher::class, ['publisher_name' => 'Publisher name', 'publisher_type' => 'Publisher type']],
    ];

    public function delete(Model $record): void
    {
        if ($record->books()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['lookup' => 'This record is still linked to a book copy.']);
        }

        try {
            $record->delete();
        } catch (QueryException $error) {
            if (($error->errorInfo[1] ?? null) !== 1451) {
                throw $error;
            }

            throw ValidationException::withMessages(['lookup' => 'This record is still linked to a book copy.']);
        }
    }
}
