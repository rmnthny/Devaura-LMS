<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'genre_id' => Genre::factory(),
            // ISBN can repeat across physical copies, so no unique() constraint.
            'isbn' => null,
            'book_title' => fake()->words(4, true),
            'book_status' => 'Available',
            'book_qr_value' => (string) Str::uuid(),
            'date_published' => fake()->dateTimeBetween('-25 years', '-1 month')->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withIsbn(string $isbn): static
    {
        return $this->state(fn (): array => ['isbn' => $isbn]);
    }

    public function damaged(): static
    {
        return $this->state(fn (): array => ['book_status' => 'Damaged']);
    }
}
