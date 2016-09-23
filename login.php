<?php

require_once './include_functions.php';
$_SESSION['message_set'] = false;
echo $twig->render('login.html', array(
    'session' => $_SESSION,
    'settings' => $settings,
    'lang' => $_SESSION['lang'],
));
