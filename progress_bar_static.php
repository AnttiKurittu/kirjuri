<?php
require_once './include_functions.php';
    
    $sql = $kirjuri_database->prepare('SELECT device_action FROM exam_requests where id=:id AND parent_id != id');
    $sql->execute(array(
        ':id' => $_GET['db_row'],
    ));
    $device_action = $sql->fetchAll(PDO::FETCH_ASSOC);

    echo $twig->render('progress_bar.html', array(
        'device_action' => $device_action[0]['device_action'],
        'settings' => $settings,
    ));
