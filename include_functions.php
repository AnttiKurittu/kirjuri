<?php
// This is the 'header' file in all php files containing shared functions etc.
// Go to installer if no credentials found.

if ((!file_exists('conf/mysql_credentials.php')) && (!file_exists('/etc/kirjuri/conf/mysql_credentials.php'))) {
    header('Location: install.php');
    die;
}

require __DIR__.'/vendor/autoload.php';
$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
$loader = new Twig_Loader_Filesystem('views/');
$twig = new Twig_Environment($loader, array(
  'debug' => true, // if you remove the cache directive, remember to remove the trailing comma from this line.
  'cache' => 'cache', // This may be a source of errors if the WWW server process does not have ownership of the cache folder.
));
$twig->addExtension(new Twig_Extension_Debug());

$purifier = new HTMLPurifier();
$pur_config = HTMLPurifier_Config::createDefault();
$pur_config->set('Cache.SerializerPath', 'cache/');

session_start();

// Declare variables
$_SESSION['message_set'] = isset($_SESSION['message_set']) ? $_SESSION['message_set'] : '';
$_SESSION['user'] = isset($_SESSION['user']) ? $_SESSION['user'] : ''; //

// If message has been set, do not clear it. Invidial files set message as shown before rendering page.
if ($_SESSION['message_set'] === false) {
    $_SESSION['message']['type'] = '';
    $_SESSION['message']['content'] = '';
}

// Check user access level before rendering page. User details are stored in a session variable.
function protect_page($required_access_level)
{
    if ((empty($_SESSION['user']) && $_SERVER['PHP_SELF'] !== '/api.php')) {
        // Check if user variable is set.

    header('Location: login.php');
        die;
    } else {
        if ($_SESSION['user']['access'] > $required_access_level) {
            message('error', $_SESSION['lang']['insufficient_privileges']);
            header('Location: '.$_SERVER['HTTP_REFERER']);
            die;
        } else {
            return true;
        }
    }
}

function sanitize_raw($string)
{
  global $purifier;
  $out = $purifier->purify($string);
  //$out = strip_tags($string, '<br><p><blockquote><pre><strong><ol><em><span><ul><li><b><i><sup><sub><code><h1><h2><h3><h4>');
  return $out;
}

function num($a)
{
    return preg_replace('/[^0-9]/', '', $a);
}

 function encrypt($in, $key) // Encrypt a string with AES-256-CBC
 {
     $iv = trim(substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 16));
     $key = base64_encode($key);
     $in = gzencode($in);
     $encrypted = openssl_encrypt($in, 'AES-256-CBC', $key, 0, $iv);

     return $iv.$encrypted;
 }

 function decrypt($in, $key) // Decrypt a string.
 {
     $iv = substr($in, 0, 16);
     $key = base64_encode($key);
     $decrypted = openssl_decrypt(substr($in, 16), 'AES-256-CBC', $key, 0, $iv);
     $decrypted = gzdecode($decrypted);

     return $decrypted;
 }

function show_saved() // Display a "changed saved"-message
{
    $_SESSION['message']['type'] = 'info';
    $_SESSION['message']['content'] = $_SESSION['lang']['changes_saved'];
    $_SESSION['message_set'] = true;

    return true;
}

function message($type, $content) // Display a message. Message is rendered by Twig according to $type, either error or info.
{
    $_SESSION['message']['type'] = $type;
    $_SESSION['message']['content'] = $content;
    $_SESSION['message_set'] = true;

    return true;
}

function kirjuri_error_handler($errno, $errstr, $errfile, $errline) // Trigger an error
{
    global $twig;
    global $settings;
    if ($settings['show_errors'] === '1') {
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
        $_SESSION['message']['content'] = $errnums[$errno].': '.$errstr.'. In file '.$errfile.', line '.$errline.'.';
        $_SESSION['message_set'] = true;
    }
    logline('Error', $errno.' '.$errstr.', File: '.$errfile.', line '.$errline);
}

