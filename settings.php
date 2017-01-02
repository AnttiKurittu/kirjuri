<?php

require_once './include_functions.php';
ksess_verify(1); // User only or higher, add or view only accounts cant change passwords.

$default_settings = parse_ini_file('conf/settings.conf', true);
$diff = array_diff_key($default_settings['settings'], $settings_contents['settings']);
foreach ($diff as $key => $value) {
    trigger_error('New setting added from '.$settings_file.': '.$key.' = "'.$value.'". Please save your settings.');
}

if (file_exists('logs/kirjuri_case_0.log'))
{
  $event_log = array_reverse(file('logs/kirjuri_case_0.log'));
}
else {
  $event_log = "";
}

if (file_exists('logs/error.log'))
{
  $event_log_errors = array_reverse(file('logs/error.log'));
}
else {
  $event_log_errors = "";
}


$_SESSION['message_set'] = false;
echo $twig->render('settings.html', array(
    'settings' => $settings_contents['settings'],
    'settings_contents' => $settings_contents,
    'diff' => $diff,
    'apikey' => hash('sha1', $_SESSION['user']['username'].$_SESSION['user']['password']),
    'session' => $_SESSION,
    'settings_file' => $settings_file,
    'event_log' => $event_log,
    'event_log_errors' => $event_log_errors,
    'lang' => $_SESSION['lang'],
));
