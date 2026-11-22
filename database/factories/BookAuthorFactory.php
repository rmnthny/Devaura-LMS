<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookAuthor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookAuthor>
 * Optional: normally attach authors using Book::authors()->attach(...).
 * Composite-PK pivot model must have $incrementing=false and $timestamps=false.
 */
class BookAuthorFactory extends Factory
{
    protected $model = BookAuthor::class;

    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'author_id' => Author::factory(),
        ];
    }
}
