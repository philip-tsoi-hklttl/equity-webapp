<?php

require 'vendor/autoload.php';
require_once ('config.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

/*
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('On99 Pok Kai');
$sheet->setCellValue('A1', '#');
$sheet->setCellValue('B1', 'First');
$sheet->setCellValue('C1', 'Last');
$sheet->setCellValue('D1', 'Handle');


$sheet->setCellValue('A2', 1);
$sheet->setCellValue('B2', 'Mark');
$sheet->setCellValue('C2', 'Jacob');
$sheet->setCellValue('D2', 'Larry');

$sheet->setCellValue('A3', 2);
$sheet->setCellValue('B3', 'Jacob');
$sheet->setCellValue('C3', 'Thornton');
$sheet->setCellValue('D3', '@fat');

$sheet->setCellValue('A4', 3);
$sheet->setCellValue('B4', 'Larry');
$sheet->setCellValue('C4', 'the Bird');
$sheet->setCellValue('D4', '@twitter');


$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('DIU NEI');

$sheet2->setCellValueByColumnAndRow(1, 1, '#');
$sheet2->setCellValueByColumnAndRow(2, 1, '2First');
$sheet2->setCellValueByColumnAndRow(3, 1, '2Last');
$sheet2->setCellValueByColumnAndRow(4, 1, '2Handle');

$sheet2->setCellValueByColumnAndRow(1, 2, 1);
$sheet2->setCellValueByColumnAndRow(2, 2, '2Mark');
$sheet2->setCellValueByColumnAndRow(3, 2, '2Jacob');
$sheet2->setCellValueByColumnAndRow(4, 2, '2Larry');

$sheet2->setCellValueByColumnAndRow(1, 3, 2);
$sheet2->setCellValueByColumnAndRow(2, 3, '2Jacob');
$sheet2->setCellValueByColumnAndRow(3, 3, '2Thornton');
$sheet2->setCellValueByColumnAndRow(4, 3, '2@fat');

$sheet2->setCellValueByColumnAndRow(1, 4, 3);
$sheet2->setCellValueByColumnAndRow(2, 4, '3Larry');
$sheet2->setCellValueByColumnAndRow(3, 4, '3the Bird');
$sheet2->setCellValueByColumnAndRow(4, 4, '3@twitter');



$filename = 'sample-'.time().'.xlsx';
// Redirect output to a client's web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
 
// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
*/

$spreadsheet = new Spreadsheet();
        
$batch = isset($_GET['batch'])?$_GET['batch']:"19700101-000000";
$sqlView = "SELECT * FROM batch WHERE batch='".$batch."' AND 1 LIMIT 1";
$resultView = $pdo->query($sqlView)->fetchAll();
$sfobj = json_decode($resultView[0]["sfobj"], true);
$data = json_decode($resultView[0]["data"], true);
$tablelist = array_keys($sfobj);




for($i=0; $i<count($tablelist); $i++){
    if($i==0){
        ${"sheet_".$i} = $spreadsheet->getActiveSheet();
    }
    else{
        ${"sheet_".$i} = $spreadsheet->createSheet();
    }

    $sfobj_target = $sfobj[$tablelist[$i]];
    $table_heading = $sfobj_target["title"];
    ${"sheet_".$i}->setTitle($sfobj_target["title"]);
    ${"fieldlist_".$i} = "";

    for($j=0; $j<count($sfobj_target["field"]); $j++){
        ${"fieldlist_".$i}.= $sfobj_target["field"][$j]." ";
        ${"sheet_".$i}->setCellValueByColumnAndRow($j+1, 1, $sfobj_target["field"][$j]);
    }

    //$debugObj["fieldlist_".$i] =  ${"fieldlist_".$i};
    

}

        
        
$filename = 'sample-'.time().'.xlsx';
// Redirect output to a client's web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');

