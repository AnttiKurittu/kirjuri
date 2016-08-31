<?php
require_once("./include_functions.php");
$dateStart = date("Y") . ":01:01 00:00:00";
$dateStop = (date("Y") + 1) . ":01:01 00:00:00";
$kirjuri_database = db('kirjuri-database');

$form_data = $_POST;

$form_data['case_name'] = isset($_POST['case_name']) ? $_POST['case_name'] : '';
$form_data['case_file_number'] = isset($_POST['case_file_number']) ? $_POST['case_file_number'] : '';
$form_data['case_investigator'] = isset($_POST['case_investigator']) ? $_POST['case_investigator'] : '';
$form_data['case_investigator_unit'] = isset($_POST['case_investigator_unit']) ? $_POST['case_investigator_unit'] : '';
$form_data['case_investigator_tel'] = isset($_POST['case_investigator_tel']) ? $_POST['case_investigator_tel'] : '';
$form_data['case_investigation_lead'] = isset($_POST['case_investigation_lead']) ? $_POST['case_investigation_lead'] : '';
$form_data['case_confiscation_date'] = isset($_POST['case_confiscation_date']) ? $_POST['case_confiscation_date'] : '';
$form_data['case_crime'] = isset($_POST['case_crime']) ? $_POST['case_crime'] : '';
$form_data['classification'] = isset($_POST['classification']) ? $_POST['classification'] : '';
$form_data['case_suspect'] = isset($_POST['case_suspect']) ? $_POST['case_suspect'] : '';
$form_data['case_request_description'] = isset($_POST['case_request_description']) ? $_POST['case_request_description'] : '';
$form_data['case_urgency'] = isset($_POST['case_urgency']) ? $_POST['case_urgency'] : '';
$form_data['case_urg_justification'] = isset($_POST['case_urg_justification']) ? $_POST['case_urg_justification'] : '';
$form_data['case_requested_action'] = isset($_POST['case_requested_action']) ? $_POST['case_requested_action'] : '';
$form_data['case_contains_mob_dev'] = isset($_POST['case_contains_mob_dev']) ? $_POST['case_contains_mob_dev'] : '';
$form_data['device_type'] = isset($_POST['device_type']) ? $_POST['device_type'] : '';
$form_data['phone_investigator'] = isset($_POST['phone_investigator']) ? $_POST['phone_investigator'] : '';
$form_data['device_location'] = isset($_POST['device_location']) ? $_POST['device_location'] : '?';
$form_data['device_action'] = isset($_POST['device_action']) ? $_POST['device_action'] : '1|?';
$form_data['device_contains_evidence'] = isset($_POST['device_contains_evidence']) ? $_POST['device_contains_evidence'] : '0';
$form_data['device_include_in_report'] = isset($_POST['device_include_in_report']) ? $_POST['device_include_in_report'] : '0';

$form_data['case_urgency'] = substr($form_data['case_urgency'], 0, 1);
$form_data['device_include_in_report'] = substr($form_data['device_include_in_report'], 0, 1);
$form_data['device_contains_evidence'] = substr($form_data['device_contains_evidence'], 0, 1);
$form_data['case_contains_mob_dev'] = substr($form_data['case_contains_mob_dev'], 0, 1);

