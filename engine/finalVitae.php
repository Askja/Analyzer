<?php

namespace engine;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class finalVitae
{

    public $template, $finalTemplate, $headersEnd = [
        ['name' => 'Защита выпускной квалификационной работы', 'width' => 30],
        ['name' => 'Подпись студента', 'width' => 40],
        ['name' => 'Номер диплома', 'width' => 15]
    ];
    private Spreadsheet $excelObj;
    private Spreadsheet $finalObj;
    private array $sheetData = [];
    private array $prefixes = [];
    private array $nums = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5];

    public function setFinalTemplate($template)
    {
        $this->finalTemplate = $template;
    }

    /**
     * @throws Exception
     */
    public function init()
    {
        $this->excelObj = (new Xlsx())->load('templates/' . $this->getTemplate());
        $this->excelObj->setActiveSheetIndex(0);
        $this->sheetData = $this->excelObj->getActiveSheet()->toArray(null, false, false, true);
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function parsePrefixes()
    {
        if (count($this->sheetData)) {
            foreach ($this->sheetData as $line) {
                foreach ($line as $key => $column) {
                    if ($key == 'A' && $this->isLesson($column)) {
                        $this->prefixes[] = $column;
                    }
                }
            }
        }
    }

    public function isLesson($str): bool
    {
        return Lessons::existsPrefix($str);
    }

    public function sortingPrefixes()
    {
        if (count($this->prefixes)) {
            $prefixes = [];
            foreach ($this->prefixes as $x => $prefix) {
                if (mb_stripos($prefix, 'МДК') !== false) break;
                $prefixes[] = $prefix;
                unset($this->prefixes[$x]);
            }
            $testArray = [];
            $position = 0;
            foreach ($this->prefixes as $prefix) {
                if (str_contains($prefix, '.')) {
                    foreach (explode('.', $prefix) as $parsed) {
                        $testArray[$position][] = $parsed;
                    }
                    ++$position;
                } else {
                    $testArray['undefined'][] = $prefix;
                }
            }
            $sortedArray = [];
            foreach ($testArray as $arg) {
                if ($arg[0] == 'МДК' || $arg[0] == 'ПМ') $type = 1;
                elseif ($arg[0] == 'ПП' || $arg[0] == 'УП') $type = 2;
                else $type = 'undefined';
                $sortedArray[$type][] = $arg;
            }
            $resortedArray = [];
            foreach ($sortedArray as $type => $array) {
                if (is_numeric($type)) {
                    foreach ($array as $parsed_prefix) {
                        $resortedArray[$type][] = implode('.', $parsed_prefix);
                    }
                }
            }
            foreach ($resortedArray as $key => &$arg) {
                if (is_numeric($key)) {
                    usort($arg, function ($a) {
                        preg_match_all('/\d/', $a, $numbers_a);
                        preg_match_all('/\d/', $a, $numbers_b);
                        $num_1 = implode("", $numbers_a[0]);
                        $num_2 = implode("", $numbers_b[0]);
                        return $num_1 <=> $num_2;
                    });
                }
            }
            $this->prefixes = [];
            foreach ($prefixes as $prefix) {
                $this->prefixes[] = $prefix;
            }
            ksort($resortedArray);
            foreach ($resortedArray as $array) {
                foreach ($array as $prefix) {
                    $this->prefixes[] = $prefix;
                }
            }
            foreach ($sortedArray['undefined'] as $parsed_prefix) {
                $this->prefixes[] = implode('.', $parsed_prefix);
            }
            $finalSortedPrefixes = [];
            foreach ($this->prefixes as $prefix) {
                foreach ($this->sheetData as $row => $line) {
                    if ($line['A'] == $prefix) {
                        $finalSortedPrefixes[] = ['prefix' => $prefix, 'row' => $row, 'lesson' => $line['B']];
                        break;
                    }
                }
            }
            $this->prefixes = $finalSortedPrefixes;
        }
    }

    /**
     * @throws Exception
     */
    public function prepareFinalTemplate()
    {
        $this->finalObj = (new Xlsx())->load('system/' . $this->getFinalTemplate());
        $this->finalObj->setActiveSheetIndex(2);
    }

    public function getFinalTemplate()
    {
        return $this->finalTemplate;
    }

    /**
     * @throws Exception
     */
    public function createFinalStatement()
    {

        $start_header_char = $start_combine_column = $formular_column_start = 'E';
        $students_column_char = 'B';
        $formular_column = 'D';
        $bdates_column = 'C';
        $header_row = 10;
        $hours_all_row = 11;
        $hours_first_row = 12;
        $students_row = $_students_row = 13;
        $number_of_diploma = 1;
        $columns_for_checking = [];
        $end_line_combine = 38;
        $column_first_hours = $column_all_hours = null;
        $group_name = explode('.', $this->getTemplate())[0];
        $num = explode('-', $group_name);
        $tag = $num[0];
        $num = $num[1];
        $num_group = $num[1] . $num[2];
        $courses = $technical_array_chars = [];
        $sheetMain = null;

        if (is_array($this->sheetData)) {
            foreach ($this->sheetData as $row) {
                foreach ($row as $_column => $column) {
                    if (mb_stripos($column, 'курс') !== false && (mb_stripos($column, 'I') !== false || mb_stripos($column, 'V') !== false)) {
                        if (!in_array($_column, $columns_for_checking)) {
                            $first_char = $_column;
                            $second_char = ++$_column;
                            $columns_for_checking[] = $first_char;
                            $columns_for_checking[] = $second_char;
                            $numeric = $this->getNumByRom(explode(' ', $column)[0]);
                            $_name = $tag . '-' . $numeric . $num_group;
                            $courses[$numeric] = $_name;
                            $technical_array_chars[$first_char] = ['name' => $_name, 'course' => 1];
                            $technical_array_chars[$second_char] = ['name' => $_name, 'course' => 2];
                        }
                    } elseif ($column == 'максимальная') {
                        if ($column_all_hours === null) {
                            $column_all_hours = $_column;
                        }
                    } elseif ($column == 'всего занятий') {
                        if ($column_first_hours === null) {
                            $column_first_hours = $_column;
                        }
                    } elseif ($column == 'Всего') {
                        if ($column_all_hours === null) {
                            $column_all_hours = $_column;
                        }
                    } elseif ($column == 'Всего по дисциплинам и МДК') {
                        if ($column_first_hours === null) {
                            $column_first_hours = $_column;
                        }
                    }
                }
            }

            $__keys = array_keys($courses);

            $start = $__keys[0] . $num_group;
            $end = $__keys[count($__keys) - 1] . $num_group;
            $range_group = $tag . '-' . $start . ' - ' . $tag . '-' . $end;
            $start_group = $tag . '-' . $start;
            $dir_for_scan = 'Ведомости/' . $tag . '-' . $start . ' - ' . $tag . '-' . $end;
            $dir_semestr = $dir_for_scan . '/' . $courses[$__keys[0]] . '/1 семестр';

            foreach (scandir($dir_semestr) as $dir) {
                if (mb_stripos($dir, 'курсовая') !== false) {
                    $prefix = str_replace('[', '', explode(']', $dir)[0]);
                    $course_sheets[] = ['file' => $dir, 'prefix' => $prefix];
                }
            }

            if ($column_first_hours <> null && $column_all_hours <> null) {
                $column_with_last_hours = [];
                foreach ($this->prefixes as $prefix) {
                    $last_column = false;
                    $line = $this->sheetData[$prefix['row']];
                    foreach ($columns_for_checking as $column) {
                        if (is_numeric($line[$column]) && (int)$line[$column] > 0) {
                            $last_column = $column;
                        }
                    }
                    if ($last_column) {
                        $column_with_last_hours[$prefix['row']] = $last_column;
                        $ratesArray[$prefix['row']] = ['name' => $technical_array_chars[$last_column]['name'], 'course' => $technical_array_chars[$last_column]['course']];
                    }
                }
                $description = $this->getAllDescription();
                $this->finalObj->getActiveSheet()->setCellValue('Z3', 'группы ' . $group_name);
                $this->finalObj->getActiveSheet()->setCellValue('Z5', $description[$group_name]);
                $studentsMain = $this->getStudents($group_name);
                foreach ($studentsMain['students'] as $student) {
                    if ($student['name']) {
                        $cell = $students_column_char . $students_row;
                        $this->finalObj->getActiveSheet()->setCellValue($cell, $student);
                        $students_row++;
                    }
                }
                if (!is_array($sheetMain)) $sheetMain = $this->finalObj->getActiveSheet()->toArray(null, true, true, true);
                foreach ($this->prefixes as $prefix) {
                    $cell = $start_header_char . $header_row;
                    $cell_all_hours = $start_header_char . $hours_all_row;
                    $cell_first_hours = $start_header_char . $hours_first_row;
                    $this->finalObj->getActiveSheet()->setCellValue($cell, '[' . $prefix['prefix'] . '] ' . $prefix['lesson']);
                    $this->finalObj->getActiveSheet()->setCellValue($cell_first_hours, $this->sheetData[$prefix['row']][$column_first_hours]);
                    $this->finalObj->getActiveSheet()->setCellValue($cell_all_hours, $this->sheetData[$prefix['row']][$column_all_hours]);
                    $this->setHeaderAlign(true, $start_header_char, $header_row);
                    $fileStudents = false;
                    $dirFileStudents = 'Ведомости/' . $range_group . '/' . $ratesArray[$prefix['row']]['name'] . '/' . $ratesArray[$prefix['row']]['course'] . ' семестр/';
                    $trimmed = '[' . $prefix['prefix'] . '] ' . mb_substr($prefix['lesson'], 0, 10);
                    if (is_dir($dirFileStudents)) {

                        foreach (scandir($dirFileStudents) as $file) {
                            if (stripos($file, trim($trimmed)) !== false) {
                                $fileStudents = $file;
                                break;
                            }
                        }
                        if ($fileStudents) {
                            $studentsObj = (new Xlsx())->load($dirFileStudents . $fileStudents);
                            $studentsObj->setActiveSheetIndex(0);
                            $sheetData = $studentsObj->getActiveSheet()->toArray(null, true, true, true);

                            $res = [];
                            $cellRate = false;
                            if (mb_strpos($fileStudents, 'ПМ.0') !== false) {
                                $start_row = 16;
                                foreach ($sheetData as $__r) {
                                    foreach ($__r as $case => $__c) {
                                        if ($__c == 'Оценка за ЭК(прописью)') {
                                            $cellRate = $case;
                                            break;
                                        }
                                    }
                                    if ($cellRate <> false) break;
                                }
                            } else {
                                if (mb_stripos($fileStudents, 'зачётная') !== false) {
                                    $cellRate = 'F';
                                    $start_row = 14;
                                } elseif (mb_stripos($fileStudents, 'экзаменационная') !== false) {
                                    $cellRate = 'F';
                                    $start_row = 14;
                                } elseif (mb_stripos($fileStudents, 'семестровая') !== false) {
                                    $cellRate = 'E';
                                    $start_row = 14;
                                } elseif (mb_stripos($fileStudents, 'курсовая') !== false) {
                                    $cellRate = 'E';
                                    $start_row = 14;
                                }
                            }
                            if ($cellRate) {
                                for ($i = $start_row, $c = $start_row + 26; $i < $c; $i++) {
                                    $res[] = [
                                        'student' => $sheetData[$i]['B'],
                                        'rate' => $sheetData[$i][$cellRate]
                                    ];
                                }
                                $line = $_students_row;
                                foreach ($res as $student) {
                                    $cellStudent = $start_header_char . $line;
                                    foreach ($sheetMain as $row) {
                                        if (strcasecmp(trim($row['B']), trim($student['student'])) == 0) {
                                            $this->finalObj->getActiveSheet()->setCellValue($cellStudent, $student['rate']);
                                            $this->finalObj->getActiveSheet()->getStyle($cellStudent)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $this->finalObj->getActiveSheet()->getStyle($cellStudent)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                                            break;
                                        }
                                    }
                                    ++$line;
                                }
                            }
                        }
                    }
                    $this->finalObj->getActiveSheet()->setCellValue($cell, '[' . $prefix['prefix'] . '] ' . $prefix['lesson']);
                    ++$start_header_char;
                }
                $fc_column = false;
                foreach ($course_sheets as $course) {
                    $cell = $start_header_char . $header_row;
                    $lesson = false;
                    foreach ($this->prefixes as $prefix) {
                        if ($prefix['prefix'] == $course['prefix']) {
                            $lesson = $prefix['lesson'];
                            break;
                        }
                    }
                    if ($lesson) {
                        $fileStudents = false;
                        $dirFileStudents = 'Ведомости/' . $range_group . '/' . $start_group . '/1 семестр/';
                        $trimmed = mb_substr($lesson, 0, 25);
                        if (is_dir($dirFileStudents)) {
                            foreach (scandir($dirFileStudents) as $file) {
                                if (stripos($file, $trimmed) !== false) {
                                    $fileStudents = $file;
                                    break;
                                }
                            }
                            if ($fileStudents) {
                                echo $dirFileStudents . $fileStudents . "\r\n";
                                $studentsObj = (new Xlsx())->load($dirFileStudents . $fileStudents);
                                $studentsObj->setActiveSheetIndex(0);
                                $sheetData = $studentsObj->getActiveSheet()->toArray(null, false, false, true);
                                $start_row = 14;
                                $res = [];
                                for ($i = $start_row, $c = $start_row + 26; $i < $c; $i++) {
                                    $res[] = [
                                        'student' => $sheetData[$i]['B'],
                                        'rate' => $sheetData[$i]['E']
                                    ];
                                }
                                $line = $_students_row;
                                foreach ($res as $student) {
                                    $cellStudent = $start_header_char . $line;
                                    foreach ($sheetMain as $row) {
                                        if (strcasecmp(trim($row['B']), trim($student['student'])) == 0) {
                                            $this->finalObj->getActiveSheet()->setCellValue($cellStudent, $student['rate']);
                                            $this->finalObj->getActiveSheet()->getStyle($cellStudent)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                                            $this->finalObj->getActiveSheet()->getStyle($cellStudent)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                                            break;
                                        }
                                    }
                                    ++$line;
                                }
                            }
                        }
                        $this->finalObj->getActiveSheet()->setCellValue($cell, '[' . $course['prefix'] . '] ' . $lesson);
                        $this->setHeaderAlign(false, $start_header_char, $header_row, 30, 160, true);
                        $fc_column = $start_header_char;
                        ++$start_header_char;
                    }
                }
                foreach ($this->headersEnd as $header) {
                    $cell = $start_header_char . $header_row;
                    $this->finalObj->getActiveSheet()->setCellValue($cell, $header['name']);
                    $this->setHeaderAlign(false, $start_header_char, $header_row, $header['width']);

                    $end_combine_column = $start_header_char;
                    ++$start_header_char;
                }
                $students_row = $_students_row;
                foreach ($studentsMain['students'] as $x => $student) {
                    $cell_diploma = $end_combine_column . $students_row;
                    $cell_bdates = $bdates_column . $students_row;
                    if ($fc_column) {
                        $cell_formular = $formular_column . $students_row;
                        $end_cell_formular = $fc_column . $students_row;
                        $this->finalObj->getActiveSheet()->setCellValue($cell_formular, '=AVERAGE(' . $formular_column_start . $students_row . ':' . $end_cell_formular . ')');
                    }
                    $this->finalObj->getActiveSheet()->getStyle($cell_diploma)->getAlignment()->setTextRotation(0);
                    $this->finalObj->getActiveSheet()->setCellValue($cell_diploma, $number_of_diploma);
                    $this->finalObj->getActiveSheet()->getStyle($cell_diploma)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $this->finalObj->getActiveSheet()->getStyle($cell_diploma)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    if (count($studentsMain['dates'])) {
                        $this->finalObj->getActiveSheet()->setCellValue($cell_bdates, $studentsMain['dates'][$x]);
                        $this->finalObj->getActiveSheet()->getStyle($cell_bdates)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $this->finalObj->getActiveSheet()->getStyle($cell_bdates)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        $this->finalObj->getActiveSheet()->getColumnDimension($bdates_column)->setWidth('13');
                        $this->finalObj->getActiveSheet()->getStyle($cell_bdates)->getAlignment()->setWrapText(false);
                    }
                    $number_of_diploma++;
                    $students_row++;
                }
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
                $this->finalObj->getActiveSheet()->getStyle($start_combine_column . $header_row . ':' . $end_combine_column . $end_line_combine)->applyFromArray($style);
                $this->saveStatement($dir_for_scan . '/Итоговая ведомость.xlsx');
            }
        }
    }

    function getNumByRom($rom)
    {
        return $this->nums[$rom];
    }

    function getAllDescription(): array
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

    function getStudents($name): array
    {
        $groups = Groups::getNames();
        $return = [];

        foreach (Students::getNames() as $student) {
            if (in_array($name, $groups)) {
                $return[] = $student['name'];
            }
        }

        return $return;
    }

    /**
     * @throws Exception
     */
    function setHeaderAlign($rotate, $column, $row, $width = 20, $height = 160, $bold = false)
    {
        $this->finalObj->getActiveSheet()->getColumnDimension($column)->setWidth($width);
        $this->finalObj->getActiveSheet()->getRowDimension($row)->setRowHeight($height);
        if ($rotate) $this->finalObj->getActiveSheet()->getStyle($column . $row)->getAlignment()->setTextRotation(90);
        else $this->finalObj->getActiveSheet()->getStyle($column . $row)->getAlignment()->setTextRotation(0);
        if ($bold) $this->finalObj->getActiveSheet()->getStyle($column . $row)->getFont()->setBold(true);
        else $this->finalObj->getActiveSheet()->getStyle($column . $row)->getFont()->setBold(false);
        $this->finalObj->getActiveSheet()->getStyle($column . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->finalObj->getActiveSheet()->getStyle($column . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $this->finalObj->getActiveSheet()->getStyle($column . $row)->getAlignment()->setWrapText(true);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    function saveStatement($file = 'Итоговая ведомость.xlsx')
    {
        $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->finalObj);
        $objWriter->save($file);
    }
}

