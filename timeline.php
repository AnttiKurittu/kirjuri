<?php

require_once './include_functions.php';
ksess_verify(2); // View only or higher
$get_case = isset($_GET['case']) ? $_GET['case'] : '';
$case_number = filter_numbers((substr($get_case, 0, 5)));
verify_case_ownership($case_number);

$kirjuri_database = connect_database('kirjuri-database');
$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id=:id AND parent_id=:id LIMIT 1');
$query->execute(array(
  ':id' => $case_number,
));
$caserow = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($caserow) === 0)
{
  header('Location: index.php');
  die;
}

if (file_exists('logs/cases/uid' . $case_number . '/events.log'))
{
  $caselog = array_reverse(file('logs/cases/uid' . $case_number . '/events.log'));
}
else {
  $caselog = "";
}

$_SESSION['message_set'] = false; // Prevent a message from being shown twice.
echo $twig->render('timeline.twig', array(
  'caselog' => $caselog,
  'session' => $_SESSION,
  'caserow' => $caserow,
  'settings' => $prefs['settings'],
  'lang' => $_SESSION['lang'],
));
