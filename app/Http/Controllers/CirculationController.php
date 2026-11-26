<?php

namespace App\Http\Controllers;

use App\Models\BorrowTransaction;
use App\Services\Circulation;
use App\Services\CirculationLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CirculationController extends Controller
{
    public function borrowForm(Request $request, CirculationLookup $lookup)
    {
        $filters = $request->validate([
            'member_query' => ['nullable', 'string', 'min:2', 'max:80'],
            'book_query' => ['nullable', 'string', 'max:80'],
            'member' => $this->idRules(),
            'copy' => $this->idRules(),
        ]);
        $memberQuery = $filters['member_query'] ?? '';
        $bookQuery = $filters['book_query'] ?? '';
        $member = isset($filters['member']) ? $lookup->member($request->user(), (int) $filters['member']) : null;
        $copy = isset($filters['copy']) ? $lookup->book((int) $filters['copy']) : null;

        return view('circulation.borrow', [
            'checkoutId' => (string) Str::uuid(),
            'loanDays' => Circulation::LOAN_DAYS,
            'member' => $member,
            'copy' => $copy,
            'memberUnavailable' => isset($filters['member']) && $member === null,
            'copyUnavailable' => isset($filters['copy']) && $copy === null,
            'memberQuery' => $memberQuery,
            'bookQuery' => $bookQuery,
            'members' => $memberQuery !== '' ? $lookup->members($request->user(), $memberQuery) : collect(),
            'books' => $bookQuery !== '' ? $lookup->books($bookQuery) : collect(),
        ]);
    }

    public function borrow(Request $request, Circulation $circulation)
    {
        $data = $request->validate([
            ...$this->copyRules(),
            'borrower_identifier' => ['nullable', 'required_without:borrower_account_id', 'prohibits:borrower_account_id', 'string', 'max:50'],
            'borrower_account_id' => [...$this->idRules(), 'required_without:borrower_identifier'],
            'request_id' => ['required', 'uuid'],
        ]);

        $loan = $circulation->borrow($request->user(), $data);
        $message = 'Checkout recorded as '.$loan->transaction_code.'. Due '.$loan->due_date->toDateString().'.';

        return $request->expectsJson() ? $this->response($loan, $loan->wasRecentlyCreated ? 201 : 200)
            : to_route('circulation.borrow.form')->with('status', $message);
    }

    public function returnForm(Request $request, CirculationLookup $lookup)
    {
        $filters = $request->validate([
            'loan_query' => ['nullable', 'string', 'max:80'],
            'transaction_code' => ['nullable', 'string', 'max:30', 'prohibits:book_qr_value,book_id'],
        ]);
        $data = $request->hasAny(['book_qr_value', 'book_id']) ? $request->validate($this->copyRules()) : [];
        $selection = isset($filters['transaction_code']) ? $filters : $data;
        $loan = null;
        $lookupError = null;
        if ($selection) {
            try {
                $loan = $lookup->openLoan($selection);
            } catch (ValidationException $error) {
                if ($request->expectsJson()) {
                    throw $error;
                }

                $lookupError = $error->validator->errors()->first();
            }
        }

        if ($loan && $request->expectsJson()) {
            return $this->response($loan, 200);
        }

        $loanQuery = $filters['loan_query'] ?? '';

        return view('circulation.return', [
            'loan' => $loan,
            'lookupError' => $lookupError,
            'overdue' => $loan?->isOverdueOn(now(config('attendance.timezone'))->toDateString()) ?? false,
            'loanQuery' => $loanQuery,
            'loans' => $loanQuery !== '' ? $lookup->loans($loanQuery) : collect(),
            'bookQr' => $data['book_qr_value'] ?? '',
        ]);
    }

    public function checkIn(Request $request, Circulation $circulation)
    {
        $data = $request->validate([
            ...$this->copyRules(),
            'transaction_code' => ['required', 'string', 'max:30'],
            'condition' => ['required', Rule::in(['Available', 'Damaged', 'Lost'])],
            'repair_on' => ['prohibited'],
        ]);
        $loan = $circulation->checkIn($request->user(), $data);

        $outcome = $loan->transaction_status === BorrowTransaction::LOST ? 'Loss' : 'Return';

        return $request->expectsJson() ? $this->response($loan, 200)
            : to_route('circulation.return.form')->with('status', $outcome.' recorded for '.$loan->transaction_code.'.');
    }

    private function copyRules(): array
    {
        return [
            'book_qr_value' => ['nullable', 'required_without:book_id', 'prohibits:book_id', 'string', 'max:50'],
            'book_id' => [...$this->idRules(), 'required_without:book_qr_value'],
        ];
    }

    private function idRules(): array
    {
        return ['nullable', 'integer', 'min:1', 'max:4294967295'];
    }

    private function response(BorrowTransaction $loan, int $status)
    {
        return response()->json([
            'transaction_code' => $loan->transaction_code,
            'transaction_status' => $loan->transaction_status,
            'due_date' => $loan->due_date->toDateString(),
            'return_date' => $loan->return_date?->toDateString(),
        ], $status);
    }
}
