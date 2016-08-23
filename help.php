<?php
require_once("./include_functions.php");
session_destroy();

if (file_exists("conf/settings.local") === True) {
  $settings_file = "conf/settings.local";
} else {
  $settings_file = "conf/settings.conf";
};

if (isset($_GET['settings']))
  {
    logline("Admin", "Log & settings viewed");
    if (($_GET['save'] === "settings") && (isset($_POST['settings_conf'])))
      {
        file_put_contents($settings_file, $_POST['settings_conf']);
        logline("Admin", "Settings saved.");
      }
    if (($_GET['save'] === "crimes") && (isset($_POST['crimes_conf'])))
      {
        file_put_contents("conf/crimes_autofill.conf", $_POST['crimes_conf']);
        logline("Admin", "Crime list changed.");
      }
  }
$kirjuri_database = db('kirjuri-database');
$query = $kirjuri_database->prepare('SELECT * FROM event_log ORDER BY id DESC LIMIT 100');
$query->execute();
$event_log = $query->fetchAll(PDO::FETCH_ASSOC);



$settings_filedump = file_get_contents($settings_file);
$crimes_filedump = file_get_contents("conf/crimes_autofill.conf");
echo $twig->render('help.html', array(
    'settings_filedump' => $settings_filedump,
    'crimes_filedump' => $crimes_filedump,
    'event_log' => $event_log,
    'settings' => $settings,
    'debug' => $_GET['settings'],
    'lang' => $_SESSION['lang']
));
?>
