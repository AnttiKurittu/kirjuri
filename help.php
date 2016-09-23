<?php

require_once './include_functions.php';
$_SESSION['message_set'] = false;
echo $twig->render('help.html', array(
    'session' => $_SESSION,
    'settings' => $settings,
    'lang' => $_SESSION['lang'],
    'readme' => file_get_contents('README.md'),
));
