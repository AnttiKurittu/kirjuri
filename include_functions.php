<?php
/* This is the 'header' file in all php files containing shared functions etc.
** It also performs some other tasks.
*/
$mysql_timer_start = microtime(true);

if (version_compare(PHP_VERSION, '7.0.0') <= 0) {
    echo "Kirjuri requires PHP7 to run. You are using " . phpversion() . ". Please upgrade your PHP environment.";
    die;
}

// Go to installer if no credentials found.
if (!file_exists('conf/mysql_credentials.php')) {
    header('Location: install.php');
    die;
}

// Load dependencies
require __DIR__.'/vendor/autoload.php';
$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
$loader = new Twig_Loader_Filesystem('views/');
$twig = new Twig_Environment($loader, array(
  'cache' => 'cache',
  'auto_reload' => true,
));

$pur_config = HTMLPurifier_Config::createDefault();
$pur_config->set('Cache.SerializerPath', './cache');
$purifier = new HTMLPurifier($pur_config);

session_name('KirjuriSessionID');
session_start(); // Start a PHP session

foreach($_GET as $key => $value) { // Lightly sanitize GET variables
  $strip_chars = array("<", ">", "'", ";");
  $value = str_replace($strip_chars, "", $value);
  $_GET[$key] = isset($value) ? $value : '';
}

// Set variables for message display.
$_SESSION['message_set'] = isset($_SESSION['message_set']) ? $_SESSION['message_set'] : '';
$_SESSION['user'] = isset($_SESSION['user']) ? $_SESSION['user'] : ''; //

// If message has been set, do not clear it. Invidial files set message as shown before rendering page.
if ($_SESSION['message_set'] === false) {
  $_SESSION['message']['type'] = '';
  $_SESSION['message']['content'] = '';
}

function event_log_write($case_id = "0", $event_level = "Action", $description, $audit_log_file = "-") {
  // Logging function.
  if (!isset($_SESSION['user']['token'])) {
	  $sessiontoken = "-";
  } else {
	  $sessiontoken = $_SESSION['user']['token'];
  }

  if (!isset($_SESSION['user']['username'])) {
    $session_username = "-";
  } else {
    $session_username = $_SESSION['user']['username'];
  }

  $case_id = filter_numbers($case_id);
  $log = strftime("%d/%b/%Y:%H:%M:%S %z", time()).';'.$session_username.';'.$event_level.';"'.$description.'";'.$_SERVER['REQUEST_URI'].';'.$_SERVER['REMOTE_ADDR'].';'.$audit_log_file.';Session ID: '.$sessiontoken.';';

  if($case_id === "0") {
     file_put_contents('logs/kirjuri.log', $log."-;\r\n", FILE_APPEND);
     return true;
  }
  else {
    if (!file_exists('logs/cases/')) {
        mkdir('logs/cases');
    }
    if (!file_exists('logs/cases/uid'. $case_id)) {
        mkdir('logs/cases/uid'. $case_id);
    }
    $case_logfile = 'logs/cases/uid' . $case_id . '/events.log';
  }
  file_put_contents($case_logfile, $log."\r\n", FILE_APPEND);
  file_put_contents('logs/kirjuri.log', $log."uid".$case_id.";"."\r\n", FILE_APPEND);
  return true;
}

function kirjuri_error_handler($errno, $errstr, $errfile, $errline) // Trigger an error
{
  global $twig;
  global $prefs;
  if ($prefs['settings']['show_errors'] === '1') {
  // Show a message if errors are permitted on screen.
    $errnums = array(
      '1' => 'Error',
      '2' => 'Warning',
      '4' => 'Parse error',
      '8' => 'Notice',
      '16' => 'Core error',
      '32' => 'Compile warning',
      '64' => 'Compile error',
      '128' => 'Compile warning',
      '256' => 'User error',
      '512' => 'User warning',
      '1024' => 'User notice',
      '2048' => 'Strict',
      '4096' => 'Recoverable error',
      '8192' => 'Deprecated',
      '16384' => 'User deprecated',
      '32767' => 'All errors'
    );
    $_SESSION['message']['type'] = 'error';
    $_SESSION['message']['content'] = $errnums[$errno].': '.$errstr;
    $_SESSION['message_set'] = true;
    }
    event_log_write('0', 'Error', $errno.' '.$errstr.', File: '.$errfile.', line '.$errline);
}

