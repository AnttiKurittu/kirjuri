<?php

require_once './include_functions.php';
ksess_verify(0);

// Declare variables
$_GET['populate'] = isset($_GET['populate']) ? $_GET['populate'] : '';
$fields = array();

foreach ($_SESSION['all_users'] as $user) { // Get user information based on GET parameter
  if ($user['id'] === $_GET['populate']) {
      $fields['id'] = $user['id'];
      $fields['username'] = $user['username'];
      $fields['name'] = $user['name'];
      $fields['access'] = $user['access'];
      $fields['flags'] = $user['flags'];
      $fields['attr_1'] = $user['attr_1'];
      $ip_access_list = json_decode($user['attr_2'], TRUE);
      $fields['apikey'] = hash('sha1', $user['username'].$user['password']);
      if ($ip_access_list['allow'][0])
      {
        $fields['whitelist'] = str_replace(",", ", ", implode(",", $ip_access_list['allow']));
      }
      if ($ip_access_list['deny'][0])
      {
        $fields['blacklist'] = str_replace(",", ", ", implode(",", $ip_access_list['deny']));
      }

      $fields['sessions'] = array();

      if ( file_exists('cache/user_' . md5($user['username']) ))

      {
        $session_dir = scandir('cache/user_'.md5($user['username']));
        foreach($session_dir as $sessionfile)
        {
          if ($sessionfile[0] !== ".")
          {
            $fields['sessions'][$sessionfile]['content'] = file_get_contents("cache/user_" . md5($user['username']) . "/" . $sessionfile);
            $fields['sessions'][$sessionfile]['last_activity'] = secondsToTime( time() - filemtime( "cache/user_" . md5($user['username']) . "/" . $sessionfile));
            $fields['sessions'][$sessionfile]['last_activity_sec'] = time() - filemtime( "cache/user_" . md5($user['username']) . "/" . $sessionfile);
          }
        }
      }
  }
}

$_SESSION['message_set'] = false;
echo $twig->render('users.html', array(
    'your_ip' => $_SERVER['REMOTE_ADDR'],
    'session' => $_SESSION,
    'settings' => $settings,
    'lang' => $_SESSION['lang'],
    'fields' => $fields,
));