function db($database) // PDO Database connection
{
    global $mysql_config;
    if ($database === 'kirjuri-database') {
        try {
            $kirjuri_database = new PDO('mysql:host=localhost;dbname='.$mysql_config['mysql_database'].'', $mysql_config['mysql_username'], $mysql_config['mysql_password']);
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

function logline($event_level, $description) // Add an entry to event_log
{
    global $mysql_config;
    try {
        if ((isset($_SESSION['user']['username'])) && (isset($description))) {
            $description = '['.$_SESSION['user']['username'].'] '.$description;
        }
        $log = date('Y-m-d H:i:s').' '.$event_level.' - '.$description.' (Method: '.$_SERVER['REQUEST_METHOD'].' URI:'.$_SERVER['REQUEST_URI'].'), IP: '.$_SERVER['REMOTE_ADDR']."\r\n";
        file_put_contents('logs/kirjuri.log', $log, FILE_APPEND);
        $kirjuri_database = new PDO('mysql:host=localhost;dbname='.$mysql_config['mysql_database'].'', $mysql_config['mysql_username'], $mysql_config['mysql_password']);
        $kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $kirjuri_database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $kirjuri_database->exec('SET NAMES utf8');
        $event_insert_row = $kirjuri_database->prepare('INSERT INTO event_log (event_timestamp,event_level,event_descr,ip) VALUES (NOW(),:event_level,:event_descr,:ip)');
        $event_insert_row->execute(array(
         ':event_level' => $event_level,
         ':event_descr' => $description.' (Method: '.$_SERVER['REQUEST_METHOD'].' URI:'.$_SERVER['REQUEST_URI'].')',
         ':ip' => $_SERVER['REMOTE_ADDR'],
       ));

        return true;
    } catch (PDOException $e) {
        file_put_contents('logs/kirjuri.log', $log, FILE_APPEND);
        message('error', 'Database error in logline(): '.$e->getMessage());
        return false;
    }
}

set_error_handler('kirjuri_error_handler'); // Give errors to the custom error handler.

if (file_exists('conf/mysql_credentials.php')) {
    // Read credentials array from a file
  $mysql_config = include 'conf/mysql_credentials.php';
} elseif (file_exists('/etc/kirjuri/mysql_credentials.php')) {
    $mysql_config = include '/etc/kirjuri/mysql_credentials.php';
} else {
    header('Location: install.php'); // If file not found, assume install.php needs to be run.
  die;
}

if (file_exists('conf/settings.local')) {
    // Check for existence of settings file.

  $settings_file = 'conf/settings.local';
} elseif (file_exists('/etc/kirjuri/settings.local')) {
    $settings_file = '/etc/kirjuri/settings.local';
} else {
    $settings_file = 'conf/settings.conf'; // Fall back to default settings.
}

$settings_contents = parse_ini_file($settings_file, true); // Parse settings file
$settings = $settings_contents['settings']; // Get settings to a variable
$settings['self'] = $_SERVER['PHP_SELF'];
$settings['release'] = file_get_contents('conf/RELEASE');

$_SESSION['lang'] = parse_ini_file('conf/'.$settings_contents['settings']['lang'], true); // Parse language file

try
{
  $kirjuri_database = db('kirjuri-database'); // Read users from database to settings.
  $query = $kirjuri_database->prepare('SELECT * from users ORDER BY access, username;');
  $query->execute();
  $users = $query->fetchAll(PDO::FETCH_ASSOC);
  $_SESSION['all_users'] = $users;
}
catch (PDOException $e)
{
  session_destroy();
  echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
  die;
}

try
{
  $kirjuri_database = db('kirjuri-database'); // Read tools from database to settings.
  $query = $kirjuri_database->prepare('SELECT * from tools ORDER BY product_name;');
  $query->execute();
  $tools = $query->fetchAll(PDO::FETCH_ASSOC);
  $_SESSION['all_tools'] = $tools;
}
catch (PDOException $e)
{
  session_destroy();
  echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
  die;
}
if($_SESSION['user']) {
  try
  {
    $query = $kirjuri_database->prepare('SELECT (SELECT COUNT(id) FROM messages WHERE msgto = :username AND received = "0") as new');
    $query->execute(array(':username' => $_SESSION['user']['username']));
    $_SESSION['unread'] = $query->fetch(PDO::FETCH_ASSOC);
  }
  catch (PDOException $e)
  {
    session_destroy();
    echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
    die;
  }
}
