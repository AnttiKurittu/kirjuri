<?php
/*
* This API is a work in progress, do not use for production. It is included here for completeness.
*
// curl http://kirjuri/api.php?q=/2017/ - Get all cases for that year
// curl http://kirjuri/api.php?q=/2017/1/ - Get data for case 1/2017
// curl http://kirjuri/api.php?q=/2017/1/device_type - Get field data for case 1/2017 device_type

// curl -X POST -i -d @./request.json "http://localhost:7888/kirjuri-wamp/api.php"

function connect_database($database) {
  $mysql_config = include('conf/mysql_credentials.php');
  if ($database === 'kirjuri-database') {
    try {
      $kirjuri_database = new PDO('mysql:host=localhost;dbname='.$mysql_config['mysql_database'].'', $mysql_config['mysql_username'], $mysql_config['mysql_password']);
      $kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $kirjuri_database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
      $kirjuri_database->exec('SET NAMES utf8');
      return $kirjuri_database;
    } catch (PDOException $e) {
      echo 'Database error: '.$e->getMessage();
      die;
    }
  }
}

function array_trim($array) {
  foreach($array as $key => $value) {
    if( ($value === "") || ($value === null) ) {
      unset($array[$key]);
    }
  }
  return $array;
}

function verify_ownership($username, $uid) {

  $kirjuri_database = connect_database('kirjuri-database');
  $query = $kirjuri_database->prepare('SELECT case_owner FROM exam_requests WHERE id = :id');
  $query->execute(array(
    ':id' => $uid,
  ));
  $output = $query->fetch(PDO::FETCH_ASSOC);
  if (empty($output['case_owner'])) {
    return true;
  } else {
    $case_owners = explode(";", $output['case_owner']);
    if(in_array($username, $case_owners)) {
      return true;
    } else {
      http_response_code(403);
      die;
    }
  }
}

// ------

$key_found = false;
$api_key = preg_replace('/[^a-zA-Z0-9]/', '', ($_GET['key']));
try {
  // Read users from database to settings.
  $kirjuri_database = connect_database('kirjuri-database');
  $query = $kirjuri_database->prepare('SELECT * from users');
  $query->execute();
  $users = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  echo 'Database error: '.$e->getMessage();
  die;
}
foreach($users as $user) {
  if (($api_key === hash('sha1', $user['username'].$user['password']) && (strpos($user['flags'], 'A') !== false) && (strpos($user['flags'], 'I') === false))) {
    $key_found = true;
    $api_user = $user['username'];
  }
}
if ($key_found === false) {
  http_response_code(403);
  die;
}

// ---

$valid_operations = array('GET', 'POST', 'PUT', 'DELETE');
if (in_array($_SERVER['REQUEST_METHOD'], $valid_operations) === false) {
  http_response_code(405);
  die;
}

$input = json_decode(file_get_contents('php://input'), true);

if (isset($_GET['q'])) {
  $path = explode('/', trim($_GET['q'],'/'));
  $year = $path[0];
  if (isset($path[0])) {
    switch($path[0]) {
      case 'case':
        if ($path[1] !== "new") {
          $case_year = $path[1];
          $case_id = $path[2];
          $date_range = array('start' => $case_year.'-01-01 00:00:00', 'stop' => ($case_year + 1).'-01-01 00:00:00');
        } else {
          unset($input['examination_request']['id']);
          unset($input['examination_request']['parent_id']);
          unset($input['examination_request']['case_id']);
          unset($input['examination_request']['is_removed']);
          unset($input['examination_request']['case_status']);
          unset($input['examination_request']['case_added_date']);
          unset($input['examination_request']['case_start_date']);
          unset($input['examination_request']['case_devicecount']);
          unset($input['examination_request']['last_updated']);
        }
        break;
      case 'uid':
        $id = $path[1];
        break;
      default:
        http_response_code(500);
        die;
    }
  }
}

$output = array();

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':
    if($path[0] === 'case') {
    $kirjuri_database = connect_database('kirjuri-database');
    $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE case_id = :case_id AND is_removed != "1" AND case_added_date BETWEEN :dateStart AND :dateStop');
    $query->execute(array(
      ':case_id' => $case_id,
      ':dateStart' => $date_range['start'],
      ':dateStop' => $date_range['stop']
    ));
    $output['examination_request'] = $query->fetch(PDO::FETCH_ASSOC);
    verify_ownership($api_user, $output['examination_request']['id']);
    if ($output['examination_request'] === false) {
      http_response_code(404);
      die;
    }
    $output['examination_request'] = array_trim($output['examination_request']);
    $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id != parent_id AND parent_id = :id AND is_removed != "1"');
    $query->execute(array(
      ':id' => $output['examination_request']['id'],
    ));
    $output['device'] = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach($output['device'] as $key => $value) {
      $output['device'][$key] = array_trim($value);
    }
  } elseif($path[0] === 'uid') {
    $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = :id AND is_removed != "1"');
    $query->execute(array(
      ':id' => $id
    ));
    $output = $query->fetch(PDO::FETCH_ASSOC);
    if ($output === false) {
      http_response_code(404);
      die;
    }
    verify_ownership($api_user, $output['parent_id']);
    $output = array_trim($output);
    if($output['parent_id'] === $output['id']) {
      $output_temp['examination_request'] = $output;
      $output = $output_temp;
    } else {
      $output_temp['device'] = $output;
      $output = $output_temp;
    }
    unset($output_temp);
    }
    break;

  case 'POST':
    if ( ($path[0] === "case") && ($path[1] === "new") ) {
      $input = $input['examination_request'];
      $date_range = array('start' => date('Y').'-01-01 00:00:00', 'stop' => (date('Y') + 1).'-01-01 00:00:00');
      $query = $kirjuri_database->prepare('select (case_id + 1) AS case_id FROM exam_requests WHERE case_added_date BETWEEN :dateStart AND :dateStop ORDER BY case_id DESC LIMIT 1 ');
      $query->execute(array(
        ':dateStart' => $date_range['start'],
        ':dateStop' => $date_range['stop']
      ));
      $next = $query->fetch(PDO::FETCH_ASSOC);
      $query_builder = 'INSERT INTO exam_requests (id, parent_id, case_id, is_removed, case_status, case_added_date, case_start_date, last_updated, case_devicecount, ';
      foreach($input as $key => $value) {
        $query_builder .= $key . ', ';
      }
      $query_builder = substr($query_builder, 0, -2);
      $query_builder .= ') VALUES ( NULL, "0", '. $next['case_id'] . ', "0", "1", NOW(), "", NOW(), "0", ';
      foreach($input as $key => $value) {
        if($key !== "id") {
          $query_builder .= ':' . $key . ', ';
        }
      }
      $pdo_query = $query_builder = substr($query_builder, 0, -2) . "); UPDATE exam_requests SET parent_id=last_insert_id() WHERE id=last_insert_id();";
      $kirjuri_database = connect_database('kirjuri-database');
      $query = $kirjuri_database->prepare($pdo_query);
      $pdo_data = array();
      foreach($input as $key => $value) {
        $pdo_data[":" . $key] = $value;
      }
      $query->execute($pdo_data);
      $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = last_insert_id()');
      $query->execute();
      $output['examination_request'] = array_trim($query->fetch(PDO::FETCH_ASSOC));
      //$output = $query->fetch(PDO::FETCH_ASSOC);
      break;
    }
    die;
}


if (!empty($output)) {
    echo json_encode($output, JSON_PRETTY_PRINT);
} else {
    echo json_encode(array(), JSON_PRETTY_PRINT);
}
echo "\r\n";
die;


/*  if(isset($_GET['uid'])) {
    if (verify_ownership($api_user, $case_id, $year) === true) {
      $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = :id AND is_removed != "1"');
      $query->execute(array(
        ':id' => $_GET['uid'],
        ':dateStart' => $date_range['start'],
        ':dateStop' => $date_range['stop']
      ));
      $output = $query->fetch(PDO::FETCH_ASSOC);
    } else {
      http_response_code(403);
      die;
    }
  }*/
