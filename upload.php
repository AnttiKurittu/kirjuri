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

$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

if (file_exists($target_file)) {
    header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=file_exists");
    die;
};

if ($_FILES["fileToUpload"]["size"] > $settings['max_attachment_size']) {
    header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=filesize_too_large");
    die;
};

if (strpos(mime_content_type($_FILES["fileToUpload"]["tmp_name"]), "text") !== false) {
  if (
     (substr(strtolower($target_file), -4) !== ".txt") ||
     (substr(strtolower($target_file), -4) !== ".csv")
     ) {
  $target_file = $target_file.".txt";
  }
};

if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
      header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=ok");
      die;
  } else {
      header('Location: ' . preg_replace('/\?.*/', '', $_SERVER['HTTP_REFERER'])."?case=".substr($_GET['case'], 0, 5)."&upload_status=error");
      die;
    }
?>
