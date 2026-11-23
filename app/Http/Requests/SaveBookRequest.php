<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveBookRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $merged = [];

        if (is_string($this->input('book_title'))) {
            $merged['book_title'] = preg_replace('/\s+/u', ' ', trim($this->input('book_title')));
        }
        if (is_string($this->input('isbn'))) {
            $cleaned = preg_replace('/[\s-]+/', '', trim($this->input('isbn')));
            $merged['isbn'] = $cleaned !== '' ? strtoupper($cleaned) : null;
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'genre_id' => ['required', 'integer', 'exists:genre,genre_id'],
            'book_title' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'regex:/^(?:[0-9]{13}|[0-9]{9}[0-9X])$/i'],
            'date_published' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'book_status' => ['required', Rule::in(Book::STATUSES)],
            'copies' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'author_ids' => ['sometimes', 'array'],
            'author_ids.*' => ['integer', 'distinct', 'exists:author,author_id'],
            'publisher_ids' => ['sometimes', 'array'],
            'publisher_ids.*' => ['integer', 'distinct', 'exists:publisher,publisher_id'],
        ];
    }
}
