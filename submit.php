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
    if (isset($value[3])) // Don't bother to sanitize string under 4 characters.
    {
      $value = filter_html($value);
    }
  }
  $_POST[$key] = isset($value) ? $value : '';
 }

/* MySQL used to populate empty INT fields with 0. The newer versions throw an
* error so some vars need to be set to 0 if empty.
*/

$default_to_zero = array("is_removed", "device_host_id", "device_item_number", "device_size_in_gb", "device_contains_evidence", "device_include_in_report", "case_contains_mob_dev");

foreach($default_to_zero as $key) {
	if ( (isset($_POST[$key])) && (empty($_POST[$key]) ))
	{
	  $_POST[$key] = "0";
	}
}

if ( (isset($_POST['phone_investigator'])) && (empty($_POST['phone_investigator']) ))
{
  $_POST['phone_investigator'] = "-";
}


// Get entires from cache if filling a form fails.
$_SESSION['post_cache'] = $_POST;
$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : '';

// ----- User management

switch($_GET['type']) {

case 'anon_login':
  foreach ($_SESSION['all_users'] as $user)
   {
    if (($user['id'] === '1') && ($user['password'] === "Not set."))
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
        ksess_init();
        event_log_write('0', 'Action', 'Anonymous login, created session ' . $_SESSION['user']['token']);
        message('info', $_SESSION['lang']['anon_login']);
        header('Location: index.php');
        die;
       }
     }
   }

case 'login':
  $_SESSION['user'] = null;
  $auth_success = false;
  $_POST['username'] = filter_username($_POST['username']);

	if (empty($_POST['password']) || empty($_POST['username'] || empty($_POST['auth_type'])))
	{
		header('Location: login.php');
		die;
	}

  if (file_exists('conf/BLOCK_' . hash('sha1', $_POST['username'])))
   {
    sleep(0);
    header('Location: login.php');
    die;
   }

   if (upgrade_insecure_password($_POST['username'], $_POST['password']) !== 0) {
     event_log_write('0', "Auth", "Upgraded insecure password for user " . $_POST['username']);
   }

  if ($_POST['auth_type'] === "local") {
    $auth_success = local_authenticate($_POST['username'], $_POST['password']);
  } elseif ($_POST['auth_type'] === "ldap") {
    $auth_success = ldap_authenticate($_POST['username'], $_POST['password']);
  } else {
    $auth_success = false;
  }
  // Authenticate function sets $_SESSION['user'] on success.
  if ( ($auth_success === true) && (isset($_SESSION['user'])) ) {
    if (strpos($_SESSION['user']['flags'], 'I') !== false) {
      $_SESSION['user'] = null;
      message('error', $_SESSION['lang']['account_inactive']);
      event_log_write('0', 'Auth', 'Login attempt with inactivated account: ' . $_POST['username']);
      header('Location: login.php');
      die;
    } elseif (ip_allowed() === false) {
      $_SESSION['user'] = null;
      message('error', $_SERVER['REMOTE_ADDR'] . ": " . $_SESSION['lang']['ip_address_restricted']);
      event_log_write('0', 'Auth', 'Login attempt from restricted IP address '.$_SERVER['REMOTE_ADDR'].': ' . $_POST['username']);
      header('Location: login.php');
      die;
    } else {
      ksess_init();
      message('info', $_SESSION['lang']['logged_in_as'] . ' ' . $_SESSION['user']['username']);
      event_log_write('0', 'Auth', 'Login, created session ' . $_SESSION['user']['token']);
      header('Location: index.php');
      die;
    }
  } elseif ($auth_success === false) {
    $_SESSION['user'] = null;
    file_put_contents('cache/BLOCK_' . md5(strtolower($_POST['username'])), "failed password attempt.");
    sleep(0);
    unlink('cache/BLOCK_' . md5(strtolower($_POST['username'])));
    message('error', $_SESSION['lang']['invalid_credentials']);
    event_log_write('0', 'Auth', 'Invalid login attempt: ' . $_POST['username']);
    header('Location: login.php');
    die;
  } else {
	  var_dump($auth_success);
	  var_dump($_SESSION['user']);
    echo "Something is pretty seriously wrong in submit.php.";
    die;
  }

case 'logout':
  event_log_write('0', 'Auth', 'Logged out.');
  ksess_destroy();
  header('Location: index.php');
  die;


case 'drop_session':
   ksess_validate($_GET['token']);
   ksess_verify(0);
   unlink('cache/user_' . urldecode($_GET['user']) . '/session_' . $_GET['session'] . '.txt');
   event_log_write('0', 'Auth', 'Admin destroyed session ' . $_GET['session']);
   header('Location: users.php?populate=' . $_GET['user_id'] . '#u');
   die;

case 'force_logout':
   // Force end session
   ksess_validate($_GET['token']);
   ksess_verify(0);
   if (file_exists('cache/user_' . urldecode($_GET['user'])))
   {
     delete_directory('cache/user_' . urldecode($_GET['user']));
     message('info', $_SESSION['lang']['user_logged_out']);
   }
   event_log_write('0', "Auth", "Admin terminated sessions: ".urldecode($_GET['user']));
   header('Location: '.$_SERVER['HTTP_REFERER']);
   die;

