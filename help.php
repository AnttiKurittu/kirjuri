<?php
require_once("./main.php");
session_destroy();
if (isset($_GET['settings']))
  {
    logline("admin", "Loki katseltu");
    if (($_GET['save'] === "settings") && (isset($_POST['settings_conf'])))
      {
        file_put_contents("conf/settings.conf", $_POST['settings_conf']);
        logline("admin", "Asetuksia muutettu.");
      }
    if (($_GET['save'] === "crimes") && (isset($_POST['crimes_conf'])))
      {
        file_put_contents("conf/crimes_autofill.conf", $_POST['crimes_conf']);
        logline("admin", "Listaa rikoslakirikoksista muutettu.");
      }
  }
$kirjuri_database = db('kirjuri-database');
$query = $kirjuri_database->prepare('SELECT * FROM event_log ORDER BY id DESC');
$query->execute();
$event_log = $query->fetchAll(PDO::FETCH_ASSOC);
$settings_filedump = file_get_contents("conf/settings.conf");
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
