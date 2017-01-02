<?php
require_once("./include_functions.php");
ksess_verify(0);

if (!file_exists($settings['mysqldump_location']))
{
  trigger_error("Can not run backup, mysqldump binary " . $settings['mysqldump_location'] . " not found. Please set the location of the binary in the settings.");
  header('Location: settings.php');
  die;
}

header('Content-Description: File Transfer');
header('Content-Encoding: UTF-8');
header('Content-Type: text; charset=utf-8');
header('Content-Disposition: attachment; filename=kirjuri database backup '.date("j-m-y").'.sql');
$out = shell_exec($settings['mysqldump_location'] . " -u " .$mysql_config['mysql_username']. " -p" .$mysql_config['mysql_password']. " " .$mysql_config['mysql_database']);
logline('0', 'Admin', 'Backed up database.');
echo $out;
?>
