<?php

require_once './include_functions.php';

$year = date('Y');
$dateRange = array(
  'start' => $year . '-01-01 00:00:00',
  'stop' => ($year + 1) . '-01-01 00:00:00'
);

foreach ($_POST as $key => $value) // Sanitize all POST data
 {
  if(!is_array($value))
  {
    $value = sanitize_raw($value);
  }
  $_POST[$key] = isset($value) ? $value : '';
 }

if ( (isset($_POST['is_removed'])) && (empty($_POST['is_removed']) ))
{
  $_POST['is_removed'] = "0";
}

if ( (isset($_POST['device_host_id'])) && (empty($_POST['device_host_id']) ))
{
  $_POST['device_host_id'] = "0";
}

if ( (isset($_POST['device_item_number'])) && (empty($_POST['device_item_number']) ))
{
  $_POST['device_item_number'] = "0";
}

if ( (isset($_POST['device_size_in_gb'])) && (empty($_POST['device_size_in_gb']) ))
{
  $_POST['device_size_in_gb'] = "0";
}

if ( (isset($_POST['device_contains_evidence'])) && (empty($_POST['device_contains_evidence']) ))
{
  $_POST['device_contains_evidence'] = "0";
}

if ( (isset($_POST['device_include_in_report'])) && (empty($_POST['device_include_in_report']) ))
{
  $_POST['device_include_in_report'] = "0";
}

if ( (isset($_POST['case_contains_mob_dev'])) && (empty($_POST['case_contains_mob_dev']) ))
{
  $_POST['case_contains_mob_dev'] = "0";
}


$_SESSION['post_cache'] = $_POST;
$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : '';

// ----- User management

if ($_GET['type'] === 'anon_login')
 {
  foreach ($_SESSION['all_users'] as $user)
   {
    if ($user['id'] === '1')
     {
      if (strpos($user['flags'], 'I') !== false)
       {
        message('error', $_SESSION['lang']['account_inactive']);
        header('Location: login.php');
        die;
       }
      else
       {
        $_SESSION['user'] = $user;
        csrf_init();
        if (!file_exists('cache/user_' . md5($_SESSION['user']['username']))) mkdir('cache/user_' . md5($_SESSION['user']['username']));
        file_put_contents('cache/user_' . md5($_SESSION['user']['username']) . '/session_' . $_SESSION['user']['token'] . '.txt', $_SESSION['user']['username'] . ' is logged in at ' . $_SERVER['REMOTE_ADDR'] . ', user agent ' . $_SERVER['HTTP_USER_AGENT'] . '. Request timestamp ' . gmdate("Y-m-d\TH:i:s\Z", $_SERVER['REQUEST_TIME']) . ". Remove this file to force logout.\r\n");
        logline('0', 'Action', 'Anonymous login.');
        message('info', $_SESSION['lang']['anon_login']);
        header('Location: index.php');
        die;
       }
     }
   }
 }

