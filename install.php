<html>
<body>
<pre>
<?php

if(empty($_POST)) {
echo '
This script will install the necassary databases for Kirjuri to operate,
save your credentials to conf/mysql_credentials.conf and prepopulate
the users with "admin" and "anonymous". If you wish to do this manually,
you can get the necessary SQL queries from the source code of this script.

The default admin credentials are "admin" / "admin".

<b>WARNING! This install script will overwrite mysq_credentials.php and settings.local,
so if you wish to keep them intact, make backup copies.</b>

Make sure your server process has ownership of the folders conf/, cache/ and attachments/.

<form role="form" method="post">
MySQL username <input name="u" type="text">
MySQL password <input name="p" type="password">
MySQL database <input name="d" type="text">
No special characters allowed.
<button type="submit">Install databases</button>
</form>';

die;
} else {
$mysql_config['mysql_username'] = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['u']);
$mysql_config['mysql_password'] = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['p']);
$mysql_config['mysql_database'] = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['d']);

$conn = new mysqli('localhost', $mysql_config['mysql_username'], $mysql_config['mysql_password']);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Create database
$sql = "CREATE DATABASE " .$mysql_config['mysql_database'];
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully";

} else {
    echo "Error creating database: " . $conn->error;

}
$conn->close();
$kirjuri_database = new PDO("mysql:host=localhost;dbname=" . $mysql_config['mysql_database'] . "", $mysql_config['mysql_username'], $mysql_config['mysql_password']);
$kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$kirjuri_database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
$kirjuri_database->exec("SET NAMES utf8");
$query = $kirjuri_database->prepare('
CREATE TABLE IF NOT EXISTS users (
id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
username varchar(256) NOT NULL,
password varchar(256) NOT NULL,
name varchar(256) DEFAULT NULL,
access int(1) DEFAULT :three,
flags varchar(16) DEFAULT NULL,
attr_1 mediumtext DEFAULT NULL,
attr_2 mediumtext DEFAULT NULL,
attr_3 mediumtext DEFAULT NULL,
attr_4 mediumtext DEFAULT NULL,
attr_5 mediumtext DEFAULT NULL,
attr_6 mediumtext DEFAULT NULL,
attr_7 mediumtext DEFAULT NULL,
attr_8 mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO users (username, password, name, access, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
:anon_name, :zero, :anon_realname, :anon_access, :system_flags, :anon_attr1,
NULL, NULL, NULL, NULL, NULL, NULL, NULL);

INSERT INTO users (username, password, name, access, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
:admin_name, :admin_default_pw, :admin_realname, :admin_access, :system_flags, :admin_attr1,
NULL, NULL, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE IF NOT EXISTS event_log (
id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
event_timestamp datetime DEFAULT NULL,
event_descr text,
event_level tinytext,
ip varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS exam_requests (
id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
parent_id int(16) DEFAULT NULL,
case_id int(16) DEFAULT NULL,
case_name text COLLATE utf8_unicode_ci,
case_suspect text COLLATE utf8_unicode_ci,
case_file_number text COLLATE utf8_unicode_ci,
case_added_date datetime DEFAULT NULL,
case_confiscation_date date DEFAULT NULL,
case_start_date datetime DEFAULT NULL,
case_ready_date datetime DEFAULT NULL,
case_remove_date datetime DEFAULT NULL,
case_devicecount int(16) DEFAULT NULL,
case_investigator text COLLATE utf8_unicode_ci,
forensic_investigator text COLLATE utf8_unicode_ci,
phone_investigator text COLLATE utf8_unicode_ci,
case_investigation_lead text COLLATE utf8_unicode_ci,
case_investigator_tel text COLLATE utf8_unicode_ci,
case_investigator_unit text COLLATE utf8_unicode_ci,
case_crime text COLLATE utf8_unicode_ci,
copy_location text COLLATE utf8_unicode_ci,
is_removed int(1) DEFAULT NULL,
case_status varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
case_requested_action text COLLATE utf8_unicode_ci,
device_action text COLLATE utf8_unicode_ci,
case_contains_mob_dev int(1) DEFAULT NULL,
case_urgency int(1) DEFAULT NULL,
case_urg_justification text COLLATE utf8_unicode_ci,
case_request_description text COLLATE utf8_unicode_ci,
examiners_notes text COLLATE utf8_unicode_ci,
device_type text COLLATE utf8_unicode_ci,
device_manuf text COLLATE utf8_unicode_ci,
device_model text COLLATE utf8_unicode_ci,
device_os text COLLATE utf8_unicode_ci,
device_identifier text COLLATE utf8_unicode_ci,
device_location text COLLATE utf8_unicode_ci,
device_item_number int(4) DEFAULT NULL,
device_document text COLLATE utf8_unicode_ci,
device_owner text COLLATE utf8_unicode_ci,
device_is_host int(1) DEFAULT :zero,
device_host_id int(16) DEFAULT NULL,
device_include_in_report int(1) DEFAULT NULL,
device_time_deviation text COLLATE utf8_unicode_ci,
device_size_in_gb int(16) DEFAULT NULL,
device_contains_evidence int(1) DEFAULT :zero,
last_updated datetime DEFAULT NULL,
classification text COLLATE utf8_unicode_ci,
report_notes mediumtext COLLATE utf8_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE exam_requests
ADD FULLTEXT KEY tapaus (
case_name,
case_suspect,
case_file_number,
case_investigator,
forensic_investigator,
phone_investigator,
case_investigation_lead,
case_investigator_unit,
case_crime,
case_requested_action,
case_request_description,
report_notes,
device_manuf,
device_model,
device_identifier,
device_owner);

');
$query->execute(array(
    ':zero' => "0",
    ':three' => "3",
    ':access' => "1",
    ':anon_name' => "anonymous",
    ':anon_realname' => "Anonymous user",
    ':anon_access' => "3", // Add only access
    ':anon_attr1' => "System account, do not remove.",
    ':admin_name' => "admin",
    ':admin_default_pw' => "8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918", // sha256(admin)
    ':admin_realname' => "Administrator",
    ':admin_access' => "0",
    ':system_flags' => "S",
    ':admin_attr1' => "Extra attribute columns for future compatibility"
));

// Add columns for upgrading existing databases.

$query = $kirjuri_database->prepare('
ALTER TABLE exam_requests ADD criminal_act_date_start DATETIME;
ALTER TABLE exam_requests ADD criminal_act_date_end DATETIME;
ALTER TABLE exam_requests ADD case_password MEDIUMTEXT;
ALTER TABLE exam_requests ADD case_owner MEDIUMTEXT;
ALTER TABLE exam_requests ADD is_protected INT(1);
');
$query->execute(array());

$mysql_config_file = '<?php return array("mysql_username" => "'.$mysql_config['mysql_username'].'", "mysql_password" => "' . $mysql_config['mysql_password'] . '", "mysql_database" => "' . $mysql_config['mysql_database'] .'"); ?>'."\n";

$default_config = file_get_contents('conf/settings.conf');

file_put_contents('conf/settings.local', $default_config);
file_put_contents('conf/mysql_credentials.php', $mysql_config_file);

echo '<br>Install script done, reload <a href="index.php">index.php.</a> ';
die;
}
?>
</pre>
