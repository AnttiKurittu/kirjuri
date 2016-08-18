<?php
require_once("./main.php");

$kirjuri_database = db('kirjuri-database');
$query              = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id=:id AND parent_id=:id LIMIT 1');
$query->execute(array(
    ':id' => $_GET['case']
));
$caserow = $query->fetchAll(PDO::FETCH_ASSOC);
$query   = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id != :id AND parent_id=:id ORDER BY device_type');
$query->execute(array(
    ':id' => $_GET['case']
));
$mediarow = $query->fetchAll(PDO::FETCH_ASSOC);
echo $twig->render('case_report.html', array(
    'caserow' => $caserow,
    'mediarow' => $mediarow,
    'settings' => $settings,
    'lang' => $_SESSION['lang']
));
?>