case 'create_user':
  ksess_validate($_POST['token']);
  ksess_verify(0);
  $ip_access_control['allow'] = explode(",", preg_replace("/[^0-9,\.\/]+/", "", $_POST['ip_whitelist']));
  $ip_access_control['deny'] = explode(",", preg_replace("/[^0-9,\.\/]+/", "", $_POST['ip_blacklist']));
  foreach($ip_access_control['allow'] as $ip)
  {
    if ((!empty($ip) && ((filter_var(explode("/", $ip)[0], FILTER_VALIDATE_IP) === false) || (explode("/", $ip)[1]) > "32")) )
    {
      message('error', $_SESSION['lang']['whitelist_not_a_valid_ip'] . ": " . $ip);
      header('Location: users.php?populate=' . $_POST['user_id']);
      die;
    }
  }

  foreach($ip_access_control['deny'] as $ip)
  {
    if ((!empty($ip) && ((filter_var(explode("/", $ip)[0], FILTER_VALIDATE_IP) === false) || (explode("/", $ip)[1]) > "32")) )
    {
      message('error', $_SESSION['lang']['blacklist_not_a_valid_ip'] . ": " . $ip);
      header('Location: users.php?populate=' . $_POST['user_id']);
      die;
    }
  }

  $ip_json = json_encode($ip_access_control);
  $username_input = filter_username($_POST['username']);

  if (
    !empty($username_input) &&
    !empty($_POST['name']) &&
    !empty($_POST['access']) &&
    password_verify($_POST['current_password'], $_SESSION['user']['password'])
    )
   {
    if ($_POST['delete_user'] === "delete" && $_SESSION['user']['access'] === "0")
    {
      $query = $kirjuri_database->prepare('DELETE FROM users WHERE username = :username AND id = :id AND (id != 2 OR id != 1)');
      $query->execute(array(
       ':username' => $_POST['username'],
       ':id' => $_POST['user_id']
      ));
      event_log_write('0', 'Remove', 'User deleted permanently: ' . $username_input);
      message('info', $_SESSION['lang']['user_deleted']);
      header('Location: submit.php?type=force_logout&user=' . urlencode($username_input) . '&token=' . $_SESSION['user']['token']);
      die;
    }
    foreach ($_SESSION['all_users'] as $user)
     {
      if ($user['username'] === $username_input)
       {
        $oldname = $user['name'];
        $returnid = $user['id'];
        if (!empty($_POST['password']))
         {
          $user_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
          event_log_write('0', 'Update', 'Password changed for user ' . $user['username'] . '.');
         }
        else
         {
          $user_password = $user['password'];
         }
        $query = $kirjuri_database->prepare('UPDATE users SET password = :password, name = :name, access = :access,
          flags = :flags, attr_1 = :attr_1, attr_2 = :attr_2 WHERE username = :username;
          UPDATE exam_requests SET forensic_investigator = :name WHERE forensic_investigator = :oldname;
          UPDATE exam_requests SET phone_investigator = :name WHERE phone_investigator = :oldname;');
        $query->execute(array(
          ':oldname' => $oldname,
          ':username' => $username_input,
          ':name' => ucwords(trim(substr($_POST['name'], 0, 256))),
          ':password' => $user_password,
          ':flags' => $_POST['flag1'] . $_POST['flag2'] . $_POST['flag3'] . $_POST['flag4'],
          ':access' => str_replace("A", "0", substr($_POST['access'], 0, 1)),
          ':attr_1' => 'User modified by ' . $_SESSION['user']['username'] . ' at ' . date('Y-m-d H:m'),
          ':attr_2' => $ip_json
        ));
        event_log_write('0', 'Update', 'User modified: ' . $username_input . ', access level ' . substr($_POST['access'], 0, 1));
        message('info', $_SESSION['lang']['user_modified']);
        header('Location: users.php?populate=' . $returnid . '#u');
        die;
       }
     }

    $query = $kirjuri_database->prepare('INSERT INTO users (username, password, name, access, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
    :username, :password, :name, :access, :flags, :attr_1, :attr_2,
    NULL, NULL, NULL, NULL, NULL, NULL);');
    $query->execute(array(
      ':username' => $username_input,
      ':name' => ucwords(trim(substr($_POST['name'], 0, 256))),
      ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
      ':flags' => $_POST['flag1'] . $_POST['flag2'],
      ':access' => str_replace("A", "0", substr($_POST['access'], 0, 1)),
      ':attr_1' => 'User created by ' . $_SESSION['user']['username'] . ' at ' . date('Y-m-d H:i'),
      ':attr_2' => $ip_json
    ));
    event_log_write('0', 'Add', 'User created: ' . $username_input . ', access level ' . substr($_POST['access'], 0, 1));
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

case 'update_password':
  ksess_validate($_POST['token']);
  ksess_verify(1);
  if ((!empty($_POST['new_password'])) && (password_verify($_POST['current_password'], $_SESSION['user']['password'])))
   {
    $query = $kirjuri_database->prepare('UPDATE users SET password = :newpassword WHERE username = :username AND id = :id');
    $query->execute(array(
      ':newpassword' => password_hash($_POST['new_password'], PASSWORD_DEFAULT),
      ':username' => $_SESSION['user']['username'],
      ':id' => $_SESSION['user']['id']
    ));
    event_log_write('0', 'Update', 'User changed password.');
    $_SESSION['user'] = '';
    session_destroy();
    header('Location: login.php');
   }
  else
   {
    message('Error', $_SESSION['lang']['bad_password']);
    header('Location: settings.php');
   }
  die;

case 'clear_cache':
  foreach(scandir('cache') as $cache_subdir) {
	  if(($cache_subdir[0] !== ".") && (substr($cache_subdir, 0, 4) !== "user")) {
		delete_directory($cache_subdir);
	  }
	}
  header('Location: login.php');
  die;

// ----- Messages

case 'send_message':
  ksess_validate($_POST['token']);
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
            ':subject' => base64_encode(gzdeflate(filter_html($_POST['subject']))),
            ':body' => base64_encode(gzdeflate(filter_html($_POST['body'])))
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
        ':subject' => base64_encode(gzdeflate(filter_html($_POST['subject']))),
        ':body' => base64_encode(gzdeflate(filter_html($_POST['body'])))
      ));
     }
    event_log_write('0', 'Add', 'message sent.');
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

