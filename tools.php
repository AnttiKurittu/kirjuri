<?php

require_once './include_functions.php';
ksess_verify(0);

// Declare variables
$_GET['populate'] = isset($_GET['populate']) ? $_GET['populate'] : '';
$fields = array();

foreach ($_SESSION['all_tools'] as $tool) { // Get tool information based on GET parameter
  if ($tool['id'] === $_GET['populate']) {
      $fields['id'] = $tool['id'];
      $fields['product_name'] = $tool['product_name'];
      $fields['hw_version'] = $tool['hw_version'];
      $fields['sw_version'] = $tool['sw_version'];
      $fields['serialno'] = $tool['serialno'];
      $fields['flags'] = $tool['flags'];
      $fields['attr_1'] = $tool['attr_1'];
      $fields['attr_2'] = explode(",", $tool['attr_2']);
      $fields['attr_3'] = $tool['attr_3'];
  }
}

$_SESSION['message_set'] = false;
echo $twig->render('tools.twig', array(
    'session' => $_SESSION,
    'settings' => $prefs['settings'],
    'lang' => $_SESSION['lang'],
    'fields' => $fields,
));