function array_trim($array) {
  foreach($array as $key => $value) {
    if( ($value === "") || ($value === null) ) {
      unset($array[$key]);
    }
  }
  return $array;
}

function local_authenticate($username, $password) {
  // Authenticate against a local account
  $username = filter_username($username);
  try {
    $kirjuri_database = connect_database('kirjuri-database');
    $query = $kirjuri_database->prepare('SELECT * FROM users WHERE username = :username AND (NOT attr_3 = :attr_3 OR attr_3 IS NULL) LIMIT 1');
    $query->execute(array(':username' => $username, ':attr_3' => "LDAP_AUTH_ONLY"));
    $user_record = $query->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    session_destroy();
    echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
    die;
  }
  if (password_verify($password, $user_record['password'])) {
    $_SESSION['user'] = $user_record;
    event_log_write('0', "Auth", "Succesful local authentication for user " . $username);
    return true;
  } else {
    $_SESSION['user'] = null;
    event_log_write('0', "Auth", "Failure on local authentication for user " . $username);
    return false;
  }
}

function ldap_authenticate($username, $password) {
  // Source: https://www.exchangecore.com/blog/how-use-ldap-active-directory-authentication-php/
  //$username = filter_username($username);
  global $prefs;
  if ($prefs['settings']['enable_ldap_authentication'] !== "1") {
    return false;
  }
  $ldap_domain = $prefs['settings']['ldap_domain'];
  $search_string = $prefs['settings']['ldap_search_string'];
  $ldaprdn = $ldap_domain . "\\" . $username;
  $ldap = ldap_connect($prefs['settings']['ldap_server_address']);
  ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
  $bind = @ldap_bind($ldap, $ldaprdn, $password);
  if ($bind) { // On succesfull LDAP auth.
    try {
      $kirjuri_database = connect_database('kirjuri-database');
      $query = $kirjuri_database->prepare('SELECT * FROM users WHERE username = :username AND attr_3 = "LDAP_AUTH_ONLY" LIMIT 1');
      $query->execute(array(':username' => $username));
      $user_record = $query->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      session_destroy();
      echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
      die;
    }
    if (empty($user_record)) {
      $filter="(sAMAccountName=".$username.")";
      $result = ldap_search($ldap, $search_string, $filter);
      ldap_sort($ldap,$result,"sn");
      $info = ldap_get_entries($ldap, $result);
      for ($i=0; $i<$info["count"]; $i++) {
        if($info['count'] > 1)
        break;
      $ldap_realname = $info[$i]["displayname"][0];
      }
      @ldap_close($ldap);
      $query = $kirjuri_database->prepare('SELECT * FROM users WHERE username = :username AND (NOT attr_3 = :attr_3 OR attr_3 IS NULL) LIMIT 1');
      $query->execute(array(':username' => $username, ':attr_3' => "LDAP_AUTH_ONLY"));
      $user_record = $query->fetch(PDO::FETCH_ASSOC);
      if ($user_record !== false) {
        event_log_write('0', "Error", "Local-only account exists for succesfully remote authenticated user " . $username);
        return false; // FAIL LOGIN IF LOCAL ACCOUNT EXISTS
      } else {
        $query = $kirjuri_database->prepare('INSERT INTO users (username, password, name, access, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8)
        VALUES (:username, :password, :name, :access, :flags, :attr_1, :attr_2, :attr_3, NULL, NULL, NULL, NULL, NULL)');
        $query->execute(array(
          ':username' => $username,
          ':name' => $ldap_realname,
          ':password' => "API_ONLY_" . generate_token(32),
          ':flags' => "MF",
          ':access' => "1",
          ':attr_1' => 'User imported from LDAP ' . $_SESSION['user']['username'] . ' at ' . date('Y-m-d H:i'),
          ':attr_2' => "",
          ':attr_3' => "LDAP_AUTH_ONLY"
        ));
      }
      event_log_write('0', "Auth", "LDAP: Created account for user " . $username);
      $query = $kirjuri_database->prepare('SELECT * FROM users WHERE username = :username AND name = :name AND attr_3 = :attr_3');
      $query->execute(array(
        ':username' => $username,
        ':name' => $ldap_realname,
        ':attr_3' => "LDAP_AUTH_ONLY"
      ));
      $user_record = $query->fetch(PDO::FETCH_ASSOC);
      $_SESSION['user'] = $user_record;
      event_log_write('0', "Auth", "Succesful remote authentication for user " . $username);
      return true;

    } elseif ($username === $user_record['username']) {
      event_log_write('0', "Auth", "Succesful remote authentication for user " . $username);
      $_SESSION['user'] = $user_record;
      return true;

    } else {
      echo "Something went really wrong.";
      die;
    }
  }
  // LDAP auth failure
  return false;
}

function ip_allowed() {
  $access_allowed_from_ip = false; // Deny access by default
  $ip_access_list = json_decode($_SESSION['user']['attr_2'], TRUE); // Get blacklists
  if (empty($ip_access_list)) {
    $ip_access_list['allow'] = array();
    $ip_access_list['deny'] = array();
  }
  if (file_exists('conf/access_list.php')) {
    $global_ip_access_list = include('conf/access_list.php');
    foreach($global_ip_access_list['allow'] as $ip) {
      array_push($ip_access_list['allow'], $ip);
    }
    foreach($global_ip_access_list['deny'] as $ip) {
      array_push($ip_access_list['deny'], $ip);
    }
    unset($global_ip_access_list);
  }
  if (!empty($ip_access_list['allow'][0])) {
    foreach($ip_access_list['allow'] as $ip) {
      if(ip_in_range($_SERVER['REMOTE_ADDR'], $ip)) {
        $access_allowed_from_ip = true;
      }
    }
  } else {
    $access_allowed_from_ip = true; // No whitelist set, default to allow.
  }
  if (!empty($ip_access_list['deny'][0])) {
    foreach($ip_access_list['deny'] as $ip) {
      if (ip_in_range($_SERVER['REMOTE_ADDR'], $ip)) {
        $access_allowed_from_ip = false; // If IP is on blacklist, deny.
      }
    }
  }
  return $access_allowed_from_ip;
}

function filter_username($username) {
  $strip_chars = array("!", "<", ">", "'",":", ";", ".", "/", "\"", "#", "%", "\\", "&", "|", "?", "*", "$", ")", "(", "[", "]", "{", "}");
  $username = strtolower(trim(str_replace($strip_chars, "", $username)));
  return $username;
}

function upgrade_insecure_password($username, $password) {
  $kirjuri_database = connect_database('kirjuri-database');
  $query = $kirjuri_database->prepare('UPDATE users SET password = :secure_password_hash WHERE username = :username AND password = :legacy_password');
  $query->execute(array(
    ':secure_password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ':username' => $username,
    ':legacy_password' => hash('sha256', $password)
  ));
  return $query->rowCount();
}

function generate_token($length) {
  // Generate a token for use as a session token.
  return substr(str_shuffle(hash('sha256', random_bytes(1024))), 0, $length);
}

function seconds_to_time($seconds) {
  // Thanks to https://stackoverflow.com/questions/8273804/convert-seconds-into-days-hours-minutes-and-seconds
  $dtF = new \DateTime('@0');
  $dtT = new \DateTime("@$seconds");
  return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
}

function delete_directory($dir) {
  // Thanks to http://stackoverflow.com/questions/1653771/how-do-i-remove-a-directory-that-is-not-empty
  if (!file_exists($dir)) {
    return true;
    }
  if (!is_dir($dir)) {
    return unlink($dir);
    }
  foreach (scandir($dir) as $item) {
    if ($item == '.' || $item == '..') {
      continue;
    }
    if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
      return false;
    }
  }
  return rmdir($dir);
}

