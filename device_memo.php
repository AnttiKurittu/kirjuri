<?php
require_once("./include_functions.php");
protect_page(2); // View only or higher

// COMMENTEDOUTFORTESTING $kirjuri_database = db('kirjuri-database'); // Init database

$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE is_removed != "1" AND id = :db_row LIMIT 1');
$query->execute(array(
  ':db_row' => $_GET['db_row']
));
$mediarow = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT id, device_type, device_manuf, device_model, device_host_id FROM exam_requests WHERE is_removed != "1" AND device_host_id = :db_row');
$query->execute(array(
  ':db_row' => $_GET['db_row']
));
$connectedmediarow = $query->fetchAll(PDO::FETCH_ASSOC);
$query             = $kirjuri_database->prepare('SELECT id, device_type, device_manuf, device_model, device_host_id FROM exam_requests WHERE is_removed != "1" AND id = :db_row');
$query->execute(array(
  ':db_row' => $mediarow[0]['device_host_id']
));
$hostdevice = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($mediarow as $entry)
 {
  $casefetch = $entry;
 }

$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE is_removed != "1" AND id = :parent_id LIMIT 1');
$query->execute(array(
  ':parent_id' => $casefetch['parent_id']
));
$caserow = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT id, parent_id, device_type, device_manuf, device_model FROM exam_requests WHERE is_removed != "1" AND parent_id = :parent_id AND id != :parent_id');
$query->execute(array(
  ':parent_id' => $casefetch['parent_id']
));
$case_device_id_list = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT id, case_id, case_name, case_suspect, case_added_date FROM exam_requests WHERE case_status <= 2 AND parent_id = id AND is_removed = "0" ORDER BY id ASC');
$query->execute();
$allcases                = $query->fetchAll(PDO::FETCH_ASSOC);

$_SESSION['message_set'] = false;
echo $twig->render('device_memo.html', array(
  'session' => $_SESSION,
  'device_actions' => $_SESSION['lang']['device_actions'],
  'device_locations' => $_SESSION['lang']['device_locations'],
  'connectedmediarow' => $connectedmediarow,
  'case_device_id_list' => $case_device_id_list,
  'hostdevice' => $hostdevice,
  'mediarow' => $mediarow,
  'allcases' => $allcases,
  'caserow' => $caserow,
  'devices' => $_SESSION['lang']['devices'],
  'media_objs' => $_SESSION['lang']['media_objs'],
  'settings' => $settings,
  'lang' => $_SESSION['lang']
));
?>
