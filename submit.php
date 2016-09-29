<?php

require_once './include_functions.php';

$year = date('Y');
$dateRange = array('start' => $year.'-01-01 00:00:00', 'stop' => ($year + 1).'-01-01 00:00:00');


$form_data = $_POST;
$_SESSION['post_cache'] = $form_data;
$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : '';
$_POST['flag1'] = isset($_POST['flag1']) ? $_POST['flag1'] : '';
$_POST['flag2'] = isset($_POST['flag2']) ? $_POST['flag2'] : '';
$_POST['flag3'] = isset($_POST['flag3']) ? $_POST['flag3'] : '';
$_POST['flag4'] = isset($_POST['flag4']) ? $_POST['flag4'] : '';
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
$form_data['forensic_investigator'] = isset($_POST['forensic_investigator']) ? $_POST['forensic_investigator'] : '';
$form_data['phone_investigator'] = isset($_POST['phone_investigator']) ? $_POST['phone_investigator'] : '';
$form_data['device_location'] = isset($_POST['device_location']) ? $_POST['device_location'] : '?';
$form_data['device_action'] = isset($_POST['device_action']) ? $_POST['device_action'] : '1|?';
$form_data['device_contains_evidence'] = isset($_POST['device_contains_evidence']) ? $_POST['device_contains_evidence'] : '0';
$form_data['device_include_in_report'] = isset($_POST['device_include_in_report']) ? $_POST['device_include_in_report'] : '0';
$form_data['case_urgency'] = substr($form_data['case_urgency'], 0, 1);
$form_data['device_include_in_report'] = substr($form_data['device_include_in_report'], 0, 1);
$form_data['device_contains_evidence'] = substr($form_data['device_contains_evidence'], 0, 1);
$form_data['case_contains_mob_dev'] = substr($form_data['case_contains_mob_dev'], 0, 1);

$save_target = isset($_POST['save']) ? $_POST['save'] : '';

// ----- User management

if ($_GET['type'] === 'anon_login') {
    foreach ($_SESSION['all_users'] as $user) {
        if ($user['id'] === '1') {
            if (strpos($user['flags'], 'I') !== false) {
                message('error', $_SESSION['lang']['account_inactive']);
                header('Location: login.php');
                die;
            } else {
                $_SESSION['user'] = $user;
                logline('Action', 'Anonymous login.');
                message('info', $_SESSION['lang']['anon_login']);
                header('Location: index.php');
                die;
            }
        }
    }
}

if ($_GET['type'] === 'login') {
    $_SESSION['user'] = null;
    if(file_exists('conf/BLOCK_'.hash('sha1', $_POST['username']))) {
      sleep(5);
    }
    foreach ($users as $user) {
        if ((strtolower($_POST['username']) === strtolower($user['username'])) && (hash('sha256', $_POST['password']) === $user['password'])) {
            if (strpos($user['flags'], 'I') === false) {
                $_SESSION['user'] = $user;
            } else {
                message('error', $_SESSION['lang']['account_inactive']);
                logline('action', 'Login attempt with inactive account: '.$_POST['username']);
                header('Location: login.php');
                die;
            }
        }
    }

    if ($_SESSION['user'] !== null) {
        message('info', $_SESSION['lang']['logged_in_as'].' '.$_SESSION['user']['username']);
        logline('action', 'Login');
        header('Location: index.php');
        die;
    } else {
        file_put_contents('conf/BLOCK_'.hash('sha1', $_POST['username']), "failed password attempt");
        sleep(5);
        unlink('conf/BLOCK_'.hash('sha1', $_POST['username']));
        message('error', $_SESSION['lang']['invalid_credentials']);
        logline('action', 'Login attempt: '.$_POST['username']);
        header('Location: login.php');
        die;
    }
}

if ($_GET['type'] === 'logout') {
    logline('action', 'Logout');
    session_destroy();
    $_SESSION['user'] = null;
    header('Location: index.php');
    die;
}

if ($_GET['type'] === 'set_password') {
    if (!empty($_POST['password'])) {
        $_SESSION['password'] = hash('sha256', hash('sha1', $_POST['password']));
    } else {
        unset($_SESSION['password']);
    }
    header('Location: '.$_SERVER['HTTP_REFERER']);
    die;
}