if ($_GET['type'] === 'examination_request')
  {
    if (empty($form_data['case_file_number']) || empty($form_data['case_investigator']) || empty($form_data['case_investigator_unit']) || empty($form_data['case_investigator_tel']) || empty($form_data['case_investigation_lead']) || empty($form_data['case_confiscation_date']) || empty($form_data['case_crime']) || empty($form_data['case_suspect']) || empty($form_data['case_request_description']) || empty($form_data['case_urgency']) || empty($form_data['case_requested_action']))
      {
        trigger_error("Error", "Fill all required fields.");
        logline("Error", "Not all fields filled.");
      }
    $query = $kirjuri_database->prepare('select case_id FROM exam_requests WHERE case_added_date BETWEEN :dateStart AND :dateStop ORDER BY case_id DESC LIMIT 1 ');
    $query->execute(array(
        ':dateStart' => $dateStart,
        ':dateStop' => $dateStop
    ));
    $case_id = $query->fetch(PDO::FETCH_ASSOC);
    $case_id = $case_id['case_id'] + 1;
    $sql = $kirjuri_database->prepare(' INSERT INTO exam_requests ( id, parent_id, case_id, case_name, case_file_number, case_investigator, case_investigator_unit, case_investigator_tel, case_investigation_lead, case_confiscation_date, last_updated, case_added_date, case_crime, classification, case_suspect, case_request_description, is_removed, case_status, case_urgency, case_urg_justification, case_requested_action, case_contains_mob_dev, case_devicecount ) VALUES ( "", "0", :case_id, :case_name, :case_file_number, :case_investigator, :case_investigator_unit, :case_investigator_tel, :case_investigation_lead, :case_confiscation_date, NOW(), NOW(), :case_crime, :classification, :case_suspect, :case_request_description, "0", "1", :case_urgency, :case_urg_justification, :case_requested_action, :case_contains_mob_dev, "0" );
        UPDATE exam_requests SET parent_id=last_insert_id() WHERE ID=last_insert_id();
        ');
    $sql->execute(array(
        ':case_id' => $case_id,
        ':case_name' => $form_data['case_name'],
        ':case_file_number' => $form_data['case_file_number'],
        ':case_investigator' => $form_data['case_investigator'],
        ':case_investigator_unit' => $form_data['case_investigator_unit'],
        ':case_investigator_tel' => $form_data['case_investigator_tel'],
        ':case_investigation_lead' => $form_data['case_investigation_lead'],
        ':case_confiscation_date' => $form_data['case_confiscation_date'],
        ':case_crime' => $form_data['case_crime'],
        ':classification' => $form_data['classification'],
        ':case_suspect' => $form_data['case_suspect'],
        ':case_request_description' => $form_data['case_request_description'],
        ':case_urgency' => $form_data['case_urgency'],
        ':case_urg_justification' => $form_data['case_urg_justification'],
        ':case_requested_action' => $form_data['case_requested_action'],
        ':case_contains_mob_dev' => $form_data['case_contains_mob_dev']
    ));
    logline("Action", "Added examination request " . $case_id . " / " . $form_data['case_name'] . "");
    $query = $kirjuri_database->prepare('SELECT id FROM exam_requests WHERE case_id=:case_id AND parent_id = id AND case_added_date BETWEEN :dateStart AND :dateStop LIMIT 1');
    $query->execute(array(
        ':case_id' => $case_id,
        ':dateStart' => $dateStart,
        ':dateStop' => $dateStop
    ));
    $row = $query->fetch(PDO::FETCH_ASSOC);
    echo $twig->render('thankyou.html', array(
        'case_id' => $case_id,
        'id' => $row['id'],
        'settings' => $settings,
        'lang' => $_SESSION['lang']
    ));
    exit;
  }
if ($_GET['type'] === 'case_update')
  {
    if ($form_data['forensic_investigator'] !== "")
      {
        $case_status = "2";
      }
    else
      {
        $case_status = "1";
      }
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_name = :case_name, case_file_number = :case_file_number, case_crime = :case_crime, classification = :classification, case_suspect = :case_suspect, case_investigation_lead = :case_investigation_lead, case_investigator = :case_investigator, forensic_investigator = :forensic_investigator, phone_investigator = :phone_investigator, case_investigator_tel = :case_investigator_tel, case_investigator_unit = :case_investigator_unit, case_request_description = :case_request_description, case_confiscation_date = :case_confiscation_date, case_start_date = NOW(), last_updated = NOW(), is_removed = "0", case_contains_mob_dev = :case_contains_mob_dev, case_status = :case_status, case_urgency = :case_urgency where id=:id AND parent_id = :id');
    $sql->execute(array(
        ':case_name' => $form_data['case_name'],
        ':case_file_number' => $form_data['case_file_number'],
        ':case_crime' => $form_data['case_crime'],
        ':classification' => $form_data['classification'],
        ':case_suspect' => $form_data['case_suspect'],
        ':case_investigation_lead' => $form_data['case_investigation_lead'],
        ':case_investigator' => $form_data['case_investigator'],
        ':forensic_investigator' => $form_data['forensic_investigator'],
        ':phone_investigator' => $form_data['phone_investigator'],
        ':case_investigator_tel' => $form_data['case_investigator_tel'],
        ':case_investigator_unit' => $form_data['case_investigator_unit'],
        ':case_request_description' => $form_data['case_request_description'],
        ':case_confiscation_date' => $form_data['case_confiscation_date'],
        ':case_contains_mob_dev' => $form_data['case_contains_mob_dev'],
        ':case_status' => $case_status,
        ':id' => $_GET['db_row'],
        ':case_urgency' => $form_data['case_urgency']
    ));
    logline("Action", "Updated request " . $form_data['case_name'] . "");
    $form_data['returnid'] = $_GET['db_row'];
    header("Location: edit_request.php?case=".$form_data['returnid']."&show_status_message=OK");
    die;
  }

if ($_GET['type'] === 'report_notes') // Save case report notes.
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, last_updated = NOW() where id=:id AND parent_id = :id');
    $sql->execute(array(
        ':id' => $form_data['returnid'],
        ':report_notes' => $form_data['report_notes']
    ));
    header("Location: edit_request.php?case=".$form_data['returnid']."show_status_message=OK&tab=report_notes");
    logline("Action", "Updated report notes, returnid=" . $form_data['returnid'] . "");
    die;
  }