function ip_in_range( $ip, $range ) {
  if ( strpos( $ip, ":" ) !== false ) {
    // Return default false for ipv6 addresses.
    return false;
  }
  // Copied and modified from https://gist.github.com/tott/7684443, thanks!
  if ( strpos( $range, '/' ) == false ) {
		$range .= '/32';
	}
	// $range is in IP/CIDR format eg 127.0.0.1/24
	list( $range, $netmask ) = explode( '/', $range, 2 );
	$range_decimal = ip2long( $range );
	$ip_decimal = ip2long( $ip );
	$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}
function ksess_init() {
  // Initialize a session token.
  $_SESSION['user']['token'] = generate_token(16); // Set session token
  if (!file_exists('cache/user_' . $_SESSION['user']['username']))
  {
    mkdir('cache/user_' . $_SESSION['user']['username']);
  }
  file_put_contents('cache/user_' . $_SESSION['user']['username'] . '/session_' . $_SESSION['user']['token'] . '.txt', $_SESSION['user']['username'] . ' is logged in at ' . $_SERVER['REMOTE_ADDR'] . ', user agent ' . $_SERVER['HTTP_USER_AGENT'] . '. Request timestamp ' . gmdate("Y-m-d\TH:i:s\Z", $_SERVER['REQUEST_TIME']) . ". Remove this file to force logout.\r\n");
}

