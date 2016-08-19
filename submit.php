<?php
require_once("./include_functions.php");
$dateStart = date("Y") . ":01:01 00:00:00";
$dateStop = (date("Y") + 1) . ":01:01 00:00:00";
$kirjuri_database = db('kirjuri-database');
if ($_GET['type'] === 'juttu')
  {
    if (empty($_POST['case_file_number']) || empty($_POST['case_investigator']) || empty($_POST['case_investigator_unit']) || empty($_POST['case_investigator_tel']) || empty($_POST['case_investigation_lead']) || empty($_POST['case_confiscation_date']) || empty($_POST['case_crime']) || empty($_POST['case_suspect']) || empty($_POST['case_request_description']) || empty($_POST['case_urgency']) || empty($_POST['case_requested_action']))
      {
        virhe("Error", "Fill all required fields.");
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
        ':case_name' => $_POST['case_name'],
        ':case_file_number' => $_POST['case_file_number'],
        ':case_investigator' => $_POST['case_investigator'],
        ':case_investigator_unit' => $_POST['case_investigator_unit'],
        ':case_investigator_tel' => $_POST['case_investigator_tel'],
        ':case_investigation_lead' => $_POST['case_investigation_lead'],
        ':case_confiscation_date' => $_POST['case_confiscation_date'],
        ':case_crime' => $_POST['case_crime'],
        ':classification' => $_POST['classification'],
        ':case_suspect' => $_POST['case_suspect'],
        ':case_request_description' => $_POST['case_request_description'],
        ':case_urgency' => $_POST['case_urgency'],
        ':case_urg_justification' => $_POST['case_urg_justification'],
        ':case_requested_action' => $_POST['case_requested_action'],
        ':case_contains_mob_dev' => $_POST['case_contains_mob_dev']
    ));
    logline("Action", "Added examination request " . $case_id . " / " . $_POST['case_name'] . "");
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
    if ($_POST['forensic_investigator'] !== "")
      {
        $case_status = "2";
      }
    else
      {
        $case_status = "1";
      }
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_name = :case_name, case_file_number = :case_file_number, case_crime = :case_crime, classification = :classification, case_suspect = :case_suspect, case_investigation_lead = :case_investigation_lead, case_investigator = :case_investigator, forensic_investigator = :forensic_investigator, phone_investigator = :phone_investigator, case_investigator_tel = :case_investigator_tel, case_investigator_unit = :case_investigator_unit, case_request_description = :case_request_description, case_confiscation_date = :case_confiscation_date, case_start_date = NOW(), last_updated = NOW(), is_removed = "0", case_contains_mob_dev = :case_contains_mob_dev, case_status = :case_status, case_urgency = :case_urgency where id=:id AND parent_id = :id');
    $sql->execute(array(
        ':case_name' => $_POST['case_name'],
        ':case_file_number' => $_POST['case_file_number'],
        ':case_crime' => $_POST['case_crime'],
        ':classification' => $_POST['classification'],
        ':case_suspect' => $_POST['case_suspect'],
        ':case_investigation_lead' => $_POST['case_investigation_lead'],
        ':case_investigator' => $_POST['case_investigator'],
        ':forensic_investigator' => $_POST['forensic_investigator'],
        ':phone_investigator' => $_POST['phone_investigator'],
        ':case_investigator_tel' => $_POST['case_investigator_tel'],
        ':case_investigator_unit' => $_POST['case_investigator_unit'],
        ':case_request_description' => $_POST['case_request_description'],
        ':case_confiscation_date' => $_POST['case_confiscation_date'],
        ':case_contains_mob_dev' => $_POST['case_contains_mob_dev'],
        ':case_status' => $case_status,
        ':id' => $_GET['db_row'],
        ':case_urgency' => $_POST['case_urgency']
    ));
    logline("Action", "Updated request " . $_POST['case_name'] . "");
    echo $twig->render('return.html', array(
        'returnid' => $_GET['db_row'],
        'showStatus' => 'OK',
        'anchor' => "#juttu"
    ));
    exit;
  }
if ($_GET['type'] === 'report_notes')
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, last_updated = NOW() where id=:id AND parent_id = :id');
    $sql->execute(array(
        ':id' => $_POST['returnid'],
        ':report_notes' => $_POST['report_notes']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_POST['returnid'],
        'showStatus' => 'OK',
        'anchor' => "&tab=report_notes"
    ));
    logline("Action", "Updated report notes, returnid=" . $_POST['returnid'] . "");
    exit;
  }
