<?php

require_once './include_functions.php';
protect_page(1); // User only or higher, add or view only accounts cant change passwords.


if ($settings['show_log'] === '1') {
    $query = $kirjuri_database->prepare('SELECT * FROM event_log WHERE event_level != "Error" ORDER BY id DESC LIMIT 100');
    $query->execute();
    $event_log = $query->fetchAll(PDO::FETCH_ASSOC);

    $query = $kirjuri_database->prepare('SELECT * FROM event_log WHERE event_level = "Error" ORDER BY id DESC LIMIT 100');
    $query->execute();
    $event_log_errors = $query->fetchAll(PDO::FETCH_ASSOC);
} else {
    $event_log = '';
    $event_log_errors = '';
};

$settings_filedump = file_get_contents($settings_file);
$default_settings = parse_ini_file('conf/settings.conf', true);
$diff = array_diff_key($default_settings['settings'], $settings_contents['settings']);
foreach ($diff as $key => $value) {
    trigger_error('Missing a settings directive from '.$settings_file.': '.$key.' = "'.$value.'". The default configuration file might have changed on update, please update your local settings file to contain this directive under [settings].');
}

$_SESSION['message_set'] = false;
echo $twig->render('settings.html', array(
    'apikey' => hash('sha1', $_SESSION['user']['username'].$_SESSION['user']['password']),
    'session' => $_SESSION,
    'settings_filedump' => $settings_filedump,
    'settings_file' => $settings_file,
    'event_log' => $event_log,
    'event_log_errors' => $event_log_errors,
    'settings' => $settings,
    'lang' => $_SESSION['lang'],
));