function ksess_validate($token) {
  // Validate a session token against token stored on user session.
  if ($token === $_SESSION['user']['token']) {
    return true;
  }
  else {
    trigger_error("CSRF token mismatch. Try again.");
    if (isset($_SERVER['HTTP_REFERER'])) {
      header('Location: '.$_SERVER['HTTP_REFERER']);
    } else {
      header('Location: index.php');
    }
    die();
  }
}

function ksess_destroy() {
  // Destroy a session file.
  if (isset($_SESSION['user']['username']))
  {
    if (file_exists('cache/user_' . $_SESSION['user']['username'] . '/session_' . $_SESSION['user']['token'] . '.txt'))
    {
      unlink('cache/user_' . $_SESSION['user']['username'] . '/session_' . $_SESSION['user']['token'] . '.txt');
    }
  }
  if (!isset($_SESSION['user']['token'])) {
    $_SESSION['user']['token'] = "Not set.";
  }
  event_log_write('0', "Auth", "Destroyed session " . $_SESSION['user']['token']);
  $_SESSION = null;
  session_destroy();
  header('Location: login.php');
  die;
}

function csrf_case_validate($token, $case_id) {
  // Validate a case access token. A case access token is generated on succesful
  // opening of a case.
  if (empty($token)) {
    trigger_error("Case access token missing. Try again.");
    if (isset($_SERVER['HTTP_REFERER'])) {
      header('Location: '.$_SERVER['HTTP_REFERER']);
    } else {
      header('Location: index.php');
    }
  }
  if (($token === $_SESSION['case_token'][$case_id]) || ($_SESSION['user']['access'] === "0")) {
    return true;
  }
  else {
    trigger_error("Case access token mismatch. Try again.");
    if (isset($_SERVER['HTTP_REFERER'])) {
      header('Location: '.$_SERVER['HTTP_REFERER']);
    } else {
      header('Location: index.php');
    }
    die();
  }
}

function ksess_verify($required_access_level) {
  // Check user access level before rendering page. User details are stored in a session variable.
  if (!isset($_SESSION['user']['username'])) {
    ksess_destroy();
  }
  if (!file_exists('cache/user_' . $_SESSION['user']['username'] . "/session_" . $_SESSION['user']['token'] . ".txt" )) {
    // Drop session if session file has been removed.
    $_SESSION = array();
    session_destroy();
    header('Location: login.php');
    die;
  }
  if ((empty($_SESSION['user']) && $_SERVER['PHP_SELF'] !== '/api.php')) {
    // Check if user variable is set.
    header('Location: login.php');
    die;
  } else {
      if ($_SESSION['user']['access'] > $required_access_level) {
          message('Access', $_SESSION['lang']['insufficient_privileges']);
          if (isset($_SERVER['HTTP_REFERER'])) {
              header('Location: '.$_SERVER['HTTP_REFERER']);
          } else {
            header('Location: index.php');
          }
          die;
      } else {
          return true;
      }
  }
}

function verify_case_ownership($id)
{
  global $kirjuri_database;
  if ($_SESSION['user']['access'] === "0")
  {
    return true;
  }
  $query = $kirjuri_database->prepare('SELECT case_owner FROM exam_requests WHERE id = :id');
  $query->execute(array(':id' => $id));
  $case_owner = $query->fetch(PDO::FETCH_ASSOC);
  $case_owner = explode(";", $case_owner['case_owner']);
  if ( (in_array($_SESSION['user']['username'], $case_owner)) || (empty($case_owner[0])) )
  {
    return true;
  }
  else {
    event_log_write($id, 'Error', 'User initiated out-of-bounds POST request to case where not in access group.');
    message('error', $_SESSION['lang']['not_in_access_group']);
    header('Location: index.php');
    die;
  }
}