case 'delete_received':
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('UPDATE messages SET deleted_to = "1" WHERE id = :id AND (msgto = :user OR msgfrom = :user) AND archived_to = "1"');
  $query->execute(array(
    ':user' => $_SESSION['user']['username'],
    ':id' => filter_numbers($_GET['id'])
  ));
  $query->execute();
  header('Location: messages.php#archive');
  die;

case 'delete_sent':
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('UPDATE messages SET deleted_from = "1" WHERE id = :id AND (msgto = :user OR msgfrom = :user) AND archived_from = "1"');
  $query->execute(array(
    ':user' => $_SESSION['user']['username'],
    ':id' => filter_numbers($_GET['id'])
  ));
  $query->execute();
  header('Location: messages.php#archive');
  die;

case 'delete_all':
  ksess_validate($_GET['token']);
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

case 'archive_received':
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('UPDATE messages SET archived_to = "1" WHERE id = :id AND received != "0" AND (msgto = :user OR msgfrom = :user)');
  $query->execute(array(
    ':user' => $_SESSION['user']['username'],
    ':id' => filter_numbers($_GET['id'])
  ));
  $query->execute();
  header('Location: messages.php#inbox');
  die;

case 'archive_sent':
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('UPDATE messages SET archived_from = "1" WHERE id = :id AND (msgto = :user OR msgfrom = :user)');
  $query->execute(array(
    ':user' => $_SESSION['user']['username'],
    ':id' => filter_numbers($_GET['id'])
  ));
  $query->execute();
  header('Location: messages.php#outbox');
  die;

case 'restore_received':
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('UPDATE messages SET archived_to = "0" WHERE id = :id AND (msgto = :user OR msgfrom = :user)');
  $query->execute(array(
    ':user' => $_SESSION['user']['username'],
    ':id' => filter_numbers($_GET['id'])
  ));
  $query->execute();
  header('Location: messages.php');
  die;

case 'restore_sent':
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('UPDATE messages SET archived_from = "0" WHERE id = :id AND (msgto = :user OR msgfrom = :user)');
  $query->execute(array(
    ':user' => $_SESSION['user']['username'],
    ':id' => filter_numbers($_GET['id'])
  ));
  $query->execute();
  header('Location: messages.php');
  die;

case 'delete_message':
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('DELETE FROM messages WHERE id = :id AND msgto = :user');
  $query->execute(array(
    ':user' => $_SESSION['user']['username'],
    ':id' => filter_numbers($_GET['id'])
  ));
  $query->execute();
  header('Location: messages.php');
  die;

// ----- Tool management

