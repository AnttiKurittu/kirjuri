<?php
require_once("./include_functions.php");
$target_dir = "attachments/".preg_replace("/[^0-9]/", "", (substr($_GET['case'], 0, 5)))."/";

$skip = False;
$upload_error = False;

if ($settings['allow_attachments'] !== "1") {
  header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=attachments_disabled");
  die;
}

if (!file_exists($target_dir)) {
  if (mkdir($target_dir, 0755) !== True) {
    trigger_error('Can not create subdirectory to attachments/. Check folder permissions.');
    header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=error");
    die;
  };
};

$total = count($_FILES['fileToUpload']['name']);

$uploadstatus = "ok";

  for($i=0; $i<$total; $i++) {

    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"][$i]);
    if (file_exists($target_file)) {
        $uploadstatus = "file_exists";
        $skip = True;
        continue;
    };

    if ($_FILES["fileToUpload"]["size"][$i] > $settings['max_attachment_size']) {
      $uploadstatus = "filesize_too_large";
      logline('Error', 'Attachment upload failure (size): '.$target_file);
      $skip = True;
      continue;
    };

    if ( (strtolower(pathinfo($target_file, PATHINFO_EXTENSION)) === "js") ||
         (strtolower(pathinfo($target_file, PATHINFO_EXTENSION)) === "php") ||
         (strtolower(pathinfo($target_file, PATHINFO_EXTENSION)) === "html") ||
         (strtolower(pathinfo($target_file, PATHINFO_EXTENSION)) === "htm")) {
      $target_file = $target_file.".txt";
      }

    if ((move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][$i], $target_file)) && ($skip !== True)) {
        logline('Action', 'Attachment uploaded: '.$target_file);
        $upload_error = False;
      } else {
        $upload_error = True;
        }
}

if ($upload_error === False) {
  header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=".$uploadstatus);
die;
} else {
  logline('Error', 'Attachment upload failure (other): '.$target_file);
  header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=error");
die;
};

?>
