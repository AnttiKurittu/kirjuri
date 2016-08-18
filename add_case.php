<?php
require_once("./main.php");
logline("info", "Page view - add request");
$confCrimes = file_get_contents('conf/crimes_autofill.conf');
echo $twig->render('add_case.html', array(
    'confCrimes' => $confCrimes,
    'settings' => $settings,
    'classifications' => $classifications,
    'inv_units' => $inv_units,
    'lang' => $_SESSION['lang']
));
?>
