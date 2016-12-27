<?php
session_start(); // Autorefresh session

if (!file_exists('cache/user_' . md5($_SESSION['user']['username']) . "/session_" . $_SESSION['user']['token'] . ".txt" ))
{
  echo '<i style="color:red;" class="fa fa-ban"></i>';
  die;
}

if (file_exists('conf/mysql_credentials.php')) {
    // Read credentials array from a file
  $mysql_config = include 'conf/mysql_credentials.php';
} elseif (file_exists('/etc/kirjuri/mysql_credentials.php')) {
    $mysql_config = include '/etc/kirjuri/mysql_credentials.php';
} else {
    header('Location: install.php'); // If file not found, assume install.php needs to be run.
  die;
}

function db_r($database) // PDO Database connection
{
    global $mysql_config;
    if ($database === 'kirjuri-database') {
        try {
            $kirjuri_database = new PDO('mysql:host=localhost;dbname='.$mysql_config['mysql_database'].'', $mysql_config['mysql_username'], $mysql_config['mysql_password']);
            $kirjuri_database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $kirjuri_database->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $kirjuri_database->exec('SET NAMES utf8');

            return $kirjuri_database;
        } catch (PDOException $e) {
            session_destroy();
            echo '<span style="color:black;">Database error: '.$e->getMessage().'. Run <a href="install.php">install</a> to create or upgrade tables and check your credentials.</span>';
            die;
        }
    }
}

$kirjuri_database = db_r('kirjuri-database'); // Read tools from database to settings.
$query = $kirjuri_database->prepare('SELECT (SELECT COUNT(id) FROM messages WHERE msgto = :username AND received = "0") as new');
$query->execute(array(':username' => $_SESSION['user']['username']));
$_SESSION['unread'] = $query->fetch(PDO::FETCH_ASSOC);
if ($_SESSION['unread']['new'] > 0) {
  echo '<span style="color:red;"><i class="fa fa-envelope-o"></i> ' . $_SESSION['unread']['new'] . '</span>';
}
