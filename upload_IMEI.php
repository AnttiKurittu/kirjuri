<?php
require_once './include_functions.php';
ksess_verify(0);
if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], 'conf/imei.txt')) {
  header('Location: settings.php');
die;
}
else
{
  trigger_error('Upload failed.');
  header('Location: settings.php');
  die;
};
