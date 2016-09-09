<?php
require_once("./include_functions.php");

protect_page(2); // View only or higher

// Declare variables
$failed_uploads             = isset($_SESSION['failed_uploads']) ? $_SESSION['failed_uploads'] : '';
$_SESSION['failed_uploads'] = ""; // Reset the session variable for failed uploads after moving it to a local variable.
$session_cache              = isset($_SESSION['post_cache']) ? $_SESSION['post_cache'] : ''; // Store form data if an error occurs
$sort_j                     = isset($_GET['j']) ? $_GET['j'] : '';
$get_case                   = isset($_GET['case']) ? $_GET['case'] : '';
$get_drop_file              = isset($_GET['drop_file']) ? $_GET['drop_file'] : '';
$upload_status              = isset($_GET['upload_status']) ? $_GET['upload_status'] : '';
$returntab                  = isset($_GET['tab']) ? $_GET['tab'] : '';
$dev_owner                  = urldecode(isset($_GET['dev_owner'])) ? $_GET['dev_owner'] : '';
$filelist                   = array();
$case_number                = preg_replace("/[^0-9]/", "", (substr($get_case, 0, 5)));
$confCrimes                 = file_get_contents('conf/crimes_autofill.conf');
$kirjuri_database           = db('kirjuri-database');
$query                      = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id=:id AND parent_id=:id LIMIT 1');
$query->execute(array(
  ':id' => $case_number
));
$caserow = $query->fetchAll(PDO::FETCH_ASSOC);
$query   = $kirjuri_database->prepare('SELECT id, case_id, case_suspect, case_name, case_devicecount FROM exam_requests WHERE case_file_number=:case_file_number AND id = parent_id AND is_removed = 0 AND case_id != :case_id');
$query->execute(array(
  ':case_file_number' => $caserow[0]['case_file_number'],
  ':case_id' => $caserow[0]['case_id']
));
$samerequest_file_number = $query->fetchAll(PDO::FETCH_ASSOC);

if ($sort_j === "dev_owner")
 {
  $j = "device_owner";
 }
elseif ($sort_j === "dev_manuf")
 {
  $j = "device_manuf";
 }
elseif ($sort_j === "dev_model")
 {
  $j = "device_model";
 }
elseif ($sort_j === "id")
 {
  $j = "id";
 }
elseif ($sort_j === "device_action")
 {
  $j = "device_action";
 }
elseif ($sort_j === "dev_location")
 {
  $j = "device_location";
 }
elseif ($sort_j === "tvp")
 {
  $j = "device_document, device_item_number";
 }
else
 {
  $j = "device_type";
 }
$query = $kirjuri_database->prepare('SELECT * FROM exam_requests WHERE id != :id AND parent_id=:id AND is_removed != "1" ORDER BY ' . $j);
$query->execute(array(
  ':id' => $get_case
));
$mediarow = $query->fetchAll(PDO::FETCH_ASSOC);
if (!file_exists("conf/instructions_" . str_replace(" ", "_", strtolower($caserow[0]['classification'])) . ".txt"))
 {
  $instructions_text = "File conf/instructions_" . str_replace(" ", "_", strtolower($caserow[0]['classification'])) . ".txt not found.";
 }
else
 {
  $instructions_text = file_get_contents("conf/instructions_" . str_replace(" ", "_", strtolower($caserow[0]['classification'])) . ".txt");
 }

$drop_file_target = "attachments/" . $case_number . "/" . stripslashes(str_replace("./", "", str_replace("../", "", urldecode($get_drop_file))));
if ((!empty($get_drop_file) && (file_exists($drop_file_target))))
 {
  logline('Action', 'Attachment deleted: ' . $drop_file_target);
  unlink($drop_file_target);
 }

if (file_exists("attachments/" . $case_number . "/"))
 {
  $i                = 0;
  $case_attachments = scandir("attachments/" . $case_number . "/", 0);
  natcasesort($case_attachments);
  foreach ($case_attachments as $file)
   {
    if (($file === ".") || ($file === ".."))
     {
      continue;
     }
    else
     {
      $filelist[$i]['filename'] = $file;
      $filelist[$i]['filesize'] = filesize("attachments/" . $case_number . "/" . $file);
      $filelist[$i]['filetype'] = mime_content_type("attachments/" . $case_number . "/" . $file);
      $i++;
     }
   }
 }

$_SESSION['message_set'] = false; // Prevent a message from being shown twice.
echo $twig->render('edit_request.html', array(
  'session' => $_SESSION,
  'failed_uploads' => $failed_uploads,
  'session_cache' => $session_cache,
  'free_disk_space' => disk_free_space("/"),
  'upload_status' => $upload_status,
  'filelist' => $filelist,
  'samerequest_file_number' => $samerequest_file_number,
  'dev_owner' => $dev_owner,
  'j' => $sort_j,
  'sort_order' => $j,
  'returntab' => $returntab,
  'caserow' => $caserow,
  'mediarow' => $mediarow,
  'settings' => $settings,
  'device_locations' => $_SESSION['lang']['device_locations'],
  'device_actions' => $_SESSION['lang']['device_actions'],
  'media_objs' => $_SESSION['lang']['media_objs'],
  'devices' => $_SESSION['lang']['devices'],
  'forensic_investigators' => $settings_contents['forensic_investigators'],
  'phone_investigators' => $settings_contents['phone_investigators'],
  'inv_units' => $settings_contents['inv_units'],
  'classifications' => $_SESSION['lang']['classifications'],
  'confCrimes' => $confCrimes,
  'instructions_text' => $instructions_text,
  'lang' => $_SESSION['lang']
));
?>
