<?php
require_once './include_functions.php';

function verify_keys($key) {
  // Version 0.9.0
  $allowed_keys = array(
    0 => 'id',
    1 => 'parent_id',
    2 => 'case_id',
    3 => 'case_name',
    4 => 'case_suspect',
    5 => 'case_file_number',
    6 => 'case_added_date',
    7 => 'case_confiscation_date',
    8 => 'case_start_date',
    9 => 'case_ready_date',
    10 => 'case_remove_date',
    11 => 'case_devicecount',
    12 => 'case_investigator',
    13 => 'forensic_investigator',
    14 => 'phone_investigator',
    15 => 'case_investigation_lead',
    16 => 'case_investigator_tel',
    17 => 'case_investigator_unit',
    18 => 'case_crime',
    19 => 'copy_location',
    20 => 'is_removed',
    21 => 'case_status',
    22 => 'case_requested_action',
    23 => 'device_action',
    24 => 'case_contains_mob_dev',
    25 => 'case_urgency',
    26 => 'case_urg_justification',
    27 => 'case_request_description',
    28 => 'examiners_notes',
    29 => 'device_type',
    30 => 'device_manuf',
    31 => 'device_model',
    32 => 'device_os',
    33 => 'device_identifier',
    34 => 'device_location',
    35 => 'device_item_number',
    36 => 'device_document',
    37 => 'device_owner',
    38 => 'device_is_host',
    39 => 'device_host_id',
    40 => 'device_include_in_report',
    41 => 'device_time_deviation',
    42 => 'device_size_in_gb',
    43 => 'device_contains_evidence',
    44 => 'last_updated',
    45 => 'classification',
    46 => 'report_notes',
    47 => 'criminal_act_date_start',
    48 => 'criminal_act_date_end',
    49 => 'case_password',
    50 => 'case_owner',
    51 => 'is_protected',
    53 => 'request_id',
    54 => 'name',
    55 => 'description',
    56 => 'type',
    57 => 'size',
    58 => 'content',
    59 => 'uploader',
    60 => 'date_uploaded',
    61 => 'hash',
    62 => 'attr_1',
    63 => 'attr_2',
    64 => 'attr_3'
  );
  if (in_array($key, $allowed_keys)) {
    return $key;
  } else {
    echo "KEY INTEGRITY CHECK FAILURE: " . $key;
    ;
    die;
  }
}

$file['content'] = file_get_contents($_FILES['fileToUpload']['tmp_name'][0]);
$case_array = json_decode(gzdecode($file['content']), TRUE);

if ($case_array === null) {
  trigger_error('Invalid KRF file.');
  header('Location: index.php');
  die;
}

$date_range = array(
  'start' => date('Y') . '-01-01 00:00:00',
  'stop' => (date('Y') + 1) . '-01-01 00:00:00'
);
$input = $case_array['parent'];
unset($input['id']);
unset($input['parent_id']);
unset($input['case_id']);
unset($input['is_removed']);
unset($input['case_added_date']);
unset($input['case_start_date']);
unset($input['case_devicecount']);
unset($input['last_updated']);
$query = $kirjuri_database->prepare('select (case_id + 1) AS case_id FROM exam_requests WHERE case_added_date BETWEEN :dateStart AND :dateStop ORDER BY case_id DESC LIMIT 1 ');
$query->execute(array(
  ':dateStart' => $date_range['start'],
  ':dateStop' => $date_range['stop']
));
$next = $query->fetch(PDO::FETCH_ASSOC);
if ($next === false) {
  $next['case_id'] = "1";
}

$query_builder = 'INSERT INTO exam_requests (id, parent_id, case_id, is_removed, case_added_date, case_start_date, last_updated, case_devicecount, ';
foreach ($input as $key => $value) {
  $query_builder .= verify_keys($key) . ', ';
}
$query_builder = substr($query_builder, 0, -2);
$query_builder .= ') VALUES ( NULL, "0", ' . $next['case_id'] . ', "0", NOW(), NULL, NOW(), "0", ';
foreach ($input as $key => $value) {
  if ($key !== "id") {
    $query_builder .= ':' . $key . ', ';
  }
}

