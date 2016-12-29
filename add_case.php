<?php
require_once './include_functions.php';
protect_page(3); // Add only or higher
$confCrimes = strip_tags(file_get_contents('conf/crimes_autofill.conf'));
echo $twig->render('add_case.html', array(
  'session' => $_SESSION,
  'confCrimes' => $confCrimes,
  'settings' => $settings,
  'classifications' => $_SESSION['lang']['classifications'],
  'inv_units' => $settings_contents['inv_units'],
  'lang' => $_SESSION['lang']
));
?>
