<?php
require __DIR__ . '/vendor/autoload.php';
$loader = new Twig_Loader_Filesystem('views/');
$twig = new Twig_Environment($loader, array(
    'debug' => true,   // if you remove the cache directive, remember to remove the trailing comma from this line.
    'cache' => "cache" // This may be a source of errors if the WWW server process does not have ownership of the cache folder.
));
$twig->addExtension(new Twig_Extension_Debug());
session_start();

/*
 If you wish to add your credentials and update kirjuri with git (not recommended in production environment)
 or by copying new version files into the folder, create this file as conf/mysql_credentials.php:
-----------
 <?php
 return array(
   'mysql_username' => "root",
   'mysql_password' => "devroot",
   'mysql_database' => "kirjuri_db",
 );
 ?>
-----------

Git update will ignore this file, and if it's not found, Kirjuri will use the default credentials in the source code.
 */

function kirjuri_error_handler($errno, $errstr, $errfile, $errline) {
  global $twig;
  global $settings;
  if($settings['show_errors'] === "1") {
    echo $twig->render('error.html', array(
        'errno' => $errno,
        'errstr' => $errstr,
        'errfile' => $errfile,
        'errline' => $errline));
    }
    logline('Error', $errno." ".$errstr.", File: ".$errfile.", line ".$errline);
}

function db($database)
  {
    global $settings;
    global $mysql_config;
    if ($database === 'kirjuri-database')
      {
        try
          {
            $kirjuri_database = new PDO("mysql:host=localhost;dbname=" . $mysql_config['mysql_database'] . "", $mysql_config['mysql_username'], $mysql_config['mysql_password']);
            $kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $kirjuri_database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $kirjuri_database->exec("SET NAMES utf8");
            return $kirjuri_database;
          }
        catch (PDOException $e)
          {

            trigger_error($e->getMessage());
            return FALSE;
          }
      }
  }

function logline($event_level, $description)
  {
    global $settings;
    global $mysql_config;
    try
      {
        $kirjuri_database = new PDO("mysql:host=localhost;dbname=" . $mysql_config['mysql_database'] . "", $mysql_config['mysql_username'], $mysql_config['mysql_password']);
        $kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $kirjuri_database->exec("SET NAMES utf8");
        $event_insert_row = $kirjuri_database->prepare('INSERT INTO event_log (id,event_timestamp,event_level,event_descr,ip) VALUES ("",NOW(),:event_level,:event_descr,:ip)');
        $event_insert_row->execute(array(
            ':event_level' => $event_level,
            ':event_descr' => $description . ' (Method: ' . $_SERVER['REQUEST_METHOD'] . ' URI:' . $_SERVER['REQUEST_URI'] . ')',
            ':ip' => $_SERVER['REMOTE_ADDR']
        ));
        return TRUE;
      }
    catch (PDOException $e)
      {
        die("KIRJURI DATABASE ERROR: " . $e->getMessage());
        return FALSE;
      }
  }

set_error_handler(kirjuri_error_handler);

if (file_exists('conf/mysql_credentials.php')) { // Read credentials
  $mysql_config = include('conf/mysql_credentials.php');
} elseif (file_exists('/etc/kirjuri/mysql_credentials.php')) { // Read credentials
  $mysql_config = include('/etc/kirjuri/mysql_credentials.php');
}
else
{
  $mysql_config['mysql_username'] = "root";
  $mysql_config['mysql_password'] = "devroot";
  $mysql_config['mysql_database'] = "kirjuri_db";
}

if (file_exists("conf/settings.local")) {
  $settings_file = "conf/settings.local";
} elseif (file_exists("/etc/kirjuri/settings.local")) {
  $settings_file = "/etc/kirjuri/settings.local";
} else {
  $settings_file = "conf/settings.conf";
};

$settings_contents = parse_ini_file($settings_file, true);
$_SESSION['lang'] = parse_ini_file("conf/" . $settings_contents['settings']['lang'], true);
$settings = $settings_contents['settings'];

?>
