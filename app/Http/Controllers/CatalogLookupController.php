<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCatalogLookupRequest;
use App\Services\CatalogLookups;
use Illuminate\Http\Request;

class CatalogLookupController extends Controller
{
    public function index(Request $request, string $lookup)
    {
        [$model, $fields] = CatalogLookups::TYPES[$lookup];
        $search = $request->validate(['q' => ['nullable', 'string', 'max:255']])['q'] ?? '';
        $records = $model::query()
            ->when($search !== '', fn ($query) => $query->whereAny(array_keys($fields), 'like', "%{$search}%"))
            ->orderBy(array_key_first($fields))->paginate(20)->withQueryString();

        return $request->expectsJson() ? $records : view('books.lookups', compact('lookup', 'fields', 'records', 'search'));
    }

    public function store(SaveCatalogLookupRequest $request, string $lookup)
    {
        [$model] = CatalogLookups::TYPES[$lookup];
        $item = $model::create($request->validated());

        return $request->expectsJson() ? response()->json($item, 201) : to_route('catalog.index', $lookup);
    }

    public function update(SaveCatalogLookupRequest $request, string $lookup, int $record)
    {
        [$model] = CatalogLookups::TYPES[$lookup];
        $item = $model::findOrFail($record);
        $item->update($request->validated());

        return $request->expectsJson() ? response()->json($item) : to_route('catalog.index', $lookup);
    }

    public function destroy(Request $request, string $lookup, int $record, CatalogLookups $lookups)
    {
        [$model] = CatalogLookups::TYPES[$lookup];
        $lookups->delete($model::findOrFail($record));

        return $request->expectsJson() ? response()->noContent() : to_route('catalog.index', $lookup);
    }
}
