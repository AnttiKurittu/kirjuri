<?php
require_once './include_functions.php';
ksess_verify(3);
$release_version = num(file_get_contents('conf/RELEASE'));
$_SESSION['message_set'] = false;
echo $twig->render('help.html', array(
    'release_version' => $release_version,
    'session' => $_SESSION,
    'settings' => $settings,
    'lang' => $_SESSION['lang'],
    'readme' => file_get_contents('README.md'),
));
