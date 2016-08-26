<?php
require_once("./include_functions.php");
$target_dir = "attachments/".preg_replace("/[^0-9]/", "", (substr($_GET['case'], 0, 5)))."/";

if ($settings['allow_attachments'] !== "1") {
  header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=attachments_disabled");
  die;
}

if (!file_exists($target_dir)) {
  if (mkdir($target_dir, 0755) !== True) {
    echo "Can not create directory. Check folder permissions.";
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
      $skip = True;
      continue;
    };

    if (strpos(mime_content_type($_FILES["fileToUpload"]["tmp_name"][$i]), "text") !== false) {
      if (
         (substr(strtolower($target_file), -4) !== ".txt") ||
         (substr(strtolower($target_file), -4) !== ".csv")
         ) {
      $target_file = $target_file.".txt";
      }
    };

    if ((move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][$i], $target_file)) && ($skip !== True)) {
        $upload_error = False;
      } else {
        $upload_error = True;
        }
}

if ($upload_error === False) {
header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=".$uploadstatus);
die;
} else {
header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=error");
die;
};

?>