$pdo_query = $query_builder = substr($query_builder, 0, -2) . "); UPDATE exam_requests SET parent_id=last_insert_id() WHERE id=last_insert_id();";
$kirjuri_database = connect_database('kirjuri-database');
$query = $kirjuri_database->prepare($pdo_query);
$pdo_data = array();
foreach ($input as $key => $value) {
  $pdo_data[":" . $key] = $value;
}
$query->execute($pdo_data);
$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = last_insert_id()');
$query->execute();
$new_parent = $query->fetch(PDO::FETCH_ASSOC);

if (!empty($case_array['children'])) {
  foreach ($case_array['children'] as $input) {
    $old_id = $input['id'];
    unset($input['id']);
    $input['parent_id'] = $new_parent['id'];
    unset($input['case_id']);
    unset($input['is_removed']);
    unset($input['case_status']);
    unset($input['case_added_date']);
    unset($input['case_start_date']);
    unset($input['case_devicecount']);
    unset($input['case_owner']);
    unset($input['last_updated']);
    $query_builder = 'INSERT INTO exam_requests (id, case_id, is_removed, case_status, case_added_date, case_start_date, last_updated, case_devicecount, case_owner, ';
    foreach ($input as $key => $value) {
      $query_builder .= $key . ', ';
    }
    $query_builder = substr($query_builder, 0, -2);
    $query_builder .= ') VALUES ( NULL, NULL, "0", NULL, NOW(), NULL, NOW(), "0", NULL, ';
    foreach ($input as $key => $value) {
      if ($key !== "id") {
        $query_builder .= ':' . $key . ', ';
      }
    }
    $pdo_query = $query_builder = substr($query_builder, 0, -2) . ");";
    $kirjuri_database = connect_database('kirjuri-database');
    $query = $kirjuri_database->prepare($pdo_query);
    $pdo_data = array();
    foreach ($input as $key => $value) {
      $pdo_data[":" . $key] = $value;
    }
    $query->execute($pdo_data);
    $query = $kirjuri_database->prepare('SELECT last_insert_id() AS id');
    $query->execute();
    $last_insert = $query->fetch(PDO::FETCH_ASSOC);
    $new_ids[$old_id] = $last_insert['id'];
  }
  foreach ($new_ids as $old_id => $new_id) {
    $query = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = :new_id WHERE device_host_id = :old_id AND parent_id = :new_parent_id;');
    $query->execute(array(
      ':new_id' => $new_id,
      ':old_id' => $old_id,
      ':new_parent_id' => $new_parent['id']
    ));
  }
}

if (isset($case_array['files'])) {
  $i = 0;
  foreach ($case_array['files'] as $file) {
    foreach ($file as $key => $value) {
      if ($key === "content") {
        $decoded_files[$i][$key] = base64_decode($value);
      } else {
        $decoded_files[$i][$key] = $value;
      }
    }
    $i++;
  }
  unset($case_array['files']);
  foreach ($decoded_files as $file) {
    unset($file['id']);
    unset($file['attr_1']);
    $file['request_id'] = $new_parent['id'];
    $query_builder = 'INSERT INTO attachments (id, ';
    foreach ($file as $key => $value) {
      $query_builder .= verify_keys($key) . ', ';
    }
    $query_builder = substr($query_builder, 0, -2);
    $query_builder .= ') VALUES (NULL, ';
    foreach ($file as $key => $value) {
      if ($key !== "id") {
        $query_builder .= ':' . $key . ', ';
      }
    }
    $pdo_query = $query_builder = substr($query_builder, 0, -2) . ");";
    $kirjuri_database = connect_database('kirjuri-database');
    $query = $kirjuri_database->prepare($pdo_query);
    $pdo_data = array();
    foreach ($file as $key => $value) {
      $pdo_data[":" . $key] = $value;
    }
    $query->execute($pdo_data);
  }
}

mkdir('logs/cases/uid' . $new_parent['id']);

file_put_contents('logs/cases/uid' . $new_parent['id'] . '/events.log', base64_decode($case_array['caselog']));
file_put_contents('logs/cases/uid' . $new_parent['id'] . '/import.log', json_encode($case_array['metadata'], JSON_PRETTY_PRINT));

event_log_write($new_parent['id'], "Add", "Imported case from file: " . $_FILES['fileToUpload']['name'][0]);
show_saved_succesfully();
header('Location: edit_request.php?case=' . $new_parent['id']);
die;
