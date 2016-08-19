<?php
require __DIR__ . '/vendor/autoload.php';
$loader = new Twig_Loader_Filesystem('views/');
$twig = new Twig_Environment($loader, array(
    'debug' => true
    //, 'cache' => "cache" // uncomment to allow caching. Create a folder called "cache" under the webroot dir and give the www process ownership.
));
$twig->addExtension(new Twig_Extension_Debug());
session_start();
if ($_SESSION['settings_fetched'] !== "1")
  {
    $_SESSION['getsettings'] = parse_ini_file("conf/settings.conf", true);
    $_SESSION['lang'] = parse_ini_file("conf/" . $_SESSION['getsettings']['settings']['lang'], true);
    $_SESSION['settings_fetched'] = "1";
  }
if ($getSettings === FALSE)
  {
    echo "Tiedostoa ei löydy: conf/settings.conf.";
    exit;
  }
$getSettings = $_SESSION['getsettings'];
$settings = $getSettings['settings'];
$devices = $getSettings['devices'];
$media_objs = $getSettings['media_objs'];
$device_locations = $getSettings['device_locations'];
$device_actions = $getSettings['device_actions'];
$forensic_investigators = $getSettings['forensic_investigators'];
$phone_investigators = $getSettings['phone_investigators'];
$classifications = $getSettings['classifications'];
$inv_units = $getSettings['inv_units'];
$interface_colors = $getSettings['interface_colors'];
function virhe($title_text, $description)
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
    if ($database === 'kirjuri-database')
      {
        try
          {
            $kirjuri_database = new PDO("mysql:host=localhost;dbname=" . $settings['mysql_database'] . "", $settings['mysql_user'], $settings['mysql_password']);
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
    try
      {
        $kirjuri_database = new PDO("mysql:host=localhost;dbname=" . $settings['mysql_database'] . "", $settings['mysql_user'], $settings['mysql_password']);
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
