<?php
require_once("./include_functions.php");
?>
<html>
  <head>
    <meta charset="utf-8">
    <script type="text/javascript">
    window.print();
    window.onfocus=function(){ window.close();}
    </script>
  </head>
<!-- <body onload="window.print()"> -->
<body>
    <pre>
<?php
if ($_GET['type'] === "examination_request")
  {
    $kirjuri_database = db('kirjuri-database');
    $query = $kirjuri_database->prepare('select * FROM exam_requests WHERE id=:id AND parent_id=id');
    $query->execute(array(
        ':id' => $_GET['db_row']
    ));
    $row = $query->fetch(PDO::FETCH_ASSOC);
    echo "<b>" . $row['case_id'] . "/" . date("y", strtotime($row['case_added_date'])) . " " . $row['case_name'] . "</b>
" . $row['case_file_number'] . "
<b>" . $row['case_crime'] . "</b>
Tech <b>" . $row['forensic_investigator'] . "</b>
Inv. " . $row['case_investigator'] . " (" . $row['case_investigator_unit'] . ")" . "</b> " . $row['case_investigator_tel'] . "";
    exit;
  }

if ($_GET['type'] === "device")
  {
    $kirjuri_database = db('kirjuri-database');
    $query = $kirjuri_database->prepare('select parent_id FROM exam_requests WHERE id=:db_row');
    $query->execute(array(
        ':db_row' => $_GET['db_row']
    ));
    $parentrow = $query->fetch(PDO::FETCH_ASSOC);
    $parent = $parentrow['parent_id'];
    $kirjuri_database = db('kirjuri-database');
    $query = $kirjuri_database->prepare('select * FROM exam_requests WHERE id=:db_row AND id = parent_id LIMIT 1');
    $query->execute(array(
        ':db_row' => $parent
    ));
    $parentrow = $query->fetch(PDO::FETCH_ASSOC);
    echo "<b>" . $parentrow['case_id'] . "/" . date("y", strtotime($parentrow['case_added_date'])) . " " . $parentrow['case_name'] . " " . $parentrow['case_file_number'] . "</b><br>";
    $kirjuri_database = db('kirjuri-database');
    $query = $kirjuri_database->prepare('select * FROM exam_requests WHERE id=:db_row AND id != parent_id LIMIT 1');
    $query->execute(array(
        ':db_row' => $_GET['db_row']
    ));
    $row = $query->fetch(PDO::FETCH_ASSOC);
    echo $row['device_type'] . "<br>" . $row['device_manuf'] . " " . $row['device_model'] . " " . $row['device_size_in_gb'] . " GB<br>";
    echo $row['device_document'] . " Esine " . $row['device_item_number'] . "<br>";
    exit;
  }
?>
</pre>
</body>
