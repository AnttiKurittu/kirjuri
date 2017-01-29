<?php
require_once './include_functions.php';
ksess_verify(3); // Add only or higher
$confCrimes = strip_tags(file_get_contents('conf/crimes_autofill.conf'));
$_SESSION['message_set'] = false;
echo $twig->render('add_case.twig', array(
  'session' => $_SESSION,
  'confCrimes' => $confCrimes,
  'settings' => $prefs['settings'],
  'classifications' => $_SESSION['lang']['classifications'],
  'inv_units' => $prefs['inv_units'],
  'lang' => $_SESSION['lang']
));
?>
