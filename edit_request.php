<?php

require_once './include_functions.php';

ksess_verify(2); // View only or higher

// Force end session

// Declare variables
$session_cache = isset($_SESSION['post_cache']) ? $_SESSION['post_cache'] : ''; // Store form data if an error occurs
$sort_j = isset($_GET['j']) ? $_GET['j'] : '';
$get_case = isset($_GET['case']) ? $_GET['case'] : '';
$returntab = isset($_GET['tab']) ? $_GET['tab'] : '';
$dev_owner = urldecode(isset($_GET['dev_owner'])) ? $_GET['dev_owner'] : '';
$filelist = array();
$case_number = filter_numbers((substr($get_case, 0, 5)));
$confCrimes = strip_tags(file_get_contents('conf/crimes_autofill.conf'));
$kirjuri_database = connect_database('kirjuri-database');
$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id=:id AND parent_id=:id LIMIT 1');
$query->execute(array(
  ':id' => $case_number,
));
$caserow = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($caserow) === 0)
{
  header('Location: index.php');
  die;
}

if (empty($_SESSION['case_token'][$case_number]))
{
  $_SESSION['case_token'][$case_number] = generate_token(16); // Initialize case token
}

if (!empty($caserow['0']['case_owner']))
{
  $case_owner = explode(";", $caserow['0']['case_owner']);
  if ( ($_SESSION['user']['access'] > "0") && !in_array($_SESSION['user']['username'], $case_owner))
  {
    event_log_write($caserow[0]['id'], "Access", "Denied, user not in access group.");
    $_SESSION['message']['type'] = 'error';
    $_SESSION['message']['content'] = sprintf($_SESSION['lang']['not_in_access_group']);
    $_SESSION['message_set'] = true;
    header('Location: index.php');
    die;
  }
}
else
{
  $case_owner = array();
}

$query = $kirjuri_database->prepare('CREATE TABLE IF NOT EXISTS attachments (id INT(10) AUTO_INCREMENT PRIMARY KEY,
request_id INT(10), name VARCHAR(256), description TEXT, type VARCHAR(256), size INT NOT NULL, content MEDIUMBLOB NOT NULL,
uploader VARCHAR(256), date_uploaded DATETIME, hash VARCHAR(256), attr_1 TEXT, attr_2 TEXT, attr_3 TEXT) ');
$query->execute();

$query = $kirjuri_database->prepare('SELECT id, case_id, case_suspect, case_name, case_devicecount FROM exam_requests WHERE case_file_number=:case_file_number AND id = parent_id AND is_removed = 0 AND case_id != :case_id');
$query->execute(array(
  ':case_file_number' => $caserow[0]['case_file_number'],
  ':case_id' => $caserow[0]['case_id'],
));

$query = $kirjuri_database->prepare('SELECT id, name, size, uploader, type FROM attachments WHERE request_id = :id');
$query->execute(array(':id' => $caserow[0]['id']));
$attachment_files = $query->fetchAll(PDO::FETCH_ASSOC);

if ($sort_j === 'dev_owner') {
    $j = 'device_owner';
} elseif ($sort_j === 'dev_manuf') {
    $j = 'device_manuf';
} elseif ($sort_j === 'dev_model') {
    $j = 'device_model';
} elseif ($sort_j === 'id') {
    $j = 'id';
} elseif ($sort_j === 'device_action') {
    $j = 'device_action';
} elseif ($sort_j === 'dev_location') {
    $j = 'device_location';
} elseif ($sort_j === 'tvp') {
    $j = 'device_document, device_item_number';
} else {
    $j = 'device_type';
}
$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id != :id AND parent_id=:id AND device_type != "task" AND is_removed != "1" ORDER BY '.$j);
$query->execute(array(
  ':id' => $case_number,
));
$mediarow = $query->fetchAll(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id != :id AND parent_id=:id AND device_type = "task" AND is_removed != "1" ORDER BY '.$j);
$query->execute(array(
  ':id' => $case_number,
));
$tasks = $query->fetchAll(PDO::FETCH_ASSOC);

if (file_exists('attachments/'.$case_number.'/')) {
    $i = 0;
    $case_attachments = scandir('attachments/'.$case_number.'/', 0);
    natcasesort($case_attachments);
    foreach ($case_attachments as $file) {
        if ($file[0] !== '.') {
            $filelist[$i]['filename'] = $file;
            $filelist[$i]['filesize'] = filesize('attachments/'.$case_number.'/'.$file);
            $filelist[$i]['filetype'] = mime_content_type('attachments/'.$case_number.'/'.$file);
            ++$i;
        }
    }
}

if (file_exists('logs/cases/uid' . $case_number . '/events.log'))
{
  $caselog = array_reverse(file('logs/cases/uid' . $case_number . '/events.log'));
}
else {
  $caselog = "";
}

$_SESSION['message_set'] = false; // Prevent a message from being shown twice.
echo $twig->render('edit_request.twig', array(
  'ct' => $_SESSION['case_token'][$case_number],
  'caselog' => $caselog,
  'case_owner' => $case_owner,
  'session' => $_SESSION,
  'session_cache' => $session_cache,
  'free_disk_space' => disk_free_space('/'),
  'attachment_files' => $attachment_files,
  'filelist' => $filelist,
  'dev_owner' => $dev_owner,
  'j' => $sort_j,
  'sort_order' => $j,
  'returntab' => $returntab,
  'caserow' => $caserow,
  'mediarow' => $mediarow,
  'tasks' => $tasks,
  'settings' => $prefs['settings'],
  'device_locations' => $_SESSION['lang']['device_locations'],
  'device_actions' => $_SESSION['lang']['device_actions'],
  'media_objs' => $_SESSION['lang']['media_objs'],
  'devices' => $_SESSION['lang']['devices'],
  'inv_units' => $prefs['inv_units'],
  'classifications' => $_SESSION['lang']['classifications'],
  'confCrimes' => $confCrimes,
  'lang' => $_SESSION['lang'],
));