function filter_html($string) // Purify HTML content for raw presentation.
{
  if(empty($string))
  {
    return "";
  }
  else
  {
    global $purifier;
    $out = $purifier->purify($string);
    if(empty($out))
    {
      message('error', 'Invalid HTML input.');
      header('Location: '.$_SERVER['HTTP_REFERER']);
      die;
    }
    return $out;
  }
}

function filter_numbers($a)  // Filter out everything but numbers.
{
    return preg_replace('/[^0-9]/', '', $a);
}

function filter_letters_and_numbers($a)
{
    return preg_replace('/[^a-zA-Z0-9_]/', '', $a);
}

function encrypt($in, $key) {
  // Encrypt a string with AES-256-CBC
  if (!function_exists('openssl_encrypt'))
  {
   event_log_write('0', 'Error', 'Missing dependency: OpenSSL. Can not encrypt audit log files. Please install OpenSSL.');
   return $in;
  }
  $iv = trim(substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 16));
  $key = base64_encode($key);
  $in = gzencode($in);
  $encrypted = openssl_encrypt($in, 'AES-256-CBC', $key, 0, $iv);
  return $iv.$encrypted;
}

function decrypt($in, $key) {
  // Decrypt a string.
  if (!function_exists('openssl_decrypt'))
  {
    event_log_write('0', 'Error', 'Missing dependency: OpenSSL. Can not decrypt audit log files. Please install OpenSSL.');
    return $in;
  }
  $iv = substr($in, 0, 16);
  $key = base64_encode($key);
  $decrypted = openssl_decrypt(substr($in, 16), 'AES-256-CBC', $key, 0, $iv);
  $decrypted = gzdecode($decrypted);
  return $decrypted;
}

function show_saved_succesfully() {
  // Display a "changes saved"-message
  $_SESSION['message']['type'] = 'info';
  $_SESSION['message']['content'] = $_SESSION['lang']['changes_saved'];
  $_SESSION['message_set'] = true;
  return true;
}

function message($type = "info", $content) {
  // Display a message. Message is rendered by Twig in base.twig, class set according to $type, either error or info.
  $_SESSION['message']['type'] = $type;
  $_SESSION['message']['content'] = $content;
  $_SESSION['message_set'] = true;
  return true;
}

function connect_database($database) {

  // PDO Database connector
  global $mysql_config;
  global $prefs;
	if (!isset($prefs['settings']['mysql_server_address'])) {
		$server = 'localhost';
	} else {
		$server = $prefs['settings']['mysql_server_address'];
	}
  if ($database === 'kirjuri-database') {
    try {

      $pdo_connect_string = 'mysql:host='.$server.';dbname='.$mysql_config['mysql_database'];
	    $kirjuri_database = new PDO($pdo_connect_string, $mysql_config['mysql_username'], $mysql_config['mysql_password']);
      $kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $kirjuri_database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
      $kirjuri_database->exec('SET NAMES utf8');
      return $kirjuri_database;
    } catch (PDOException $e) {
      session_destroy();
      echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
      die;
    }
  }
}

