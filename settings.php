<?php
require_once("./include_functions.php");

$save_target = isset($_POST['save']) ? $_POST['save'] : '';

if (($save_target === "settings") && (isset($_POST['settings_conf'])))
  {
    if(file_exists($settings_file)) {
      file_put_contents($settings_file, $_POST['settings_conf']);
      logline("Admin", "Settings saved.");
      $settings_contents = parse_ini_file($settings_file, true);
      $_SESSION['lang'] = parse_ini_file("conf/" . $settings_contents['settings']['lang'], true);
      $settings = $settings_contents['settings'];
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

$default_settings = parse_ini_file('conf/settings.conf', true);
$diff = array_diff_key($default_settings['settings'], $settings_contents['settings']);
foreach($diff as $key => $value) {
  trigger_error('Missing a settings directive from '.$settings_file.': '.$key.' = "'.$value.'". The default configuration file might have changed on update, please update your local settings file to contain this directive under [settings].');
}

echo $twig->render('settings.html', array(
    'settings_filedump' => $settings_filedump,
    'settings_file' => $settings_file,
    'event_log' => $event_log,
    'event_log_errors' => $event_log_errors,
    'settings' => $settings,
    'lang' => $_SESSION['lang']
));
?>
