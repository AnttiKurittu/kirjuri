<?php

$case_number = preg_replace('/[^0-9]/', '', (substr($_GET['case'], 0, 5)));
require_once './include_functions.php';
protect_page(2); // View only or higher


$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE parent_id = :id AND is_removed != "1" ORDER BY id');
$query->execute(array(
  ':id' => $case_number,
));
$request_items = $query->fetchAll(PDO::FETCH_ASSOC);
$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE parent_id = :id AND is_removed != "0" ORDER BY id');
$query->execute(array(
  ':id' => $case_number,
));
$request_items_removed = $query->fetchAll(PDO::FETCH_ASSOC);
$filename = 'Kirjuri '.$request_items[0]['case_id'].'-'.date('Y', strtotime($request_items[0]['case_added_date'])).' '.$request_items[0]['case_name'];
header('Content-Description: File Transfer');
header('Content-Encoding: UTF-8');
header('Content-Type: text; charset=utf-8');
header('Content-Disposition: attachment; filename='.trim($filename).'.csv');
echo 'sep=;';
echo "\n";
foreach (array_keys($request_items[0]) as $key) {
    echo $key.';';
}
echo "\n";
foreach ($request_items as $result) {
    foreach ($result as $result) {
        $item = str_replace("'", '"', $result);
        $item = str_replace('"', '""', $item);
        $item = str_replace('\\', '', $item);
        $item = str_replace(';', ',', $item);
        echo '"'.$item.'";';
    }
  ;
    echo "\n";
}
;
echo 'Removed items:'."\n";
foreach ($request_items_removed as $result) {
    foreach ($result as $result) {
        $item = str_replace("'", '"', $result);
        $item = str_replace('"', '""', $item);
        $item = str_replace('\\', '', $item);
        $item = str_replace(';', ',', $item);
        echo '"'.$item.'";';
    }
  ;
    echo "\n";
}
;