case 'reserve_tool':
  // Set variables that might not be present.
  $_POST['reserve_start_date'] = isset($_POST['reserve_start_date']) ? $_POST['reserve_start_date'] : '';
  $_POST['reserve_start_time'] = isset($_POST['reserve_start_time']) ? $_POST['reserve_start_time'] : '';
  $_POST['reserve_end_date']   = isset($_POST['reserve_end_date']) ? $_POST['reserve_end_date'] : '';
  $_POST['reserve_end_time']   = isset($_POST['reserve_end_time']) ? $_POST['reserve_end_time'] : '';
  ksess_verify(1);
  // Check for CSRF token, no POST token present if removing a reservation
  if(isset($_POST['token'])) {
      ksess_validate($_POST['token']);
  } elseif(isset($_GET['token'])) {
      ksess_validate($_GET['token']);
  } else {
      // Y U no token? U die!!
      die;
  }
  // Concatenate time and date to one string.
  $res_start = $_POST['reserve_start_date'] . " " . $_POST['reserve_start_time'];
  $res_end   = $_POST['reserve_end_date'] . " " . $_POST['reserve_end_time'];
  // If tool ID is set in POST, then this is a reservation, check for empty vars.
  if(isset($_POST['tool_id'])) {
      $returnid = filter_numbers($_POST['tool_id']);
      if((empty($res_start)) || (empty($res_end)) || (empty($returnid))) {
          message('Error', $_SESSION['lang']['missing_form_field']);
          header('Location: tools.php?populate=' . $returnid);
          die;
      }
  } else {
      // Get tool id from GET variable if dropping a reservation.
      $returnid = filter_numbers($_GET['returnid']);
  }
  // If start of reservation is after end, swap values.
  if(strtotime($res_start) > strtotime($res_end)) {
      // If end is before start, swap values.
      $tmp       = $res_end;
      $res_end   = $res_start;
      $res_start = $tmp;
      unset($tmp);
  }
  // Get tool reservations stored as a JSON value in attr_4.
  $query = $kirjuri_database->prepare('SELECT attr_4 FROM tools WHERE id = :tool_id');
  $query->execute(array(
      ':tool_id' => $returnid
  ));
  $res_json = $query->fetch(PDO::FETCH_ASSOC);
  $res_json = $res_json['attr_4'];
  // Decode the array if it is populated, set an empty array if not.
  if(!empty($res_json)) {
      $res_arr = json_decode($res_json, true);
  } else {
      $res_arr = array();
  }
  // Compare the proposed reservation date with existing dates if POST data present.
  // Convert the string to epoch for easier comparison.
  if(isset($_POST['tool_id'])) {
      $c = strtotime($res_start);
      $d = strtotime($res_end);
      foreach($res_arr as $key => $compare) {
          $a = strtotime($compare['reserve_start']);
          $b = strtotime($compare['reserve_end']);
          // If new reservation overlaps old reservations, exit with error and highlight first conflict.
          if( ($a <= $c) && ($c < $b) || ($a < $d) && ($d <= $b) || ($c < $a) && ($d > $b)) {
              message('error', $_SESSION['lang']['calendar_conflict'] . ": " . $compare['reserve_start'] . ' -> ' . $compare['reserve_end'] . ": " . $compare['reserved_for']);
              header('Location: tools.php?populate=' . $returnid . '&highlight=' . $key);
              die;
          }
      }
  }
  // If dropping a reservation, check that the user is admin or the reservation is for them.
  // Using real names here, so changing user's real name will prevent removing old reservations.
  if(isset($_GET['drop'])) {
      if(($_SESSION['user']['access'] === "0") || ($res_arr[$_GET['drop']]['reserved_for'] === $_SESSION['user']['name'])) {
          event_log_write('0', "Calendar", "Removed tool ID " . $returnid . " reservation: " . $res_arr[$_GET['drop']]['reserve_start'] . " -> " . $res_arr[$_GET['drop']]['reserve_end'] . " for " . $res_arr[$_GET['drop']]['reserved_for']);
          // Remove the reservation from the reservations array.
          unset($res_arr[$_GET['drop']]);
      }
  } else {
      event_log_write('0', "Calendar", "Reserved tool ID " . $returnid . ": " . $res_start . " -> " . $res_end . " for " . $_POST['reserved_for']);
      // Count from zero and find the next free array number.
      $i = 0;
      while(isset($res_arr[$i]) === true) {
          $i++;
      }
      // Populate the free number.
      $res_arr[$i]['reserved_for']  = $_POST['reserved_for'];
      $res_arr[$i]['reserve_start'] = $res_start;
      $res_arr[$i]['reserve_end']   = $res_end;
      // Do not allow oversized comments. 500 characters should be enough.
      $res_arr[$i]['comment']       = substr($_POST['comment'], 0, 500);
  }
  // Declare a function for sorting the array by start date
  function date_compare($a, $b) {
      $t1 = strtotime($a['reserve_start']);
      $t2 = strtotime($b['reserve_start']);
      return $t1 - $t2;
  }
  // Sort the array
  usort($res_arr, 'date_compare');
  // Encode the reservations array back to JSON
  $res_json = json_encode($res_arr);
  // Write the JSON string back to the database.
  $query    = $kirjuri_database->prepare('UPDATE tools SET attr_4 = :attr_4 WHERE id = :tool_id');
  $query->execute(array(
      ':tool_id' => $returnid,
      ':attr_4' => $res_json
  ));
  // Done, return to tools.
  header('Location: tools.php?populate=' . $returnid);
  die;

case 'add_tool':
  ksess_verify(0);
  ksess_validate($_POST['token']);
  if (!empty($_POST['product_name']))
   {
    $query = $kirjuri_database->prepare('INSERT INTO tools (product_name, hw_version, sw_version, serialno, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
    :product_name, :hw_version, :sw_version, :serialno, :flags, NOW(), "", :comment, NULL, NULL, NULL, NULL, NULL);');
    $query->execute(array(
      ':product_name' => trim(substr($_POST['product_name'], 0, 128)),
      ':hw_version' => trim(substr($_POST['hw_version'], 0, 64)),
      ':sw_version' => trim(substr($_POST['sw_version'], 0, 64)),
      ':serialno' => $_POST['serialno'],
      ':comment' => $_POST['comment'],
      ':flags' => $_POST['flag1'] . $_POST['flag2']
    ));
    event_log_write('0', 'Add', 'tool created: ' . trim(substr($_POST['product_name'], 0, 64)));
    message('info', $_SESSION['lang']['tool_added'] . ": " . trim(substr($_POST['product_name'], 0, 128)));
    header('Location: tools.php');
    die;
   }
  else
   {
    header('Location: tools.php');
    die;
   }

