<?php
require_once './include_functions.php';
ksess_verify(3); // View only or higher
if ($_SESSION['user']['access'] === "3")
{
  header('Location: add_case.php');
  die;
}

$sort_j = isset($_GET['j']) ? $_GET['j'] : '';
$sort_d = isset($_GET['d']) ? $_GET['d'] : '';
$sort_s = isset($_GET['s']) ? $_GET['s'] : '';
$has_attachments[] = '';
$order_direction = $statuslimit = '';
$search_term = '';

if (empty($_GET['year'])) {
    $year = date('Y'); // Use current year if none specified
} else {
    $year = filter_numbers((substr($_GET['year'], 0, 4))); // Get year from GET
}

$dateRange = array('start' => $year.'-01-01 00:00:00', 'stop' => ($year + 1).'-01-01 00:00:00');
$order_by = 'case_status ASC, case_id DESC';

if ($sort_d === 'a') {
    // Get sorting order

  $order_direction = ' ASC';
} else {
    $order_direction = ' DESC';
}

if (isset($sort_j)) {
    // Get sorting column

  if ($sort_j === '1') {
      $order_by = 'case_id'.$order_direction;
  }
    if ($sort_j === '2') {
        $order_by = 'case_name'.$order_direction;
    }
    if ($sort_j === '3') {
        $order_by = 'case_file_number'.$order_direction;
    }
    if ($sort_j === '4') {
        $order_by = 'case_crime'.$order_direction;
    }
    if ($sort_j === '5') {
        $order_by = 'case_suspect'.$order_direction;
    }
    if ($sort_j === '6') {
        $order_by = 'case_investigator'.$order_direction;
    }
    if ($sort_j === '7') {
        $order_by = 'forensic_investigator'.$order_direction;
    }
    if ($sort_j === '8') {
        $order_by = 'phone_investigator'.$order_direction;
    }
    if ($sort_j === '9') {
        $order_by = 'case_added_date'.$order_direction;
    }
}
if (isset($sort_s)) {
    // Get sorting by status

  if ($sort_s === '1') {
      $statuslimit = 'AND case_status = "1" ';
  }
    if ($sort_s === '2') {
        $statuslimit = 'AND case_status = "2" ';
    }
    if ($sort_s === '3') {
        $statuslimit = 'AND case_status = "3" ';
    }
}
if (isset($_GET['search']) && (!empty($_GET['search']))) {
    // If a search string is present, handle that. Handled via GET for bookmarking a search.
    // if the search is for UID, jump to that case.
  $search_term = substr($_GET['search'], 0, 128);
  if (substr($search_term, 0, 3) === "UID") {
    $get_uid = filter_numbers(substr($search_term, 3, 11));
    if (empty($get_uid)) { // If no UID present, return to index.
      header('Location: '.$_SERVER['HTTP_REFERER']);
      die;
    }
    $query = $kirjuri_database->prepare('SELECT id, parent_id FROM exam_requests WHERE id = :get_uid');
    $query->execute(array(':get_uid' => $get_uid));
    $get_uid_result = $query->fetch(PDO::FETCH_ASSOC);
    if ($get_uid_result === false) { // If ID does not exist, return to index.
      header('Location: '.$_SERVER['HTTP_REFERER']);
      die;
    }
    if ($get_uid_result['id'] === $get_uid_result['parent_id']) // Jump to case.
    {
      header('Location: edit_request.php?case='.$get_uid_result['parent_id']);
      die;
    }
    else
    {
      header('Location: device_memo.php?uid='.$get_uid_result['id']); // Jump to device.
      die;
    }
  }
  else
  {
    $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = id '.$statuslimit.'AND is_removed = "0" AND MATCH (case_name,case_suspect,case_file_number,case_investigator,forensic_investigator,phone_investigator,case_investigation_lead,case_investigator_unit,case_crime,case_requested_action,case_request_description,report_notes,examiners_notes,device_manuf,device_model,device_identifier,device_owner) AGAINST (:search_term IN BOOLEAN MODE) AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY '.$order_by);
    $query->execute(array(
    ':search_term' => $search_term,
    ':dateStart' => $dateRange['start'],
    ':dateStop' => $dateRange['stop']));
  }
}
else
{
    // If no search term present, just get all case id's for device counting.
  $query = $kirjuri_database->prepare('SELECT id FROM exam_requests WHERE id = parent_id '.$statuslimit.'AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY '.$order_by);
    $query->execute(array(
    ':dateStart' => $dateRange['start'],
    ':dateStop' => $dateRange['stop'],
  ));
    $row_active = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($row_active as $entry) {
        // Count and update devicecount in case they loses track.
    $query = $kirjuri_database->prepare('SELECT COUNT(id) FROM exam_requests WHERE parent_id = :id AND is_removed = "0";');
        $query->execute(array(
      ':id' => $entry['id'],
    ));
        $count = $query->fetchAll(PDO::FETCH_ASSOC);
        $query = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = :case_devicecount WHERE id = :id AND parent_id = :id;');
        $query->execute(array(
      ':id' => $entry['id'],
      ':case_devicecount' => ($count[0]['COUNT(id)'] - 1),
    ));
    }
  // Get the cases
  $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = parent_id '.$statuslimit.'AND is_removed = "0" AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY '.$order_by);
    $query->execute(array(
    ':dateStart' => $dateRange['start'],
    ':dateStop' => $dateRange['stop'],
  ));
}
$row_cases = $query->fetchAll(PDO::FETCH_ASSOC); // Get devices to show new devices (action status 1)

$query = $kirjuri_database->prepare('SELECT parent_id, device_action FROM exam_requests WHERE id != parent_id AND is_removed = "0" AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY parent_id, device_action ASC');
$query->execute(array(
  ':dateStart' => $dateRange['start'],
  ':dateStop' => $dateRange['stop'],
));
$row_devices = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT DISTINCT(request_id) AS request_id FROM attachments');
$query->execute(array(
  ':dateStart' => $dateRange['start'],
  ':dateStop' => $dateRange['stop'],
));
$files = $query->fetchAll(PDO::FETCH_ASSOC);
$attachments = array();
foreach($files as $file) {
  array_push($attachments, $file['request_id']);
}
unset($files);

// Scan attachment directories and return directories that have any files as an array.

$_SESSION['message_set'] = false;

if (file_exists('conf/index_columns.local'))
{
  $show_columns = parse_ini_file('conf/index_columns.local', true);
}
elseif (file_exists('conf/index_columns.conf'))
{
  $show_columns = parse_ini_file('conf/index_columns.conf', true);
}
else {
  $show_columns = "";
}

echo $twig->render('index.twig', array(
  'show_columns' => $show_columns,
  'session' => $_SESSION,
  'attachments' => $attachments,
  'search_term' => $search_term,
  'sort_s' => $sort_s,
  'query_d' => $sort_d,
  'query_j' => $sort_j,
  'query_s' => $sort_s,
  'order_by' => $order_by,
  'dateStart' => $dateRange['start'],
  'row_cases' => $row_cases,
  'row_devices' => $row_devices,
  'settings' => $prefs['settings'],
  'lang' => $_SESSION['lang'],
));
