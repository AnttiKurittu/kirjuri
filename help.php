<?php
require_once("./include_functions.php");
echo $twig->render('help.html', array(
    'settings' => $settings,
    'lang' => $_SESSION['lang'],
    'readme' => file_get_contents('README.md')
));
?>
