<?php
header('Content-type: application/json; charset=utf8');
//header("Content-type: text/plain");
$username = "monitor";
$password = "monitor";
$hostname = "localhost";

$response=array();

//connection to the database
$dbhandle = mysql_connect($hostname, $username, $password)
or die("Unable to connect to MySQL");
mysql_set_charset('utf8');

//select a database to work with
$selected = mysql_select_db("capture_db",$dbhandle)
or die("Could not select test");

$inputs = array();

$query = "SELECT id, description, min, max, current, unit, notify, enabled, alerted, created, last FROM input";
if (isset($_GET['description'])) {
	$description = $_GET["description"];
	$query = $query." WHERE description like '".str_replace("*", "%", $description)."'";
}

//execute the SQL query and return records
$result = mysql_query($query);

while($row = mysql_fetch_assoc($result)) {
	$info = array();
	$info["id"]=$row["id"];
	$info["description"]=$row["description"];
	$info["min"]=$row["min"];
	$info["max"]=$row["max"];
	$info["current"]=$row["current"];
	$info["unit"]=$row["unit"];
	$info["notify"]=$row["notify"];
	$info["enabled"]=$row["enabled"];
	$info["alerted"]=$row["alerted"];
	$info["created"]=$row["created"];
	$info["last"]=$row["last"];
	$inputs[] = $info;
}


//close the connection
mysql_close($dbhandle);

print(json_encode($inputs,JSON_UNESCAPED_UNICODE));

?>