/*
  case 'PUT':
    // UPDATE CASE DETAILS
    $query = $kirjuri_database->prepare('SELECT id FROM exam_requests WHERE case_id = case_id AND is_removed != "1" AND case_added_date BETWEEN :dateStart AND :dateStop');
    $query->execute(array(
      ':case_id' => $case_id,
      ':dateStart' => $date_range['start'],
      ':dateStop' => $date_range['stop']
    ));
    $query_builder = "";
    $data = json_decode('php://input', true);
    foreach($data as $key => $value) {
      if ($key === "id" ) {
        $query_builder .= 'INSERT INTO exam_requests (id, last_updated, case_added_date, is_removed, case_status, ';
      } else {
        $query_builder .= $key . ', ';
      }
    }
    $query_builder = substr($query_builder, 0, -2);
    $query_builder .= ') VALUES ( NULL, NOW(), NOW(), "0", "3", ';
    foreach($data as $key => $value) {
      if($key !== "id") {
        $query_builder .= ':' . $key . ', ';
      }
    }
    $pdo_query = $query_builder = substr($query_builder, 0, -2) . ");";
    $kirjuri_database = connect_database('kirjuri-database');
    $query = $kirjuri_database->prepare($pdo_query);
    $pdo_data = array();
    foreach($data as $key => $value) {
      $pdo_data[":" . $key] = $value;
    }
    $query->execute($pdo_data);
    //$output = $query->fetch(PDO::FETCH_ASSOC);
    break;*/


/*    $case_owners = explode(";", $output['case_owner']);
    if (!in_array($_SESSION['user']['username'], $case_owners))
    {
      http_response_code(403);
      die;
    }*/

/*foreach ($users as $user) {
    if (($key === hash('sha1', $user['username'].$user['password']) && (strpos($user['flags'], 'A') !== false) && (strpos($user['flags'], 'I') === false))) {
        $key_found = true;
        break;
    }
}
*/
