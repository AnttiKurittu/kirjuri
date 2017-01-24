<?php

require_once './include_functions.php';
ksess_verify(3);

$open = isset($_GET['open']) ? $_GET['open'] : '';
$prefill_msgto = isset($_GET['msgto']) ? $_GET['msgto'] : '';
$_SESSION['post_cache']['subject'] = urldecode(isset($_GET['subject']) ? $_GET['subject'] : '');
$show = isset($_GET['show']) ? $_GET['show'] : '';

foreach($_POST as $key => $value) // Sanitize all POST data
{
  $value = filter_html($value);
  $_POST[$key] = isset($value) ? $value : '';
}

$open = filter_numbers($open);

if ($open > 0)
{
  $query = $kirjuri_database->prepare('UPDATE messages SET received = NOW() WHERE id = :open AND msgto = :username');
  $query->execute(array(':open' => $open, ':username' => $_SESSION['user']['username']));
}

$query = $kirjuri_database->prepare('SELECT
(SELECT COUNT(id) FROM messages WHERE msgto = :username AND received = "0") as new,
(SELECT COUNT(id) FROM messages WHERE msgto = :username AND archived_to = "0") AS msgcount_to,
(SELECT COUNT(id) FROM messages WHERE msgfrom = :username AND archived_from = "0") AS msgcount_from,
(SELECT COUNT(id) FROM messages WHERE (msgto = :username AND archived_to = "1" AND deleted_to = "0") OR (msgfrom = :username AND archived_from = "1" AND deleted_from = "0")) AS msgcount_archived');
$query->execute(array(':username' => $_SESSION['user']['username']));
$_SESSION['unread'] = $query->fetch(PDO::FETCH_ASSOC);

$query = $kirjuri_database->prepare('SELECT * FROM messages WHERE msgfrom = :username OR msgto = :username ORDER BY attr_1 DESC');
$query->execute(array(':username' => $_SESSION['user']['username']));
$messages = $query->fetchAll(PDO::FETCH_ASSOC);

$i = 0;
foreach($messages as $message) {
  $messages[$i]['body'] = gzinflate(base64_decode($message['body']));
  $messages[$i]['subject'] = gzinflate(base64_decode($message['subject']));
  $i++;
}

$_SESSION['message_set'] = false;
echo $twig->render('messages.twig', array(
    'post' => $_POST,
    'session' => $_SESSION,
    'settings' => $prefs['settings'],
    'lang' => $_SESSION['lang'],
    'messages' => $messages,
    'open' => $open,
    'show' => $show,
    'prefill_msgto' => $prefill_msgto
));
