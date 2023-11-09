<?php

namespace Ac\Pm\SpreadSheet\Traits;

use Ac\Pm\SpreadSheet\MainExporter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait MainSpreadsheetExportTrait
{
    
    private static string $currentLetter;
    private static int $dataStartingRow = 1;
    
    public static function export(array $data)
    {
        $exporter = new MainExporter(static::class, [
            'title' => static::TITLE . date(" d.m.Y H:i:s"),
            'subject' => static::TITLE,
            'creator' => $GLOBALS['USER']->GetFullName(),
            'date' => date("Y-m-d")
        ]);
        $activeSheet = $exporter->xls->getActiveSheet();
        self::makeExportTitle($activeSheet, $data[0] ?: []);
        self::setData($activeSheet, $data);
        $exporter->outputData();
    }
    
    public static function setData(Worksheet $sheet, array $data): void
    {
        $arFieldNameKeys = self::getFieldNameKeys();
        foreach ($data as $rowIndex => $dataRow) {
            $digit = $rowIndex + self::$dataStartingRow;
            $columnIndex = 0;
            foreach ($dataRow as $fieldName => $value) {
                if (!isset($arFieldNameKeys[$fieldName])) {
                    continue;
                }
                $cellName = self::getNextLetter(!$columnIndex++) . $digit;
                $sheet->setCellValueExplicit($cellName, $value, 's');
                $sheet->getStyle($cellName)->getAlignment()->setWrapText(true);
            }
        }
    }
    
    private static function makeExportTitle(Worksheet $sheet, array $firstRow): void
    {
        static::makeExportNestedTitle($sheet);
        if (empty($firstRow)) {
            return;
        }
        $map = static::getMap();
        foreach ($map as $field) {
            $fieldName = $field->getName();
            if (isset($firstRow[$fieldName])) {
                $letter = self::getNextLetter();
                $cellName = $letter . self::$dataStartingRow;
                $sheet->setCellValueExplicit($cellName, $field->getTitle(), 's');
                
                /* Styles */
                $width = ceil((\Ac\Pm\SpreadSheetForms\Hotmeals\HotMealsStarter::FIELD_WIDTH[$fieldName] ?: 200) / 5);
                $sheet->getColumnDimension($letter)->setWidth($width);
                $sheet->getStyle($cellName)->getAlignment()->setWrapText(true);
                $sheet->getStyle($cellName)->getFont()->getColor()->setRGB('000000');
                $sheet->getColumnDimension($cellName)->setAutoSize(true);
                $sheet->getStyle($cellName)->getAlignment()->setVertical('center');
                $sheet->getStyle($cellName)->getAlignment()->setHorizontal('center');
                $sheet->getStyle($cellName)
                    ->getFill()
                    ->setFillType('solid')
                    ->getStartColor()
                    ->setARGB('FFffdebd');
            }
        }
        
        self::$dataStartingRow++;
        
        $sheet->getStyle('A1:' . $cellName)->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'bold' => true,
                'italic' => false,
                'size' => 12,
                'strikethrough' => false,
                'color' => [
                    'rgb' => '000000'
                ]
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => [
                        'rgb' => '000000'
                    ]
                ],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
                'wrapText' => true,
            ],
        ]);
    }
    
    private static function makeExportNestedTitle(Worksheet $sheet): void
    {
        if (!method_exists(new static, 'getNestedHeaders')) {
            return;
        }
        $nestedHeaders = static::getNestedHeaders();
        // Не рассматривал вариант с несколькими надстройками, как в документации https://jspreadsheet.com/docs/nested-headers
        $counter = 1;
        foreach ($nestedHeaders as $header) {
            if (($counter + $header['colspan'] - 1) == 1) {
                continue;
            }
            $regionLeft = self::getLetterFromNumber($counter) . '1';
            $regionRight = self::getLetterFromNumber($counter + $header['colspan'] - 1) . '1';
            echo "$regionLeft:$regionRight";
            $sheet->mergeCells("$regionLeft:$regionRight");
            $sheet->setCellValueExplicit($regionLeft, $header['title'], 's');
            $sheet->getStyle($regionLeft)
                ->getFill()
                ->setFillType('solid')
                ->getStartColor()
                ->setARGB('FFffdebd');
            $counter += $header['colspan'];
        }
        self::$dataStartingRow++;
    }
    
    private static function getFieldNameKeys(): array
    {
        $map = static::getMap();
        $arFieldNames = [];
        foreach ($map as $field) {
            $arFieldNames[] = $field->getName();
        }
        return array_flip($arFieldNames);
    }
    
    private static function getLetterFromNumber(int $columnNumber): string
    {
        $first = floor($columnNumber / 32);
        $last = $columnNumber % 32;
        $result = '';
        if ($first) {
            $result .= chr(ord('A') + $first - 1);
        }
        $result .= chr(ord('A') + $last - 1);
        return $result;
    }
    
    private static function getNextLetter(bool $reset = false): string
    {
        if (!$reset && isset(self::$currentLetter)) {
            $lastChar = self::$currentLetter[strlen(self::$currentLetter) - 1];
            if ($lastChar === 'Z') {
                self::$currentLetter = 'AA';
            } else {
                self::$currentLetter = substr(self::$currentLetter, 0, -1)
                    . chr(ord($lastChar) + 1);
            }
        } else {
            self::$currentLetter = 'A';
        }
        return self::$currentLetter;
    }
    
}