if ($_GET['type'] === 'examiners_notes')
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET examiners_notes = :examiners_notes, last_updated = NOW() where id=:id AND parent_id = :id');
    $sql->execute(array(
        ':id' => $_POST['returnid'],
        ':examiners_notes' => $_POST['examiners_notes']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_POST['returnid'],
        'showStatus' => 'OK',
        'anchor' => "&tab=examiners_notes"
    ));
    logline("Action", "Updated examiners notes, returnid=" . $_POST['returnid'] . "");
    exit;
  }
if ($_GET['type'] === 'set_removed')
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
    echo $twig->render('return.html', array(
        'returnid' => $_GET['returnid'],
        'anchor' => "&tab=devices"
    ));
    exit;
  }
if ($_GET['type'] === 'to_report')
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_include_in_report = "1", last_updated = NOW() where id=:id AND parent_id != id');
    $sql->execute(array(
        ':id' => $_GET['db_row']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_GET['returnid'],
        'anchor' => "&tab=devices"
    ));
    exit;
  }
if ($_GET['type'] === 'not_to_report')
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_include_in_report = "0", last_updated = NOW() where id=:id AND parent_id != id');
    $sql->execute(array(
        ':id' => $_GET['db_row']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_GET['returnid'],
        'anchor' => "&tab=devices"
    ));
    exit;
  }
if ($_GET['type'] === 'device_detach')
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = "0", last_updated = NOW() where id=:id AND parent_id != id');
    $sql->execute(array(
        ':id' => $_GET['db_row']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_GET['returnid'],
        'anchor' => "&tab=devices"
    ));
    exit;
  }
if ($_GET['type'] === 'device_attach')
  {
    if (isset($_POST['isanta']))
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = :isanta, last_updated = NOW() where id=:id AND parent_id != id;
        UPDATE exam_requests SET device_is_host = "1" where id = :isanta;');
        $sql->execute(array(
            ':id' => $_GET['db_row'],
            ':isanta' => $_POST['isanta']
        ));
      }
    echo $twig->render('return.html', array(
        'returnid' => $_GET['returnid'],
        'anchor' => "&tab=devices"
    ));
    exit;
  }
if ($_GET['type'] === 'move_case')
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, device_action = :device_action, last_updated = NOW() WHERE id=:id');
    $sql->execute(array(
        ':id' => $_GET['db_row'],
        ':device_location' => $_POST['device_location'],
        ':device_action' => $_POST['device_action']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_GET['returnid'],
        'anchor' => "&tab=devices",
        'deviceid' => $_GET['db_row']
    ));
    exit;
  }
if ($_GET['type'] === 'set_removed_case')
  {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() WHERE id=:id AND parent_id = :id');
    $sql->execute(array(
        ':id' => $_POST['remove_exam_request']
    ));
    logline("Action", "Removed case ID " . $_GET['db_row'] . "");
    echo $twig->render('return.html', array(
        'returnpage' => 'etusivu'
    ));
    exit;
  }
if ($_GET['type'] === 'move_all')
  {
    if ($_POST['device_action'] != "NO_CHANGE")
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() WHERE parent_id=:parent_id');
        $sql->execute(array(
            ':parent_id' => $_GET['returnid'],
            ':device_action' => $_POST['device_action']
        ));
      }
    if ($_POST['device_location'] != "NO_CHANGE")
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() WHERE parent_id=:parent_id');
        $sql->execute(array(
            ':parent_id' => $_GET['returnid'],
            ':device_location' => $_POST['device_location']
        ));
      }
    echo $twig->render('return.html', array(
        'returnid' => $_GET['returnid'],
        'anchor' => "&tab=devices"
    ));
    exit;
  }
if ($_GET['type'] === 'paatos')
  {
    if ($_POST['case_status'] === '1')
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, forensic_investigator = "", case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
      }
    else
      {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
      }
    $sql->execute(array(
        ':id' => $_POST['returnid'],
        ':case_status' => $_POST['case_status']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_POST['returnid'],
        'anchor' => "#tila"
    ));
    logline("Action", "Changed request " . $_POST['returnid'] . " status: " . $_POST['case_status'] . "");
    exit;
  }