if ($_GET['type'] === 'login')
 {
  $_SESSION['user'] = null;
  if (file_exists('conf/BLOCK_' . hash('sha1', $_POST['username'])))
   {
    sleep(5);
   }
  foreach ($users as $user)
   {
     // Log user in.
     if ((strtolower($_POST['username']) === strtolower($user['username'])) && (password_verify($_POST['password'], $user['password']) ))
      {
       if (strpos($user['flags'], 'I') === false)
        {
         $_SESSION['user'] = $user;
         csrf_init();
        }
       else
        {
         message('error', $_SESSION['lang']['account_inactive']);
         logline('0', 'Action', 'Login attempt with inactivated account: ' . $_POST['username']);
         header('Location: login.php');
         die;
        }
      }
      // See if user still has old, insecure password hash - update and log in.
    elseif ((strtolower($_POST['username']) === strtolower($user['username'])) && (hash('sha256', $_POST['password']) === $user['password']))
     {
      if (strpos($user['flags'], 'I') === false)
       {
        $_SESSION['user'] = $user;
        $query = $kirjuri_database->prepare('UPDATE users SET password = :secure_password_hash WHERE username = :username AND password = :legacy_password');
        $query->execute(array(
          ':secure_password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
          ':username' => $_SESSION['user']['username'],
          ':legacy_password' => hash('sha256', $_POST['password'])
        ));
        $_SESSION['user']['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        logline('0', 'Info', 'Updated the legacy password hash for user.');
        csrf_init();
       }
      else
       {
        message('error', $_SESSION['lang']['account_inactive']);
        logline('0', 'Action', 'Login attempt with inactive account: ' . $_POST['username']);
        header('Location: login.php');
        die;
       }
     }
   }

  if ($_SESSION['user'] !== null)
   {
    message('info', $_SESSION['lang']['logged_in_as'] . ' ' . $_SESSION['user']['username']);
    logline('0', 'Action', 'Login');
    if (!file_exists('cache/user_' . md5($_SESSION['user']['username']))) mkdir('cache/user_' . md5($_SESSION['user']['username']));
    file_put_contents('cache/user_' . md5($_SESSION['user']['username']) . '/session_' . $_SESSION['user']['token'] . '.txt', $_SESSION['user']['username'] . ' is logged in at ' . $_SERVER['REMOTE_ADDR'] . ', user agent ' . $_SERVER['HTTP_USER_AGENT'] . '. Request timestamp ' . gmdate("Y-m-d\TH:i:s\Z", $_SERVER['REQUEST_TIME']) . ". Remove this file to force logout.\r\n");
    header('Location: index.php');
    die;
   }
  else
   {
    file_put_contents('cache/BLOCK_' . md5(strtolower($_POST['username'])), "failed password attempt");
    sleep(5);
    unlink('cache/BLOCK_' . md5(strtolower($_POST['username'])));
    message('error', $_SESSION['lang']['invalid_credentials']);
    logline('0', 'Action', 'Invalid login attempt: ' . $_POST['username']);
    header('Location: login.php');
    die;
   }
 }

if ($_GET['type'] === 'logout')
 {
  logline('0', 'Action', 'Logout');
  unlink('cache/user_' . md5($_SESSION['user']['username']) . '/session_' . $_SESSION['user']['token'] . '.txt');
  session_destroy();
  $_SESSION['user'] = null;
  header('Location: index.php');
  die;
 }

 if ($_GET['type'] === 'force_logout')
 {
   // Force end session
   csrf_session_validate($_GET['token']);
   protect_page(0);
   if (file_exists('cache/user_' . md5(urldecode($_GET['user']))))
   {
     deleteDirectory('cache/user_' . md5(urldecode($_GET['user'])));
     message('info', $_SESSION['lang']['user_logged_out']);
   }
   logline('0', "Admin", "Admin terminated sessions: ".urldecode($_GET['user']));
   header('Location: '.$_SERVER['HTTP_REFERER']);
   die;
 }

if ($_GET['type'] === 'create_user')
 {
  csrf_session_validate($_POST['token']);
  protect_page(0);
  $username_input = strtolower(trim(substr(preg_replace("/[^a-zA-Z0-9\ äöåÄÖÅ]+/", "",$_POST['username']), 0, 64)));
  if (
    !empty($username_input) &&
    !empty($_POST['name']) &&
    !empty($_POST['access']) &&
    password_verify($_POST['current_password'], $_SESSION['user']['password'])
    )
   {
    foreach ($_SESSION['all_users'] as $user)
     {
      if ($user['username'] === $username_input)
       {
        $oldname = $user['name'];
        $returnid = $user['id'];
        if (!empty($_POST['password']))
         {
          $user_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
          logline('0', 'Admin', 'Password changed for user ' . $user['username'] . '.');
         }
        else
         {
          $user_password = $user['password'];
         }
        $query = $kirjuri_database->prepare('UPDATE users SET password = :password, name = :name, access = :access, flags = :flags, attr_1 = :attr_1 WHERE username = :username;
           UPDATE exam_requests SET forensic_investigator = :name WHERE forensic_investigator = :oldname;
           UPDATE exam_requests SET phone_investigator = :name WHERE phone_investigator = :oldname;');
        $query->execute(array(
          ':oldname' => $oldname,
          ':username' => $username_input,
          ':name' => trim(substr($_POST['name'], 0, 256)),
          ':password' => $user_password,
          ':flags' => $_POST['flag1'] . $_POST['flag2'] . $_POST['flag3'] . $_POST['flag4'],
          ':access' => substr($_POST['access'], 0, 1),
          ':attr_1' => 'User modified by ' . $_SESSION['user']['username'] . ' at ' . date('Y-m-d H:m')
        ));
        logline('0', 'Admin', 'User modified: ' . $username_input . ', access level ' . substr($_POST['access'], 0, 1));
        message('info', $_SESSION['lang']['user_modified']);
        header('Location: users.php?populate=' . $returnid . '#u');
        die;
       }
     }

    if ($_POST['access'] === 'A')
     {
      $_POST['access'] = '0'; // Stupid PHP evaluates 0 as empty, so circumvent with "A" to write "0" as access.
     }
    $query = $kirjuri_database->prepare('INSERT INTO users (username, password, name, access, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
    :username, :password, :name, :access, :flags, :attr_1,
    NULL, NULL, NULL, NULL, NULL, NULL, NULL);');
    $query->execute(array(
      ':username' => $username_input,
      ':name' => trim(substr($_POST['name'], 0, 256)),
      ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
      ':flags' => $_POST['flag1'] . $_POST['flag2'],
      ':access' => substr($_POST['access'], 0, 1),
      ':attr_1' => 'User created by ' . $_SESSION['user']['username'] . ' at ' . date('Y-m-d H:m')
    ));
    logline('0', 'Admin', 'User created: ' . $username_input . ', access level ' . substr($_POST['access'], 0, 1));
    message('info', $_SESSION['lang']['user_created']);
    header('Location: users.php');
   }
  else
   {
    message('error', $_SESSION['lang']['create_error']);
    $_SESSION['message_set'] = true;
    header('Location: users.php');
   }
  die;
 }

if ($_GET['type'] === 'update_password')
 {
  csrf_session_validate($_POST['token']);
  protect_page(1);
  if ((!empty($_POST['new_password'])) && (password_verify($_POST['current_password'], $_SESSION['user']['password'])))
   {
    $query = $kirjuri_database->prepare('UPDATE users SET password = :newpassword WHERE username = :username AND id = :id');
    $query->execute(array(
      ':newpassword' => password_hash($_POST['new_password'], PASSWORD_DEFAULT),
      ':username' => $_SESSION['user']['username'],
      ':id' => $_SESSION['user']['id']
    ));
    logline('0', 'Admin', 'User changed password.');
    $_SESSION['user'] = '';
    session_destroy();
    header('Location: login.php');
   }
  else
   {
    message('error', $_SESSION['lang']['bad_password']);
    header('Location: settings.php');
   }
  die;
 }

// ----- Messages

if ($_GET['type'] === 'send_message')
 {
  csrf_session_validate($_POST['token']);
  if (!empty($_POST['body']) && !empty($_POST['msgto']))
   {
    if ($_POST['msgto'] === "ALL_USERS")
     {
      foreach ($_SESSION['all_users'] as $user)
       {
        if ($user['username'] !== $_SESSION['user']['username'])
         {
          $query = $kirjuri_database->prepare('INSERT INTO messages (msgfrom, msgto, subject, body, received, archived_from, archived_to, deleted_from, deleted_to, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
        :msgfrom, :msgto, :subject, :body, "0", "0", "0", "0", "0", NOW(), NULL, NULL, NULL, NULL, NULL, NULL, NULL);');
          $query->execute(array(
            ':msgfrom' => $_SESSION['user']['username'],
            ':msgto' => $user['username'],
            ':subject' => base64_encode(gzdeflate(sanitize_raw($_POST['subject']))),
            ':body' => base64_encode(gzdeflate(sanitize_raw($_POST['body'])))
          ));
         }
       }
     }
    else
     {
      if ($_POST['msgto'] === $_SESSION['user']['username'])
       {
        $msgfrom = "Myself";
       }
      else
       {
        $msgfrom = $_SESSION['user']['username'];
       }
      $query = $kirjuri_database->prepare('INSERT INTO messages (msgfrom, msgto, subject, body, received, archived_from, archived_to, deleted_from, deleted_to, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
    :msgfrom, :msgto, :subject, :body, "0", "0", "0", "0", "0", NOW(), NULL, NULL, NULL, NULL, NULL, NULL, NULL);');
      $query->execute(array(
        ':msgfrom' => $msgfrom,
        ':msgto' => $_POST['msgto'],
        ':subject' => base64_encode(gzdeflate(sanitize_raw($_POST['subject']))),
        ':body' => base64_encode(gzdeflate(sanitize_raw($_POST['body'])))
      ));
     }
    logline('0', 'Action', 'message sent.');
    message('info', $_SESSION['lang']['message_sent']);
    $_SESSION['post_cache'] = "";
    header('Location: messages.php');
    die;
   }
  else
   {
    message('error', $_SESSION['lang']['message_or_to_missing']);
    header('Location: messages.php?show=compose');
    die;
   }
 }

if ($_GET['type'] === 'delete_received')
 {
  csrf_session_validate($_GET['token']);
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
  csrf_session_validate($_GET['token']);
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
  csrf_session_validate($_GET['token']);
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
  csrf_session_validate($_GET['token']);
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
  csrf_session_validate($_GET['token']);
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
  csrf_session_validate($_GET['token']);
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
  csrf_session_validate($_GET['token']);
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
  csrf_session_validate($_GET['token']);
  $query = $kirjuri_database->prepare('DELETE FROM messages WHERE id = :id AND msgto = :user');
  $query->execute(array(
    ':user' => $_SESSION['user']['username'],
    ':id' => num($_GET['id'])
  ));
  $query->execute();
  header('Location: messages.php');
  die;
 }

// ----- Log management

if ($_GET['type'] === 'rotate_logs')
{
  protect_page(0);
  csrf_session_validate($_GET['token']);
  logline('0', 'Admin', '----- LOG ROTATION -----'); // End log with rotation message
  $event_log = file_get_contents('logs/kirjuri_case_0.log');
  file_put_contents('logs/kirjuri_events.log', $event_log, FILE_APPEND);
  file_put_contents('logs/kirjuri_case_0.log', "");
  unset($event_log);

  logline('0', 'Error', '----- LOG ROTATION -----');
  $event_log = file_get_contents('logs/error.log');
  file_put_contents('logs/kirjuri_errors.log', $event_log, FILE_APPEND);
  file_put_contents('logs/error.log', "");
  unset($error_log);
  header('Location: settings.php#logs');
  die;
}
// ----- Tool management

if ($_GET['type'] === 'add_tool')
 {
  protect_page(0);
  csrf_session_validate($_POST['token']);
  if (!empty($_POST['product_name']))
   {
    $query = $kirjuri_database->prepare('INSERT INTO tools (product_name, hw_version, sw_version, serialno, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
    :product_name, :hw_version, :sw_version, :serialno, :flags, NOW(), NOW(), :comment, NULL, NULL, NULL, NULL, NULL);');
    $query->execute(array(
      ':product_name' => trim(substr($_POST['product_name'], 0, 128)),
      ':hw_version' => trim(substr($_POST['hw_version'], 0, 64)),
      ':sw_version' => trim(substr($_POST['sw_version'], 0, 64)),
      ':serialno' => $_POST['serialno'],
      ':comment' => $_POST['comment'],
      ':flags' => $_POST['flag1'] . $_POST['flag2']
    ));
    logline('0', 'Admin', 'tool created: ' . trim(substr($_POST['product_name'], 0, 64)));
    message('info', $_SESSION['lang']['tool_added'] . ": " . trim(substr($_POST['product_name'], 0, 128)));
    header('Location: tools.php');
    die;
   }
  else
   {
    header('Location: tools.php');
    die;
   }
 }

if ($_GET['type'] === 'update_tool')
 {
  protect_page(0);
  csrf_session_validate($_POST['token']);
  $query = $kirjuri_database->prepare('UPDATE tools SET product_name = :product_name, hw_version = :hw_version, sw_version = :sw_version, serialno = :serialno, attr_3 = :comment, flags = :flags,
      attr_2 = CONCAT(NOW(),";", :hw_version, ";", :sw_version, ";", :flags, ";", ", ", IFNULL(attr_2,"")) WHERE id = :tool_id');
  $query->execute(array(
    ':tool_id' => $_POST['tool_id'],
    ':product_name' => trim(substr($_POST['product_name'], 0, 128)),
    ':hw_version' => trim(substr($_POST['hw_version'], 0, 64)),
    ':sw_version' => trim(substr($_POST['sw_version'], 0, 64)),
    ':serialno' => $_POST['serialno'],
    ':comment' => $_POST['comment'],
    ':flags' => $_POST['flag1'] . $_POST['flag2']
  ));
  logline('0', 'Admin', 'tool updated: ' . trim(substr($_POST['product_name'], 0, 128)));
  message('info', $_SESSION['lang']['tool_updated'] . ": " . trim(substr($_POST['product_name'], 0, 128)));
  header('Location: tools.php?populate=' . $_POST['tool_id']);
  die;
 }

// ----- Request management

if ($_GET['type'] === 'case_access')
 {
  $id = num($_GET['id']);
  protect_page(1);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_owner($id);

  if ( (isset($_POST['access']['all_users'])) || (!isset($_POST['access'])))
  {
    $accessgroup = "";
  }
  elseif (isset($_POST['access']['admin_only']))
  {
    $accessgroup = "admin";
  }
  else
  {
    $accessgroup = implode(";", $_POST['access']);
  }
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET case_owner = :accessgroup WHERE id = :id');
  $query->execute(array(
    ':id' => $id,
    ':accessgroup' => $accessgroup
  ));
  logline($id, 'Access', 'Access group updated: ' . str_replace(";", ", ", $accessgroup));
  header('Location: edit_request.php?case=' . $id . '&tab=access');
  die;
 }

if ($_GET['type'] === 'examination_request')
 {
  // Create an examination request.
  csrf_session_validate($_POST['token']);
  if (empty($_POST['case_file_number']) || empty($_POST['case_investigator']) || empty($_POST['case_investigator_unit']) || empty($_POST['case_investigator_tel']) || empty($_POST['case_investigation_lead']) || empty($_POST['case_confiscation_date']) || empty($_POST['case_crime']) || empty($_POST['case_suspect']) || empty($_POST['case_request_description']) || empty($_POST['case_urgency']) || empty($_POST['case_requested_action']))
   {
    trigger_error('Fill all required fields.');
   }
  $query = $kirjuri_database->prepare('select case_id FROM exam_requests WHERE case_added_date BETWEEN :dateStart AND :dateStop ORDER BY case_id DESC LIMIT 1 ');
  $query->execute(array(
    ':dateStart' => $dateRange['start'],
    ':dateStop' => $dateRange['stop']
  ));
  $case_id = $query->fetch(PDO::FETCH_ASSOC);
  $case_id = $case_id['case_id'] + 1;
  $sql = $kirjuri_database->prepare(' INSERT INTO exam_requests ( id, parent_id, case_id, case_name, case_file_number, case_investigator, case_investigator_unit, case_investigator_tel, case_investigation_lead, case_confiscation_date, last_updated, case_added_date, case_crime, examiners_notes, classification, case_suspect, case_request_description, is_removed, case_status, case_urgency, case_urg_justification, case_requested_action, case_contains_mob_dev, case_devicecount ) VALUES ( NULL, "0", :case_id, :case_name, :case_file_number, :case_investigator, :case_investigator_unit, :case_investigator_tel, :case_investigation_lead, :case_confiscation_date, NOW(), NOW(), :case_crime, :examiners_notes, :classification, :case_suspect, :case_request_description, "0", "1", :case_urgency, :case_urg_justification, :case_requested_action, :case_contains_mob_dev, "0" );
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
    ':case_contains_mob_dev' => $_POST['case_contains_mob_dev'],
    ':examiners_notes' => "<b>" . $_SESSION['lang']['passwords'] . "</b>: " . $_POST['examiners_notes']
  ));
  $query = $kirjuri_database->prepare('SELECT id FROM exam_requests WHERE case_id=:case_id AND parent_id = id AND case_added_date BETWEEN :dateStart AND :dateStop LIMIT 1');
  $query->execute(array(
    ':case_id' => $case_id,
    ':dateStart' => $dateRange['start'],
    ':dateStop' => $dateRange['stop']
  ));
  $row = $query->fetch(PDO::FETCH_ASSOC);
  $_SESSION['post_cache'] = '';
  logline($row['id'], 'Action', 'Added examination request ' . $case_id . ' / ' . $_POST['case_name']);
  echo $twig->render('thankyou.html', array(
    'session' => $_SESSION,
    'case_id' => $case_id,
    'id' => $row['id'],
    'settings' => $settings,
    'lang' => $_SESSION['lang']
  ));
  exit;
 }
if ($_GET['type'] === 'case_update')
 {
  // Update examination request.
  protect_page(1);
  csrf_session_validate($_POST['token']);
  verify_owner($_GET['uid']);
  if ($_POST['forensic_investigator'] !== '')
   {
    // Set the case as started if an f.investigator is assigned.

    $case_status = '2';
   }
  else
   {
    $case_status = '1';
   }
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_name = :case_name, case_file_number = :case_file_number, case_crime = :case_crime, classification = :classification, case_suspect = :case_suspect, case_investigation_lead = :case_investigation_lead, case_investigator = :case_investigator, forensic_investigator = :forensic_investigator, phone_investigator = :phone_investigator, case_investigator_tel = :case_investigator_tel, case_investigator_unit = :case_investigator_unit, case_request_description = :case_request_description, case_confiscation_date = :case_confiscation_date, case_start_date = NOW(), last_updated = NOW(), is_removed = "0", case_contains_mob_dev = :case_contains_mob_dev, case_status = :case_status, case_urgency = :case_urgency where id=:id AND parent_id = :id');
  $sql->execute(array(
    ':username' => $_SESSION['user']['username'],
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
    ':id' => $_GET['uid'],
    ':case_urgency' => $_POST['case_urgency']
  ));
  logline($_GET['uid'], 'Action', 'Updated request ' . $_POST['case_name'] . '');
  $_POST['returnid'] = $_GET['uid'];
  $_SESSION['post_cache'] = '';
  show_saved();
  header('Location: edit_request.php?case=' . $_POST['returnid']);
  die;
 }

if ($_GET['type'] === 'report_notes')
 {
  // Save case report notes.
  protect_page(1);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $_POST['returnid']);
  verify_owner($_POST['returnid']);
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, last_updated = NOW() where id=:id AND parent_id = :id AND is_removed != "1"');
  $sql->execute(array(
    ':username' => $_SESSION['user']['username'],
    ':id' => $_POST['returnid'],
    ':report_notes' => sanitize_raw($_POST['report_notes'])
  ));
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['report_notes_saved']);
  $_SESSION['message_set'] = true;
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=report_notes');
  logline($_POST['returnid'], 'Action', 'Updated report notes');
  die;
 }

