<?php

namespace App\Services;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LibraryReportWorkbook extends StringValueBinder implements FromArray, WithCustomValueBinder, WithStrictNullComparison, WithStyles, WithTitle
{
    public function __construct(
        private string $title,
        private array $summary,
        private array $headings,
        private array $rows,
    ) {
        $this->setNumericConversion(false);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        $reportTitle = $this->title === LibraryReports::TYPES['activity'] ? $this->title : $this->title.' Report';
        $values = [["Upper Bicutan National High School Library\n{$reportTitle}"]];
        foreach ($this->summary as $label => $value) {
            $values[] = [$label, $value];
        }
        $values[] = [''];
        $values[] = $this->headings;

        return [...$values, ...($this->rows ?: [[LibraryReports::EMPTY_MESSAGE]])];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $headerRow = count($this->summary) + 3;
        $firstRecordRow = $headerRow + 1;
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings));
        $lastRow = $sheet->getHighestDataRow();
        $hasRecords = $this->rows !== [];

        $sheet->setShowGridlines(false);
        $sheet->freezePane('A'.$firstRecordRow);
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 11, 'color' => ['rgb' => '252C34']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '26384A']],
            'alignment' => ['wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF1F4']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(48);

        foreach (array_values($this->summary) as $index => $value) {
            $row = $index + 2;
            $sheet->mergeCells("B{$row}:{$lastColumn}{$row}");
            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(mb_strlen((string) $value) > 85 ? 36 : 22);
        }

        foreach ($this->headings as $index => $heading) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $width = match ($heading) {
                'Book', 'Action' => 46,
                'Actor' => 32,
                'Event', 'Transaction', 'Reference', 'Time in', 'Time out', 'Date and time', 'Checked at' => 25,
                'Copy #' => 11,
                default => 22,
            };
            $sheet->getColumnDimension($column)->setWidth($index === 0 ? max($width, 25) : $width);
        }

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '34475A']],
            'alignment' => ['wrapText' => true],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);
        $sheet->getStyle("A{$firstRecordRow}:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true);
        if ($hasRecords) {
            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
            for ($row = $firstRecordRow + 1; $row <= $lastRow; $row += 2) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F3F5');
            }
        } else {
            $sheet->mergeCells("A{$lastRow}:{$lastColumn}{$lastRow}");
            $sheet->getStyle("A{$lastRow}")->getFont()->setItalic(true);
        }
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(count($this->headings) > 4 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow)
            ->setPrintArea("A1:{$lastColumn}{$lastRow}");
        $sheet->getHeaderFooter()->setOddFooter('&LUBNHS Library Services&RPage &P of &N');

        return null;
    }
}
