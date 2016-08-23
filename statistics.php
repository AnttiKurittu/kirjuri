<?php
require_once("./include_functions.php");
$kirjuri_database = db('kirjuri-database');
$dateStart = $_GET['year'] . ":01:01 00:00:00";
$dateStop = ($_GET['year'] + 1) . ":01:01 00:00:00";
$query = $kirjuri_database->prepare('select * FROM exam_requests WHERE id = parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateStart,
    ':datestop' => $dateStop
));
$all_cases = $query->fetchAll(PDO::FETCH_ASSOC);
$query = $kirjuri_database->prepare('select * FROM exam_requests WHERE id != parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateStart,
    ':datestop' => $dateStop
));
$all_devices = $query->fetchAll(PDO::FETCH_ASSOC);
$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE case_status = "1" AND id=parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateStart,
    ':datestop' => $dateStop
));
$count_new = $query->fetch(PDO::FETCH_ASSOC);
$count_new = $count_new['COUNT(id)'];
$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE id = parent_id');
$query->execute();
$count_total = $query->fetch(PDO::FETCH_ASSOC);
$count_total = $count_total['COUNT(id)'];
$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE case_status = "2" AND id=parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateStart,
    ':datestop' => $dateStop
));
$count_open = $query->fetch(PDO::FETCH_ASSOC);
$count_open = $count_open['COUNT(id)'];
$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE case_status = "3" AND id=parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateStart,
    ':datestop' => $dateStop
));
$count_finished = $query->fetch(PDO::FETCH_ASSOC);
$count_finished = $count_finished['COUNT(id)'];
$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE is_removed = "0" AND id != parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateStart,
    ':datestop' => $dateStop
));
$count_alldevs = $query->fetch(PDO::FETCH_ASSOC);
$count_alldevs = $count_alldevs['COUNT(id)'];
$query = $kirjuri_database->prepare('select COUNT(id) FROM exam_requests WHERE case_contains_mob_dev = "1" AND id = parent_id AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateStart,
    ':datestop' => $dateStop
));
$count_phones = $query->fetch(PDO::FETCH_ASSOC);
$count_phones = $count_phones['COUNT(id)'];
$query = $kirjuri_database->prepare('select SUM(device_size_in_gb) FROM exam_requests WHERE id != parent_id AND is_removed = "0" AND case_added_date BETWEEN :datestart AND :datestop');
$query->execute(array(
    ':datestart' => $dateStart,
    ':datestop' => $dateStop
));
$summa = $query->fetch(PDO::FETCH_ASSOC);
$summed_size = $summa['SUM(device_size_in_gb)'];
echo $twig->render('statistics.html', array(
    'forensic_investigators' => $forensic_investigators,
    'statistics_chart_colors' => $statistics_chart_colors,
    'devices' => $_SESSION['lang']['devices'],
    'media_objs' => $_SESSION['lang']['media_objs'],
    'inv_units' => $inv_units,
    'classifications' => $_SESSION['lang']['classifications'],
    'all_cases' => $all_cases,
    'all_devices' => $all_devices,
    'count_total' => $count_total,
    'count_new' => $count_new,
    'count_open' => $count_open,
    'count_finished' => $count_finished,
    'count_alldevs' => $count_alldevs,
    'count_phones' => $count_phones,
    'dateStart' => $dateStart,
    'dateStop' => $dateStop,
    'summed_size' => $summed_size,
    'settings' => $settings,
    'lang' => $_SESSION['lang']
));
?>