if ($_GET['type'] === 'examiners_notes')
 {
  // Save examiners private notes
  protect_page(1);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $_POST['returnid']);
  verify_owner($_POST['returnid']);
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET examiners_notes = :examiners_notes, last_updated = NOW() where id=:id AND parent_id = :id AND is_removed != "1"');
  $sql->execute(array(
    ':username' => $_SESSION['user']['username'],
    ':id' => $_POST['returnid'],
    ':examiners_notes' => sanitize_raw($_POST['examiners_notes']) . '<p> -- ' . $_SESSION['user']['username'] . ' (' . date('Y-m-d H:m') . ')</p><br><br>'
  ));
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['exam_notes_saved']);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=examiners_notes');
  logline($_POST['returnid'], 'Action', 'Updated examiners notes');
  die;
 }

if ($_GET['type'] === 'set_removed')
 {
  // Remove device from case
  protect_page(1);
  csrf_session_validate($_GET['token']);
  csrf_case_validate($_GET['ct'], $_GET['returnid']);
  verify_owner($_GET['returnid']);
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() where id=:id AND parent_id = :returnid;
        UPDATE exam_requests SET is_removed = "1" where device_host_id=:id');
  $sql->execute(array(
    ':id' => $_GET['uid'],
    ':returnid' => $_GET['returnid']
  ));
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
  $_POST['returnid'] = $_GET['returnid'];
  $_SESSION['post_cache'] = '';
  logline($_GET['returnid'], 'Action', 'Removed device UID' . $_GET['uid']);
  message('info', $_SESSION['lang']['device_removed']);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=devices');
  die;
 }