if ($_GET['type'] === 'examiners_notes') // Save examiners private notes
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET examiners_notes = :examiners_notes, last_updated = NOW() where id=:id AND parent_id = :id');
    $sql->execute(array(
        ':id' => $form_data['returnid'],
        ':examiners_notes' => $form_data['examiners_notes']
    ));
    header("Location: edit_request.php?case=".$form_data['returnid']."show_status_message=OK&tab=examiners_notes");
    logline("Action", "Updated examiners notes, returnid=" . $form_data['returnid'] . "");
    die;
  }


if ($_GET['type'] === 'set_removed') // Remove device from case
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() where id=:id AND parent_id != id;
        UPDATE exam_requests SET is_removed = "1" where device_host_id=:id');
    $sql->execute(array(
        ':id' => $_GET['db_row']
    ));
    logline("Action", "Removed device ID " . $_GET['db_row'] . "");
    $sql = $kirjuri_database->prepare('SELECT count(id) from exam_requests where id != parent_id AND parent_id=:id AND is_removed="0"');
    $sql->execute(array(
        ':id' => $_GET['returnid']
    ));
    $devicecount = $sql->fetch(PDO::FETCH_ASSOC);
    $devicecount = $devicecount['count(id)'];
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = :devicecount, last_updated = NOW() where id=:id');
    $sql->execute(array(
        ':devicecount' => $devicecount,
        ':id' => $_GET['returnid']
    ));
    $form_data['returnid'] = $_GET['returnid'];
    header("Location: edit_request.php?case=".$form_data['returnid']."&tab=devices");
    die;
  }

if ($_GET['type'] === 'device_attach') // Associate a media/device with host device
  {
    if (isset($form_data['isanta']))
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = :isanta, last_updated = NOW() where id=:id AND parent_id != id;
        UPDATE exam_requests SET device_is_host = "1" where id = :isanta;');
        $sql->execute(array(
            ':id' => $_GET['db_row'],
            ':isanta' => $form_data['isanta']
        ));
      }
    $form_data['returnid'] = $_GET['returnid'];
    header("Location: edit_request.php?case=".$form_data['returnid']."&tab=devices");
    die;
  }

if ($_GET['type'] === 'device_detach') // Remove device association
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = "0", last_updated = NOW() where id=:id AND parent_id != id');
    $sql->execute(array(
        ':id' => $_GET['db_row']
    ));
    $form_data['returnid'] = $_GET['returnid'];
    header("Location: edit_request.php?case=".$form_data['returnid']."&tab=devices");
    die;
  }

if ($_GET['type'] === 'set_removed_case') // Remove case
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() WHERE id=:id AND parent_id = :id');
    $sql->execute(array(
        ':id' => $form_data['remove_exam_request']
    ));
    logline("Action", "Removed case ID " . $form_data['remove_exam_request'] . "");
    header("Location: index.php");
    die;
  }

if ($_GET['type'] === 'move_all') // Change all device locations and actions
  {
    if ($form_data['device_action'] != "NO_CHANGE")
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() WHERE parent_id=:parent_id');
        $sql->execute(array(
            ':parent_id' => $_GET['returnid'],
            ':device_action' => $form_data['device_action']
        ));
      }
    if ($form_data['device_location'] != "NO_CHANGE")
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() WHERE parent_id=:parent_id');
        $sql->execute(array(
            ':parent_id' => $_GET['returnid'],
            ':device_location' => $form_data['device_location']
        ));
      }
    $form_data['returnid'] = $_GET['returnid'];
    header("Location: edit_request.php?case=".$form_data['returnid']."&tab=devices");
    die;
  }

if ($_GET['type'] === 'update_request_status') // Set case status
  {
    if ($form_data['case_status'] === '1')
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, forensic_investigator = "", phone_investigator = "", case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
      }
    else
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
      }
    $sql->execute(array(
        ':id' => $form_data['returnid'],
        ':case_status' => $form_data['case_status']
    ));

    logline("Action", "Changed request " . $form_data['returnid'] . " status: " . $form_data['case_status'] . "");
    header("Location: edit_request.php?case=".$form_data['returnid']);
    die;
  }

  if ($_GET['type'] === 'change_device_status') // Dynamically set device action
    {
      $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() where id=:id AND parent_id != id');
      $sql->execute(array(
          ':device_action' => $form_data['device_action'],
          ':id' => $_GET['db_row']
      ));

      echo $twig->render('progress_bar.html', array(
          'device_action' => $form_data['device_action'],
          'settings' => $settings
      ));
      exit;
    }

    if ($_GET['type'] === 'change_device_location') // Dynamically set device action
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() where id=:id AND parent_id != id');
        $sql->execute(array(
            ':device_location' => $form_data['device_location'],
            ':id' => $_GET['db_row']
        ));
        exit;
      }

