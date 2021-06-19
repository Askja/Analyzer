<?php
class Report{
	
	private $report_file,
			$dir,
			$file;
			
	protected $excelObj,
			  $sheetData;
	
	function __construct($dir){
		$this->dir = $dir;
	}
	function getDir(){
		return $this->dir;
	}
	function setReportFile($file){
		$this->file = $file;
	}
	function getReportFile(){
		return $this->file;
	}
	function readReportFile($upd = false){
		$this->excelObj = new PHPExcel();
		$file = ($upd <> false ? $upd : $this->getDir() . '/' . $this->getReportFile());
		$this->excelObj = PHPExcel_IOFactory::load($file);
		$this->excelObj->setActiveSheetIndex(0);
		$this->sheetData = $this->excelObj->getActiveSheet()->toArray(null, true, true, true);
	}
	function parseReportFile(){
		$start_row_groups = 9;
		$column_groups = 'A';
		
		$parsed = [];
		
		for($x = $start_row_groups, $row = $this->sheetData[$start_row_groups], $cell = $row[$column_groups]; mb_stripos($cell, 'итого') === false; ++$x, $row = $this->sheetData[$x], $cell = $row[$column_groups]){
			list($tag, $num) = explode('-', $cell);
			$cNum = $num[0];
			$comNum = $num[1].$num[2];
			$parsed[] = [
				'tag' => $tag,
				'course' => $cNum,
				'compare' => '/' . $tag . '-([1-5])' . $comNum . '/',
				'row' => $x,
				'need' => $cell
			];
		}
		return $parsed;
	}
	function parseAllDirs($p, $dir, $s){
		$list = [];
		foreach(scandir($dir) as $d){
			if($d != '.' && $d != '..'){
				foreach($p as $parsed){
					preg_match_all($parsed['compare'], $d, $m, PREG_SET_ORDER, 0);
					if(count($m)){
						$list[] = [
							'dir' => $dir . '/' . $d . '/' . $parsed['need'] . '/' . $s . ' семестр',
							'row' => $parsed['row']
						];
						break;
					}
				}
			}
		}
		return $list;
	}
	function createReport($list){
		$columns_range = [
			'best' => 'E',
			'good' => 'G', 
			'bad' => 'I'
		];
		$columns_sem = [
			'best' => 'K',
			'good' => 'M', 
			'bad' => 'O'
		];
		
		$default_name_statement = 'Ведомость куратора.xlsx';
		
		$column_all = 'C';
		
		foreach($list as $parsed){
			$file = $parsed['dir'] . '/' . $default_name_statement;
			if( file_exists($file) && is_file($file) ){
				$rates = $this->getCuratorRates($file);
				$this->excelObj->getActiveSheet()->setCellValue($columns_range['best'] . $parsed['row'], ($rates['range']['best'] > 0 ? $rates['range']['best'] : '') );
				$this->excelObj->getActiveSheet()->setCellValue($columns_range['good'] . $parsed['row'], $rates['range']['good'] );
				$this->excelObj->getActiveSheet()->setCellValue($columns_range['bad'] . $parsed['row'], $rates['range']['bad'] + $rates['range']['not'] );
				
				$this->excelObj->getActiveSheet()->setCellValue($columns_sem['best'] . $parsed['row'], ($rates['sem']['best'] > 0 ? $rates['sem']['best'] : '') );
				$this->excelObj->getActiveSheet()->setCellValue($columns_sem['good'] . $parsed['row'], $rates['sem']['good'] );
				$this->excelObj->getActiveSheet()->setCellValue($columns_sem['bad'] . $parsed['row'], $rates['sem']['bad'] + $rates['sem']['not'] );
				$this->excelObj->getActiveSheet()->setCellValue($column_all . $parsed['row'], $rates['all'] );
			}
		}
	}
	function saveReport($s){
		$default_dir = 'Отчеты';
		if(!is_dir($default_dir)) mkdir($default_dir);
		$objWriter = new PHPExcel_Writer_Excel2007($this->excelObj);
		$objWriter->save($default_dir . '/Отчет по всем группам ' . $s . ' семестр.xlsx');
	}
	function getCuratorRates($file){
		$define_list = 1;
		$columns_range = [
			'best' => 'B',
			'good' => 'C',
			'normal' => 'D',
			'bad' => 'E',
			'not' => 'F'
		];
		$range_row = 7;
		$columns_sem = [
			'best' => 'B',
			'good' => 'C',
			'normal' => 'D',
			'bad' => 'E',
			'not' => 'F'
		];
		$sem_row = 12;
		
		$excelObj = new PHPExcel();
		$excelObj = PHPExcel_IOFactory::load($file);
		$excelObj->setActiveSheetIndex($define_list);
		$sheetData = $excelObj->getActiveSheet()->toArray(null, true, true, true);
		$response = [];
		foreach($columns_range as $p => $col){
			$response['range'][$p] = $sheetData[$range_row][$col];
		}
		foreach($columns_sem as $p => $col){
			$response['sem'][$p] = $sheetData[$sem_row][$col];
		}
		$excelObj->setActiveSheetIndex(0);
		$sheetData = $excelObj->getActiveSheet()->toArray(null, true, true, true);
		$response['all'] = $sheetData[48]['E'];
		
		return $response;
	}
}
?>