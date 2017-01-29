<?php
// Clear any lingering sessions.
session_name('KirjuriSessionID');
session_start();
session_destroy();
if (version_compare(PHP_VERSION, '7.0.0') <= 0) {
    echo "Kirjuri requires PHP7 to run. You are using " . phpversion() . ". Please upgrade your PHP environment.";
    die;
}
?>
<html>
<head>
  <link href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
  <body>
    <div class="container-fluid">
      <div class="main">
    <h3>Kirjuri installer</h3>
<?php
if (file_exists("conf/mysql_credentials.php"))
{ echo "Installer has already been run on this instance. Please remove the file conf/mysql_credentials.php to run the installer again.";
  die;
}

function error_handler($n, $s, $f) // Custom error handler for the installation script.
{
    global $i;
    echo '<p style="color:red;">Installation error: ('.$n.') '.$s.'</p>';
    $i = 0;
}
set_error_handler('error_handler');

echo '<p>Web server running as "'.exec('whoami').'"</p>';
echo '<p>Testing write permissions...</p>';

$i = 0; // Count folders
$test_folders = array(
  'conf',
  'cache',
  'logs',
);
foreach ($test_folders as $folder) {
    $result = '';
    file_put_contents($folder.'/test.txt', 'test');
    $result = file_get_contents($folder.'/test.txt');
    if ($result === 'test') {
        ++$i;
        echo '<span style="color:green;"> + '.$folder.'/ is writable. </span><br>';
        unlink($folder.'/test.txt');
    }
}

if ($i === count($test_folders)) {
    // See if all folders passed the write test
  echo '<b style="color:green;">   Write test passed!</b><hr>';
} else {
    echo '<b style="color:red;">   Write test failed, please check that the www server process owns the following folders: </b><br>';
    foreach ($test_folders as $folder) {
        echo '    '.$folder.'/<br>';
    }
    die;
}

