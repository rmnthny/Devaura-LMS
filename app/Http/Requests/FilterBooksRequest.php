<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterBooksRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $merged = [];

        if (is_string($this->input('title'))) {
            $merged['title'] = trim($this->input('title'));
        }
        if (is_string($this->input('isbn'))) {
            $cleaned = preg_replace('/[\s-]+/', '', trim($this->input('isbn')));
            $merged['isbn'] = $cleaned !== '' ? strtoupper($cleaned) : null;
        }
        if (is_string($this->input('qr'))) {
            $cleaned = strtoupper(trim($this->input('qr')));
            $merged['qr'] = $cleaned !== '' ? $cleaned : null;
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:17'],
            'qr' => ['nullable', 'string', 'max:50'],
            'genre_id' => ['nullable', 'integer', 'exists:genre,genre_id'],
            'author_id' => ['nullable', 'integer', 'exists:author,author_id'],
            'book_status' => ['nullable', Rule::in(Book::STATUSES)],
        ];
    }
}
