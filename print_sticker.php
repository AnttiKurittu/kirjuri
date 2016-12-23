<?php
require_once './include_functions.php';
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
<?php
if ($_GET['type'] === 'examination_request') {
    $query = $kirjuri_database->prepare('select * FROM exam_requests WHERE id=:id AND parent_id=id');
    $query->execute(array(':id' => $_GET['uid']));
    $row = $query->fetch(PDO::FETCH_ASSOC);
    $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
    echo $generator->getBarcode('UID'.$row['id'], $generator::TYPE_CODE_128);
    echo '<br><b>[UID'.$row['id'].'] '.$row['case_id'].'/'.date('Y', strtotime($row['case_added_date'])).', '.$row['case_file_number'].'</b>';
    echo '<br><b>'.$row['case_name'].' '.$row['case_suspect'].'</b>';
    echo '<br>' . $row['case_investigator'].' ' .$row['case_investigator_unit'];
    exit;
}


if ($_GET['type'] === 'device') {
    $query = $kirjuri_database->prepare('select parent_id FROM exam_requests WHERE id=:uid');
    $query->execute(array(
        ':uid' => $_GET['uid'],
    ));
    $parentrow = $query->fetch(PDO::FETCH_ASSOC);
    $parent = $parentrow['parent_id'];
    $query = $kirjuri_database->prepare('select * FROM exam_requests WHERE id=:uid AND id = parent_id LIMIT 1');
    $query->execute(array(
        ':uid' => $parent,
    ));
    $parentrow = $query->fetch(PDO::FETCH_ASSOC);
    $query = $kirjuri_database->prepare('select * FROM exam_requests WHERE id=:uid AND id != parent_id LIMIT 1');
    $query->execute(array(
        ':uid' => $_GET['uid'],
    ));
    $row = $query->fetch(PDO::FETCH_ASSOC);
    $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
    echo $generator->getBarcode('UID'.$row['id'], $generator::TYPE_CODE_128);
    echo '<br><b>[UID'.$row['id'].'] '.$parentrow['case_id'].'/'.date('Y', strtotime($parentrow['case_added_date'])).', '.$parentrow['case_file_number'].'</b>';
    echo '<br>'.$row['device_type'].' '.$row['device_manuf'].' '.$row['device_model'].'<br>';
    exit;
}

?>
</body>
