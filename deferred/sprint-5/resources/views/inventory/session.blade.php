@extends('layouts.library')

@section('title', 'Inventory audit')

@section('content')
    <a class="d-print-none" href="{{ route('inventory.sessions.index') }}">Inventory audits</a>
    <h1 class="h3">Inventory audit #{{ $session->getKey() }}</h1>
    <p>{{ $session->audit_status }} @if($session->remarks) · {{ $session->remarks }} @endif</p>
    <div class="d-flex gap-2 flex-wrap mb-3 d-print-none">
        @foreach(['' => 'All copies', 'unchecked' => 'Unchecked', 'discrepancies' => 'Discrepancies', 'on-loan' => 'On loan'] as $filter => $label)
            <a class="btn btn-outline-secondary" href="{{ route('inventory.sessions.show', array_filter(['session' => $session, 'filter' => $filter])) }}">{{ $label }}</a>
        @endforeach
        <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Print current page</button>
    </div>
    <div class="table-responsive border rounded mb-3">
        <table class="table mb-0">
            <thead><tr><th>Copy</th><th>Expected</th><th>Verification</th><th>Discrepancy</th>@if($session->audit_status === 'In Progress')<th class="d-print-none">Record or recount</th>@endif</tr></thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->book->book_title }} · #{{ $item->book_id }}</td><td>{{ $item->expected_status }}</td>
                        <td>{{ $item->verification_status }} @if($item->observed_condition) · {{ $item->observed_condition }} @endif
                            @if($item->on_loan) <small class="d-block">Open loan on file</small> @endif
                        </td>
                        <td>{{ $item->discrepancy_type ?? '—' }} @if($item->remarks)<small class="d-block">{{ $item->remarks }}</small>@endif</td>
                        @if($session->audit_status === 'In Progress')
                            <td class="d-print-none">
                                <form action="{{ route('inventory.sessions.items.update', [$session, $item->getKey()]) }}" method="post" class="d-flex flex-wrap gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="verification_status" aria-label="Verification" class="form-select form-select-sm w-auto">
                                        <option value="Present">Present</option><option value="Missing" @selected($item->verification_status === 'Missing')>Missing</option>
                                    </select>
                                    <select name="observed_condition" aria-label="Condition if present" class="form-select form-select-sm w-auto">
                                        <option value="Good">Good</option><option value="Damaged" @selected($item->observed_condition === 'Damaged')>Damaged</option>
                                    </select>
                                    <input class="form-control form-control-sm w-auto" name="remarks" maxlength="1000" aria-label="Remarks" value="{{ $item->remarks }}">
                                    <button class="btn btn-sm btn-outline-primary">Save</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="5">No copies match this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-print-none">{{ $items->links() }}</div>
    @if($session->audit_status === 'In Progress')
        <form action="{{ route('inventory.sessions.finish', $session) }}" method="post" class="d-flex gap-2 mt-3 d-print-none">
            @csrf
            @method('PATCH')
            <button class="btn btn-primary" name="outcome" value="approve">Approve audit</button>
            <button class="btn btn-outline-danger" name="outcome" value="cancel">Cancel audit</button>
        </form>
    @endif
@endsection