case 'update_tool':
  ksess_verify(0);
  ksess_validate($_POST['token']);
  if($_POST['drop_tool'] === "yes") {
    $query = $kirjuri_database->prepare('DELETE FROM tools WHERE id = :tool_id');
    $query->execute(array(
      ':tool_id' => $_POST['tool_id']
    ));
    event_log_write('0', 'Remove', 'tool ID ' . $_POST['tool_id'] . ' removed: ' . trim(substr($_POST['product_name'], 0, 128)));
    message('info', $_SESSION['lang']['tool_removed'] . ": " . trim(substr($_POST['product_name'], 0, 128)));
  } else {
    $query = $kirjuri_database->prepare('UPDATE tools SET hw_version = :hw_version, sw_version = :sw_version, attr_3 = :comment, flags = :flags,
        attr_2 = CONCAT(NOW(),";", :hw_version_old, " -> ", :hw_version, ";", :sw_version_old, " -> ", :sw_version, ";", :flags, ";", ", ", IFNULL(attr_2,"")) WHERE id = :tool_id');
    $query->execute(array(
      ':tool_id' => $_POST['tool_id'],
      ':hw_version' => trim(substr($_POST['hw_version'], 0, 64)),
      ':sw_version' => trim(substr($_POST['sw_version'], 0, 64)),
      ':hw_version_old' => trim(substr($_POST['hw_version_old'], 0, 64)),
      ':sw_version_old' => trim(substr($_POST['sw_version_old'], 0, 64)),
      ':comment' => $_POST['comment'],
      ':flags' => $_POST['flag1']
    ));
    event_log_write('0', 'Update', 'tool updated: ' . trim(substr($_POST['product_name'], 0, 128)) . ", HW version: " .
    $_POST['hw_version_old'] . " -> " . $_POST['hw_version'] . ", SW version: " .
    $_POST['sw_version_old'] . " -> " . $_POST['sw_version'] . ", Comment: " .
    $_POST['comment_old'] . " -> " . $_POST['comment']);
    message('info', $_SESSION['lang']['tool_updated'] . ": " . trim(substr($_POST['product_name'], 0, 128)));
  }
  header('Location: tools.php?populate=' . $_POST['tool_id']);
  die;

// ----- Request management

case 'save_template':
  ksess_verify(0);
  ksess_validate($_POST['token']);
  $template = filter_letters_and_numbers($_GET['template']);
  $templatefile = filter_html($_POST['templatefile']);
  file_put_contents('conf/' . $template . ".local", $templatefile);
  header('Location: settings.php');
  die;

case 'case_access':
  $id = filter_numbers($_GET['id']);
  ksess_verify(1);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_case_ownership($id);
  $audit_stamp = audit_log_write($_POST);
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
  event_log_write($id, 'Access', 'Access group updated: ' . str_replace(";", ", ", $accessgroup) . ". " , $audit_stamp);
  header('Location: edit_request.php?case=' . $id . '&tab=access');
  die;

case 'examination_request':
  // Create an examination request.
  ksess_validate($_POST['token']);
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
  $query = $kirjuri_database->prepare(' INSERT INTO exam_requests ( id, parent_id, case_id, case_name, case_file_number, case_investigator, case_investigator_unit, case_investigator_tel, case_investigation_lead, case_confiscation_date, last_updated, case_added_date, case_crime, examiners_notes, classification, case_suspect, case_request_description, is_removed, case_status, case_urgency, case_urg_justification, case_requested_action, case_contains_mob_dev, case_devicecount ) VALUES ( NULL, "0", :case_id, :case_name, :case_file_number, :case_investigator, :case_investigator_unit, :case_investigator_tel, :case_investigation_lead, :case_confiscation_date, NOW(), NOW(), :case_crime, :examiners_notes, :classification, :case_suspect, :case_request_description, "0", "1", :case_urgency, :case_urg_justification, :case_requested_action, :case_contains_mob_dev, "0" );
        UPDATE exam_requests SET parent_id=last_insert_id() WHERE ID=last_insert_id();
        ');
  $query->execute(array(
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
  $audit_stamp = audit_log_write($_POST);
  $query = $kirjuri_database->prepare('SELECT LAST_INSERT_ID() as id'); // Update device count
  $query->execute();
  $new_uid = $query->fetch(PDO::FETCH_ASSOC);
  if (!file_exists('logs/cases/')) {
      mkdir('logs/cases');
  }
  if (!file_exists('logs/cases/uid'. $new_uid['id'])) {
      mkdir('logs/cases/uid'. $new_uid['id']);
  }
  $_SESSION['post_cache'] = '';
  event_log_write($new_uid['id'], 'Add', 'Added examination request ' . $case_id . ' / ' . $_POST['case_name'] . ".", $audit_stamp);
  echo $twig->render('thankyou.twig', array(
    'session' => $_SESSION,
    'case_id' => $case_id,
    'id' => $new_uid['id'],
    'settings' => $prefs['settings'],
    'lang' => $_SESSION['lang']
  ));
  die;

case 'case_update':
  // Update examination request.
  ksess_verify(1);
  ksess_validate($_POST['token']);
  verify_case_ownership($_GET['uid']);
  if (!isset($_POST['phone_investigator'])) {
    $_POST['phone_investigator'] = "-";
  }
  $audit_stamp = audit_log_write($_POST);
  if ($_POST['forensic_investigator'] !== '')
   {
    // Set the case as started if an f.investigator is assigned.

    $case_status = '2';
   }
  else
   {
    $case_status = '1';
   }
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET case_name = :case_name, case_file_number = :case_file_number, case_crime = :case_crime, classification = :classification, case_suspect = :case_suspect, case_investigation_lead = :case_investigation_lead, case_investigator = :case_investigator, forensic_investigator = :forensic_investigator, phone_investigator = :phone_investigator, case_investigator_tel = :case_investigator_tel, case_investigator_unit = :case_investigator_unit, case_request_description = :case_request_description, case_confiscation_date = :case_confiscation_date, case_start_date = NOW(), last_updated = NOW(), is_removed = "0", case_contains_mob_dev = :case_contains_mob_dev, case_status = :case_status, case_urgency = :case_urgency where id=:id AND parent_id = :id');
  $query->execute(array(
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
  event_log_write($_GET['uid'], 'Update', 'Updated request ' . $_POST['case_name'] . ". ", $audit_stamp);
  $_POST['returnid'] = $_GET['uid'];
  $_SESSION['post_cache'] = '';
  show_saved_succesfully();
  header('Location: edit_request.php?case=' . $_POST['returnid']);
  die;

case 'report_notes':
  // Save case report notes.
  ksess_verify(1);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $_POST['returnid']);
  verify_case_ownership($_POST['returnid']);
  $audit_stamp = audit_log_write($_POST);
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, last_updated = NOW() where id=:id AND parent_id = :id AND is_removed != "1"');
  $query->execute(array(
    ':username' => $_SESSION['user']['username'],
    ':id' => $_POST['returnid'],
    ':report_notes' => filter_html($_POST['report_notes'])
  ));
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['report_notes_saved']);
  $_SESSION['message_set'] = true;
  event_log_write($_POST['returnid'], 'Update', 'Updated report notes. ', $audit_stamp);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=report_notes');
  die;

case 'examiners_notes':
  // Save examiners private notes
  ksess_verify(1);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $_POST['returnid']);
  verify_case_ownership($_POST['returnid']);
  $audit_stamp = audit_log_write($_POST);
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET examiners_notes = :examiners_notes, last_updated = NOW() where id=:id AND parent_id = :id AND is_removed != "1"');
  $query->execute(array(
    ':username' => $_SESSION['user']['username'],
    ':id' => $_POST['returnid'],
    ':examiners_notes' => $_POST['examiners_notes']
  ));
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['exam_notes_saved']);
  event_log_write($_POST['returnid'], 'Update', 'Updated examiners notes. ' , $audit_stamp);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=examiners_notes');
  die;


case 'set_removed':
  // Remove device from case
  ksess_verify(1);
  ksess_validate($_GET['token']);
  csrf_case_validate($_GET['ct'], $_GET['returnid']);
  verify_case_ownership($_GET['returnid']);
  $audit_stamp = audit_log_write($_GET);
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() where id=:id AND parent_id = :returnid;
        UPDATE exam_requests SET is_removed = "1" where device_host_id=:id');
  $query->execute(array(
    ':id' => $_GET['uid'],
    ':returnid' => $_GET['returnid']
  ));
  $query = $kirjuri_database->prepare('SELECT count(id) from exam_requests where id != parent_id AND parent_id=:id AND is_removed="0"');
  $query->execute(array(
    ':id' => $_GET['returnid']
  ));
  $devicecount = $query->fetch(PDO::FETCH_ASSOC);
  $devicecount = $devicecount['count(id)'];
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = :devicecount, last_updated = NOW() where id=:id');
  $query->execute(array(
    ':devicecount' => $devicecount,
    ':id' => $_GET['returnid']
  ));
  $_POST['returnid'] = $_GET['returnid'];
  $_SESSION['post_cache'] = '';
  event_log_write($_GET['returnid'], 'Remove', 'Removed device UID' . $_GET['uid'] . ". " , $audit_stamp);
  message('info', $_SESSION['lang']['device_removed']);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=devices');
  die;

case 'device_attach':
  // Associate a media/device with host device
  ksess_verify(1);
  ksess_validate($_POST['token']);
  if (isset($_POST['isanta']))
   {
    $query = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = :isanta, last_updated = NOW() where id=:id AND parent_id != id;
        UPDATE exam_requests SET device_is_host = "1" where id = :isanta;');
    $query->execute(array(
      ':id' => $_GET['uid'],
      ':isanta' => $_POST['isanta']
    ));
   }
  $_POST['returnid'] = $_GET['returnid'];
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['device_attached']);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=devices');
  die;

case 'device_detach':
  // Remove device association
  ksess_verify(1);
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET device_host_id = "0", last_updated = NOW() where id=:id AND parent_id != id');
  $query->execute(array(
    ':id' => $_GET['uid']
  ));
  $_POST['returnid'] = $_GET['returnid'];
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['device_detached']);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=devices');
  die;

