<?php
require_once './include_functions.php';

$_SESSION['user']['access'] = isset($_SESSION['user']['access']) ? $_SESSION['user']['access'] : '';

if ($_SESSION['user']['access'] !== "0")
{
  http_response_code(403);
  die;
}
$active_session_found = false;
$username = urldecode($_GET['user']);
$user_dir = 'cache/user_'.$username;
if (file_exists('cache/user_'.$username))
{
  $session_dir = scandir('cache/user_'.$username);
  foreach($session_dir as $sessionfile)
  {
    if ($sessionfile[0] !== ".")
    {
      if ( (time() - filemtime( "cache/user_" . $username . "/" . $sessionfile)) <= 30)
      {
        $active_session_found = true; // Found a fresh session file
      }
      elseif ( (time() - filemtime( "cache/user_" . $username . "/" . $sessionfile)) >= 259200)
      {
        unlink("cache/user_" . $username . "/" . $sessionfile); // Purge offline session files older than three days.
        event_log_write('0', 'Admin', 'Purged stale session ' . $sessionfile . ' for user ' . $username);
      }
    }
  }

if ($active_session_found === true)
{
  echo '<i title="online" style="color:#0D0;" class="fa fa-circle"></i>';
  die;
}
else
{
  echo '<i title="offline" style="color:gray;" class="fa fa-circle"></i>';
  die;
}

}
else {
  echo '<i title="passive" style="color:gray;" class="fa fa-circle-o"></i>';
  die;
}
