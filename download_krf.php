<?php

require_once './include_functions.php';
$case_number = filter_numbers((substr($_GET['case'], 0, 5)));
ksess_verify(2); // View only or higher
verify_case_ownership($case_number);

$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE parent_id = :id AND id = :id AND is_removed != "1"');
$query->execute(array(
  ':id' => $case_number
));
$request['parent'] = $query->fetch(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE parent_id = :id AND id != :id AND is_removed != "1"');
$query->execute(array(
  ':id' => $case_number
));
$request['children'] = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT * FROM attachments WHERE request_id = :id');
$query->execute(array(
  ':id' => $case_number
));
$files = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($request['parent'] as $key => $value) {
  if ($key === "case_owner") {
    $request['parent'][$key] = null;
  } elseif ($key === "attr_1") {
    $request['parent'][$key] = null;
  } else {
    $request['parent'][$key] = $value;
  }
}

$request['parent'] = array_trim($request['parent']);
$request['files'] = array();

$i = 0;
foreach ($request['children'] as $child) {
  $request['children'][$i] = array_trim($child);
  $i++;
}

if ($files !== false) {
  $i = 0;
  foreach ($files as $file) {
    foreach ($file as $key => $value) {
      if ($key === "content") {
        $request['files'][$i][$key] = base64_encode($value);
      } elseif ($key === "attr_1") {
        $request['files'][$i][$key] = null;
      } else {
        $request['files'][$i][$key] = $value;
      }
    }
    $i++;
  }
}

$request['caselog'] = base64_encode(file_get_contents('logs/cases/uid' . $case_number . '/events.log'));
$request['metadata']['created_by'] = $_SESSION['user']['username'] . ": " . $_SESSION['user']['name'];
$request['metadata']['department'] = $prefs['settings']['organization'];
$request['metadata']['timestamp'] = time();
$request['metadata']['kirjuri_version'] = trim(file_get_contents('conf/RELEASE'));
$request['metadata']['devices'] = count($request['children']);

if ($files !== false) {
  $request['metadata']['files'] = count($request['files']);
} else {
  $request['metadata']['files'] = "0";
}
$request['metadata']['filename'] = $filename = 'Kirjuri '.$request['parent']['case_id'].'-'.date('Y', strtotime($request['parent']['case_added_date'])).' '.$request['parent']['case_name'] . ".krf";
$file = json_encode($request, JSON_PRETTY_PRINT);
//echo "<pre>";print_r(htmlspecialchars($file));die;
$file = gzencode($file);
header('Content-Description: File Transfer');
header('Content-Type: application/x-gzip');
header('Content-Disposition: attachment; filename="' . $request['metadata']['filename'] . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($file));
echo $file;