case 'set_removed_case':
  // Remove case
  $id = filter_numbers($_POST['remove_exam_request']);
  ksess_verify(1);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_case_ownership($id);
  $audit_stamp = audit_log_write($_GET);
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET is_removed = "1", last_updated = NOW() WHERE id=:id AND parent_id = :id');
  $query->execute(array(
    ':username' => $_SESSION['user']['username'],
    ':id' => $id
  ));
  event_log_write($id, 'Remove', 'Removed case UID' . $id . ". " , $audit_stamp);
  $_SESSION['post_cache'] = '';
  message('info', $_SESSION['lang']['case_removed']);
  header('Location: index.php');
  die;

case 'move_all':
  // Change all device locations and/or actions
  ksess_verify(1);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $_GET['returnid']);
  verify_case_ownership($_GET['returnid']);
  $audit_stamp = audit_log_write($_POST);
  if ($_POST['device_action'] != 'NO_CHANGE')
   {
    $query = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() WHERE parent_id=:parent_id');
    $query->execute(array(
      ':parent_id' => $_GET['returnid'],
      ':device_action' => $_POST['device_action']
    ));
   }
  if ($_POST['device_location'] != 'NO_CHANGE')
   {
    $query = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() WHERE parent_id=:parent_id AND is_removed != "1"');
    $query->execute(array(
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
  event_log_write($_GET['returnid'], 'Update', 'Set all devices in case UID' . $_GET['returnid'] . ". " , $audit_stamp);
  header('Location: edit_request.php?case=' . $_POST['returnid'] . '&tab=devices');
  die;

case 'update_request_status':
  // Set case status
  $id = filter_numbers($_POST['returnid']);
  ksess_verify(1);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_case_ownership($id);
  $audit_stamp = audit_log_write($_POST);
  if ($_POST['case_status'] === '1')
   {
    $query = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, forensic_investigator = "", phone_investigator = "", case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
   }
  else
   {
    $query = $kirjuri_database->prepare('UPDATE exam_requests SET case_status = :case_status, case_ready_date = NOW(), last_updated = NOW() WHERE parent_id = :id');
   }
  $query->execute(array(
    ':id' => $id,
    ':case_status' => $_POST['case_status']
  ));
  event_log_write($id, 'Update', 'Changed request ' . $id . ' status: ' . $_POST['case_status'] . '. ' , $audit_stamp);
  $_SESSION['post_cache'] = '';
  show_saved_succesfully();
  header('Location: edit_request.php?case=' . $id);
  die;

case 'change_device_status':
  // Dynamically set device action
  if ($_SESSION['user']['access'] > 1)
   {
    die;
   }
  $query = $kirjuri_database->prepare('SELECT parent_id FROM exam_requests where id=:id');
  $query->execute(array(
    ':id' => $_GET['uid']
  ));
  $case_id = $query->fetch(PDO::FETCH_ASSOC);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $case_id['parent_id']);
  verify_case_ownership($case_id['parent_id']);
  $audit_stamp = audit_log_write($_POST);
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET device_action = :device_action, last_updated = NOW() where id=:id AND parent_id != id');
  $query->execute(array(
    ':device_action' => $_POST['device_action'],
    ':id' => $_GET['uid']
  ));
  $_SESSION['post_cache'] = '';
  event_log_write($case_id['parent_id'], "Update", "Changed device UID".$_GET['uid']." status to " .$_POST['device_action']. ". " , $audit_stamp);
  echo $twig->render('progress_bar.twig', array(
    'device_action' => $_POST['device_action'],
    'settings' => $prefs['settings']
  ));
  die;

case 'change_device_location':
  // Dynamically set device location.
  if ($_SESSION['user']['access'] > 1)
  {
    die;
  }
  $query = $kirjuri_database->prepare('SELECT parent_id FROM exam_requests where id=:id');
  $query->execute(array(
   ':id' => $_GET['uid']
  ));
  $case_id = $query->fetch(PDO::FETCH_ASSOC);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $case_id['parent_id']);
  verify_case_ownership($case_id['parent_id']);
  $audit_stamp = audit_log_write($_POST);
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET device_location = :device_location, last_updated = NOW() where id=:id AND parent_id != id');
  $query->execute(array(
    ':device_location' => $_POST['device_location'],
    ':id' => $_GET['uid']
  ));
  $_SESSION['post_cache'] = '';
  event_log_write($case_id['parent_id'], "Update", "Changed device UID".$_GET['uid']." location to " .$_POST['device_location']. ". " , $audit_stamp);
  die;

case 'devicememo':
  // Update individual device details.
  $id = filter_numbers($_POST['parent_id']);
  ksess_verify(1);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_case_ownership($id);
  $audit_stamp = audit_log_write($_POST);
  if (trim(strtolower(strip_tags($_POST['report_notes']))) === trim(strtolower(strip_tags($_POST['template_report_notes']))))
  {
    $_POST['report_notes'] = "";
  }
  if (isset($_POST['new_parent_id']) && ($id !== $_POST['new_parent_id']))
  {
    $id = filter_numbers($_POST['new_parent_id']);
  }

  if (!empty($_POST['used_tool']))
   {
    $_POST['examiners_notes'] = filter_html($_POST['examiners_notes']) . '<p>' . $_POST['used_tool'] . '</p>';
   }
  $query = $kirjuri_database->prepare('UPDATE exam_requests SET report_notes = :report_notes, examiners_notes = :examiners_notes, device_type = :device_type, device_manuf = :device_manuf, device_model = :device_model, device_size_in_gb = :device_size_in_gb,
      device_owner = :device_owner, device_os = :device_os, device_time_deviation = :device_time_deviation, last_updated = NOW(),
      case_request_description = :case_request_description, device_item_number = :device_item_number, device_document = :device_document, device_identifier = :device_identifier,
      device_contains_evidence = :device_contains_evidence, device_include_in_report = :device_include_in_report WHERE id = :id AND parent_id != id;
        UPDATE exam_requests SET last_updated = NOW() where id = :parent_id;
        UPDATE exam_requests SET parent_id = :parent_id WHERE id = :id OR device_host_id = :id;');
  $query->execute(array(
    ':report_notes' => filter_html($_POST['report_notes']),
    ':examiners_notes' => filter_html($_POST['examiners_notes']),
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
  event_log_write($id, 'Update', 'Updated device memo UID' . $_POST['id'] . '. ' , $audit_stamp);
  $_SESSION['post_cache'] = '';
  show_saved_succesfully();
  header('Location: device_memo.php?uid=' . $_POST['returnid']);
  die;

case 'device':
  // Create new device entry
  $id = filter_numbers($_POST['parent_id']);
  ksess_verify(1);
  ksess_validate($_POST['token']);
  csrf_case_validate($_POST['ct'], $id);
  verify_case_ownership($id);
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
    $imei_TAC = substr(filter_numbers($_POST['device_identifier']), 0, 8);
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

  $query = $kirjuri_database->prepare('UPDATE exam_requests SET case_devicecount = case_devicecount + 1, last_updated = NOW() WHERE id = :parent_id'); // Update device count
  $query->execute(array(
    ':parent_id' => $_POST['parent_id']
  ));

  $query = $kirjuri_database->prepare('INSERT INTO exam_requests (parent_id, device_host_id, device_type, device_manuf, device_model, device_identifier, device_location, device_item_number, device_document, device_time_deviation, device_os, device_size_in_gb, device_is_host, device_owner, device_include_in_report, device_contains_evidence, case_added_date, case_request_description, device_action, is_removed, last_updated, examiners_notes ) VALUES (:parent_id, :device_host_id, :device_type, :device_manuf, :device_model, :device_identifier, :device_location, :device_item_number, :device_document, :device_time_deviation, :device_os, :device_size_in_gb, :device_is_host, :device_owner, "1", "0", NOW(), :case_request_description, :device_action, :is_removed, NOW(), :examiners_notes);
        ');
  $query->execute(array(
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
    ':examiners_notes' => filter_html($_POST['examiners_notes'])
  ));
  $query = $kirjuri_database->prepare('SELECT LAST_INSERT_ID() as id'); // Update device count
  $query->execute();
  $new_uid = $query->fetch(PDO::FETCH_ASSOC);
  $audit_stamp = audit_log_write($_POST);
  event_log_write($id, 'Add', 'Added device UID' . $new_uid['id'] . ": ". $_POST['device_type'] . ' ' . $_POST['device_manuf'] . ' ' . $_POST['device_model'] . ' ' . $_POST['device_identifier'] . ' to case ' . $id . '. ' , $audit_stamp);
  $_SESSION['post_cache'] = '';
  $_SESSION['message']['type'] = 'info';
  $_SESSION['message']['content'] = 'Changes saved.';
  $_SESSION['message_set'] = true;
  header('Location: edit_request.php?case=' . $_POST['parent_id'] . '&tab=devices');
  die;

case "reset_default_settings":
  ksess_verify(0);
  ksess_validate($_GET['token']);
  unlink('conf/settings.local');
  event_log_write('0', 'Admin', 'Default settings restored.');
  header('Location: settings.php');
  die;

case "save_langfile":
  ksess_verify(0);
  ksess_validate($_POST['token']);
  $langfile_name = 'conf/lang_' . substr(filter_letters_and_numbers($_POST['countrycode']), 0, 3) . ".JSON";
  unset($_POST['countrycode']);
  unset($_POST['token']);
  $langfile = json_encode($_POST, JSON_PRETTY_PRINT);
  file_put_contents($langfile_name, $langfile);
  show_saved_succesfully();
  header('Location: lang_editor.php');
  die;

case "save_settings":
  ksess_verify(0);
  ksess_validate($_POST['token']);
  $audit_stamp = audit_log_write($_POST);
  $settings_output = "; Saved settings\r\n\r\n[settings]\r\n";
  foreach($_POST['settings'] as $key => $value)
  {
    $settings_output = $settings_output . $key . " = \"" . $value . "\";\r\n";
  }
  $settings_output = $settings_output . "\r\n[inv_units]\r\n";
  $units = explode(",", $_POST['inv_units']);
  $unit_key = 1;
  foreach ($units as $value) {
    $unit_key++;
    $settings_output = $settings_output . $unit_key . " = \"" . trim($value) . "\";\r\n";
  }
  $settings_output = $settings_output . "\r\n[statistics_chart_colors]\r\n";
  foreach($_POST['chart'] as $key => $value)
  {
    $settings_output = $settings_output . $key . " = \"" . $value . "\";\r\n";
  }
  file_put_contents('conf/settings.local', $settings_output);
  event_log_write('0', 'Admin', 'Settings saved.', $audit_stamp);
  show_saved_succesfully();
  $_SESSION['post_cache'] = '';
  header('Location: settings.php');
  die;

case 'remove_attachment':
  ksess_verify(1);
  ksess_validate($_GET['token']);
  $query = $kirjuri_database->prepare('SELECT name, hash, id, request_id, attr_1 FROM attachments WHERE id = :id');
  $query->execute(array(':id' => $_GET['file']));
  $file = $query->fetch(PDO::FETCH_ASSOC);
  csrf_case_validate($_GET['ct'], $file['request_id']);
  verify_case_ownership($file['request_id']);
  $query = $kirjuri_database->prepare('DELETE FROM attachments WHERE id = :id');
  $query->execute(array(':id' => $_GET['file']));
  event_log_write($file['request_id'], 'Remove', 'Attachment removed: '. $file['name'] . ", file sha256: " . $file['hash'], $file['attr_1']);
  header('Location: edit_request.php?case=' . $file['request_id']);
  die;

default:
	// Default to error if no handlers
	event_log_write('0', 'Error', 'submit.php called with erroneous value.');
	header('Location: index.php'); // Fall back to index with an error if no conditions are met.
	die;
  }
die;