// Continue the installer if data is present.
if ( (empty($_POST['u'])) ||  (empty($_POST['p'])) || (empty($_POST['d'])) || (empty($_POST['ap'])) ) {
    echo '<pre><form role="form" method="post">
This script will install the necessary databases for Kirjuri to operate,
save your credentials to <i>conf/mysql_credentials.php</i> and prepopulate
the users with "admin" and "anonymous". If you wish to do this manually,
you can get the necessary SQL queries from the source code of this script.

You can rerun this install script at any time to create a new database. This is
useful is you wish to create a test database first and then later create a
production database.

Please choose a name for your database. The default is "kirjuri".

<input type="checkbox" name="drop_database" value="drop"> Drop existing database. <b style="color:red;">THIS WILL DELETE YOUR DATA AND USERS.</b>
<input type="checkbox" name="migrate_old_database" value="migrate"> Migrate tutkinta.jutut database. <b style="color:red;">THIS WILL OVERWRITE YOUR EXISTING DATABASE.</b>

<input name="u" type="text"> MySQL username
<input name="p" type="password"> MySQL password
<input name="d" type="text" value="kirjuri"> MySQL database
<input name="ap" type="password"> Create admin password

<button type="submit">Install / rebuild databases</button></form></pre>
</div>
</div>
</body>
</html>';
    die;
  } else {
    // If form is submitted
  $admin_password = password_hash($_POST['ap'], PASSWORD_DEFAULT);
  $_POST['drop_database'] = isset($_POST['drop_database']) ? $_POST['drop_database'] : '';
  $_POST['migrate_old_database'] = isset($_POST['migrate_old_database']) ? $_POST['migrate_old_database'] : '';
  $mysql_config['mysql_username'] = trim(preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['u']));
  $mysql_config['mysql_password'] = $_POST['p'];
  $mysql_config['mysql_database'] = strtolower(trim(preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['d'])));

  // Check for invalid database names
  if (in_array($mysql_config['mysql_database'], array(
    'mysql',
    'information_schema',
    'performance_schema',
    'users',
    'files',
  ), true)) {
      echo '<p style="color:red;">Reserved database name, please choose something else.</p>';
      die;
  }

  // Open a MySQL connection for database creation
  $conn = new mysqli('localhost', $mysql_config['mysql_username'], $mysql_config['mysql_password']);
  // Check the connection
  if ($conn->connect_error) {
      die('<p style="color:red;">Connection failed: '.$conn->connect_error.'</p>');
  }

  // Save credentials to file
  $mysql_config_file = '<?php return '.var_export($mysql_config, true).'; ?>'."\n";
  file_put_contents('conf/mysql_credentials.php', $mysql_config_file);

  // Drop database if wanted
  if ($_POST['drop_database'] === 'drop') {
      // Drop database
    $query = 'DROP DATABASE '.$mysql_config['mysql_database'];
      if ($conn->query($query) === true) {
          echo '<p style="color:green;">Database dropped successfully.</p>';
      } else {
          echo '<p style="color:red;">Error dropping database: '.$conn->error.'</p>';
      }
  }

  // Create new database
  $query = 'CREATE DATABASE '.$mysql_config['mysql_database'];
    if ($conn->query($query) === true) {
        echo '<p style="color:green;">Database created successfully.</p>';
    } else {
        echo '<p style="color:red;">Error creating database: '.$conn->error.'</p>'; // Fail if exists and continue.
    }
    $conn->close();

    $kirjuri_database = new PDO('mysql:host=localhost;dbname='.$mysql_config['mysql_database'].'', $mysql_config['mysql_username'], $mysql_config['mysql_password']);
    $kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $kirjuri_database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    $kirjuri_database->exec('SET NAMES utf8');

    try {
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
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
        $query->execute(array(':three' => '3'));
        echo '<p style="color:green;">User table created.</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">Error creating user table: ', $e->getMessage(), '.</p>';
    }

    try {
        $query = $kirjuri_database->prepare('
  CREATE TABLE IF NOT EXISTS tools (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  product_name varchar(256) NOT NULL,
  hw_version varchar(256) DEFAULT NULL,
  sw_version varchar(256) DEFAULT NULL,
  serialno varchar(256) DEFAULT NULL,
  flags varchar(16) DEFAULT NULL,
  attr_1 mediumtext DEFAULT NULL,
  attr_2 mediumtext DEFAULT NULL,
  attr_3 mediumtext DEFAULT NULL,
  attr_4 mediumtext DEFAULT NULL,
  attr_5 mediumtext DEFAULT NULL,
  attr_6 mediumtext DEFAULT NULL,
  attr_7 mediumtext DEFAULT NULL,
  attr_8 mediumtext DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
        $query->execute(array());
        echo '<p style="color:green;">Tool table created.</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">Error creating tool table: ', $e->getMessage(), '.</p>';
    }

    try {
        $query = $kirjuri_database->prepare('
  CREATE TABLE IF NOT EXISTS messages (
  id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  subject varchar(1024) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  msgfrom varchar(256) DEFAULT NULL,
  msgto varchar(256) DEFAULT NULL,
  received varchar(256) DEFAULT NULL,
  archived_from varchar(1) DEFAULT NULL,
  archived_to varchar(1) DEFAULT NULL,
  deleted_from varchar(1) DEFAULT NULL,
  deleted_to varchar(1) DEFAULT NULL,
  attr_1 mediumtext DEFAULT NULL,
  attr_2 mediumtext DEFAULT NULL,
  attr_3 mediumtext DEFAULT NULL,
  attr_4 mediumtext DEFAULT NULL,
  attr_5 mediumtext DEFAULT NULL,
  attr_6 mediumtext DEFAULT NULL,
  attr_7 mediumtext DEFAULT NULL,
  attr_8 mediumtext DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
        $query->execute(array());
        echo '<p style="color:green;">Messages table created.</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">Error creating messages table: ', $e->getMessage(), '.</p>';
    }


    try {
        $query = $kirjuri_database->prepare('
  INSERT INTO users (id, username, password, name, access, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
  :anon_user_id, :anon_name, :anon_pw, :anon_realname, :anon_access, :system_flags, :anon_attr1,
  NULL, NULL, NULL, NULL, NULL, NULL, NULL);

  INSERT INTO users (id, username, password, name, access, flags, attr_1, attr_2, attr_3, attr_4, attr_5, attr_6, attr_7, attr_8) VALUES (
  :admin_user_id, :admin_name, :admin_default_pw, :admin_realname, :admin_access, :system_flags, :admin_attr1,
  NULL, NULL, NULL, NULL, NULL, NULL, NULL);');
        $query->execute(array(
      ':anon_user_id' => '1',
      ':admin_user_id' => '2',
      ':anon_name' => 'anonymous',
      ':anon_realname' => 'Anonymous user',
      ':anon_access' => '3', // Add only access
      ':anon_attr1' => 'System account, do not remove.',
      ':admin_name' => 'admin',
      ':admin_default_pw' => $admin_password,
      ':anon_pw' => 'Not set.',
      ':admin_realname' => 'Administrator',
      ':admin_access' => '0',
      ':system_flags' => 'S',
      ':admin_attr1' => 'Extra attribute columns for future compatibility',
    ));
        echo '<p style="color:green;">Default users added.</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">Error creating user table: ', $e->getMessage(), '.</p>';
    }

    try {
        $query = $kirjuri_database->prepare('
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
  device_owner);');
        $query->execute(array(
      ':zero' => '0',
    ));
        echo '<p style="color:green;">Examination requests table created.</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">Error creating exam_requests: ', $e->getMessage(), '. Tables not created.</p>';
    }

  // Bring data from the limited release version database.

  if ($_POST['migrate_old_database'] === 'migrate') {
      try {
          $query = $kirjuri_database->prepare('
      INSERT INTO exam_requests SELECT * FROM tutkinta.jutut;
      ');
          $query->execute(array());
          echo '<p style="color:green;">Table tutkinta.jutut migrated.</p>';
      } catch (Exception $e) {
          echo '<p style="color:red;">Can not migrate old tables from tutkinta.jutut: ', $e->getMessage(), '.</p>';
      }
  }

  // Add columns for upgrading existing databases.

  try {
      $query = $kirjuri_database->prepare('
    ALTER TABLE exam_requests ADD criminal_act_date_start DATETIME;
    ALTER TABLE exam_requests ADD criminal_act_date_end DATETIME;
    ALTER TABLE exam_requests ADD case_password MEDIUMTEXT;
    ALTER TABLE exam_requests ADD case_owner MEDIUMTEXT;
    ALTER TABLE exam_requests ADD is_protected INT(1);
    ');
      $query->execute(array());
      echo '<p style="color:green;">Examination requests table upgraded.</p>';
  } catch (Exception $e) {
      echo '<p style="color:red;">Caught MySQL exception: ', $e->getMessage(), '. This is expected with existing tables.</p>';
  }
    echo '<p>Install script done, reload <a href="index.php">index.php</a>. The admininistrator account is "admin", log in with the password you designated.</p></div>
    </div>
    </body>
    </html>';
    die;
}
?>
</pre>
