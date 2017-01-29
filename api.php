<?php

require_once './include_functions.php';

function return_with_code($i)
{
    if ($i === '403') {
        header('HTTP/1.0 403 Forbidden');
        header('X-Message: API access denied. Key error, API access denied or user account disabled.');
        die;
    } elseif ($i === '500') {
        header('HTTP/1.0 500 Internal server error');
        header('X-Message: API error.');
        die;
    } elseif ($i === '200') {
        header('X-Message: Fields updated.');
        die;
    }
}
// Set which year to read from, default to current year.
if (isset($_GET['year'])) {
    $year = filter_numbers($_GET['year']);
} else {
    $year = date('y');
}

// Define date range.

$dateRange = array('start' => $year.'-01-01 00:00:00', 'stop' => ($year + 1).'-01-01 00:00:00');
$key = preg_replace('/[^a-z0-9]/', '', (substr($_GET['key'], 0, 40)));
$request_id = filter_numbers(substr($_GET['id'], 0, 9));
$key_found = false;
$output = array();

$operation = substr(filter_letters_and_numbers($_GET['operation']), 0, 6);

if (in_array($operation, array(
  'add',
  'update',
  'get',
  'info',
  'find',
)) === false) {
    return_with_code('500');
}

foreach ($_SESSION['all_users'] as $user) {
    if (($key === hash('sha1', $user['username'].$user['password']) && (strpos($user['flags'], 'A') !== false) && (strpos($user['flags'], 'I') === false))) {
        $_SESSION['user'] = $user;
        $key_found = true;
        break;
    }
}

