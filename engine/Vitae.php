<?php


namespace engine;


use models\DirsHelper;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Vitae
{
    public ?Xlsx $excelObj = null;
    public array $sheetData = [];
    public ?Worksheet $activeSheet = null;
    public string $tempDirectory = 'temp';
    public string $reportsDirectory = 'Отчёты';
    public string $statementsDirectory = 'Ведомости';
    public string $sourceFile = 'report.xlsx';

    /**
     * @throws Exception
     */
    public function load(?string $file = null): static
    {
        if ($file === null) {
            $file = $this->sourceFile;
        }

        $object = $this->getExcelObj()->load($this->getTemp() . '/system/' . $file);
        $object->setActiveSheetIndex(0);

        $this->activeSheet = $object->getActiveSheet();

        $this->sheetData = $this->activeSheet->toArray(
            null,
            false,
            false,
            true
        );

        return $this;
    }

    /**
     * @return Xlsx
     */
    public function getExcelObj(): Xlsx
    {
        if ($this->excelObj === null) {
            $this->excelObj = new Xlsx();
        }

        return $this->excelObj;
    }

    /**
     * @return string
     */
    public function getTemp(): string
    {
        if (!is_dir($this->tempDirectory)) {
            mkdir($this->tempDirectory);
        }

        return $this->tempDirectory;
    }

    /**
     * @return array
     */
    public function parse(): array
    {
        for (
            $startRowGroups = 9, $columnGroups = 'A', $parsedData = [],
            $x = $startRowGroups, $row = $this->sheetData[$startRowGroups], $cell = $row[$columnGroups];
            mb_stripos($cell, 'итого') === false;
            ++$x, $row = $this->sheetData[$x], $cell = $row[$columnGroups]
        ) {
            list($tag, $num) = explode('-', $cell);

            $parsedData[] = [
                'tag' => $tag,
                'course' => $num[0],
                'expr' => '/' . $tag . '-([1-5])' . $num[1] . $num[2] . '/',
                'row' => $x,
                'cell' => $cell
            ];
        }

        return $parsedData;
    }

    /**
     * @param $parsedData
     * @return bool
     * @throws Exception
     */
    public function createVitae($parsedData): bool
    {
        if (empty($parsedData)) {
            return false;
        }

        $columns = ['range' => [
            'best' => 'E',
            'good' => 'G',
            'bad' => 'I'
        ], 'sem' => [
            'best' => 'K',
            'good' => 'M',
            'bad' => 'O'
        ]];
        $allCell = 'C';

        $defaultNameStatement = 'Ведомость куратора.xlsx';

        foreach ($parsedData as $parsed) {
            $file = $parsed['dir'] . '/' . $defaultNameStatement;

            if (is_file($file) && file_exists($file)) {
                $rates = $this->getCuratorRates($file);
                $row = $parsed['row'];

                foreach ($columns as $col => $column) {
                    $this->activeSheet
                        ->setCellValue($column['best'] . $row, ($rates[$col]['best'] > 0 ? $rates['range']['best'] : ''))
                        ->setCellValue($column['good'] . $row, $rates[$col]['good'])
                        ->setCellValue($column['bad'] . $row, $rates[$col]['bad'] + $rates['range']['not']);
                }

                $this->activeSheet->setCellValue($allCell . $row, $rates['all']);
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function getCuratorRates($file): array
    {
        $columns = ['range' => [
            'best' => 'B',
            'good' => 'C',
            'normal' => 'D',
            'bad' => 'E',
            'not' => 'F'
        ], 'sem' => [
            'best' => 'B',
            'good' => 'C',
            'normal' => 'D',
            'bad' => 'E',
            'not' => 'F'
        ],];
        $rows = ['range' => 7, 'sem' => 12];
        $responseData = [];

        $excelObj = new Xlsx();
        $excelObj = $excelObj->load($file);
        $sheetData = $excelObj->setActiveSheetIndex(1)->toArray(null, false, false, true);

        foreach ($columns as $col => $column) {
            foreach ($column as $type => $cell) {
                $responseData[$col][$type] = $sheetData[$rows[$col]][$cell];
            }
        }

        $sheetData = $excelObj->setActiveSheetIndex(0)->toArray(null, false, false, true);
        $responseData['all'] = $sheetData[48]['E'];

        return $responseData;
    }

    /**
     * @param $parsedData
     * @param $sem
     * @return array
     */
    public function parseAllDirs($parsedData, $sem): array
    {
        $list = [];

        foreach (DirsHelper::scan($_dir = $this->getStatementsDirectory()) as $dir) {
            foreach ($parsedData as $parsed) {
                if (preg_match($parsed['compare'], $dir, $m, 2)) {
                    $list[] = [
                        'dir' => $_dir . '/' . $dir . '/' . $parsed['cell'] . '/' . $sem . ' семестр',
                        'row' => $parsed['row'],
                    ];

                    break;
                }
            }
        }

        return $list;
    }

    /**
     * @return string
     */
    public function getStatementsDirectory(): string
    {
        if (!is_dir($this->statementsDirectory)) {
            mkdir($this->statementsDirectory);
        }

        return $this->statementsDirectory;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    public function saveReport($s): bool
    {
        $spreadSheet = new Spreadsheet();
        $spreadSheet->addSheet($this->getActiveSheet(), 0);
        $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadSheet);
        $objWriter->save($this->getReportsDirectory() . '/Отчет по всем группам ' . $s . ' семестр.xlsx');

        return false;
    }

    /**
     * @return Worksheet|array|null
     */
    public function getActiveSheet(): Worksheet|array|null
    {
        return $this->activeSheet;
    }

    /**
     * @return string
     */
    public function getReportsDirectory(): string
    {
        if (!is_dir($this->reportsDirectory)) {
            mkdir($this->reportsDirectory);
        }

        return $this->reportsDirectory;
    }
}