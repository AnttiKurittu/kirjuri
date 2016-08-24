<?php
require __DIR__ . '/vendor/autoload.php';
$loader = new Twig_Loader_Filesystem('views/');
$twig = new Twig_Environment($loader, array(
    'debug' => true, 'cache' => "cache" // This may be a source of errors if the WWW server process does not have ownership of this folder.
));
$twig->addExtension(new Twig_Extension_Debug());
session_start();
//
// These are the default credentials, replace with your own.
//
$mysql_username = "root";
$mysql_password = "devroot";
$mysql_database = "kirjuri_db";

if (!file_exists('cache')) {
  mkdir('cache', 0755);
};

if ($_SESSION['settings_fetched'] !== "1")
  {
    if (file_exists("conf/settings.local") === True) {
      $settings_file = "conf/settings.local";
    } else {
      $settings_file = "conf/settings.conf";
    };
    $_SESSION['getsettings'] = parse_ini_file($settings_file, true);
    $_SESSION['lang'] = parse_ini_file("conf/" . $_SESSION['getsettings']['settings']['lang'], true);
    $_SESSION['settings_fetched'] = "1";
  }
if ($getSettings === FALSE)
  {
    echo "File not found: conf/settings.conf";
    exit;
  }

$getSettings = $_SESSION['getsettings'];
$settings = $getSettings['settings'];
$forensic_investigators = $getSettings['forensic_investigators'];
$phone_investigators = $getSettings['phone_investigators'];
$inv_units = $getSettings['inv_units'];
$statistics_chart_colors = $getSettings['statistics_chart_colors'];

function kirjuri_error($title_text, $description)
  {
    global $twig;
    echo $twig->render('error.html', array(
        'title_text' => $title_text,
        'viesti' => $description
    ));
    exit;
  }

function db($database)
  {
    global $settings;
    global $mysql_username;
    global $mysql_database;
    global $mysql_password;
    if ($database === 'kirjuri-database')
      {
        try
          {
            $kirjuri_database = new PDO("mysql:host=localhost;dbname=" . $mysql_database . "", $mysql_username, $mysql_password);
            $kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $kirjuri_database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $kirjuri_database->exec("SET NAMES utf8");
            return $kirjuri_database;
          }
        catch (PDOException $e)
          {
            die("KIRJURI_ERROR: " . $e->getMessage());
            return FALSE;
          }
      }
  }
function logline($event_level, $description)
  {
    global $settings;
    global $mysql_username;
    global $mysql_database;
    global $mysql_password;
    try
      {
        $kirjuri_database = new PDO("mysql:host=localhost;dbname=" . $mysql_database . "", $mysql_username, $mysql_password);
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
        die("KIRJURI_ERROR: " . $e->getMessage());
        return FALSE;
      }
  }
?>
