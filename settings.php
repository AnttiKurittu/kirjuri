<?php
require_once("./include_functions.php");

$save_target = isset($_POST['save']) ? $_POST['save'] : '';

if (($save_target === "settings") && (isset($_POST['settings_conf'])))
  {
    if(file_exists($settings_file)) {
      file_put_contents($settings_file, $_POST['settings_conf']);
      logline("Admin", "Settings saved.");
    } else {
      trigger_error("Settings file ".$settings_file." not found.");
    }
  }

if ($settings['show_log'] === "1") {
  $kirjuri_database = db('kirjuri-database');
  $query = $kirjuri_database->prepare('SELECT * FROM event_log WHERE event_level != "Error" ORDER BY id DESC LIMIT 100');
  $query->execute();
  $event_log = $query->fetchAll(PDO::FETCH_ASSOC);

  $kirjuri_database = db('kirjuri-database');
  $query = $kirjuri_database->prepare('SELECT * FROM event_log WHERE event_level = "Error" ORDER BY id DESC LIMIT 100');
  $query->execute();
  $event_log_errors = $query->fetchAll(PDO::FETCH_ASSOC);

} else {
  $event_log = "";
  $event_log_errors = "";
};

$settings_filedump = file_get_contents($settings_file);

echo $twig->render('settings.html', array(
    'settings_filedump' => $settings_filedump,
    'settings_file' => $settings_file,
    'event_log' => $event_log,
    'event_log_errors' => $event_log_errors,
    'settings' => $settings,
    'lang' => $_SESSION['lang']
));
?>
