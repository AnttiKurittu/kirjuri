<?php

require_once './include_functions.php';
protect_page(2); // View only or higher
$id = num($_GET['case']);
verify_owner($id);


$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id=:id AND parent_id=:id LIMIT 1');
$query->execute(array(
    ':id' => $id,
));
$caserow = $query->fetchAll(PDO::FETCH_ASSOC);
$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id != :id AND parent_id=:id ORDER BY device_type');
$query->execute(array(
    ':id' => $id,
));
$mediarow = $query->fetchAll(PDO::FETCH_ASSOC);
echo $twig->render('case_report.html', array(
    'session' => $_SESSION,
    'caserow' => $caserow,
    'mediarow' => $mediarow,
    'settings' => $settings,
    'lang' => $_SESSION['lang'],
));
