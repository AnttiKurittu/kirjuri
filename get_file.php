<?php
require_once './include_functions.php';
ksess_verify(1);
ksess_validate($_GET['token']);

if (!isset($_GET['file'])) {
	die;
}
$file_id = filter_numbers($_GET['file']);

$query = $kirjuri_database->prepare('SELECT name, content, size, type, request_id, hash FROM attachments WHERE id = :id');
$query->execute(array(':id' => $file_id));
$file = $query->fetch(PDO::FETCH_ASSOC);
csrf_case_validate($_GET['ct'], $file['request_id']);
verify_case_ownership($file['request_id']);
event_log_write($file['request_id'], 'File', 'Attachment downloaded: '. $file['name'] . ", sha256: ". $file['hash']);

if (!empty($file['content'])) {
    header('Content-Description: File Transfer');
    header('Content-Type: '.$file['type']);
    header('Content-Disposition: attachment; filename="'.basename($file['name']).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $file['size']);
		echo gzdecode($file['content']);
    die;
} else {
	echo "File not found.";
	die;
}
?>
