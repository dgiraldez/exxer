<?php
$date = $_GET['date'];
$time = $_GET['time'];
$datetime = '140913133801';
$datetime2 = date_create_from_format('dmyHis', $date.$time); 
echo $datetime2->format('Y-m-d H:i:s'); 
?>