if ($_GET['type'] === 'device_attach')
 {
  // Associate a media/device with host device
  protect_page(1);
  csrf_session_validate($_POST['token']);
  if (isset($_POST['isanta']))
   {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = :isanta, last_updated = NOW() where id=:id AND parent_id != id;
        UPDATE exam_requests SET device_is_host = "1" where id = :isanta;');
    $sql->execute(array(
      ':id' => $_GET['uid'],
      ':isanta' => $_POST['isanta']
    ));
   }
  $_POST['returnid'] = $_GET['returnid'];
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['device_attached']);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=devices');
  die;
 }

if ($_GET['type'] === 'device_detach')
 {
  // Remove device association
  protect_page(1);
  csrf_session_validate($_GET['token']);
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = "0", last_updated = NOW() where id=:id AND parent_id != id');
  $sql->execute(array(
    ':id' => $_GET['uid']
  ));
  $_POST['returnid'] = $_GET['returnid'];
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['device_detached']);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=devices');
  die;
 }

if ($_GET['type'] === 'set_removed_case')
 {
  // Remove case
  $id = num($_POST['remove_exam_request']);
  protect_page(1);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_owner($id);
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() WHERE id=:id AND parent_id = :id');
  $sql->execute(array(
    ':username' => $_SESSION['user']['username'],
    ':id' => $id
  ));
  logline($id, 'Action', 'Removed case UID' . $id);
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['case_removed']);
  header('Location: index.php');
  die;
 }

