<?php
require_once("./main.php");
if (empty($_GET['vuosi']))
  {
    $vuosi = date("Y");
  }
else
  {
    $vuosi = $_GET['vuosi'];
  }
$dateStart = $vuosi . "-01-01 00:00:00";
$dateStop = ($vuosi + 1) . "-01-01 00:00:00";
$order_by = "case_status ASC, case_id DESC";
$order_direction = $statuslimit = "";
$hakutermi = "";
$kirjuri_database = db('kirjuri-database');
if (isset($_GET['d']))
  {
    if ($_GET['d'] === "a")
      {
        $order_direction = " ASC";
      }
  }
else
  {
    $order_direction = " DESC";
  }
if (isset($_GET['j']))
  {
    if ($_GET['j'] === "1")
      {
        $order_by = "case_id" . $order_direction;
      }
    if ($_GET['j'] === "2")
      {
        $order_by = "case_name" . $order_direction;
      }
    if ($_GET['j'] === "3")
      {
        $order_by = "case_file_number" . $order_direction;
      }
    if ($_GET['j'] === "4")
      {
        $order_by = "case_crime" . $order_direction;
      }
    if ($_GET['j'] === "5")
      {
        $order_by = "case_suspect" . $order_direction;
      }
    if ($_GET['j'] === "6")
      {
        $order_by = "case_investigator" . $order_direction;
      }
    if ($_GET['j'] === "7")
      {
        $order_by = "forensic_investigator" . $order_direction;
      }
    if ($_GET['j'] === "8")
      {
        $order_by = "phone_investigator" . $order_direction;
      }
    if ($_GET['j'] === "9")
      {
        $order_by = "case_added_date" . $order_direction;
      }
  }
if (isset($_GET['s']))
  {
    if ($_GET['s'] === "1")
      {
        $statuslimit = 'AND case_status = "1" ';
      }
    if ($_GET['s'] === "2")
      {
        $statuslimit = 'AND case_status = "2" ';
      }
    if ($_GET['s'] === "3")
      {
        $statuslimit = 'AND case_status = "3" ';
      }
  }
if (isset($_GET['hae']))
  {
    $hakusana = substr($_GET['hae'], 0, 128);
    $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = id ' . $statuslimit . 'AND is_removed = "0" AND MATCH (case_name,case_suspect,case_file_number,case_investigator,forensic_investigator,phone_investigator,case_investigation_lead,case_investigator_unit,case_crime,case_requested_action,case_request_description,report_notes,examiners_notes,device_manuf,device_model,device_identifier,device_owner) AGAINST (:hakusana IN BOOLEAN MODE) AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY ' . $order_by);
    $query->execute(array(
        ':hakusana' => $hakusana,
        ':dateStart' => $dateStart,
        ':dateStop' => $dateStop
    ));
  }
else
  {
    $query = $kirjuri_database->prepare('SELECT id FROM exam_requests WHERE id = parent_id ' . $statuslimit . 'AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY ' . $order_by);
    $query->execute(array(
        ':dateStart' => $dateStart,
        ':dateStop' => $dateStop
    ));
    $row_aktiiviset = $query->fetchAll(PDO::FETCH_ASSOC);
    foreach ($row_aktiiviset as $entry)
      {
        $query = $kirjuri_database->prepare('SELECT COUNT(id) FROM exam_requests WHERE parent_id = :id AND is_removed = "0";');
        $query->execute(array(
            ':id' => $entry['id']
        ));
        $count = $query->fetchAll(PDO::FETCH_ASSOC);
        $query = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = :case_devicecount WHERE id = :id AND parent_id = :id;');
        $query->execute(array(
            ':id' => $entry['id'],
            ':case_devicecount' => ($count[0]["COUNT(id)"] - 1)
        ));
      }
    ;
    $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = parent_id ' . $statuslimit . 'AND is_removed = "0" AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY ' . $order_by);
    $query->execute(array(
        ':dateStart' => $dateStart,
        ':dateStop' => $dateStop
    ));
  }
$row_cases = $query->fetchAll(PDO::FETCH_ASSOC);
$query = $kirjuri_database->prepare('SELECT parent_id, device_action FROM exam_requests WHERE id != parent_id AND is_removed = "0" AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY parent_id, device_action ASC');
$query->execute(array(
    ':dateStart' => $dateStart,
    ':dateStop' => $dateStop
));
$row_devices = $query->fetchAll(PDO::FETCH_ASSOC);
echo $twig->render('index.html', array(
    'hakusana' => $hakusana,
    'query_d' => $_GET['d'],
    'query_j' => $_GET['j'],
    'query_s' => $_GET['s'],
    'order_by' => $order_by,
    'dateStart' => $dateStart,
    'row_cases' => $row_cases,
    'row_devices' => $row_devices,
    'count_new' => $count_new,
    'count_open' => $count_open,
    'count_finished' => $count_finished,
    'count_devices' => $count_devices,
    'summed_size' => $summed_size,
    'settings' => $settings,
    'lang' => $_SESSION['lang']
));
?>
