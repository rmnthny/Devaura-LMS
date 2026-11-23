<?php

namespace App\Services;

use App\Models\Department;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LibraryImportTemplate
{
    private const COLUMN_NOTES = [
        'member_role' => 'Required. Choose Student or Faculty.',
        'reference_number' => 'Required. Enter a 12-digit LRN for a student or a 7-digit DepEd employee number for faculty. Keep leading zeros.',
        'last_name' => 'Required. Use letters and spaces, for example Dela Cruz.',
        'first_name' => 'Required. Use letters and spaces, for example Juan.',
        'middle_initial' => 'Optional. Enter one initial, for example M.',
        'department' => 'Required for faculty. Enter the subject department as listed in the LMS. Leave blank for students.',
        'email' => 'Required. Enter the member’s email address for account setup.',
        'book_title' => 'Required. Enter the book title.',
        'isbn' => 'Optional. Enter a valid ISBN-10 or ISBN-13.',
        'genre' => 'Required. Enter the book genre.',
        'authors' => 'Optional. Separate multiple authors with semicolons.',
        'publishers' => 'Optional. Separate multiple publishers with semicolons.',
        'date_published' => 'Optional. Use YYYY-MM-DD.',
        'copies' => 'Optional. Enter a whole number from 1 to 50. Leave blank for one copy.',
    ];

    public function writeCsv(string $type): void
    {
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $this->labels($type), ',', '"', '', "\r\n");
        fclose($output);
    }

    public function writeExcel(string $type): void
    {
        $workbook = new Spreadsheet;
        try {
            $sheet = $workbook->getActiveSheet();
            $sheet->setTitle(ucfirst($type));
            $sheet->freezePane('A2');
            $headers = ImportRows::HEADERS[$type];
            $sheet->fromArray($this->labels($type), startCell: 'A1');
            $sheet->setSelectedCell('A2');

            foreach ($headers as $index => $header) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->getColumnDimension($column)->setWidth(match ($header) {
                    'book_title', 'email' => 34,
                    'authors', 'publishers', 'department' => 26,
                    'member_role', 'middle_initial', 'copies' => 17,
                    default => 21,
                });
                $note = $sheet->getComment($column.'1');
                $note->setWidth('230pt')->setHeight('65pt');
                $note->getText()->createTextRun(self::COLUMN_NOTES[$header]);

                if (in_array($header, ['reference_number', 'isbn'], true)) {
                    $sheet->getStyle("{$column}:{$column}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                }
            }

            $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17365D']],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(27);

            if ($type === 'members') {
                $this->memberValidation($sheet);
            } else {
                $this->validation($sheet, 'G', DataValidation::TYPE_WHOLE, 'Enter a whole number from 1 to 50.')
                    ->setOperator(DataValidation::OPERATOR_BETWEEN)->setFormula1('1')->setFormula2('50');
            }

            (new Xlsx($workbook))->save('php://output');
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    private function labels(string $type): array
    {
        return array_map(
            fn (string $header) => $header === 'isbn' ? 'ISBN' : ucwords(str_replace('_', ' ', $header)),
            ImportRows::HEADERS[$type],
        );
    }

    private function memberValidation(Worksheet $sheet): void
    {
        $this->validation($sheet, 'A', DataValidation::TYPE_LIST, 'Choose Student or Faculty.')
            ->setFormula1('"Student,Faculty"')->setShowDropDown(true);

        $this->validation($sheet, 'B', DataValidation::TYPE_CUSTOM, 'Enter a 12-digit LRN for Student or a 7-digit employee number for Faculty.')
            ->setFormula1('AND(LEN(B2)=IF($A2="Student",12,IF($A2="Faculty",7,0)),IFERROR(TEXT(VALUE(B2),REPT("0",LEN(B2)))=B2,FALSE))');

        $names = Department::orderBy('department_name')->pluck('department_name');
        $departments = $names->implode(',');
        // Excel limits inline lists to 255 characters.
        if ($departments !== '' && mb_strlen($departments) <= 253
            && ! $names->contains(fn (string $name) => strpbrk($name, ',"') !== false)) {
            $this->validation($sheet, 'F', DataValidation::TYPE_LIST, 'Choose a subject department for Faculty. Leave this blank for Student.')
                ->setFormula1('"'.$departments.'"')->setShowDropDown(true);
        }
    }

    private function validation(Worksheet $sheet, string $column, string $type, string $message): DataValidation
    {
        $lastRow = ImportRows::MAX_DATA_ROWS + 1;

        return $sheet->getDataValidation("{$column}2")
            ->setSqref("{$column}2:{$column}{$lastRow}")
            ->setType($type)
            ->setAllowBlank(true)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Check this entry')
            ->setError($message);
    }
}