if ($_GET['type'] === 'create_user') {
    protect_page(0);
    if ((!empty($_POST['username'])) &&
      (!empty($_POST['name'])) &&
      (!empty($_POST['access'])) &&
      (hash('sha256', $_POST['current_password']) === $_SESSION['user']['password'])) {
        foreach ($_SESSION['all_users'] as $user) {
            if ($user['username'] === trim($_POST['username'])) {
                $oldname = $user['name'];
                $returnid = $user['id'];
                if (!empty($_POST['password'])) {
                    $user_password = hash('sha256', $_POST['password']);
                    logline('Admin', 'Password changed for user '.$user['username'].'.');
                } else {
                    $user_password = $user['password'];
                }

                $query = $kirjuri_database->prepare(
          'UPDATE users SET password = :password, name = :name, access = :access, flags = :flags, attr_1 = :attr_1 WHERE username = :username;
           UPDATE exam_requests SET forensic_investigator = :name WHERE forensic_investigator = :oldname;
           UPDATE exam_requests SET phone_investigator = :name WHERE phone_investigator = :oldname;');
                $query->execute(array(
              ':oldname' => $oldname,
              ':username' => strtolower(trim(substr($_POST['username'], 0, 64))),
              ':name' => trim(substr($_POST['name'], 0, 256)),
              ':password' => $user_password,
              ':flags' => $_POST['flag1'].$_POST['flag2'].$_POST['flag3'].$_POST['flag4'],
              ':access' => substr($_POST['access'], 0, 1),
              ':attr_1' => 'User modified by '.$_SESSION['user']['username'].' at '.date('Y-m-d H:m'),
          ));
                logline('Admin', 'User modified: '.trim(substr($_POST['username'], 0, 64)).', access level '.substr($_POST['access'], 0, 1));
                message('info', $_SESSION['lang']['user_modified']);
                header('Location: users.php?populate='.$returnid.'#u');
                die;
            }
        }

        if ($_POST['access'] === 'A') {
            $_POST['access'] = '0'; // Stupid PHP evaluates 0 as empty, so circumvent with "A" to write "0" as access.
        }
        $query = $kirjuri_database->prepare('INSERT INTO users (username, password, name, access, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
    :username, :password, :name, :access, :flags, :attr_1,
    NULL, NULL, NULL, NULL, NULL, NULL, NULL);');
        $query->execute(array(
        ':username' => strtolower(trim(substr($_POST['username'], 0, 64))),
        ':name' => trim(substr($_POST['name'], 0, 256)),
        ':password' => hash('sha256', $_POST['password']),
        ':flags' => $_POST['flag1'].$_POST['flag2'],
        ':access' => substr($_POST['access'], 0, 1),
        ':attr_1' => 'User created by '.$_SESSION['user']['username'].' at '.date('Y-m-d H:m'),
    ));
        logline('Admin', 'User created: '.trim(substr($_POST['username'], 0, 64)).', access level '.substr($_POST['access'], 0, 1));
        message('info', $_SESSION['lang']['user_created']);
        header('Location: users.php');
    } else {
        message('error', $_SESSION['lang']['create_error']);
        $_SESSION['message_set'] = true;
        header('Location: users.php');
    }
    die;
}

if ($_GET['type'] === 'update_password') {
    protect_page(1);
    if ((!empty($_POST['new_password'])) && (hash('sha256', $_POST['current_password']) === $_SESSION['user']['password'])) {
        $query = $kirjuri_database->prepare('UPDATE users SET password = :newpassword WHERE username = :username AND id = :id');
        $query->execute(array(
        ':newpassword' => hash('sha256', $_POST['new_password']),
        ':username' => $_SESSION['user']['username'],
        ':id' => $_SESSION['user']['id'],
    ));
        logline('Admin', 'User changed password.');
        $_SESSION['user'] = '';
        session_destroy();
        header('Location: login.php');
    } else {
        message('error', $_SESSION['lang']['bad_password']);
        header('Location: settings.php');
    }
    die;
}

// ----- Messages

if ($_GET['type'] === 'send_message') {
    if(!empty($_POST['body'])) {
      if($_POST['msgto'] === "ALL_USERS") {
      foreach($_SESSION['all_users'] as $user)
      {
      if ($user['username'] !== $_SESSION['user']['username']) {
        $query = $kirjuri_database->prepare('INSERT INTO messages (msgfrom, msgto, subject, body, received, archived_from, archived_to, deleted_from, deleted_to, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
        :msgfrom, :msgto, :subject, :body, "0", "0", "0", "0", "0", NOW(), NULL, NULL, NULL, NULL, NULL, NULL, NULL);');
        $query->execute(array(
            ':msgfrom' => $_SESSION['user']['username'],
            ':msgto' => $user['username'],
            ':subject' => base64_encode(gzdeflate($_POST['subject'])),
            ':body' => base64_encode(gzdeflate($_POST['body']))
        ));
      }
    }
  } else {
    if($_POST['msgto'] === $_SESSION['user']['username']) {
      $msgfrom = "Myself";
    } else {
      $msgfrom = $_SESSION['user']['username'];
    }
    $query = $kirjuri_database->prepare('INSERT INTO messages (msgfrom, msgto, subject, body, received, archived_from, archived_to, deleted_from, deleted_to, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
    :msgfrom, :msgto, :subject, :body, "0", "0", "0", "0", "0", NOW(), NULL, NULL, NULL, NULL, NULL, NULL, NULL);');
    $query->execute(array(
        ':msgfrom' => $msgfrom,
        ':msgto' => $_POST['msgto'],
        ':subject' => base64_encode(gzdeflate($_POST['subject'])),
        ':body' => base64_encode(gzdeflate($_POST['body']))
    ));
    }
        logline('Action', 'message sent.');
        //message('info', $_SESSION['lang']['tool_added'] . ": ". trim(substr($_POST['product_name'], 0, 128)));
        header('Location: messages.php');
    die;
  } else {
        header('Location: messages.php');
        die;
  }
}

if ($_GET['type'] === 'delete_received')
{
    $query = $kirjuri_database->prepare('UPDATE messages SET deleted_to = "1" WHERE id = :id AND (msgto = :user OR msgfrom = :user) AND archived_to = "1"');
    $query->execute(array(
          ':user' => $_SESSION['user']['username'],
          ':id' => num($_GET['id'])
      ));
    $query->execute();
    header('Location: messages.php#archive');
    die;
}

if ($_GET['type'] === 'delete_sent')
{
    $query = $kirjuri_database->prepare('UPDATE messages SET deleted_from = "1" WHERE id = :id AND (msgto = :user OR msgfrom = :user) AND archived_from = "1"');
    $query->execute(array(
          ':user' => $_SESSION['user']['username'],
          ':id' => num($_GET['id'])
      ));
    $query->execute();
    header('Location: messages.php#archive');
    die;
}

if ($_GET['type'] === 'delete_all')
{
    $query = $kirjuri_database->prepare('UPDATE messages SET deleted_to = "1" WHERE msgto = :user AND archived_to = "1"');
    $query->execute(array(
          ':user' => $_SESSION['user']['username']
      ));
    $query = $kirjuri_database->prepare('UPDATE messages SET deleted_from = "1" WHERE msgfrom = :user AND archived_from = "1"');
    $query->execute(array(
          ':user' => $_SESSION['user']['username']
      ));
    $query->execute();
    header('Location: messages.php#inbox');
    die;
}

if ($_GET['type'] === 'archive_received')
{
    $query = $kirjuri_database->prepare('UPDATE messages SET archived_to = "1" WHERE id = :id AND received != "0" AND (msgto = :user OR msgfrom = :user)');
    $query->execute(array(
          ':user' => $_SESSION['user']['username'],
          ':id' => num($_GET['id'])
      ));
    $query->execute();
    header('Location: messages.php#inbox');
    die;
}

if ($_GET['type'] === 'archive_sent')
{
    $query = $kirjuri_database->prepare('UPDATE messages SET archived_from = "1" WHERE id = :id AND (msgto = :user OR msgfrom = :user)');
    $query->execute(array(
          ':user' => $_SESSION['user']['username'],
          ':id' => num($_GET['id'])
      ));
    $query->execute();
    header('Location: messages.php#outbox');
    die;
}


if ($_GET['type'] === 'restore_received')
{
    $query = $kirjuri_database->prepare('UPDATE messages SET archived_to = "0" WHERE id = :id AND (msgto = :user OR msgfrom = :user)');
    $query->execute(array(
          ':user' => $_SESSION['user']['username'],
          ':id' => num($_GET['id'])
      ));
    $query->execute();
    header('Location: messages.php');
    die;
}

if ($_GET['type'] === 'restore_sent')
{
    $query = $kirjuri_database->prepare('UPDATE messages SET archived_from = "0" WHERE id = :id AND (msgto = :user OR msgfrom = :user)');
    $query->execute(array(
          ':user' => $_SESSION['user']['username'],
          ':id' => num($_GET['id'])
      ));
    $query->execute();
    header('Location: messages.php');
    die;
}

if ($_GET['type'] === 'delete_message')
{
    $query = $kirjuri_database->prepare('DELETE FROM messages WHERE id = :id AND msgto = :user');
    $query->execute(array(
          ':user' => $_SESSION['user']['username'],
          ':id' => num($_GET['id'])
      ));
    $query->execute();
    header('Location: messages.php');
    die;
}

// ----- Tool management

if ($_GET['type'] === 'add_tool') {
    protect_page(0);
    if(!empty($_POST['product_name'])) {
    $query = $kirjuri_database->prepare('INSERT INTO tools (product_name, hw_version, sw_version, serialno, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
    :product_name, :hw_version, :sw_version, :serialno, :flags, NOW(), NOW(), :comment, NULL, NULL, NULL, NULL, NULL);');
        $query->execute(array(
        ':product_name' => trim(substr($_POST['product_name'], 0, 128)),
        ':hw_version' => trim(substr($_POST['hw_version'], 0, 64)),
        ':sw_version' => trim(substr($_POST['sw_version'], 0, 64)),
        ':serialno' => $_POST['serialno'],
        ':comment' => $_POST['comment'],
        ':flags' => $_POST['flag1'].$_POST['flag2']
    ));
        logline('Admin', 'tool created: '.trim(substr($_POST['product_name'], 0, 64)));
        message('info', $_SESSION['lang']['tool_added'] . ": ". trim(substr($_POST['product_name'], 0, 128)));
        header('Location: tools.php');
    die;
  } else {
        header('Location: tools.php');
        die;
  }
}

if ($_GET['type'] === 'update_tool') {
    protect_page(0);
    $query = $kirjuri_database->prepare('UPDATE tools SET product_name = :product_name, hw_version = :hw_version, sw_version = :sw_version, serialno = :serialno, attr_3 = :comment, flags = :flags,
      attr_2 = CONCAT(NOW(),";", :hw_version, ";", :sw_version, ";", :flags, ";", ", ", IFNULL(attr_2,"")) WHERE id = :tool_id');
        $query->execute(array(
        ':tool_id' => $_POST['tool_id'],
        ':product_name' => trim(substr($_POST['product_name'], 0, 128)),
        ':hw_version' => trim(substr($_POST['hw_version'], 0, 64)),
        ':sw_version' => trim(substr($_POST['sw_version'], 0, 64)),
        ':serialno' => $_POST['serialno'],
        ':comment' => $_POST['comment'],
        ':flags' => $_POST['flag1'].$_POST['flag2']
    ));
        logline('Admin', 'tool updated: '.trim(substr($_POST['product_name'], 0, 128)));
        message('info', $_SESSION['lang']['tool_updated'] . ": ". trim(substr($_POST['product_name'], 0, 128)));
        header('Location: tools.php?populate=' . $_POST['tool_id']);
    die;
}

// ----- Request management

if ($_GET['type'] === 'examination_request') {
    // Create an examination request.

    if (empty($form_data['case_file_number']) || empty($form_data['case_investigator']) || empty($form_data['case_investigator_unit']) || empty($form_data['case_investigator_tel']) || empty($form_data['case_investigation_lead']) || empty($form_data['case_confiscation_date']) || empty($form_data['case_crime']) || empty($form_data['case_suspect']) || empty($form_data['case_request_description']) || empty($form_data['case_urgency']) || empty($form_data['case_requested_action'])) {
        trigger_error('Fill all required fields.');
    }
    $query = $kirjuri_database->prepare('select case_id FROM exam_requests WHERE case_added_date BETWEEN :dateStart AND :dateStop ORDER BY case_id DESC LIMIT 1 ');
    $query->execute(array(
        ':dateStart' => $dateRange['start'],
        ':dateStop' => $dateRange['stop'],
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
        ':case_contains_mob_dev' => $form_data['case_contains_mob_dev'],
    ));
    logline('Action', 'Added examination request '.$case_id.' / '.$form_data['case_name']);
    $query = $kirjuri_database->prepare('SELECT id FROM exam_requests WHERE case_id=:case_id AND parent_id = id AND case_added_date BETWEEN :dateStart AND :dateStop LIMIT 1');
    $query->execute(array(
        ':case_id' => $case_id,
        ':dateStart' => $dateRange['start'],
        ':dateStop' => $dateRange['stop'],
    ));
    $row = $query->fetch(PDO::FETCH_ASSOC);
    $_SESSION['post_cache'] = '';
    echo $twig->render('thankyou.html', array(
        'session' => $_SESSION,
        'case_id' => $case_id,
        'id' => $row['id'],
        'settings' => $settings,
        'lang' => $_SESSION['lang'],
    ));
    exit;
}
if ($_GET['type'] === 'case_update') {
    // Update examination request.

    protect_page(1);
    if ($form_data['forensic_investigator'] !== '') {
        // Set the case as started if an f.investigator is assigned.

        $case_status = '2';
    } else {
        $case_status = '1';
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
        ':case_urgency' => $form_data['case_urgency'],
    ));
    logline('Action', 'Updated request '.$form_data['case_name'].'');
    $form_data['returnid'] = $_GET['db_row'];
    $_SESSION['post_cache'] = '';
    show_saved();
    header('Location: edit_request.php?case='.$form_data['returnid']);
    die;
}

if ($_GET['type'] === 'report_notes') {
    // Save case report notes.

    protect_page(1);
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, last_updated = NOW() where id=:id AND parent_id = :id AND is_removed != "1"');
    $sql->execute(array(
        ':id' => $form_data['returnid'],
        ':report_notes' => $form_data['report_notes'],
    ));
    $_SESSION['post_cache'] = '';
    message('info', $_SESSION['lang']['report_notes_saved']);
    $_SESSION['message_set'] = true;
    header('Location: edit_request.php?case='.$form_data['returnid'].'&tab=report_notes');
    logline('Action', 'Updated report notes, returnid='.$form_data['returnid'].'');
    die;
}

if ($_GET['type'] === 'examiners_notes') {
    // Save examiners private notes

    protect_page(1);
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET examiners_notes = :examiners_notes, last_updated = NOW() where id=:id AND parent_id = :id AND is_removed != "1"');
    $sql->execute(array(
        ':id' => $form_data['returnid'],
        ':examiners_notes' => $form_data['examiners_notes'].'<p> -- '.$_SESSION['user']['username'].' ('.date('Y-m-d H:m').')</p><br><br>',
    ));
    $_SESSION['post_cache'] = '';
    message('info', $_SESSION['lang']['exam_notes_saved']);
    header('Location: edit_request.php?case='.$form_data['returnid'].'&tab=examiners_notes');
    logline('Action', 'Updated examiners notes, returnid='.$form_data['returnid'].'');
    die;
}

if ($_GET['type'] === 'set_removed') {
    // Remove device from case

    protect_page(1);
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() where id=:id AND parent_id != id;
        UPDATE exam_requests SET is_removed = "1" where device_host_id=:id');
    $sql->execute(array(
        ':id' => $_GET['db_row'],
    ));
    logline('Action', 'Removed device ID '.$_GET['db_row'].'');
    $sql = $kirjuri_database->prepare('SELECT count(id) from exam_requests where id != parent_id AND parent_id=:id AND is_removed="0"');
    $sql->execute(array(
        ':id' => $_GET['returnid'],
    ));
    $devicecount = $sql->fetch(PDO::FETCH_ASSOC);
    $devicecount = $devicecount['count(id)'];
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = :devicecount, last_updated = NOW() where id=:id');
    $sql->execute(array(
        ':devicecount' => $devicecount,
        ':id' => $_GET['returnid'],
    ));
    $form_data['returnid'] = $_GET['returnid'];
    $_SESSION['post_cache'] = '';
    message('info', $_SESSION['lang']['device_removed']);
    header('Location: edit_request.php?case='.$form_data['returnid'].'&tab=devices');
    die;
}

if ($_GET['type'] === 'device_attach') {
    // Associate a media/device with host device

    protect_page(1);
    if (isset($form_data['isanta'])) {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = :isanta, last_updated = NOW() where id=:id AND parent_id != id;
        UPDATE exam_requests SET device_is_host = "1" where id = :isanta;');
        $sql->execute(array(
            ':id' => $_GET['db_row'],
            ':isanta' => $form_data['isanta'],
        ));
    }
    $form_data['returnid'] = $_GET['returnid'];
    $_SESSION['post_cache'] = '';
    message('info', $_SESSION['lang']['device_attached']);
    header('Location: edit_request.php?case='.$form_data['returnid'].'&tab=devices');
    die;
}

if ($_GET['type'] === 'device_detach') {
    // Remove device association

    protect_page(1);
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = "0", last_updated = NOW() where id=:id AND parent_id != id');
    $sql->execute(array(
        ':id' => $_GET['db_row'],
    ));
    $form_data['returnid'] = $_GET['returnid'];
    $_SESSION['post_cache'] = '';
    message('info', $_SESSION['lang']['device_detached']);
    header('Location: edit_request.php?case='.$form_data['returnid'].'&tab=devices');
    die;
}

if ($_GET['type'] === 'set_removed_case') {
    // Remove case

    protect_page(1);
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() WHERE id=:id AND parent_id = :id');
    $sql->execute(array(
        ':id' => $form_data['remove_exam_request'],
    ));
    logline('Action', 'Removed case ID '.$form_data['remove_exam_request'].'');
    $_SESSION['post_cache'] = '';
    message('info', $_SESSION['lang']['case_removed']);
    header('Location: index.php');
    die;
}

if ($_GET['type'] === 'move_all') {
    // Change all device locations and/or actions

    protect_page(1);
    if ($form_data['device_action'] != 'NO_CHANGE') {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() WHERE parent_id=:parent_id');
        $sql->execute(array(
            ':parent_id' => $_GET['returnid'],
            ':device_action' => $form_data['device_action'],
        ));
    }
    if ($form_data['device_location'] != 'NO_CHANGE') {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() WHERE parent_id=:parent_id AND is_removed != "1"');
        $sql->execute(array(
            ':parent_id' => $_GET['returnid'],
            ':device_location' => $form_data['device_location'],
        ));
    }
    $form_data['returnid'] = $_GET['returnid'];
    $_SESSION['post_cache'] = '';
    if ($form_data['device_action'] === 'NO_CHANGE' && $form_data['device_location'] === 'NO_CHANGE') {
        // Do nothing
    } else {
    }
    header('Location: edit_request.php?case='.$form_data['returnid'].'&tab=devices');
    die;
}

if ($_GET['type'] === 'update_request_status') {
    // Set case status

    protect_page(1);
    if ($form_data['case_status'] === '1') {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, forensic_investigator = "", phone_investigator = "", case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
    } else {
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
    }
    $sql->execute(array(
        ':id' => $form_data['returnid'],
        ':case_status' => $form_data['case_status'],
    ));

    logline('Action', 'Changed request '.$form_data['returnid'].' status: '.$form_data['case_status'].'');
    $_SESSION['post_cache'] = '';
    show_saved();
    header('Location: edit_request.php?case='.$form_data['returnid']);
    die;
}

  if ($_GET['type'] === 'change_device_status') {
      // Dynamically set device action

      if ($_SESSION['user']['access'] > 1) {
          die;
      }
      $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() where id=:id AND parent_id != id');
      $sql->execute(array(
          ':device_action' => $form_data['device_action'],
          ':id' => $_GET['db_row'],
      ));
      $_SESSION['post_cache'] = '';
      echo $twig->render('progress_bar.html', array(
          'device_action' => $form_data['device_action'],
          'settings' => $settings,
      ));
      exit;
  }

    if ($_GET['type'] === 'change_device_location') {
        // Dynamically set device location.

        if ($_SESSION['user']['access'] > 1) {
            die;
        }
        $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() where id=:id AND parent_id != id');
        $sql->execute(array(
            ':device_location' => $form_data['device_location'],
            ':id' => $_GET['db_row'],
        ));
        $_SESSION['post_cache'] = '';
        die;
    }

if ($_GET['type'] === 'devicememo') {
    // Update individual device details.
    protect_page(1);
    if (!empty($_POST['used_tool']))
    {
      $form_data['examiners_notes'] = $form_data['examiners_notes'] . '<p>'.$_POST['used_tool'].'</p>';
    }
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
        ':device_contains_evidence' => $form_data['device_contains_evidence'],
    ));
    $form_data['returnid'] = $_GET['returnid'];
    logline('Action', 'Updated device memo '.$form_data['id'].'');
    $_SESSION['post_cache'] = '';
    show_saved();
    header('Location: device_memo.php?db_row='.$form_data['returnid']);
    die;
}

