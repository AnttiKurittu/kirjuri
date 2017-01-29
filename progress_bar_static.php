<?php
require_once './include_functions.php';
$query = $kirjuri_database->prepare('SELECT device_action FROM exam_requests where id=:id AND parent_id != id');
$query->execute(array(':id' => $_GET['uid']));
$device_action = $query->fetch(PDO::FETCH_ASSOC);
echo $twig->render('progress_bar.twig', array('device_action' => $device_action['device_action'], 'settings' => $prefs['settings']));
?>
