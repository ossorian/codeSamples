<?php

namespace Ac\Pm\SpreadSheet;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once($_SERVER['DOCUMENT_ROOT'] . '/local/composer/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/composer/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php');

class MainExporter
{
    public Spreadsheet $xls;
    public $tableClass;
    private string $title = 'Файл без названия';
    
    public function __construct($tableClass, $createParams)
    {
        $this->tableClass = $tableClass;
        $this->xls = new Spreadsheet();
        $this->xls->setActiveSheetIndex(0);
        $sheet = $this->xls->getActiveSheet();
        
        if ($createParams['title']) {
            $this->xls->getProperties()->setTitle($createParams['title']);
            if (empty($createParams['subject'])) {
                $this->title = $createParams['title'];
            }
        }
        if ($createParams['subject']) {
            $this->xls->getProperties()->setSubject($createParams['subject']);
            $sheet->setTitle($createParams['subject']);
            $this->title = $createParams['title'];
        }
        if ($createParams['creator']) {
            $this->xls->getProperties()->setCreator($createParams['creator']);
        }
        //d.m.Y
        if ($createParams['date']) {
            $this->xls->getProperties()->setCreated($createParams['date']);
        }
    
        $sheet->getPageSetup()->SetPaperSize(9);
        $sheet->getPageSetup()->setOrientation('landscape');
    
        $sheet->getPageMargins()->setTop(1);
        $sheet->getPageMargins()->setRight(0.75);
        $sheet->getPageMargins()->setLeft(0.75);
        $sheet->getPageMargins()->setBottom(1);
    }
    
    
    public function outputData(): void
    {
        $GLOBALS['APPLICATION']->RestartBuffer();
        $writer = new Xlsx($this->xls);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $this->title . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        $writer->save("php://output");
    }
}