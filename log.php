<html>
<head>
</head>
<body>
	<h1>kirjuri.log</h1>
<pre>
<?php
require_once './include_functions.php';
ksess_verify(0);
$log = file('logs/kirjuri.log');
$log = array_reverse($log);
foreach($log as $line) {
	echo str_replace(";", " ", $line);
}
?>
</pre>
</body>
</html>
