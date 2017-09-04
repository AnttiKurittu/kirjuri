<?php

require_once './include_functions.php';
ksess_verify(2); // View only or higher.

if (empty($_GET['year'])) {
    $year = date('Y'); // Use current year if none specified
} else {
    $year = filter_numbers((substr($_GET['year'], 0, 4))); // Get year from GET
}

$dateRange = array('start' => $year.'-01-01 00:00:00', 'stop' => ($year + 1).'-01-01 00:00:00');

$query = $kirjuri_database->prepare('select * FROM exam_requests WHERE is_removed != "1" AND id = parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateRange['start'],
    ':datestop' => $dateRange['stop'],
));
$all_cases = $query->fetchAll(PDO::FETCH_ASSOC);

if (empty($all_cases)) {
    message('error', $_SESSION['lang']['no_cases']);
    header('Location: index.php');
	die();
}

$query = $kirjuri_database->prepare('select * FROM exam_requests WHERE is_removed != "1" AND id != parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateRange['start'],
    ':datestop' => $dateRange['stop'],
));
$all_devices = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE is_removed != "1" AND case_status = "1" AND id=parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateRange['start'],
    ':datestop' => $dateRange['stop'],
));
$count_new = $query->fetch(PDO::FETCH_ASSOC);
$count_new = $count_new['COUNT(id)'];

$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE is_removed != "1" AND id = parent_id AND is_removed != "1"');
$query->execute();
$count_total = $query->fetch(PDO::FETCH_ASSOC);
$count_total = $count_total['COUNT(id)'];

$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE is_removed != "1" AND case_status = "2" AND id=parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateRange['start'],
    ':datestop' => $dateRange['stop'],
));
$count_open = $query->fetch(PDO::FETCH_ASSOC);
$count_open = $count_open['COUNT(id)'];

$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE is_removed != "1" AND case_status = "3" AND id=parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateRange['start'],
    ':datestop' => $dateRange['stop'],
));
$count_finished = $query->fetch(PDO::FETCH_ASSOC);
$count_finished = $count_finished['COUNT(id)'];
$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE is_removed != "1" AND id != parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateRange['start'],
    ':datestop' => $dateRange['stop'],
));
$count_alldevs = $query->fetch(PDO::FETCH_ASSOC);
$count_alldevs = $count_alldevs['COUNT(id)'];
$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE is_removed != "1" AND case_contains_mob_dev = "1" AND id = parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateRange['start'],
    ':datestop' => $dateRange['stop'],
));
$count_phones = $query->fetch(PDO::FETCH_ASSOC);
$count_phones = $count_phones['COUNT(id)'];


$query = $kirjuri_database->prepare('select SUM(device_size_in_gb) FROM exam_requests WHERE is_removed != "1" AND id != parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateRange['start'],
    ':datestop' => $dateRange['stop'],
));
$summa = $query->fetch(PDO::FETCH_ASSOC);
$summed_size = $summa['SUM(device_size_in_gb)'];

// Get sum of data of devices by unit to $device_data_by_unit

$cases_by_unit = array();
foreach($prefs['inv_units'] as $unit)
{
  $query = $kirjuri_database->prepare('select id FROM exam_requests WHERE is_removed != "1" AND id = parent_id AND case_investigator_unit = :unit AND case_added_date BETWEEN :datestart AND :datestop');
  $query->execute(array(
      ':unit' => $unit,
      ':datestart' => $dateRange['start'],
      ':datestop' => $dateRange['stop'],
  ));
  $cases_for_unit = $query->fetchAll(PDO::FETCH_ASSOC);
  $cases_by_unit[$unit] = array();
  foreach ($cases_for_unit as $case)
  {
    array_push($cases_by_unit[$unit], $case['id']);
  }
}

foreach($cases_by_unit as $key => $unit)
{
  $device_data_by_unit[$key] = 0;
  foreach($unit as $case_id)
  {
    $query = $kirjuri_database->prepare('
    select SUM(device_size_in_gb) AS sum FROM exam_requests WHERE is_removed != "1" AND parent_id = :case_id');
    $query->execute(array(':case_id' => $case_id));
    $devices_sum = $query->fetch(PDO::FETCH_ASSOC);
    $device_data_by_unit[$key] = $device_data_by_unit[$key] + $devices_sum['sum'];
  }
}

foreach($cases_by_unit as $key => $unit)
{
  $device_count_by_unit[$key] = 0;
  foreach($unit as $case_id)
  {
    $query = $kirjuri_database->prepare('
    select COUNT(id) AS sum FROM exam_requests WHERE is_removed != "1" AND parent_id = :case_id AND id != :case_id');
    $query->execute(array(':case_id' => $case_id));
    $device_count = $query->fetch(PDO::FETCH_ASSOC);
    $device_count_by_unit[$key] = $device_count_by_unit[$key] + $device_count['sum'];
  }
}



$_SESSION['message_set'] = false;
echo $twig->render('statistics.twig', array(
    'session' => $_SESSION,
    'statistics_chart_colors' => $prefs['statistics_chart_colors'],
    'devices' => $_SESSION['lang']['devices'],
    'media_objs' => $_SESSION['lang']['media_objs'],
    'inv_units' => $prefs['inv_units'],
    'classifications' => $_SESSION['lang']['classifications'],
    'all_cases' => $all_cases,
    'all_devices' => $all_devices,
    'device_count' => count($all_devices),
    'count_total' => $count_total,
    'count_new' => $count_new,
    'count_open' => $count_open,
    'count_finished' => $count_finished,
    'count_alldevs' => $count_alldevs,
    'count_phones' => $count_phones,
    'dateStart' => $dateRange['start'],
    'dateStop' => $dateRange['stop'],
    'summed_size' => $summed_size,
    'device_data_by_unit' => $device_data_by_unit,
    'device_count_by_unit' => $device_count_by_unit,
    'settings' => $prefs['settings'],
    'lang' => $_SESSION['lang'],
));
