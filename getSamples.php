<?php
//http://127.0.0.1/projects/monitor/getSamples.php?id=1
//header("Content-type: text/plain");
header('Content-type: application/json; charset=utf8');

$samples = array();

if (isset($_GET['id'])) {

$id = (int) $_GET["id"];

$username = "monitor";
$password = "monitor";
$hostname = "localhost";

$response=array();

//connection to the database
$dbhandle = mysql_connect($hostname, $username, $password)
or die("Unable to connect to MySQL");

//select a database to work with
$selected = mysql_select_db("capture_db",$dbhandle)
or die("Could not select test");

//execute the SQL query and return records
$query = "SELECT value, created FROM samples WHERE input_id = ".$id;
$result = mysql_query($query);

while ($row = mysql_fetch_assoc($result)) {
	$info = array();
	$info["value"]=$row["value"];
	$info["created"]=$row["created"];
	$samples[] = $info;
}

//close the connection
mysql_close($dbhandle);
}

print(json_encode($samples,JSON_UNESCAPED_UNICODE));

?>