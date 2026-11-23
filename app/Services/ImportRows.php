<?php

namespace App\Services;

use App\Models\Department;
use App\Support\MemberFieldRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\CellIterator;

class ImportRows
{
    public const MAX_FILE_KB = 5120;

    public const MAX_DATA_ROWS = 2000;

    private const MAX_COLUMNS = 64;

    private const REQUIRED_HEADERS = [
        'members' => ['member_role', 'reference_number', 'last_name', 'first_name', 'email'],
        'books' => ['book_title', 'genre'],
    ];

    public const HEADERS = [
        'members' => ['member_role', 'reference_number', 'last_name', 'first_name', 'middle_initial', 'department', 'email'],
        'books' => ['book_title', 'isbn', 'genre', 'authors', 'publishers', 'date_published', 'copies'],
    ];

    public function validate(UploadedFile $file, string $type): array
    {
        try {
            $records = $this->read($file);
        } catch (\Throwable $error) {
            if ($error instanceof ValidationException) {
                throw $error;
            }
            // The reader can include spreadsheet contents in errors.
            $this->fail('The spreadsheet could not be read. Check that the file opens, then save a fresh copy and retry.');
        }

        $headerRecord = array_shift($records) ?? [];
        if (array_filter($headerRecord, fn ($value) => preg_match('/^[=+\-@]/', ltrim((string) $value)))) {
            $this->fail('Use plain text for column headers.');
        }
        $header = array_map(
            fn ($value) => preg_replace('/[\s-]+/u', '_', strtolower(trim((string) $value))),
            $headerRecord,
        );
        $namedColumns = array_filter($header, fn ($name) => $name !== '');
        $missing = array_diff(self::REQUIRED_HEADERS[$type], $namedColumns);
        if ($missing !== [] || count($namedColumns) !== count(array_unique($namedColumns))) {
            $this->fail($missing !== [] ? 'Missing headers: '.implode(', ', $missing).'.' : 'The header contains duplicate columns.');
        }

        $departments = $type === 'members'
            ? Department::pluck('department_id', 'department_name')->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id])
            : collect();
        $seen = ['email' => [], 'reference_number' => []];
        $errors = [];
        $totalErrors = 0;
        $rows = [];

        foreach ($records as $index => $record) {
            if (count(array_filter($record, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($namedColumns as $column => $name) {
                $row[$name] = trim((string) ($record[$column] ?? ''));
            }
            $rowNumber = $index + 2;
            $messages = [];
            foreach ($record as $column => $value) {
                if (preg_match('/^[=+\-@]/u', ltrim((string) $value))) {
                    $messages[] = 'A cell starts with a formula marker.';
                }
                if (! isset($namedColumns[$column]) && trim((string) $value) !== '') {
                    $messages[] = 'A cell has data under an unnamed column. Add a header or clear the cell.';
                }
            }

            $values = array_replace(array_fill_keys(self::HEADERS[$type], ''), array_intersect_key($row, array_flip(self::HEADERS[$type])));
            $values = array_map(fn ($value) => $value === '' ? null : $value, $values);
            $validationMessages = MemberFieldRules::messages();
            if ($type === 'members') {
                $values['member_role'] = $values['member_role'] === null ? null : ucfirst(strtolower($values['member_role']));
                $values['middle_initial'] = MemberFieldRules::formatMiddleInitial($values['middle_initial']);
                $values['department_id'] = $values['member_role'] === 'Faculty'
                    ? $departments->get(mb_strtolower($values['department'] ?? '')) : null;

                $rules = [
                    ...MemberFieldRules::names(),
                    'member_role' => ['required', Rule::in(['Student', 'Faculty'])],
                    'reference_number' => ['required', 'string', $values['member_role'] === 'Faculty' ? 'digits:7' : 'digits:12'],
                    'department_id' => ['required_if:member_role,Faculty', 'nullable', 'integer'],
                    'email' => MemberFieldRules::email(),
                ];
                $validationMessages['reference_number.digits'] = $values['member_role'] === 'Faculty'
                    ? 'Enter a 7-digit DepEd employee number.'
                    : 'Enter a 12-digit student LRN.';
                $validationMessages['department_id.required_if'] = 'Choose a recognized subject department for Faculty.';
                if ($values['member_role'] === 'Student' && $values['department'] !== null) {
                    $messages[] = 'Leave the department blank for Student.';
                }
                foreach (array_keys($seen) as $key) {
                    if ($values[$key] === null) {
                        continue;
                    }
                    $unique = mb_strtolower($values[$key]);
                    if (isset($seen[$key][$unique])) {
                        $messages[] = "Duplicate {$key} in the file (first seen on row {$seen[$key][$unique]}).";
                    }
                    $seen[$key][$unique] ??= $rowNumber;
                }
            } else {
                $values['isbn'] = $values['isbn'] === null ? null : strtoupper(preg_replace('/[ -]/', '', $values['isbn']));
                $values['copies'] ??= 1;
                $rules = [
                    'book_title' => ['required', 'string', 'max:255'],
                    'isbn' => ['nullable', 'regex:/\A(?:[0-9]{9}[0-9X]|[0-9]{13})\z/'],
                    'genre' => ['required', 'string', 'max:100', 'regex:/\A[\p{L}\p{N}][\p{L}\p{M}\p{N} &,\x27()\/.-]*\z/u'],
                    'date_published' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
                    'copies' => ['required', 'integer', 'between:1,50'],
                ];
                foreach (['authors' => 105, 'publishers' => 255] as $field => $limit) {
                    $values[$field] = array_values(array_filter(array_map('trim', explode(';', $values[$field] ?? ''))));
                    $rules[$field] = ['array', 'max:50'];
                    $rules["{$field}.*"] = ['string', "max:{$limit}", 'not_regex:/^[=+\-@]/'];
                }
                foreach ($values['authors'] as $name) {
                    foreach (self::splitAuthor($name) as $part) {
                        if ($part !== null && mb_strlen($part) > 50) {
                            $messages[] = 'An author first or last name exceeds 50 characters.';
                        }
                    }
                }
                if ($values['isbn'] !== null && ! $this->validIsbn($values['isbn'])) {
                    $messages[] = 'The ISBN must be a valid ISBN-10 or ISBN-13.';
                }
            }

            $validator = Validator::make($values, $rules, $validationMessages);
            $messages = [...$messages, ...$validator->errors()->all()];
            foreach ($messages as $message) {
                if ($totalErrors++ < 10) {
                    $errors[] = "Row {$rowNumber}: {$message}";
                }
            }
            if ($messages === []) {
                $rows[] = $validator->validated();
            }
        }

        if ($totalErrors > 0) {
            if ($totalErrors > 10) {
                $errors[] = '...and '.($totalErrors - 10).' more errors';
            }
            throw ValidationException::withMessages(['file' => $errors]);
        }
        if ($rows === []) {
            $this->fail('The file has no data rows.');
        }

        return $rows;
    }

    private function read(UploadedFile $file): array
    {
        $path = $file->getPathname();
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'csv') {
            return $this->readDelimited($path);
        }

        if (! in_array($extension, ['xls', 'xlsx'], true)) {
            $this->fail('Upload a CSV (.csv) or Excel (.xls or .xlsx) file.');
        }
        if ($extension === 'xlsx') {
            if (! class_exists(\ZipArchive::class)) {
                $this->fail('Excel import needs Zip enabled in the PHP instance serving this app. Restart the server after enabling extension=zip.');
            }
            $zip = new \ZipArchive;
            if ($zip->open($path) !== true) {
                $this->fail('The XLSX file could not be opened.');
            }
            try {
                $size = 0;
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $size += $zip->statIndex($index)['size'];
                    if ($size > 32 * 1024 * 1024) {
                        $this->fail('The XLSX expands beyond the supported size.');
                    }
                }
            } finally {
                $zip->close();
            }
        }

        $reader = IOFactory::createReader($extension === 'xls' ? 'Xls' : 'Xlsx');
        $info = $reader->listWorksheetInfo($path);
        if (count($info) !== 1) {
            $this->fail('Upload an Excel workbook with one worksheet. Copy the records into the Excel template if your workbook has multiple tabs.');
        }
        $sheetInfo = $info[0];
        if ($sheetInfo['totalRows'] > self::MAX_DATA_ROWS + 1 || $sheetInfo['totalColumns'] > self::MAX_COLUMNS) {
            $this->fail('Use a worksheet with at most 2,000 data rows and 64 columns.');
        }
        $spreadsheet = $reader->setLoadSheetsOnly($sheetInfo['worksheetName'])->setReadEmptyCells(false)->load($path);
        try {
            $sheet = $spreadsheet->getActiveSheet();
            $rows = [];
            foreach ($sheet->getRowIterator(1, max(1, $sheetInfo['totalRows'])) as $row) {
                $values = [];
                $cells = $row->getCellIterator('A', $sheetInfo['lastColumnLetter']);
                $cells->setIfNotExists(CellIterator::IF_NOT_EXISTS_RETURN_NULL);
                foreach ($cells as $cell) {
                    $raw = $cell?->getValue();
                    if (in_array($cell?->getDataType(), [DataType::TYPE_FORMULA, DataType::TYPE_ERROR], true)) {
                        $raw = '=';
                    } elseif ($cell?->getDataType() === DataType::TYPE_NUMERIC && is_numeric($raw)) {
                        if (Date::isDateTime($cell)) {
                            $raw = Date::excelToDateTimeObject($raw)->format('Y-m-d');
                        } elseif (preg_match('/^[0-9]+$/', $cell->getFormattedValue())) {
                            $raw = $cell->getFormattedValue();
                        }
                    }
                    $values[] = $raw;
                }
                $rows[] = $values;
            }
            return $rows;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function readDelimited(string $path): array
    {
        $content = file_get_contents($path);
        if (str_starts_with($content, "\xFF\xFE") || str_starts_with($content, "\xFE\xFF")) {
            $encoding = $content[0] === "\xFF" ? 'UTF-16LE' : 'UTF-16BE';
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', $encoding);
        } elseif (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        if (! mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0")) {
            $this->fail('Save the delimited file as UTF-8 CSV or Unicode Text and retry.');
        }

        $stream = fopen('php://temp', 'w+b');
        try {
            if (fwrite($stream, $content) !== strlen($content)) {
                $this->fail('The server could not read the delimited file.');
            }
            $delimiter = ',';
            $mostColumns = 0;
            foreach ([',', ';', "\t"] as $candidate) {
                rewind($stream);
                $columns = count(fgetcsv($stream, 0, $candidate, '"', '') ?: []);
                if ($columns > $mostColumns) {
                    $delimiter = $candidate;
                    $mostColumns = $columns;
                }
            }
            rewind($stream);
            $rows = [];
            while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                if (count($rows) >= self::MAX_DATA_ROWS + 1) {
                    $this->fail('The file may contain at most 2,000 data rows.');
                }
                if (count($row) > self::MAX_COLUMNS) {
                    $this->fail('Use at most 64 columns.');
                }
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }

    public static function splitAuthor(string $name): array
    {
        $position = strrpos($name, ' ');

        return $position === false ? [null, $name] : [substr($name, 0, $position), substr($name, $position + 1)];
    }

    private function validIsbn(string $isbn): bool
    {
        if (! preg_match('/\A(?:[0-9]{9}[0-9X]|[0-9]{13})\z/', $isbn)) {
            return false;
        }

        $length = strlen($isbn);
        $sum = 0;
        foreach (str_split($isbn) as $index => $digit) {
            $weight = $length === 10 ? 10 - $index : ($index % 2 === 0 ? 1 : 3);
            $sum += $weight * ($digit === 'X' ? 10 : (int) $digit);
        }

        return $sum % ($length === 10 ? 11 : 10) === 0;
    }
}