if ($_GET['type'] === 'devicememo')
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, examiners_notes = :examiners_notes, device_type = :device_type, device_manuf = :device_manuf, device_model = :device_model, device_size_in_gb = :device_size_in_gb,
      device_owner = :device_owner, device_os = :device_os, device_time_deviation = :device_time_deviation, last_updated = NOW(),
      case_request_description = :case_request_description, device_item_number = :device_item_number, device_document = :device_document, device_identifier = :device_identifier,
      device_contains_evidence = :device_contains_evidence, device_include_in_report = :device_include_in_report WHERE id = :id AND parent_id != id;
        UPDATE exam_requests SET last_updated = NOW() where id = :parent_id;
        UPDATE exam_requests SET parent_id = :parent_id WHERE id = :id OR device_host_id = :id;');
    $sql->execute(array(
        ':report_notes' => $form_data['report_notes'],
        ':examiners_notes' => $form_data['examiners_notes'],
        ':device_type' => $form_data['device_type'],
        ':device_manuf' => $form_data['device_manuf'],
        ':device_model' => $form_data['device_model'],
        ':device_size_in_gb' => $form_data['device_size_in_gb'],
        ':device_owner' => $form_data['device_owner'],
        ':device_os' => $form_data['device_os'],
        ':device_time_deviation' => $form_data['device_time_deviation'],
        ':case_request_description' => $form_data['case_request_description'],
        ':device_item_number' => $form_data['device_item_number'],
        ':device_document' => $form_data['device_document'],
        ':device_identifier' => $form_data['device_identifier'],
        ':parent_id' => $form_data['parent_id'],
        ':id' => $form_data['id'],
        ':device_include_in_report' => $form_data['device_include_in_report'],
        ':device_contains_evidence' => $form_data['device_contains_evidence']
    ));
    $form_data['returnid'] = $_GET['returnid'];
    logline("Action", "Updated device memo " . $form_data['id'] . "");
    header("Location: device_memo.php?db_row=".$form_data['returnid']."&show_status_message=OK");
    die;
  }

if ($_GET['type'] === 'device')
  {
    if ($form_data['device_host_id'] === "0")
      {
        $device_is_host = "1";
      }
    else
      {
        $device_is_host = "0";
      }
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = case_devicecount + 1, last_updated = NOW() WHERE id = :parent_id');
    $sql->execute(array(
        ':parent_id' => $form_data['parent_id']
    ));
    $kirjuri_database = db('kirjuri-database');
    $sql = $kirjuri_database->prepare('INSERT INTO exam_requests (id, parent_id, device_host_id, device_type, device_manuf, device_model, device_identifier, device_location, device_item_number, device_document, device_time_deviation, device_os, device_size_in_gb, device_is_host, device_owner, device_include_in_report, device_contains_evidence, case_added_date, case_request_description, device_action, is_removed, last_updated ) VALUES ( "", :parent_id, :device_host_id, :device_type, :device_manuf, :device_model, :device_identifier, :device_location, :device_item_number, :device_document, :device_time_deviation, :device_os, :device_size_in_gb, :device_is_host, :device_owner, "1", "0", NOW(), :case_request_description, :device_action, :is_removed, NOW());
        ');
    $sql->execute(array(
        ':parent_id' => $form_data['parent_id'],
        ':device_host_id' => $form_data['device_host_id'],
        ':device_type' => $form_data['device_type'],
        ':device_manuf' => $form_data['device_manuf'],
        ':device_model' => $form_data['device_model'],
        ':device_identifier' => $form_data['device_identifier'],
        ':device_location' => $form_data['device_location'],
        ':device_item_number' => $form_data['device_item_number'],
        ':device_document' => $form_data['device_document'],
        ':device_time_deviation' => $form_data['device_time_deviation'],
        ':device_os' => $form_data['device_os'],
        ':device_size_in_gb' => $form_data['device_size_in_gb'],
        ':device_is_host' => $device_is_host,
        ':device_owner' => $form_data['device_owner'],
        ':case_request_description' => $form_data['case_request_description'],
        ':device_action' => $form_data['device_action'],
        ':is_removed' => $form_data['is_removed']
    ));


    logline("Action", "Added device " . $form_data['device_type'] . " " . $form_data['device_manuf'] . " " . $form_data['device_model'] . " with id [" . $form_data['device_identifier'] . "] to case " . $form_data['parent_id'] . "");
    header("Location: edit_request.php?case=".$form_data['parent_id']."&tab=devices");
    die;
  }

// Default to error if no handlers

header("Location: index.php");
trigger_error('submit.php called with erroneous value.');
?>
