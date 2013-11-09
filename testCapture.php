<?php
//  http://server/projects/monitor/capture.php?id=n&value=v&date=ddmmyy&time=hhmmss
header("Content-type: text/plain");
$id = (int) $_GET["id"];
$value  = (int) $_GET["value"];
$date  = $_GET["date"];
$time  = $_GET["time"];
//$datetime = $date+$time;
$datetime = date_create_from_format('dmyHis', $date.$time)->format('Y-m-d H:i:s'); 
echo "id[".$id."] value[".$value."] date[".$date."] time[".$time."]"
?>