if ($_GET['type'] === 'move_all')
 {
  // Change all device locations and/or actions

  protect_page(1);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $_GET['returnid']);
  verify_owner($_GET['returnid']);
  if ($_POST['device_action'] != 'NO_CHANGE')
   {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() WHERE parent_id=:parent_id');
    $sql->execute(array(
      ':parent_id' => $_GET['returnid'],
      ':device_action' => $_POST['device_action']
    ));
   }
  if ($_POST['device_location'] != 'NO_CHANGE')
   {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() WHERE parent_id=:parent_id AND is_removed != "1"');
    $sql->execute(array(
      ':parent_id' => $_GET['returnid'],
      ':device_location' => $_POST['device_location']
    ));
   }
  $_POST['returnid'] = $_GET['returnid'];
  $_SESSION['post_cache'] = '';
  if ($_POST['device_action'] === 'NO_CHANGE' && $_POST['device_location'] === 'NO_CHANGE')
   {
    // Do nothing
   }
  else
   {
   }
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=devices');
  die;
 }

if ($_GET['type'] === 'update_request_status')
 {
  // Set case status
  $id = num($_POST['returnid']);
  protect_page(1);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_owner($id);
  if ($_POST['case_status'] === '1')
   {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, forensic_investigator = "", phone_investigator = "", case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
   }
  else
   {
    $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
   }
  $sql->execute(array(
    ':id' => $id,
    ':case_status' => $_POST['case_status']
  ));

  logline($id, 'Action', 'Changed request ' . $id . ' status: ' . $_POST['case_status'] . '');
  $_SESSION['post_cache'] = '';
  show_saved();
  header('Location: edit_request.php?case=' . $id);
  die;
 }

