<?php

namespace engine;

use JetBrains\PhpStorm\ArrayShape;
use models\DirsHelper;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Class VitaeCollections
 * @package engine
 */
class VitaeCollections
{
    protected array $settings = [];
    protected array $nums = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5];

    /**
     * VitaeCollections constructor.
     * @param string $saveDir
     * @param string $scanDir
     * @param string $sysDir
     */
    function __construct(string $saveDir, string $scanDir, string $sysDir)
    {
        $this->settings['saveDir'] = $saveDir;
        $this->settings['scanDir'] = $scanDir;
        $this->settings['systemDir'] = $sysDir;
    }

    /**
     * @throws Exception
     */
    #[ArrayShape(['name' => "string", 'tag' => "string", 'courses' => "array", 'rows' => "array", 'dirRange' => "array", 'link' => "string"])]
    public function createGroupData(string $dir): array
    {
        $link = $this->getScanDir() . '/' . $dir;

        $xls = new Xlsx();
        $xls = $xls->load($link);
        $xls->setActiveSheetIndex(0);
        $sheetData = $xls->getActiveSheet()->toArray(null, false, false, true);

        $name = explode('.', $dir)[0];
        $num = explode('-', $name);

        $GROUP = [
            'name' => $name,
            'tag' => $num[0],
            'courses' => [],
            'rows' => [],
            'dirRange' => [],
            'link' => $link,
        ];

        $num = $num[1];
        $numGroup = $num[1] . $num[2];

        foreach ($sheetData as $rowID => $row) {
            foreach ($row as $columnID => $column) {
                if (
                    mb_stripos($column, 'курс') !== false
                    && (
                        mb_stripos($column, 'I') !== false
                        || mb_stripos($column, 'V') !== false
                    )
                ) {
                    $numeric = $this->getNumByRom(explode(' ', $column)[0]);
                    $c = ++$columnID;

                    $GROUP['courses'][$numeric] = [
                        'column_start' => $columnID,
                        'column_end' => $c,
                        'name' => $GROUP['tag'] . '-' . $numeric . $numGroup
                    ];
                } elseif (mb_strlen($column) && Lessons::existsPrefix($column) && !in_array($rowID, $GROUP['rows'])) {
                    $GROUP['rows'][] = $rowID;
                }
            }
        }
        $__keys = array_keys($GROUP['courses']);
        $GROUP['dir_range']['start'] = $__keys[0] . $numGroup;
        $GROUP['dir_range']['end'] = $__keys[count($__keys) - 1] . $numGroup;

        return $GROUP;
    }

    /**
     * @return mixed
     */
    public function getScanDir(): mixed
    {
        return $this->settings['scanDir'];
    }

    /**
     * @param $rom
     * @return mixed
     */
    public function getNumByRom($rom): mixed
    {
        return $this->nums[$rom];
    }

    /**
     * @return array
     */
    public function getCreated(): array
    {
        $res = [];

        foreach (DirsHelper::scan($this->getSaveDir()) as $saveDir) {
            foreach (DirsHelper::scan($this->getSaveDir() . '/' . $saveDir) as $group) {
                if (is_dir($this->getSaveDir() . '/' . $saveDir . '/' . $group)) {
                    $selfPrefix = explode('-', $group)[0];
                    $numCourse = explode('-', $group)[1];
                    $numCourse = mb_substr($numCourse, 0, 1);
                    $selfNumber = substr($group, -1);

                    foreach (DirsHelper::scan($this->getSaveDir() . '/' . $saveDir . '/' . $group) as $course) {
                        foreach (DirsHelper::scan($this->getSaveDir() . '/' . $saveDir . '/' . $group . '/' . $course) as $courseDir) {
                            $parent = false;

                            foreach ($this->getList() as $search) {
                                $prefix = explode('-', $search)[0];
                                $number = substr(explode('.', $search)[0], -1);

                                if ($prefix == $selfPrefix && $number == $selfNumber) {
                                    $parent = $search;
                                    break;
                                }
                            }

                            $res[] = [
                                'course' => $numCourse,
                                'parent' => $parent,
                                'link' => $this->getSaveDir() . '/' . $saveDir . '/' . $group . '/' . $course . '/' . $courseDir,
                                'name' => $courseDir,
                                'group' => $group,
                                'type' => self::getStringTypeByName($courseDir),
                                'past' => $course
                            ];
                        }
                    }
                } elseif (is_file($this->getSaveDir() . '/' . $saveDir . '/' . $group)) {
                    $res[] = [
                        'link' => $this->getSaveDir() . '/' . $saveDir . '/' . $group,
                        'name' => $group,
                        'group' => $saveDir,
                        'past' => '---'
                    ];
                }
            }
        }

        return $res;
    }

    /**
     * @return mixed
     */
    public function getSaveDir(): mixed
    {
        return $this->settings['saveDir'];
    }

    /**
     * @return array
     */
    public function getList(): array
    {
        return DirsHelper::scan($this->getScanDir(), additionalMasks: ['_i.txt']);
    }

    /**
     * @param string $name
     * @return int|string
     */
    static function getStringTypeByName(string $name): int|string
    {
        return ((((mb_stripos($name, 'зачётная') !== false ? 'FFFF00' : mb_stripos($name, 'экзаменационная') !== false) ? 'FF0000' : mb_stripos($name, 'семестровая') !== false) ? '00B050' : mb_stripos($name, 'курсовая') !== false) ? '7030A0' : 0);
    }

    /**
     * @param string $lesson
     * @param array $GROUP
     * @param int $c
     * @param string $path
     * @param string $color
     * @param string $profession
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function createVitae(string $lesson, array $GROUP, int $c, string $path, string $color, string $profession)
    {
        $statement = $this->getStatementByType($color);

        if ($statement['file']) {
            $startRow = $statement['start_row'];
            $description = $this->getAllDescription();
            $groupID = Groups::getIDByName($GROUP['name']);
            $file = str_ireplace("\n", "", $path . '/[' . $profession . '] ' . mb_substr($lesson, 0, 20) . ' - ' . $statement['name'] . '.xlsx');

            $xls = (new Xlsx())->load($statement['file']);
            $xls->setActiveSheetIndex(0);
            $sheet = $xls->getActiveSheet();

            $sheet
                ->setTitle('Ведомость')
                ->setCellValue($statement['cell_name'], $c)
                ->setCellValue($statement['cell_description'], $description[$GROUP['name']])
                ->setCellValue($statement['cell_lesson'], $profession . ' ' . $lesson);

            foreach (Students::getNames($groupID) as $student) {
                $sheet->setCellValue("B$startRow", $student);

                ++$startRow;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xls);

            $writer->save($file);
        }
    }

    /**
     * @param $color
     * @return array
     */
    public function getStatementByType($color): array
    {
        return match ($color) {
            'FFFF00' => ['file' => $this->getSystemDir() . '/yellow.xlsx', 'name' => 'зачётная', 'cell_name' => 'C7', 'cell_description' => 'C8', 'cell_lesson' => 'C9', 'start_row' => 14, 'cell_rate' => 'F'],
            'FF0000' => ['file' => $this->getSystemDir() . '/red.xlsx', 'name' => 'экзаменационная', 'cell_name' => 'C7', 'cell_description' => 'C8', 'cell_lesson' => 'C9', 'start_row' => 14, 'cell_rate' => 'F'],
            '00B050' => ['file' => $this->getSystemDir() . '/green.xlsx', 'name' => 'семестровая', 'cell_name' => 'C7', 'cell_description' => 'C8', 'cell_lesson' => 'C9', 'start_row' => 14, 'cell_rate' => 'E'],
            '7030A0' => ['file' => $this->getSystemDir() . '/pink.xlsx', 'name' => 'курсовая', 'cell_name' => 'C7', 'cell_description' => 'C8', 'cell_lesson' => 'C9', 'start_row' => 14, 'cell_rate' => 'E'],
            default => [],
        };
    }

    /**
     * @return mixed
     */
    public function getSystemDir(): mixed
    {
        return $this->settings['systemDir'];
    }

    /**
     * @return array
     */
    public function getAllDescription(): array
    {
        $groups = Groups::getNames();
        $return = [];

        foreach (Professions::getNamesWithGID() as $profession) {
            if (array_key_exists($profession['gid'], $groups)) {
                $return[$groups[$profession['gid']]] = $profession['name'];
            }
        }

        return $return;
    }

    /**
     * @param string $file
     * @param $type
     * @param $color
     */
    public function setTypeAndColorByFile(string $file, &$type, &$color) {
        if (mb_stripos($file, 'зачётная') !== false) {
            $type = 1;
            $color = 'FFFF00';
        } elseif (mb_stripos($file, 'экзаменационная') !== false) {
            $type = 1;
            $color = 'FF0000';
        } elseif (mb_stripos($file, 'семестровая') !== false) {
            $type = 2;
            $color = '00B050';
        } elseif (mb_stripos($file, 'курсовая') !== false) {
            $type = 2;
            $color = '7030A0';
        } elseif (mb_stripos($file, 'квалификационная') !== false) {
            $type = 1;
        } else {
            $type = false;
        }
    }

    /**
     * @param array $GROUP
     * @param array $lessons
     * @param string $courseFilter
     * @param int $past
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function createSummaryVitae(array $GROUP, array $lessons, string $courseFilter, int $past)
    {
        $dir = $this->getSaveDir() . '/' . $GROUP['tag'] . '-' . $GROUP['dir_range']['start'] . ' - ' . $GROUP['tag'] . '-' . $GROUP['dir_range']['end'];
        $header = ['Результаты промежуточной аттестации', 'Результаты семестрового контроля'];
        $groupID = Groups::getIDByName($GROUP['name']);

        foreach (DirsHelper::scan($dir) as $group) {
            if ($courseFilter !== "all") {
                $num = explode('-', $group);
                $___course = $num[1];
                $___course = $___course[0];
                $can = !($___course != $courseFilter);
            } else $can = true;

            if ($can) {
                $nextDir = $dir . '/' . $group;
                foreach (DirsHelper::scan($nextDir) as $_course => $course) {
                    if ($past != "all") {
                        $_can = !(mb_substr($course, 0, 1) != $past);
                    } else $_can = true;

                    if ($_can) {
                        $endDir = $nextDir . '/' . $course;
                        $response = [];
                        $first = $second = 0;

                        foreach (DirsHelper::scan($endDir) as $file) {
                            $this->setTypeAndColorByFile($file, $type, $color);

                            if ($type) {
                                $response[$type][] = $file;
                                if ($type == 1) ++$first;
                                elseif ($type == 2) ++$second;
                            }
                        }

                        $xls = (new Xlsx())->load($this->getSystemDir() . '/all.xlsx');
                        $xls->setActiveSheetIndex(0);
                        $description = $this->getAllDescription();
                        $activeSheet = $xls->getActiveSheet();

                        $activeSheet
                            ->setCellValue('C7', $group)
                            ->setCellValue('C8', $description[$GROUP['name']]);

                        $startRow = 12;

                        foreach (Students::getNames($groupID) as $student) {
                            $activeSheet->setCellValue("B$startRow", $student['name']);

                            ++$startRow;
                        }

                        $sheetMain = $xls->getActiveSheet()->toArray(null, true, true, true);
                        $char = 'C';

                        ksort($response);

                        foreach ($response as $array) {
                            $activeSheet->setCellValue('G5', ($_course - 1));

                            foreach ($array as $lesson) {
                                $b = pathinfo($lesson)['filename'];
                                list($_o, $o) = explode('] ', $b);
                                $comb = str_ireplace('[', '', $_o);
                                $needle = mb_substr($o, 0, mb_strrpos($o, '-'));
                                $desc = false;
                                foreach ($lessons as $l) {
                                    if (mb_stripos($l, trim($needle)) !== false) {
                                        $desc = $l;
                                        break;
                                    }
                                }

                                if ($desc) {
                                    $res = [];
                                    $cell = $char . "11";
                                    $activeSheet
                                        ->setCellValue($cell, '[' . $comb . '] ' . $desc)
                                        ->getColumnDimension($char)->setWidth(20);

                                    $activeSheet->getRowDimension('11')->setRowHeight(160);

                                    $activeSheet
                                        ->getStyle($cell)->getAlignment()->setTextRotation(90)
                                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                        ->setVertical(Alignment::VERTICAL_CENTER)
                                        ->setWrapText(true);

                                    $xlsObj = (new Xlsx())->load($endDir . '/' . $lesson);
                                    $xlsObj->setActiveSheetIndex(0);
                                    $sheetObj = $xlsObj->getActiveSheet();
                                    $sheetData = $sheetObj->toArray(null, false, false, true);

                                    $color = self::getStringTypeByName($lesson);

                                    $s = $this->getStatementByType($color);
                                    if (!count($s) && mb_stripos($lesson, 'квалификационная') !== false) {
                                        $s = [
                                            'file' => 'blue.xlsx',
                                            'name' => 'квалификационная',
                                            'cell_name' => 'C7',
                                            'cell_description' => 'C8',
                                            'cell_lesson' => 'C9',
                                            'start_row' => 16
                                        ];

                                        $column = false;
                                        foreach ($sheetData as $row) {
                                            foreach ($row as $x => $col) {
                                                if (mb_stripos($col, 'Оценка за ЭК') !== false) {
                                                    $column = $x;
                                                    break;
                                                }
                                            }
                                        }

                                        if ($column) {
                                            $s['cell_rate'] = $column;
                                        }
                                    }

                                    for ($i = $startRow = $s['start_row'], $c = $startRow + 29; $i < $c; $i++) {
                                        $res[] = [
                                            'student' => $sheetData[$i]['B'],
                                            'rate' => $sheetData[$i][$s['cell_rate']]
                                        ];
                                    }

                                    $line = 12;

                                    foreach ($res as $student) {
                                        foreach ($sheetMain as $row) {
                                            $activeSheet
                                                ->getStyle($char . $line)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                ->setVertical(Alignment::VERTICAL_CENTER);

                                            if (strcasecmp(trim($row['B']), trim($student['student'])) == 0) {
                                                $activeSheet->setCellValue($char . $line, $student['rate']);
                                                break;
                                            } else {
                                                $activeSheet->setCellValue($char . $line, "");
                                            }
                                        }

                                        ++$line;
                                    }

                                    ++$char;
                                }
                            }
                        }

                        for ($char = $_merge = 'C', $t = 0; $t < $first; $t++, $char++) {
                            $_merge = $char;
                        }

                        if ($first > 0) {
                            if ($_merge != 'C') {
                                $activeSheet->mergeCells('C10:' . $_merge . '10');
                            }

                            $activeSheet
                                ->getStyle('C10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                ->setVertical(Alignment::VERTICAL_CENTER);

                            $activeSheet->setCellValue('C10', $header[0]);
                        }

                        for ($_char = $_merge = $char, $t = 0; $t < $second; $t++, $char++) {
                            $_merge = $char;
                        }

                        if ($second > 0) {
                            if ($_char != $_merge) {
                                $activeSheet->mergeCells($_char . '10:' . $_merge . '10');
                            }

                            $activeSheet
                                ->setCellValue($_char . '10', $header[1])
                                ->getStyle($_char . '10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                ->setVertical(Alignment::VERTICAL_CENTER);
                        }

                        $activeSheet->getStyle('A10:' . $_merge . '40')->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => [
                                        'argb' => '000000'
                                    ]
                                ]
                            ]
                        ]);

                        $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xls);
                        $objWriter->save($endDir . '/Сводная ведомость.xlsx');
                    }
                }
            }
        }
    }


    /**
     * @throws Exception
     */
    public function createQualifierStatement($GROUP, $_xls, $__course, $half)
    {
        $activeSheet = $_xls->getActiveSheet();
        $sheetData = $activeSheet->toArray(null, false, false, true);

        $header = [
            'Результаты теоретического обучения',
            'Результаты практического обучения',
            'Оценка за ЭК(прописью)',
            'Оценка освоения ВПД(освоен/не освоен)',
        ];

        $__range =
            $GROUP['tag'] . '-' . $GROUP['dir_range']['start'] . ' - ' .
            $GROUP['tag'] . '-' . $GROUP['dir_range']['end'];

        if (!is_dir($this->getSaveDir() . '/' . $__range)) mkdir($this->getSaveDir() . '/' . $__range);

        $posX = -1;
        $first = $second = 0;
        $res = [];

        foreach ($GROUP['courses'] as $course) {
            $mainPath = $this->getSaveDir() . '/' . $__range . '/' . $course['name'];
            $can = true;

            if ($__course != "all") {
                $num = explode('-', $course['name']);
                $___course = $num[1];
                $___course = $___course[0];

                if ($___course != $__course) $can = false;
            }

            if ($can) {
                if (!is_dir($mainPath)) mkdir($mainPath);

                $cells = [
                    1 => ['char' => $course['column_start']],
                    2 => ['char' => $course['column_end']],
                ];

                foreach ($cells as $_cell => $cell) {
                    $_can = true;

                    if ($half != "all") {
                        if ($_cell != $half) $_can = false;
                    }

                    if ($_can) {
                        foreach ($GROUP['rows'] as $line) {

                            $excelLine = $sheetData[$line];
                            $_dir = $mainPath . '/' . $_cell . ' семестр';

                            if (!is_dir($_dir)) mkdir($_dir);

                            $number = '';
                            if (mb_stripos($excelLine['A'], '.') !== false) {
                                list(, $number) = explode('.', $excelLine['A']);
                            }

                            $color = mb_substr(
                                $activeSheet
                                    ->getStyle($cell['char'] . $line)
                                    ->getFill()
                                    ->getStartColor()
                                    ->getARGB()
                                , 2);

                            if (mb_stripos($excelLine['A'], 'ПМ') !== false && $color == '0000FF') {
                                if ($number != '00') {
                                    $posX = $cell['char'];
                                    $res[$posX][intval($number)]['nameStatement'] = $excelLine['B'];
                                }
                            }

                            if (
                                $posX != -1
                                && mb_stripos($excelLine['A'], 'ПМ') === false
                                && $cell['char'] == $posX
                            ) {
                                if (mb_stripos($excelLine['A'], 'МДК') !== false) {
                                    $idX = 1;
                                    $first++;
                                } elseif (mb_stripos($excelLine['A'], 'УП') !== false) {
                                    $idX = 2;
                                    $second++;
                                } elseif (mb_stripos($excelLine['A'], 'ПП') !== false) {
                                    $idX = 2;
                                    $second++;
                                } else {
                                    $idX = -1;
                                    $posX = -1;
                                }

                                if (!is_array($res[$posX][intval($number)][$idX])) $res[$posX][intval($number)][$idX] = [];

                                if ($posX != -1 && $idX != -1 && is_array($res[$posX][intval($number)][$idX])) {
                                    $push = [
                                        'lesson' => $excelLine['B'],
                                        'path' => $_dir,
                                        'pref' => $excelLine['A']
                                    ];

                                    foreach ($GROUP['courses'] as $_c) {
                                        $_cells = [
                                            1 => ['char' => $_c['column_start']],
                                            2 => ['char' => $_c['column_end']],
                                        ];

                                        foreach ($_cells as $__cell => $___cell) {
                                            $value = $excelLine[$___cell['char']];
                                            $d =
                                                $this->getSaveDir() . '/' . $GROUP['tag'] . '-' . $GROUP['dir_range']['start']
                                                . ' - ' . $GROUP['tag'] . '-' . $GROUP['dir_range']['end'];

                                            if ($value > 0 && is_numeric($value)) {
                                                $_file_0 = false;

                                                foreach (scandir($d . '/' . $_c['name'] . '/' . $__cell . ' семестр/') as $temp) {
                                                    if (mb_stripos($temp, '[' . $excelLine['A'] . '] ' . mb_substr($excelLine['B'], 0, 20)) !== false) {
                                                        $_file_0 = $temp;
                                                        break;
                                                    }
                                                }

                                                if ($_file_0) {
                                                    $push['file'] = $_file_0;
                                                    $push['fileDir'] = $d . '/' . $_c['name'] . '/' . $__cell . ' семестр/';
                                                }
                                            }
                                        }
                                    }
                                    $res[$posX][intval($number)][$idX][] = $push;
                                }
                                $res[$posX][intval($number)]['right'] = $second;
                                $res[$posX][intval($number)]['left'] = $first;
                                $res[$posX][intval($number)]['name'] = $course['name'];
                            }
                        }
                    }
                }
            }
        }

        if (is_array($res)) {
            foreach ($res as $_y => $list) {
                if ($_y != '-1') {
                    foreach ($list as $_x => $check) {
                        if ($check['right'] != count($check[2]) || $check['left'] != count($check[1])) {
                            unset($res[$_y][$_x]);
                        }
                    }
                } else unset($res[$_y]);
            }
        }

        if (count($res)) {
            foreach ($res as $x => $cell) {
                if ($x != -1) {
                    foreach ($cell as $y => $qual) {
                        if (is_array($qual)) {
                            if (isset($qual['name']) && mb_strlen(trim($qual['name'])) > 0 && ($qual['left'] > 0 || $qual['right'] > 0)) {
                                $xls = (new Xlsx())->load($this->getSystemDir() . '/blue.xlsx');
                                $xls->setActiveSheetIndex(0);
                                $_activeSheet = $xls->getActiveSheet();
                                $description = $this->getAllDescription();
                                $xls->getActiveSheet()->setCellValue('C7', $qual['name']);
                                $xls->getActiveSheet()->setCellValue('C8', $description[$GROUP['name']]);

                                $char = 'C';
                                $start_row = 16;

                                if (file_exists($this->settings['studentsDir'] . '/' . $GROUP['name'] . '.txt')) {
                                    $students = file($this->settings['studentsDir'] . '/' . $GROUP['name'] . '.txt', 6);
                                    for ($i = 0, $c = count($students); $i < $c; $i++, $start_row++) {
                                        if (mb_stripos($students[$i], ':') !== false) {
                                            $__n = explode(':', $students[$i])[0];
                                        } else $__n = $students[$i];
                                        $xls->getActiveSheet()->setCellValue("B$start_row", $__n);
                                    }
                                }
                                $sheetMain = $xls->getActiveSheet()->toArray(null, false, false, true);
                                $sChar = false;
                                if (array_key_exists(1, $qual) && count($qual[1])) {
                                    foreach ($qual[1] as $_qual) {
                                        if (array_key_exists('file', $_qual) && $_qual['file']) {
                                            $_res = [];
                                            $cell = $char . "15";
                                            $_activeSheet
                                                ->setCellValue($cell, '[' . $_qual['pref'] . '] ' . $_qual['lesson'])
                                                ->getColumnDimension($char)->setWidth(20);
                                            $_activeSheet->getRowDimension('15')->setRowHeight(160);
                                            $_activeSheet
                                                ->getStyle($cell)->getAlignment()->setTextRotation(90)
                                                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                ->setVertical(Alignment::VERTICAL_CENTER)
                                                ->setWrapText(true);

                                            $_wrapper = (new Xlsx())->load($_qual['fileDir'] . '/' . $_qual['file']);
                                            $_wrapper->setActiveSheetIndex(0);
                                            $_sheet = $_wrapper->getActiveSheet();
                                            $sheetData = $_sheet->toArray(null, false, false, true);

                                            if (mb_stripos($_qual['file'], 'зачётная') !== false) {
                                                $color = 'FFFF00';
                                            } elseif (mb_stripos($_qual['file'], 'экзаменационная') !== false) {
                                                $color = 'FF0000';
                                            } elseif (mb_stripos($_qual['file'], 'семестровая') !== false) {
                                                $color = '00B050';
                                            } elseif (mb_stripos($_qual['file'], 'курсовая') !== false) {
                                                $color = '7030A0';
                                            } else {
                                                $color = false;
                                            }

                                            $s = $this->getStatementByType($color);
                                            $start_row = $s['start_row'];

                                            for ($i = $start_row, $c = $start_row + 29; $i < $c; $i++) {
                                                $_res[] = [
                                                    'student' => $sheetData[$i]['B'],
                                                    'rate' => $sheetData[$i][$s['cell_rate']]
                                                ];
                                            }

                                            $line = 16;

                                            foreach ($_res as $student) {
                                                foreach ($sheetMain as $row) {
                                                    if (strcasecmp(trim($row['B']), trim($student['student'])) == 0) {
                                                        $xls->getActiveSheet()->setCellValue($char . $line, $student['rate']);
                                                        break;
                                                    } else {
                                                        $xls->getActiveSheet()->setCellValue($char . $line, "");
                                                    }
                                                }

                                                $_activeSheet
                                                    ->getStyle($char . $line)->getAlignment()
                                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                    ->setVertical(Alignment::VERTICAL_CENTER);

                                                ++$line;
                                            }
                                            ++$char;
                                        }
                                        $sChar = $char;
                                    }
                                }

                                $_qual = false;

                                if (array_key_exists(2, $qual) && count($qual[2])) {
                                    foreach ($qual[2] as $_qual) {
                                        if (array_key_exists('file', $_qual) && $_qual['file']) {
                                            $_res = [];
                                            $cell = $char . "15";
                                            $_activeSheet
                                                ->setCellValue($cell, '[' . $_qual['pref'] . '] ' . $_qual['lesson'])
                                                ->getColumnDimension($char)->setWidth(20);
                                            $_activeSheet->getRowDimension('15')->setRowHeight(160);
                                            $_activeSheet
                                                ->getStyle($cell)->getAlignment()
                                                ->setTextRotation(90)
                                                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                ->setVertical(Alignment::VERTICAL_CENTER)
                                                ->setWrapText(true);

                                            $_wrapper = (new Xlsx())->load($_qual['fileDir'] . '/' . $_qual['file']);
                                            $_wrapper->setActiveSheetIndex(0);
                                            $_sheet = $_wrapper->getActiveSheet();

                                            $sheetData = $_sheet->toArray(null, false, false, true);

                                            if (mb_stripos($_qual['file'], 'зачётная') !== false) {
                                                $color = 'FFFF00';
                                            } elseif (mb_stripos($_qual['file'], 'экзаменационная') !== false) {
                                                $color = 'FF0000';
                                            } elseif (mb_stripos($_qual['file'], 'семестровая') !== false) {
                                                $color = '00B050';
                                            } elseif (mb_stripos($_qual['file'], 'курсовая') !== false) {
                                                $color = '7030A0';
                                            } else {
                                                $color = false;
                                            }

                                            $s = $this->getStatementByType($color);
                                            $start_row = $s['start_row'];
                                            for ($i = $start_row, $c = $start_row + 29; $i < $c; $i++) {
                                                $_res[] = [
                                                    'student' => $sheetData[$i]['B'],
                                                    'rate' => $sheetData[$i][$s['cell_rate']]
                                                ];
                                            }

                                            $line = 16;
                                            foreach ($_res as $student) {
                                                foreach ($sheetMain as $row) {
                                                    if (strcasecmp(trim($row['B']), trim($student['student'])) == 0) {
                                                        $xls->getActiveSheet()->setCellValue($char . $line, $student['rate']);
                                                        break;
                                                    } else {
                                                        $xls->getActiveSheet()->setCellValue($char . $line, "");
                                                    }
                                                }
                                                $_activeSheet
                                                    ->getStyle($char . $line)->getAlignment()
                                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                    ->setVertical(Alignment::VERTICAL_CENTER);

                                                ++$line;
                                            }
                                            ++$char;
                                        }
                                    }
                                }
                                $first = $qual['left'];
                                $second = $qual['right'];

                                for ($t = 0, $char = $_merge = 'C'; $t < $first; ++$t, ++$char) {
                                    $_merge = $char;
                                }

                                if ($first > 0) {
                                    $_activeSheet->mergeCells('C14:' . $_merge . '14')
                                        ->getStyle('C14')->getAlignment()
                                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                        ->setVertical(Alignment::VERTICAL_CENTER);

                                    $_activeSheet->setCellValue('C14', $header[0]);
                                }

                                $_char = $char;

                                for ($t = 0; $t < $second; ++$t, ++$char) {
                                    $_merge = $char;
                                }

                                if ($second > 0) {
                                    $_activeSheet
                                        ->mergeCells($_char . '14:' . $_merge . '14');
                                    $_activeSheet
                                        ->setCellValue($sChar . '14', $header[1])
                                        ->getStyle($_char . '14')->getAlignment()
                                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                        ->setVertical(Alignment::VERTICAL_CENTER);
                                }

                                ++$_merge;
                                $_activeSheet->getColumnDimension($_merge)->setWidth(25);
                                $_activeSheet
                                    ->getStyle($_merge . '14')->getAlignment()
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                    ->setVertical(Alignment::VERTICAL_CENTER)
                                    ->setWrapText(true);

                                $_activeSheet->setCellValue($_merge . '14', $header[2]);

                                $line = 16;
                                for ($i = 0, $c = 29; $i < $c; $i++) {
                                    $_activeSheet
                                        ->getStyle($_merge . $line)->getAlignment()
                                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                        ->setVertical(Alignment::VERTICAL_CENTER);
                                    ++$line;
                                }

                                $_activeSheet
                                    ->getStyle($_merge . '14')->getAlignment()
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                    ->setVertical(Alignment::VERTICAL_CENTER);
                                ++$_merge;

                                $_activeSheet->getColumnDimension($_merge)->setWidth(25);
                                $_activeSheet
                                    ->getStyle($_merge . '14')->getAlignment()
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                    ->setVertical(Alignment::VERTICAL_CENTER)
                                    ->setWrapText(true);

                                $_activeSheet
                                    ->setCellValue($_merge . '14', $header[3])
                                    ->getStyle($_merge . '14')->getAlignment()
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                    ->setVertical(Alignment::VERTICAL_CENTER);

                                $style = array(
                                    'borders' => array(
                                        'allBorders' => array(
                                            'style' => Border::BORDER_THIN,
                                            'color' => array(
                                                'argb' => '000000'
                                            )
                                        )
                                    )
                                );

                                $_activeSheet->getStyle('A14:' . $_merge . '44')->applyFromArray($style);
                                $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xls);
                                $objWriter->save($_qual['path'] . '/[ПМ.0' . $y . '] ' . mb_substr($qual['nameStatement'], 0, 10) . ' - квалификационная ведомость.xlsx');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function createCuratorStatement(array $GROUP, $lessons, $__course, $past)
    {
        $dir =
            $this->getSaveDir() . '/' . $GROUP['tag'] . '-' . $GROUP['dir_range']['start'] . ' - '
            . $GROUP['tag'] . '-' . $GROUP['dir_range']['end'];

        $description = $this->getAllDescription();
        $groupID = Groups::getIDByName($GROUP['name']);
        $students = Students::getNames($groupID);

        foreach (scandir($dir) as $group) {
            if ($group != '.' && $group != '..') {
                $can = true;

                if ($__course != "all") {
                    $num = explode('-', $group);
                    $___course = $num[1];
                    $___course = $___course[0];
                    if ($___course != $__course) $can = false;
                }

                if ($can) {
                    $nextDir = $dir . '/' . $group;

                    foreach (scandir($nextDir) as $_course => $course) {
                        if ($course != '.' && $course != '..') {
                            $_can = true;
                            if ($past != "all") {
                                if (mb_substr($course, 0, 1) != $past) $_can = false;
                            }

                            if ($_can) {
                                $endDir = $nextDir . '/' . $course;
                                $response = array();
                                foreach (scandir($endDir) as $file) {
                                    if ($file != '.' && $file != '..') {
                                        $this->setTypeAndColorByFile($file, $type, $color);

                                        if ($type) {
                                            $response[$type][] = ['dir' => $endDir, 'file' => $file];
                                        }
                                    }
                                }

                                $xls = (new Xlsx())->load($this->getSystemDir() . '/curator.xls');
                                $xls->setActiveSheetIndex(0);

                                $activeSheet = $xls->getActiveSheet();
                                $activeSheet
                                    ->setCellValue('F7', $group)
                                    ->setCellValue('F9', $description[$GROUP['name']])
                                    ->setCellValue('O5', ($_course - 1));

                                $start_row = 14;
                                if (file_exists($this->settings['studentsDir'] . '/' . $GROUP['name'] . '.txt')) {
                                    for ($i = 0, $c = count($students); $i < $c; $i++, $start_row++) {
                                        if (mb_stripos($students[$i], ':') !== false) {
                                            $__n = explode(':', $students[$i])[0];
                                        } else $__n = $students[$i];
                                        $xls->getActiveSheet()->setCellValue("F$start_row", $__n);
                                    }
                                }

                                $chars = array(
                                    1 => 'G',
                                    2 => 'U'
                                );

                                foreach ($response as $idx => $array) {
                                    $_char = $chars[$idx];
                                    foreach ($array as $lesson) {
                                        $cell = $_char . "12";
                                        $b = pathinfo($lesson['file'])['filename'];
                                        list($_o, $o) = explode('] ', $b);
                                        $comb = str_replace('[', '', $_o);
                                        $needle = mb_substr($o, 0, mb_strrpos($o, '-'));
                                        $desc = false;
                                        foreach ($lessons as $l) {
                                            if (mb_stripos($l, trim($needle)) !== false) {
                                                $desc = $l;
                                                break;
                                            }
                                        }
                                        if ($desc) {
                                            $_wrapper = (new Xlsx())->load($lesson['dir'] . '/' . $lesson['file']);
                                            $_wrapper->setActiveSheetIndex(0);
                                            $_sheet = $_wrapper->getActiveSheet();
                                            $cell_rate = false;
                                            $sheetData = $_sheet->toArray(null, false, false, true);
                                            if (mb_stripos($lesson['file'], 'зачётная') !== false) {
                                                $cell_rate = 'F';
                                                $start_row = 14;
                                            } elseif (mb_stripos($lesson['file'], 'экзаменационная') !== false) {
                                                $cell_rate = 'F';
                                                $start_row = 14;
                                            } elseif (mb_stripos($lesson['file'], 'семестровая') !== false) {
                                                $cell_rate = 'E';
                                                $start_row = 14;
                                            } elseif (mb_stripos($lesson['file'], 'курсовая') !== false) {
                                                $cell_rate = 'E';
                                                $start_row = 14;
                                            } elseif (mb_stripos($lesson['file'], 'квалификационная') !== false) {
                                                $_x_ = 'A';
                                                $start_row = 16;
                                                while (++$_x_ < 'Z') {
                                                    if ($sheetData[15][$_x_] == 'Оценка за ЭК(прописью)') {
                                                        $cell_rate = $_x_;
                                                        break;
                                                    }
                                                }
                                            }
                                            $_res = [];
                                            for ($i = $start_row, $c = $start_row + 26; $i < $c; $i++) {
                                                $_res[] = [
                                                    'student' => $sheetData[$i]['B'],
                                                    'rate' => $sheetData[$i][$cell_rate]
                                                ];
                                            }
                                            $line = 14;
                                            $sheetMain = $xls->getActiveSheet()->toArray(null, true, true, true);
                                            foreach ($_res as $student) {
                                                foreach ($sheetMain as $row) {
                                                    if (strcasecmp(trim($row['F']), trim($student['student'])) == 0) {
                                                        $activeSheet->setCellValue($_char . $line, $student['rate']);
                                                        break;
                                                    }
                                                }
                                                $activeSheet
                                                    ->getStyle($_char . $line)->getAlignment()
                                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                    ->setVertical(Alignment::VERTICAL_CENTER);

                                                ++$line;
                                            }

                                            $activeSheet->setCellValue($cell, '[' . $comb . '] ' . $desc);
                                            $activeSheet->getColumnDimension($_char)->setWidth(20);
                                            $activeSheet->getRowDimension('12')->setRowHeight(160);
                                            $activeSheet
                                                ->getStyle($cell)->getAlignment()->setTextRotation(90)
                                                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                ->setVertical(Alignment::VERTICAL_CENTER)
                                                ->setWrapText(true);
                                            ++$_char;
                                        }
                                    }
                                }
                                $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xls);
                                $objWriter->save($endDir . '/Ведомость куратора.xlsx');
                            }
                        }
                    }
                }
            }
        }
    }
}