function audit_log_write($post_data) {
  // Store an audit log entry of the request.
  if (!file_exists('conf/audit_credentials.php')) {
  // Autogenerate an audit log encryption key on first entry.
    file_put_contents('conf/audit_credentials.php', '<?php return "'.generate_token(64).'" ?>');
    event_log_write('0', 'Audit', 'No encryption key found at conf/audit_credentials.php, audit log encryption key autogenerated.');
  }
  if (isset($post_data['REVERT_FROM_AUDIT'])) {
    event_log_write('0', 'Update', "Pushed audit log entry back to database: " . $post_data['REVERT_FROM_AUDIT']);
  }
  $data['user']['username'] = $_SESSION['user']['username'];
  $data['user']['ip_address'] = $_SERVER['REMOTE_ADDR'];
  $data['user']['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
  $data['user']['referer'] = $_SERVER['HTTP_REFERER'];
  $data['user']['request_uri'] = $_SERVER['REQUEST_URI'];
  $data['user']['request_method'] = $_SERVER['REQUEST_METHOD'];
  $data['request_contents'] = $post_data;
  $data = json_encode($data, JSON_PRETTY_PRINT);
  $audit_file_sha256 = hash('sha256', $data);
  $data = encrypt($data, include('conf/audit_credentials.php'));
  $audit_file_epoch = time();

  if (!file_exists('logs/audit/' . substr($audit_file_epoch, 0, 6))) {
	mkdir('logs/audit/' . substr($audit_file_epoch, 0, 6));
  }
  $auditfile_identifier = $audit_file_epoch . "_". strtoupper(generate_token(4)) . ".log";
  if (file_put_contents('logs/audit/' . substr($audit_file_epoch, 0, 6) . '/' . $auditfile_identifier, $data)) {
	event_log_write('0', 'Audit', 'Logged request '. $auditfile_identifier .', sha256: '. $audit_file_sha256);
    return $auditfile_identifier;
  }
  else {
    return "Audit file failure.";
  }
}

set_error_handler('kirjuri_error_handler'); // Give errors to the custom error handler.

/* Some things to run on every page load. */

if (file_exists('conf/mysql_credentials.php')) {
  // Read credentials array from a file
  $mysql_config = include 'conf/mysql_credentials.php';
} else {
  session_destroy();
  header('Location: install.php'); // If file not found, assume install.php needs to be run.
  die;
}

if (file_exists('conf/settings.local')) {
  $settings_file = 'conf/settings.local';
} elseif (file_exists('conf/settings.conf')) {
  $settings_file = 'conf/settings.conf'; // Fall back to default settings.
} else {
  echo "Missing settings file at conf/settings.conf. Can not continue.";
  die;
}
$prefs = parse_ini_file($settings_file, true); // Parse settings file
$prefs['settings']['self'] = $_SERVER['PHP_SELF'];
$prefs['settings']['release'] = file_get_contents('conf/RELEASE');

if (file_exists('conf/' . basename($prefs['settings']['lang'], '.conf') . '.JSON')) {
  $_SESSION['lang'] = json_decode(file_get_contents('conf/' . basename($prefs['settings']['lang'], '.conf') . '.JSON'), true); // Parse language file
} else {
  $_SESSION['lang'] = parse_ini_file('conf/' . basename($prefs['settings']['lang'], '.conf') . '.conf', true); // Parse language file
}

if (isset($prefs['settings']['timezone'])) {
  date_default_timezone_set($prefs['settings']['timezone']);
}

try {
  // Create the attachments table.
  $kirjuri_database = connect_database('kirjuri-database');
  $query = $kirjuri_database->prepare('CREATE TABLE IF NOT EXISTS attachments (id INT(10) AUTO_INCREMENT PRIMARY KEY,
  request_id INT(10), name VARCHAR(256), description TEXT, type VARCHAR(256), size INT NOT NULL, content MEDIUMBLOB NOT NULL,
  uploader VARCHAR(256), date_uploaded DATETIME, hash VARCHAR(256), attr_1 TEXT, attr_2 TEXT, attr_3 TEXT) ');
  $query->execute();
} catch (PDOException $e) {
  session_destroy();
  echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
  die;
}

try {
  // Read users from database to settings.
  $query = $kirjuri_database->prepare('SELECT * from users ORDER BY access, username;');
  $query->execute();
  $users = $query->fetchAll(PDO::FETCH_ASSOC);
  $_SESSION['all_users'] = $users;
} catch (PDOException $e) {
  session_destroy();
  echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
  die;
}

try {
  // Read tools from database to settings.
  $query = $kirjuri_database->prepare('SELECT * FROM tools ORDER BY product_name;');
  $query->execute();
  $tools = $query->fetchAll(PDO::FETCH_ASSOC);
  $_SESSION['all_tools'] = $tools;
} catch (PDOException $e) {
  session_destroy();
  echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
  die;
}

if($_SESSION['user']) { // Get unread message count
  try {
    $query = $kirjuri_database->prepare('SELECT (SELECT COUNT(id) FROM messages WHERE msgto = :username AND received = "0") AS new');
    $query->execute(array(':username' => $_SESSION['user']['username']));
    $_SESSION['unread'] = $query->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    session_destroy();
    echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
    die;
  }
}

if ( (microtime(true) - $mysql_timer_start) > "2.0") {
	trigger_error("Your MySQL connection is slow. This might be a timeout issue when resolving the localhost hostname to an IP address. Try setting the MySQL server to your server IP from the settings.");
}

/* Really extensive access logging.

if (isset($_SERVER['HTTP_REFERER'])) {
	$url = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	if ($_SERVER['HTTP_REFERER'] !== $url) {
		event_log_write('0', "Access", $_SERVER['HTTP_REFERER'] . ' -> ' . $url);
	}
}

*/