if ($_GET['type'] === 'change_device_status')
 {
  // Dynamically set device action
  if ($_SESSION['user']['access'] > 1)
   {
    die;
   }
  $sql = $kirjuri_database->prepare('SELECT parent_id FROM exam_requests where id=:id');
  $sql->execute(array(
    ':id' => $_GET['uid']
  ));
  $case_id = $sql->fetch(PDO::FETCH_ASSOC);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $case_id['parent_id']);
  verify_owner($case_id['parent_id']);
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() where id=:id AND parent_id != id');
  $sql->execute(array(
    ':device_action' => $_POST['device_action'],
    ':id' => $_GET['uid']
  ));
  $_SESSION['post_cache'] = '';
  echo $twig->render('progress_bar.html', array(
    'device_action' => $_POST['device_action'],
    'settings' => $settings
  ));
  exit;
 }

if ($_GET['type'] === 'change_device_location')
 {
  // Dynamically set device location.

  if ($_SESSION['user']['access'] > 1)
  {
    die;
  }
  $sql = $kirjuri_database->prepare('SELECT parent_id FROM exam_requests where id=:id');
  $sql->execute(array(
   ':id' => $_GET['uid']
  ));
  $case_id = $sql->fetch(PDO::FETCH_ASSOC);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $case_id['parent_id']);
  verify_owner($case_id['parent_id']);
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() where id=:id AND parent_id != id');
  $sql->execute(array(
    ':device_location' => $_POST['device_location'],
    ':id' => $_GET['uid']
  ));
  $_SESSION['post_cache'] = '';
  die;
 }

