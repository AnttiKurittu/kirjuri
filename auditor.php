<?php
require_once './include_functions.php';

ksess_verify(0);
if (isset($_GET['view'])) {

  $audit_file = filter_numbers(substr($_GET['view'], 0, 10)) . "_" . substr($_GET['view'], 11, 19);
  $audit_file_epoch = substr($_GET['view'], 0, 10);
  $output_format = "";
  $audit_file_date = strftime("%d/%b/%Y:%H:%M:%S %z", $audit_file_epoch);
  $audit_file_age = seconds_to_time(time() - $audit_file_epoch);
  if (isset($_GET['format']))
  {
    $output_format = $_GET['format'];
  }
  $encrypted = false;
  $data = trim(file_get_contents('logs/audit/' . substr($audit_file, 0, 6) . '/' . $audit_file));
  if ($data[0] !== "{")
  {
    $encrypted = true;
    $data = decrypt($data, include('conf/audit_credentials.php'));
  }
  $data_array = json_decode($data, true);
  $data_array['user']['audit_file_sha256'] = hash('sha256', $data);

  //event_log_write('0', 'Audit', 'Request viewed: ' . $audit_file);

  // https://stackoverflow.com/questions/3686177/php-to-search-within-txt-file-and-echo-the-whole-line
  $matches = array();
  $handle = @fopen("logs/kirjuri.log", "r");
  if ($handle)
{
    while (!feof($handle))
    {
        $buffer = fgets($handle);
        if(strpos($buffer, $audit_file) !== FALSE)
            $matches[] = $buffer;
    }
    fclose($handle);
}

//show results:

  $_SESSION['message_set'] = false;
  echo $twig->render('auditor.twig', array(
    'returnid' => $_GET['returnid'],
    'log_matches' => $matches,
    'output_format' => $output_format,
    'request_contents' => $data_array['request_contents'],
    'filename' => $audit_file,
    'encrypted' => $encrypted,
    'session' => $_SESSION,
    'settings' => $prefs['settings'],
    'lang' => $_SESSION['lang'],
    'data' => $data,
    'data_array' => $data_array,
    'timestamp' => $audit_file_date,
    'file_age' => $audit_file_age
  ));

} else {
  echo $twig->render('auditor.twig', array(
    'returnid' => $_GET['returnid'],
    'output_format' => "",
    'request_contents' => "",
    'filename' => "",
    'session' => $_SESSION,
    'settings' => $prefs['settings'],
    'lang' => $_SESSION['lang'],
    'data' => ""
  ));
}
?>
