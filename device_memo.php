<?php

require_once './include_functions.php';
ksess_verify(2); // View only or higher

$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE is_removed != "1" AND id = :uid AND parent_id != id LIMIT 1');
$query->execute(array(
  ':uid' => $_GET['uid'],
));
$mediarow = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($mediarow) === 0)
{
  header('Location: index.php');
  die;
}

$query = $kirjuri_database->prepare('SELECT id, device_type, device_manuf, device_model, device_host_id FROM exam_requests WHERE is_removed != "1" AND device_host_id = :uid');
$query->execute(array(
  ':uid' => $_GET['uid'],
));
$connectedmediarow = $query->fetchAll(PDO::FETCH_ASSOC);
$query = $kirjuri_database->prepare('SELECT id, device_type, device_manuf, device_model, device_host_id FROM exam_requests WHERE is_removed != "1" AND id = :uid');
$query->execute(array(
  ':uid' => $mediarow[0]['device_host_id'],
));
$hostdevice = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($mediarow as $entry) {
    $casefetch = $entry;
}

verify_case_ownership($casefetch['parent_id']);

if (empty($_SESSION['case_token'][ $casefetch['parent_id'] ]))
{
  $_SESSION['case_token'][$casefetch['parent_id']] = generate_token(16); // Initialize case token
}

$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE is_removed != "1" AND id = :parent_id LIMIT 1');
$query->execute(array(
  ':parent_id' => $casefetch['parent_id'],
));
$caserow = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT id, parent_id, device_type, device_manuf, device_model FROM exam_requests WHERE is_removed != "1" AND parent_id = :parent_id AND id != :parent_id');
$query->execute(array(
  ':parent_id' => $casefetch['parent_id'],
));
$case_device_id_list = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT id, case_id, case_name, case_suspect, case_added_date FROM exam_requests WHERE case_status <= 2 AND parent_id = id AND is_removed = "0" ORDER BY id ASC');
$query->execute();
$allcases = $query->fetchAll(PDO::FETCH_ASSOC);


$imei_data = "";
if ( strpos( strtoupper($mediarow[0]['device_identifier']), "IMEI") !== false)
{
  $imei_TAC =  substr(filter_numbers($mediarow[0]['device_identifier']),0,8);
  if (strlen($imei_TAC) === 8) {
    if (file_exists('conf/imei.txt'))
    {
      $imei_list = file('conf/imei.txt');
      foreach($imei_list as $line)
      {
        if (substr($line, 0 , 8) === $imei_TAC)
        {
          $imei_data = explode("|", $line);
        }
      }
    }
  }
}

if (file_exists('conf/report_notes.local'))
{
  $templates['report_notes'] = file_get_contents('conf/report_notes.local');
  $templates['report_notes'] = filter_html($templates['report_notes']);
}
elseif (file_exists('conf/report_notes.template'))
{
  $templates['report_notes'] = file_get_contents('conf/report_notes.template');
  $templates['report_notes'] = filter_html($templates['report_notes']);
}
else {
  $templates['report_notes'] = "";
}

$_SESSION['message_set'] = false;
echo $twig->render('device_memo.twig', array(
  'ct' => $_SESSION['case_token'][$casefetch['parent_id']],
  'templates' => $templates,
  'imei_data' => $imei_data,
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
  'settings' => $prefs['settings'],
  'lang' => $_SESSION['lang'],
));
