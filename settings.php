<?php

require_once './include_functions.php';
ksess_verify(1); // User only or higher, add or view only accounts cant change passwords.

$default_settings = parse_ini_file('conf/settings.conf', true);
$diff = array_diff_key($default_settings['settings'], $prefs['settings']);

if ($_SESSION['user']['access'] === "0")
{
  foreach ($diff as $key => $value)
  {
      trigger_error('New setting added from '.$settings_file.': '.$key.' = "'.$value.'". Please save your settings.');
  }
}

$conffiles = scandir('conf/');
foreach($conffiles as $file) {
  if(substr($file,0,5) === "lang_") {
    $langfile = substr(substr($file, 5),0, -5);
    $langfiles[] = $langfile;
  }
}

$langfiles = array_unique($langfiles);

$php_servertime = time();
try {
  $kirjuri_database = connect_database('kirjuri-database');
  $query = $kirjuri_database->prepare('SELECT @@global.time_zone AS tz');
  $query->execute();
  $mysql_timezone = $query->fetch(PDO::FETCH_ASSOC);
  $mysql_timezone = $mysql_timezone['tz'];

} catch (PDOException $e) {
  session_destroy();
  echo 'Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.';
  die;
}

if (file_exists('conf/report_notes.local'))
{
  $templates['report_notes'] = file_get_contents('conf/report_notes.local');
  $templates['report_notes'] = filter_html($templates['report_notes']);
}
elseif (file_exists('conf/report_notes.template'))
{
  $templates['report_notes'] = file_get_contents('conf/report_notes.template');
  $templates['report_notes'] = filter_html($templates['report_notes']);
}
else {
  $templates['report_notes'] = "";
}

$_SESSION['message_set'] = false;
echo $twig->render('settings.twig', array(
    'template_report_notes' => $templates['report_notes'],
    'server_time' => $php_servertime,
    'php_timezone' => date_default_timezone_get(),
    'mysql_timezone' => $mysql_timezone,
    'settings' => $prefs['settings'],
    'langfiles' => $langfiles,
    'settings_contents' => $prefs,
    'diff' => $diff,
    'apikey' => hash('sha1', $_SESSION['user']['username'].$_SESSION['user']['password']),
    'session' => $_SESSION,
    'settings_file' => $settings_file,
    'lang' => $_SESSION['lang'],
));
