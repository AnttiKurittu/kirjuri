<?php
require_once("./main.php");
$kirjuri_database = db('kirjuri-database');
$case_file_number = substr($_GET['case_file_number'], 0, 18);
$hakusana = $_GET['hae'];
if (!empty($case_file_number))
  {
    $query = $kirjuri_database->prepare('SELECT case_status, case_name, case_suspect, id, case_added_date, case_id FROM exam_requests WHERE case_file_number = :case_file_number AND id = parent_id AND is_removed != "1" ORDER BY case_id');
    $query->execute(array(
        ':case_file_number' => $case_file_number
    ));
    $out = $query->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($out))
      {
        echo "<h4><i class='fa fa-exclamation' style='color:red;'></i> NOTICE! Possible duplicate request:</h4>";
        foreach ($out as $entry)
          {
            if (empty($entry['case_name']))
              {
                $case_name = "RE " . $entry['case_suspect'];
              }
            else
              {
                $case_name = $entry['case_name'] . ", RE " . $entry['case_suspect'];
              }
            ;
            if ($entry['case_status'] === "3")
              {
                $tila = "success";
                $vaihe = "valmis";
              }
            elseif ($entry['case_status'] === "2")
              {
                $tila = "warning";
                $vaihe = "kesken";
              }
            elseif ($entry['case_status'] === "1")
              {
                $tila = "danger";
                $vaihe = "uusi";
              }
            else
              {
                $tila = "danger";
                $vaihe = "TUNTEMATON";
              }
            ;
            echo "<p><a class='btn btn-" . $tila . " btn-xs' href='view_case.php?case=" . $entry['id'] . "'>" . $entry['case_id'] . "/" . substr($entry['case_added_date'], 0, 4) . " " . $case_name . " (" . $vaihe . ")</a></p>";
          }
        ;
        echo "";
      }
    ;
  }
;
?>
