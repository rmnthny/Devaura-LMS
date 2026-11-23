<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryAuditRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'book_qr_value' => ['required', 'string', 'max:50'],
            'condition' => ['required', Rule::in(Book::AUDIT_CONDITIONS)],
        ];
    }
}