if ($_GET['type'] === 'devicememo')
 {
  // Update individual device details.
  $id = num($_POST['parent_id']);
  protect_page(1);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_owner($id);

  if (isset($_POST['new_parent_id']) && ($id !== $_POST['new_parent_id']))
  {
    $id = num($_POST['new_parent_id']);
  }

  if (!empty($_POST['used_tool']))
   {
    $_POST['examiners_notes'] = sanitize_raw($_POST['examiners_notes']) . '<p>' . $_POST['used_tool'] . '</p>';
   }
  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, examiners_notes = :examiners_notes, device_type = :device_type, device_manuf = :device_manuf, device_model = :device_model, device_size_in_gb = :device_size_in_gb,
      device_owner = :device_owner, device_os = :device_os, device_time_deviation = :device_time_deviation, last_updated = NOW(),
      case_request_description = :case_request_description, device_item_number = :device_item_number, device_document = :device_document, device_identifier = :device_identifier,
      device_contains_evidence = :device_contains_evidence, device_include_in_report = :device_include_in_report WHERE id = :id AND parent_id != id;
        UPDATE exam_requests SET last_updated = NOW() where id = :parent_id;
        UPDATE exam_requests SET parent_id = :parent_id WHERE id = :id OR device_host_id = :id;');
  $sql->execute(array(
    ':report_notes' => sanitize_raw($_POST['report_notes']),
    ':examiners_notes' => sanitize_raw($_POST['examiners_notes']),
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
    ':parent_id' => $id,
    ':id' => $_POST['id'],
    ':device_include_in_report' => $_POST['device_include_in_report'],
    ':device_contains_evidence' => $_POST['device_contains_evidence']
  ));
  $_POST['returnid'] = $_GET['returnid'];
  logline($id, 'Action', 'Updated device memo UID' . $_POST['id'] . '');
  $_SESSION['post_cache'] = '';
  show_saved();
  header('Location: device_memo.php?uid=' . $_POST['returnid']);
  die;
 }

