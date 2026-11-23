<?php

namespace App\Http\Controllers;

use App\Services\ImportRows;
use App\Services\LibraryImportTemplate;
use App\Services\LibraryImports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LibraryImportController extends Controller
{
    public function template(Request $request, LibraryImportTemplate $template)
    {
        $type = $request->route('kind');

        return match ($request->query('format', 'csv')) {
            'csv' => response()->streamDownload(
                fn () => $template->writeCsv($type),
                "{$type}-import-template.csv",
                ['Content-Type' => 'text/csv; charset=UTF-8'],
            ),
            'xlsx' => response()->streamDownload(
                fn () => $template->writeExcel($type),
                "{$type}-import-template.xlsx",
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ),
            default => abort(404),
        };
    }

    public function store(Request $request, ImportRows $reader, LibraryImports $imports)
    {
        $data = $request->validateWithBag('libraryImport', [
            'file' => ['required', 'file', 'extensions:csv,xls,xlsx', 'max:'.ImportRows::MAX_FILE_KB],
        ], [
            'file.required' => 'Choose a spreadsheet file to import.',
            'file.extensions' => 'Upload a CSV (.csv) or Excel (.xls or .xlsx) file.',
            'file.max' => 'The spreadsheet file must be 5 MB or less.',
        ]);
        $type = $request->route('kind');
        $file = $data['file'];
        $path = $file->getPathname();
        try {
            $rows = $reader->validate($file, $type);
        } catch (ValidationException $error) {
            throw $error->errorBag('libraryImport');
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        try {
            $batch = $imports->queue($type, $rows, $request->user());
        } catch (\Exception $error) {
            Log::error('Library import could not be dispatched or finished inline.', [
                'type' => $type,
                'exception_type' => $error::class,
            ]);
            throw ValidationException::withMessages([
                'file' => 'The import could not finish. Check the directory for any saved records before uploading the file again.',
            ])->errorBag('libraryImport');
        }
        $request->session()->put("library-imports.{$type}", $batch->id);

        return $request->expectsJson()
            ? response()->json(['batch_id' => $batch->id, 'rows' => count($rows)], 202)
            : to_route("{$type}.index");
    }
}
