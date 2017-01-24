<?php
require_once './include_functions.php';
ksess_verify(1);
$id = filter_numbers(substr($_POST['case'], 0, 5));
unset($_SESSION['failed_uploads']);

if ($prefs['settings']['allow_attachments'] !== '1') {
    header('Location: '.preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER']).'?case='.substr($_GET['case'], 0, 5).'');
    die;
}
$total = count($_FILES['fileToUpload']['name']);
  for ($i = 0; $i < $total; ++$i) {
    if ($_FILES['fileToUpload']['size'][$i] > $prefs['settings']['max_attachment_size']) {
        $_SESSION['failed_uploads'][] = $_FILES['fileToUpload']['name'][$i] . "(filesize too big)";
        event_log_write($id, 'Error', 'Upload failed (filesize): '. basename($_FILES['fileToUpload']['name'][$i]));
        continue;
    };
    $file['name'] = basename($_FILES['fileToUpload']['name'][$i]);
    $file['type'] = mime_content_type($_FILES['fileToUpload']['tmp_name'][$i]);
    $file['content'] = file_get_contents($_FILES['fileToUpload']['tmp_name'][$i], 16000000);
	  $file['hash'] = hash('sha256', $file['content']);
    $query = $kirjuri_database->prepare('SELECT name FROM attachments WHERE hash = :hash AND request_id = :request_id');
    $query->execute(array(':hash' => $file['hash'], ':request_id' => $id));
    $file_exists = $query->fetch(PDO::FETCH_ASSOC);
    if ($file_exists === false) {
      $file['size'] = $_FILES['fileToUpload']['size'][$i];
      $compressed_data = gzencode($file['content']);
      unset($file['content']);
      $size_in_database = strlen($compressed_data);
      $query = $kirjuri_database->prepare('INSERT INTO attachments
      (id, name, request_id, type, size, content, uploader, hash, date_uploaded) VALUES
      (NULL, :name, :request_id, :type, :size, :content, :uploader, :hash, NOW())');
      $query->execute(array(
        ':request_id' => $id,
        ':name' => $file['name'],
        ':type' => $file['type'],
        ':size' => $file['size'],
        ':content' => $compressed_data,
        ':uploader' => $_SESSION['user']['username'],
        ':hash' => $file['hash']
      ));
      $compression_ratio = (100 - (($size_in_database / $file['size']) * 100));
      $_POST['content'] = "File data, " . $file['size'] . " bytes, compressed to " . $size_in_database . " bytes. (Reduction of " . round($compression_ratio, 2) . "%). sha256: " . $file['hash'];
      $audit_stamp = audit_log_write($_POST);
      $query = $kirjuri_database->prepare('UPDATE attachments SET attr_1 = :audit_stamp WHERE hash = :hash');
      $query->execute(array(
        ':audit_stamp' => $audit_stamp,
        ':hash' => $file['hash']
      ));
      event_log_write($id, 'Add', 'Attachment uploaded: '. $file['name'] . ", file sha256: " . $file['hash'], $audit_stamp);
    } else {
      $_SESSION['failed_uploads'][] = $file['name'] . " (file already exists)";
      event_log_write($id, 'Error', 'Upload failed (file exists): '. $file['name']);
    }
}
header('Location: '.preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER']).'?case='.$id);
die;
