<?php

// AJAX generator page

require_once './include_functions.php';

$case_file_number = substr($_GET['case_file_number'], 0, 18);
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

if (!empty($case_file_number)) {
    $query = $kirjuri_database->prepare('SELECT case_status, case_name, case_suspect, id, case_added_date, case_id FROM exam_requests WHERE case_file_number = :case_file_number AND id = parent_id AND is_removed != "1" ORDER BY case_id');
    $query->execute(array(
        ':case_file_number' => $case_file_number,
    ));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($out)) {
        echo "<h4><i class='fa fa-exclamation' style='color:red;'></i> " .$_SESSION['lang']['notice_duplicate_request']. ":</h4>";
        foreach ($out as $entry) {
            if (empty($entry['case_name'])) {
                $case_name = $_SESSION['lang']['suspect_abbrev']." ".$entry['case_suspect'];
            } else {
                $case_name = $entry['case_name'].', RE '.$entry['case_suspect'];
            }
            ;
            if ($entry['case_status'] === '3') {
                $case_status = 'success';
                $case_progress = $_SESSION['lang']['ready'];
            } elseif ($entry['case_status'] === '2') {
                $case_status = 'warning';
                $case_progress = $_SESSION['lang']['open'];
            } elseif ($entry['case_status'] === '1') {
                $case_status = 'danger';
                $case_progress = $_SESSION['lang']['new'];
            } else {
                $case_status = 'danger';
                $case_progress = '???';
            }
            ;
            echo "<p><a class='btn btn-".$case_status." btn-xs' href='edit_request.php?case=".$entry['id']."'>".$entry['case_id'].'/'.substr($entry['case_added_date'], 0, 4).' '.$case_name.' ('.$case_progress.')</a></p>';
        }
        ;
        echo '';
    }
    ;
}
;