if ($_GET['type'] === 'device')
 {
  // Create new device entry
  $id = num($_POST['parent_id']);
  protect_page(1);
  csrf_session_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_owner($id);
  if ($_POST['device_host_id'] === '0')
   {
    // If new device is an associated media, it is not a host by itself

    $device_is_host = '1';
   }
  else
   {
    $device_is_host = '0';
   }
  if (empty($_POST['device_type']))
   {
    $_SESSION['message']['type'] = 'error';
    $_SESSION['message']['content'] = sprintf($_SESSION['lang']['missing_form_field'], $_SESSION['lang']['device_type']);
    $_SESSION['message_set'] = true;
    header('Location: edit_request.php?case=' . $_POST['parent_id'] . '&tab=devices');
    die;
   }
  if (empty($_POST['device_action']))
   {
    $_SESSION['message']['type'] = 'error';
    $_SESSION['message']['content'] = sprintf($_SESSION['lang']['missing_form_field'], $_SESSION['lang']['action_status']);
    $_SESSION['message_set'] = true;
    header('Location: edit_request.php?case=' . $_POST['parent_id'] . '&tab=devices');
    die;
   }
  if (empty($_POST['device_location']))
   {
    $_SESSION['message']['type'] = 'error';
    $_SESSION['message']['content'] = sprintf($_SESSION['lang']['missing_form_field'], $_SESSION['lang']['device_location']);
    $_SESSION['message_set'] = true;
    header('Location: edit_request.php?case=' . $_POST['parent_id'] . '&tab=devices');
    die;
   }
  $_POST['examiners_notes'] = "";
  if (strpos(strtoupper($_POST['device_identifier']), "IMEI") !== false)
   {
    $imei_TAC = substr(num($_POST['device_identifier']), 0, 8);
    if (strlen($imei_TAC) === 8)
     {
      if (file_exists('conf/imei.txt'))
       {
        $imei_list = file('conf/imei.txt');
        foreach ($imei_list as $line)
         {
          if (substr($line, 0, 8) === $imei_TAC)
           {
            $imei_data = explode("|", $line);
            $_POST['device_manuf'] = $imei_data['10'];
            $_POST['device_model'] = $imei_data['11'];
            $_POST['examiners_notes'] = implode(", ", $imei_data);
           }
         }
       }
     }
   }

  $sql = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = case_devicecount + 1, last_updated = NOW() WHERE id = :parent_id'); // Update device count
  $sql->execute(array(
    ':parent_id' => $_POST['parent_id']
  ));

  $sql = $kirjuri_database->prepare('INSERT INTO exam_requests (parent_id, device_host_id, device_type, device_manuf, device_model, device_identifier, device_location, device_item_number, device_document, device_time_deviation, device_os, device_size_in_gb, device_is_host, device_owner, device_include_in_report, device_contains_evidence, case_added_date, case_request_description, device_action, is_removed, last_updated, examiners_notes ) VALUES (:parent_id, :device_host_id, :device_type, :device_manuf, :device_model, :device_identifier, :device_location, :device_item_number, :device_document, :device_time_deviation, :device_os, :device_size_in_gb, :device_is_host, :device_owner, "1", "0", NOW(), :case_request_description, :device_action, :is_removed, NOW(), :examiners_notes);
        ');
  $sql->execute(array(
    ':parent_id' => $id,
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
    ':is_removed' => $_POST['is_removed'],
    ':examiners_notes' => sanitize_raw($_POST['examiners_notes'])
  ));
  logline($id, 'Action', 'Added ' . $_POST['device_type'] . '/' . $_POST['device_manuf'] . '/' . $_POST['device_model'] . '/' . $_POST['device_identifier'] . ' to case ' . $id);
  $_SESSION['post_cache'] = '';
  $_SESSION['message']['type'] = 'info';
  $_SESSION['message']['content'] = 'Changes saved.';
  $_SESSION['message_set'] = true;
  header('Location: edit_request.php?case=' . $_POST['parent_id'] . '&tab=devices');
  die;
 }

if (($_POST['save'] === 'settings') && (isset($_POST['settings_conf'])))
 {
  // Save settings to file

  protect_page(1);
  csrf_session_validate($_POST['token']);
  if (file_exists($settings_file))
   {
    file_put_contents($settings_file, $_POST['settings_conf']);
    logline('0', 'Admin', 'Settings saved.');
   }
  else
   {
    trigger_error('Settings file ' . $settings_file . ' not found.');
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