if ($_GET['type'] === 'device') {
    // Create new device entry

    protect_page(1);
    if ($form_data['device_host_id'] === '0') {
        // If new device is an associated media, it is not a host by itself

        $device_is_host = '1';
    } else {
        $device_is_host = '0';
    }
    if (empty($form_data['device_type'])) {
        $_SESSION['message']['type'] = 'error';
        $_SESSION['message']['content'] = 'Device type missing.';
        $_SESSION['message_set'] = true;
        header('Location: edit_request.php?case='.$form_data['parent_id'].'&tab=devices');
        die;
    }
    if ($form_data['device_action'] === '1|?') {
        $_SESSION['message']['type'] = 'error';
        $_SESSION['message']['content'] = 'Device action missing.';
        $_SESSION['message_set'] = true;
        header('Location: edit_request.php?case='.$form_data['parent_id'].'&tab=devices');
        die;
    }
    if ($form_data['device_location'] === '?') {
        $_SESSION['message']['type'] = 'error';
        $_SESSION['message']['content'] = 'Device location missing.';
        $_SESSION['message_set'] = true;
        header('Location: edit_request.php?case='.$form_data['parent_id'].'&tab=devices');
        die;
    }
    $form_data['examiners_notes'] = "";
    if ( strpos( strtoupper($form_data['device_identifier']), "IMEI") !== false)
    {
      $imei_TAC =  substr(num($form_data['device_identifier']),0,8);
      if (strlen($imei_TAC) === 8) {
        if (file_exists('conf/imei.txt'))
        {
          $imei_list = file('conf/imei.txt');
          foreach($imei_list as $line)
          {
            if (substr($line, 0 , 8) === $imei_TAC)
            {
              $imei_data = explode("|", $line);
              $form_data['device_manuf'] = $imei_data['10'];
              $form_data['device_model'] = $imei_data['11'];
              $form_data['examiners_notes'] = implode(", " ,$imei_data);
            }
          }
        }
      }
    }

    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = case_devicecount + 1, last_updated = NOW() WHERE id = :parent_id'); // Update device count
    $sql->execute(array(
        ':parent_id' => $form_data['parent_id'],
    ));

    $sql = $kirjuri_database->prepare('INSERT INTO exam_requests (parent_id, device_host_id, device_type, device_manuf, device_model, device_identifier, device_location, device_item_number, device_document, device_time_deviation, device_os, device_size_in_gb, device_is_host, device_owner, device_include_in_report, device_contains_evidence, case_added_date, case_request_description, device_action, is_removed, last_updated, examiners_notes ) VALUES (:parent_id, :device_host_id, :device_type, :device_manuf, :device_model, :device_identifier, :device_location, :device_item_number, :device_document, :device_time_deviation, :device_os, :device_size_in_gb, :device_is_host, :device_owner, "1", "0", NOW(), :case_request_description, :device_action, :is_removed, NOW(), :examiners_notes);
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
        ':is_removed' => $form_data['is_removed'],
        ':examiners_notes' => $form_data['examiners_notes']
    ));
    logline('Action', 'Added device '.$form_data['device_type'].' '.$form_data['device_manuf'].' '.$form_data['device_model'].' with id ['.$form_data['device_identifier'].'] to case '.$form_data['parent_id'].'');
    $_SESSION['post_cache'] = '';
    $_SESSION['message']['type'] = 'info';
    $_SESSION['message']['content'] = 'Changes saved.';
    $_SESSION['message_set'] = true;
    header('Location: edit_request.php?case='.$form_data['parent_id'].'&tab=devices');
    die;
}

  if (($save_target === 'settings') && (isset($_POST['settings_conf']))) {
      // Save settings to file

      protect_page(1);
      if (file_exists($settings_file)) {
          file_put_contents($settings_file, $_POST['settings_conf']);
          logline('Admin', 'Settings saved.');
      } else {
          trigger_error('Settings file '.$settings_file.' not found.');
      }
      $_SESSION['post_cache'] = '';
      show_saved();
      header('Location: settings.php');
      die;
  }

// Default to error if no handlers
trigger_error('submit.php called with erroneous value.');
header('Location: index.php'); // Fall back to index with an error if no conditions are met.
die;
