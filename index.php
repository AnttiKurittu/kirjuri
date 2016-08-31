<?php
require_once("./include_functions.php");

$sort_j = isset($_GET['j']) ? $_GET['j'] : '';
$sort_d = isset($_GET['d']) ? $_GET['d'] : '';
$sort_s = isset($_GET['s']) ? $_GET['s'] : '';
$has_attachments[] = "";

if (empty($_GET['year']))
  {
    $year = date("Y");
  }
else
  {
    $year = $_GET['year'];
  }
$dateStart = $year . "-01-01 00:00:00";
$dateStop = ($year + 1) . "-01-01 00:00:00";
$order_by = "case_status ASC, case_id DESC";
$order_direction = $statuslimit = "";
$search_term = "";
$kirjuri_database = db('kirjuri-database');


if ($sort_d === "a")
  {
    $order_direction = " ASC";
  }
else
  {
    $order_direction = " DESC";
  }

if (isset($sort_j))
  {
    if ($sort_j === "1")
      {
        $order_by = "case_id" . $order_direction;
      }
    if ($sort_j === "2")
      {
        $order_by = "case_name" . $order_direction;
      }
    if ($sort_j === "3")
      {
        $order_by = "case_file_number" . $order_direction;
      }
    if ($sort_j === "4")
      {
        $order_by = "case_crime" . $order_direction;
      }
    if ($sort_j === "5")
      {
        $order_by = "case_suspect" . $order_direction;
      }
    if ($sort_j === "6")
      {
        $order_by = "case_investigator" . $order_direction;
      }
    if ($sort_j === "7")
      {
        $order_by = "forensic_investigator" . $order_direction;
      }
    if ($sort_j === "8")
      {
        $order_by = "phone_investigator" . $order_direction;
      }
    if ($sort_j === "9")
      {
        $order_by = "case_added_date" . $order_direction;
      }
  }
if (isset($sort_s))
  {
    if ($sort_s === "1")
      {
        $statuslimit = 'AND case_status = "1" ';
      }
    if ($sort_s === "2")
      {
        $statuslimit = 'AND case_status = "2" ';
      }
    if ($sort_s === "3")
      {
        $statuslimit = 'AND case_status = "3" ';
      }
  }
if (isset($_GET['search']))
  {
    $search_term = substr($_GET['search'], 0, 128);
    $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = id ' . $statuslimit . 'AND is_removed = "0" AND MATCH (case_name,case_suspect,case_file_number,case_investigator,forensic_investigator,phone_investigator,case_investigation_lead,case_investigator_unit,case_crime,case_requested_action,case_request_description,report_notes,examiners_notes,device_manuf,device_model,device_identifier,device_owner) AGAINST (:search_term IN BOOLEAN MODE) AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY ' . $order_by);
    $query->execute(array(
        ':search_term' => $search_term,
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

// Scan attachment directories and return directories that have files as an array.
$subdirectories = scandir("attachments/");
foreach(glob('attachments/*', GLOB_ONLYDIR) as $dir) {
    $allFiles = scandir($dir); // Or any other directory
    $files = array_diff($allFiles, array('.', '..'));
    if(!empty($files)) {
      $dir_has_files = explode("/", $dir);
      $has_attachments[] = $dir_has_files[1];
    } else {
      logline('Action', 'Empty directory autoremoved: '.$dir);
      rmdir($dir); // Remove empty directories
    }
}

echo $twig->render('index.html', array(
    'has_attachments' => $has_attachments,
    'search_term' => $search_term,
    'query_d' => $sort_d,
    'query_j' => $sort_j,
    'query_s' => $sort_s,
    'order_by' => $order_by,
    'dateStart' => $dateStart,
    'row_cases' => $row_cases,
    'row_devices' => $row_devices,
    'settings' => $settings,
    'lang' => $_SESSION['lang']
));
?>
