<?php
//  http://server/projects/monitor/capture.php?id=n&value=v&date=ddmmyy&time=hhmmss
header("Content-type: text/plain");
$id = (int) $_GET["id"];
$value  = (int) $_GET["value"];
$date  = $_GET["date"];
$time  = $_GET["time"];
//$datetime = $date+$time;
$datetime = date_create_from_format('dmyHis', $date.$time)->format('Y-m-d H:i:s'); 

$con=mysqli_connect("localhost","monitor","monitor","capture_db");
// Check connection
if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

//guardo el valor
$insert="INSERT INTO samples (input_id, value, created) VALUES ($id,$value,'$datetime')";
mysqli_query($con,$insert) or die('Error: ' . mysqli_error($con));

//consulto parametros del input
$query="SELECT description, min, max, unit, notify, enabled, alerted FROM input WHERE id = ".$id;
$result = mysqli_query($con,$query);
if (!$result)
{
	die('Error: ' . mysqli_error($con));
}
if (mysqli_num_rows($result) != 1) {
	die('Error: filas distinto de 1');
}
$row = mysqli_fetch_assoc($result);

//analizo si tengo que generar la alerta
if ($row["enabled"]) {
	if ($value < $row["min"] or $value > $row["max"]) {
		if (!$row["alerted"]) {
			//envio mail
			$to = $row["notify"];
			$subject = "Exxer Monitor Alert";
 			$body = $row["description"]." fuera de rango (".$value." ".$row["unit"].")";
			//echo "Notified to ".$to;
			//echo $body;
 			mail($to, $subject, $body) or die('Error: no puede enviar mail');

			//marco que envie mail, para que no repita a cada rato
			$update="UPDATE input SET alerted = 1 WHERE id=".$id;
			mysqli_query($con,$update) or die('Error: ' . mysqli_error($con));

		}
	} else {
		if ($row["alerted"]) {
			//echo "reset alert";
			//habilito nuevamente el envio de alertas
			$update="UPDATE input SET alerted = 0 WHERE id=".$id;
			mysqli_query($con,$update) or die('Error: ' . mysqli_error($con));
		}
	}
}

$update = "UPDATE input SET current = ".$value.", last = '".$datetime."' WHERE id=".$id;
mysqli_query($con,$update) or die('Error: ' . mysqli_error($con));

mysqli_close($con);
?>
