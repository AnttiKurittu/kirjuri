<?php
$case_number = preg_replace("/[^0-9]/", "", (substr($_GET['case'], 0, 5)));
header('Content-Description: File Transfer');
header('Content-Encoding: UTF-8');
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename=Kirjuri_request_' . $case_number . '.csv');
require_once("./include_functions.php");
$kirjuri_database = db('kirjuri-database');
$query            = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE parent_id = :id AND is_removed != "1"');
$query->execute(array(
  ':id' => $case_number
));
$query_results = $query->fetchAll(PDO::FETCH_ASSOC);
echo "sep=;";
echo "\n";
foreach (array_keys($query_results[0]) as $key) {
  echo $key . ";";
}
echo "\n";
foreach ($query_results as $result) {
  foreach ($result as $result) {
    $item = str_replace("'", "\"", $result);
    $item = str_replace("\"", "\"\"", $item);
    $item = str_replace("\\", "", $item);
    $item = str_replace(";", ",", $item);
    echo '"' . $item . '";';
  };
  echo "\n";
};
?>
