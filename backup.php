<?php
require_once("./include_functions.php");
protect_page(0);

// Set the location of your "mysqldump" binary
// $mysqldump = "/usr/bin/mysqldump"; // Linux
$mysqldump = "/Applications/MAMP/Library/bin/mysqldump"; // macOS MAMP

header('Content-Description: File Transfer');
header('Content-Encoding: UTF-8');
header('Content-Type: text; charset=utf-8');
header('Content-Disposition: attachment; filename=kirjuri database backup '.date("j-m-y").'.sql');
$out = shell_exec($mysqldump . " -u " .$mysql_config['mysql_username']. " -p" .$mysql_config['mysql_password']. " " .$mysql_config['mysql_database']);
logline('Admin', 'Backed up database.');
echo $out;
?>