if ($key_found === false) {
    return_with_code('403');
} else {
    // Get information about a case or device with UID
  if ($operation === 'get') {
      $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = :id');
      $query->execute(array(
      ':id' => $request_id,
    ));
      $output = $query->fetchAll(PDO::FETCH_ASSOC);
  }
  // Get information on cases in Kirjuri.
  elseif ($operation === 'find') {
      $search_term = substr($_POST['find'], 0, 128);
      $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id = parent_id AND is_removed = "0" AND MATCH (
      case_name,
      case_suspect,
      case_file_number,
      case_investigator,
      forensic_investigator,
      phone_investigator,
      case_investigation_lead,
      case_investigator_unit,
      case_crime,
      case_requested_action,
      case_request_description,
      report_notes,
      examiners_notes)
      AGAINST
      (:search_term IN BOOLEAN MODE) AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY id');
      $query->execute(array(
      ':search_term' => $search_term,
      ':dateStart' => $dateRange['start'],
      ':dateStop' => $dateRange['stop'],
    ));
      $output['cases'] = $query->fetchAll(PDO::FETCH_ASSOC);
      $query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id != parent_id AND is_removed = "0" AND MATCH (
      report_notes,
      examiners_notes,
      device_manuf,
      device_model,
      device_identifier,
      device_owner)
      AGAINST
      (:search_term IN BOOLEAN MODE) AND case_added_date BETWEEN :dateStart AND :dateStop ORDER BY id');
      $query->execute(array(
      ':search_term' => $search_term,
      ':dateStart' => $dateRange['start'],
      ':dateStop' => $dateRange['stop'],
    ));
      $output['devices'] = $query->fetchAll(PDO::FETCH_ASSOC);
  }
  // Update case information fields.
  elseif ($operation === 'info') {
      $query = $kirjuri_database->prepare('SELECT id, case_id, case_name, case_status, forensic_investigator, phone_investigator FROM exam_requests WHERE id = parent_id');
      $query->execute();
      $output['cases'] = $query->fetchAll(PDO::FETCH_ASSOC);
      $query = $kirjuri_database->prepare('SELECT id, device_type, device_manuf, device_model, device_identifier, device_owner, device_action, device_location FROM exam_requests WHERE id != parent_id');
      $query->execute();
      $output['devices'] = $query->fetchAll(PDO::FETCH_ASSOC);
  }
  // Update case information fields.
  elseif ($operation === 'update') {
      $build_query = 'UPDATE exam_requests SET last_updated = NOW()';

    // This loop will build an SQL query out of the POST fields submitted.
    foreach ($_POST as $key => $field) {
        $key = preg_replace('/[^a-zA-Z_]/', '', $key);
        // Do not overwrite existing data for report notes or examination notes but append instead.
        if (($key === 'examiners_notes') || ($key === 'report_notes')) {
            $field = '<p>'.$field.'</p>';
            $build_query = $build_query.', '.$key.' = concat(ifnull('.$key.',""), '.$kirjuri_database->quote($field).')';
        } else {
            $build_query = $build_query.', '.$key.' = '.$kirjuri_database->quote($field);
        }
    }

      $build_query = $build_query.' WHERE id = :id';

      try {
          $query = $kirjuri_database->prepare($build_query);
          $query->execute(array(
        ':id' => $request_id,
      ));
          logline('0', 'API', 'Case updated.');
      } catch (Exception $e) {
          echo $e;
          return_with_code('500');
      }
  }
  // Add a new case to Kirjuri
  elseif ($operation === 'add') {
      try {
          $query = $kirjuri_database->prepare('select case_id FROM exam_requests WHERE case_added_date BETWEEN :dateStart AND :dateStop ORDER BY case_id DESC LIMIT 1 ');
          $query->execute(array(
        ':dateStart' => $dateRange['start'],
        ':dateStop' => $dateRange['stop'],
      ));
          $case_id = $query->fetch(PDO::FETCH_ASSOC);
          $case_id = $case_id['case_id'] + 1;
          $query = $kirjuri_database->prepare('INSERT INTO exam_requests
      (parent_id,
      case_id,
      case_name,
      case_file_number,
      case_investigator,
      case_investigator_unit,
      case_investigator_tel,
      case_investigation_lead,
      case_confiscation_date,
      forensic_investigator,
      phone_investigator,
      last_updated,
      case_added_date,
      case_crime,
      classification,
      case_suspect,
      case_request_description,
      is_removed,
      case_status,
      case_urgency,
      case_urg_justification,
      case_requested_action,
      case_contains_mob_dev,
      case_devicecount )
      VALUES
      ("0",
      :case_id,
      :case_name,
      :case_file_number,
      :case_investigator,
      :case_investigator_unit,
      :case_investigator_tel,
      :case_investigation_lead,
      :case_confiscation_date,
      :forensic_investigator,
      :phone_investigator,
      NOW(),
      NOW(),
      :case_crime,
      :classification,
      :case_suspect,
      :case_request_description,
      "0",
      "1",
      :case_urgency,
      :case_urg_justification,
      :case_requested_action,
      :case_contains_mob_dev,
      "0");
      UPDATE exam_requests SET parent_id=last_insert_id() WHERE ID=last_insert_id();');
          $query->execute(array(
        ':case_id' => $case_id,
        ':case_name' => $_POST['case_name'],
        ':case_file_number' => $_POST['case_file_number'],
        ':forensic_investigator' => $_POST['forensic_investigator'],
        ':phone_investigator' => $_POST['phone_investigator'],
        ':case_investigator' => $_POST['case_investigator'],
        ':case_investigator' => $_POST['case_investigator'],
        ':case_investigator_unit' => $_POST['case_investigator_unit'],
        ':case_investigator_tel' => $_POST['case_investigator_tel'],
        ':case_investigation_lead' => $_POST['case_investigation_lead'],
        ':case_confiscation_date' => $_POST['case_confiscation_date'],
        ':case_crime' => $_POST['case_crime'],
        ':classification' => $_POST['classification'],
        ':case_suspect' => $_POST['case_suspect'],
        ':case_request_description' => $_POST['case_request_description'],
        ':case_urgency' => filter_numbers($_POST['case_urgency']),
        ':case_urg_justification' => $_POST['case_urg_justification'],
        ':case_requested_action' => $_POST['case_requested_action'],
        ':case_contains_mob_dev' => filter_numbers($_POST['case_contains_mob_dev']),
      ));
          logline('0', 'API', 'Row inserted.');
      } catch (Exception $e) {
          return_with_code('500');
      }
  }
}

if (!empty($output)) {
    echo json_encode($output, JSON_PRETTY_PRINT);
} else {
    // Return empty.
}
echo "\r\n";
die;
