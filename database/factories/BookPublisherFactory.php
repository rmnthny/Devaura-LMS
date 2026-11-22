<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookPublisher;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookPublisher>
 * Optional: normally attach publishers via Book::publishers()->attach(...).
 * Composite-PK pivot model must have $incrementing=false and $timestamps=false.
 */
class BookPublisherFactory extends Factory
{
    protected $model = BookPublisher::class;

    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'publisher_id' => Publisher::factory(),
        ];
    }
}
