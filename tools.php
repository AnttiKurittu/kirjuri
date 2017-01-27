<?php

require_once './include_functions.php';
ksess_verify(1);

function stringToColorCode($str) {
  $code = dechex(crc32($str));
  $code = substr($code, 0, 6);
  return $code;
}

if (isset($_GET['highlight'])){
	$highlight = filter_numbers($_GET['highlight']);
} else {
	$highlight = "";
}

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
	  $fields['attr_4'] = json_decode($tool['attr_4'], true);
  }
}

$tool_reservations = array();
foreach ($_SESSION['all_tools'] as $tool) {
	$tool_reservations[$tool['id']] = json_decode($tool['attr_4'], true);
	}


	
$i = 0;
while ($i < 100) {
	$evcolors[] = "#" . substr(str_shuffle("5566778899AABBCC"), 0, 6);
	$i++;
}

$_SESSION['message_set'] = false;
echo $twig->render('tools.twig', array(
    'evcolors' => array_unique($evcolors),
	'highlight' => $highlight,
    'session' => $_SESSION,
    'settings' => $prefs['settings'],
    'lang' => $_SESSION['lang'],
    'fields' => $fields,
	'tool_reservations' => $tool_reservations
));