if ($_GET['type'] === 'devicememo')
  {
    if ($_POST['device_contains_evidence'] === "1")
      {
        $device_contains_evidence = "1";
      }
    else
      {
        $device_contains_evidence = "0";
      }
    if ($_POST['device_include_in_report'] === "1")
      {
        $device_include_in_report = "1";
      }
    else
      {
        $device_include_in_report = "0";
      }
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, device_type = :device_type, device_manuf = :device_manuf, device_model = :device_model, device_size_in_gb = :device_size_in_gb,
      device_owner = :device_owner, device_os = :device_os, device_time_deviation = :device_time_deviation, last_updated = NOW(),
      case_request_description = :case_request_description, device_item_number = :device_item_number, device_document = :device_document, device_identifier = :device_identifier,
      device_contains_evidence = :device_contains_evidence, device_include_in_report = :device_include_in_report, device_location = :device_location,
      device_action = :device_action WHERE id = :id AND parent_id != id;
        UPDATE exam_requests SET last_updated = NOW() where id = :parent_id;
        UPDATE exam_requests SET parent_id = :parent_id WHERE id = :id OR device_host_id = :id;');
    $sql->execute(array(
        ':report_notes' => $_POST['report_notes'],
        ':device_type' => $_POST['device_type'],
        ':device_manuf' => $_POST['device_manuf'],
        ':device_model' => $_POST['device_model'],
        ':device_size_in_gb' => $_POST['device_size_in_gb'],
        ':device_owner' => $_POST['device_owner'],
        ':device_os' => $_POST['device_os'],
        ':device_time_deviation' => $_POST['device_time_deviation'],
        ':case_request_description' => $_POST['case_request_description'],
        ':device_item_number' => $_POST['device_item_number'],
        ':device_document' => $_POST['device_document'],
        ':device_identifier' => $_POST['device_identifier'],
        ':parent_id' => $_POST['parent_id'],
        ':id' => $_POST['id'],
        ':device_include_in_report' => $device_include_in_report,
        ':device_contains_evidence' => $device_contains_evidence,
        ':device_location' => $_POST['device_location'],
        ':device_action' => $_POST['device_action']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_GET['returnid'],
        'returnpage' => 'devicememo'
    ));
    logline("Action", "Updated device memo " . $_POST['id'] . "");
    exit;
  }
if ($_GET['type'] === 'device')
  {
    if ($_POST['device_host_id'] === "0")
      {
        $device_is_host = "1";
      }
    else
      {
        $device_is_host = "0";
      }
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = case_devicecount + 1, last_updated = NOW() WHERE id = :parent_id');
    $sql->execute(array(
        ':parent_id' => $_POST['parent_id']
    ));
    $kirjuri_database = db('kirjuri-database');
    $sql = $kirjuri_database->prepare('INSERT INTO exam_requests (id, parent_id, device_host_id, device_type, device_manuf, device_model, device_identifier, device_location, device_item_number, device_document, device_time_deviation, device_os, device_size_in_gb, device_is_host, device_owner, device_include_in_report, device_contains_evidence, case_added_date, case_request_description, device_action, is_removed, last_updated ) VALUES ( "", :parent_id, :device_host_id, :device_type, :device_manuf, :device_model, :device_identifier, :device_location, :device_item_number, :device_document, :device_time_deviation, :device_os, :device_size_in_gb, :device_is_host, :device_owner, "1", "0", NOW(), :case_request_description, :device_action, :is_removed, NOW());
        ');
    $sql->execute(array(
        ':parent_id' => $_POST['parent_id'],
        ':device_host_id' => $_POST['device_host_id'],
        ':device_type' => $_POST['device_type'],
        ':device_manuf' => $_POST['device_manuf'],
        ':device_model' => $_POST['device_model'],
        ':device_identifier' => $_POST['device_identifier'],
        ':device_location' => $_POST['device_location'],
        ':device_item_number' => $_POST['device_item_number'],
        ':device_document' => $_POST['device_document'],
        ':device_time_deviation' => $_POST['device_time_deviation'],
        ':device_os' => $_POST['device_os'],
        ':device_size_in_gb' => $_POST['device_size_in_gb'],
        ':device_is_host' => $device_is_host,
        ':device_owner' => $_POST['device_owner'],
        ':case_request_description' => $_POST['case_request_description'],
        ':device_action' => $_POST['device_action'],
        ':is_removed' => $_POST['is_removed']
    ));
    echo $twig->render('return.html', array(
        'returnid' => $_POST['parent_id'],
        'anchor' => "&tab=devices"
    ));
    logline("Action", "Added device " . $_POST['device_type'] . " " . $_POST['device_manuf'] . " " . $_POST['device_model'] . " tunnisteella [" . $_POST[device_identifier] . "] juttuun " . $_POST['parent_id'] . "");
    exit;
  }
echo "Index page error.";
logline("Error", "Index page error");
exit;
?>
