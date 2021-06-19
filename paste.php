<pre>
    <meta charset="utf-8">
<?php
require 'vendor/autoload.php';

use engine\Application;
use engine\VitaeCollections;
use models\DirsHelper;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

Application::init();

//$mysql = new mysqli('localhost', Application::MYSQL_LOGIN, Application::MYSQL_PASS);
//$mysql->select_db(Application::MYSQL_DB);

$table = Application::MYSQL_STUDENTS_TABLE;
/*
$dir = 'temp/students';
$inserts = [];
foreach (scandir($dir) as $file) {
    if (is_file($dir . '/' . $file) && $file !== '_i.txt') {
        list($group,) = explode('.', $file);
        foreach (file($dir . '/' . $file, 6) as $line) {
            //print_r($line);
            list($name, $bDate) = explode(':', mb_convert_encoding($line, 'cp1251', 'utf-8'));
            list($d, $m, $y) = explode('.', $bDate); // 03.12.1998
            $bDate = "$y-$m-$d";
            $inserts[] = $mysql->parse("INSERT INTO ?n (group_id, name, rates_id, bdate) VALUES ((SELECT id FROM ?n WHERE name=?s), ?s, 0, ?s)", $table, Application::MYSQL_GROUPS_TABLE, $group, $name, $bDate);
        }
    }
}
print_r(implode(";\r\n", $inserts));
echo $mysql->set_charset('utf8');
foreach ($mysql->query('SELECT * FROM ' . Application::MYSQL_GROUPS_TABLE)->fetch_all(MYSQLI_ASSOC) as $group) {
    print_r(mb_convert_encoding($group['name'], 'cp1251', 'utf-8'));
}*/

//print_r(Groups::getAll());

$vitae = new VitaeCollections('temp/Ведомости', 'temp/templates', 'temp/system');
print_r($vitae->getCreated());
/*
try {
    $startTime = time();

    $sNameFile = urldecode('ПИ-405');

    foreach (DirsHelper::scan($vitae->getScanDir()) as $dir) {
        if (mb_stripos($dir, $sNameFile) !== false) {
            $scanDir = $dir;
            break;
        }
    }

    if (isset($scanDir)) {
        $GROUP = $vitae->createGroupData($scanDir);

        $xls = (new Xlsx())->load($vitae->getScanDir() . '/' . $scanDir);
        $xls->setActiveSheetIndex(0);

        $sheetData = $xls->getActiveSheet()->toArray(null, false, false, true);
        $lessons = [];

        foreach ($GROUP['rows'] as $line) {
            $lesson = $sheetData[$line]['B'];
            if (!in_array($lesson, $lessons)) {
                $lessons[] = $lesson;
            }
        }

        $vitae->createSummaryVitae($GROUP, $lessons, 4, 1);
    }

    print_r("Time: " . (time() - $startTime));
} catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
}
*/
/*
$lines = [];
foreach (file('temp/combinations.txt', 6) as $l) {
    $lines[] = "INSERT INTO LessonsKeys (name) VALUE ('$l')";
}

print_r(implode(";\r\n", $lines));*/
?>
</pre>