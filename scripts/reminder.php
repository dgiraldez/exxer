<?php
//necesitamos que envie mail cada 10 minutos si hay alguna alarma activada

$link = mysqli_connect("localhost","monitor","monitor","capture_db") or
	die("Could not connect: " . mysqli_error());

//consulto inputs, los mismos que se muestran en las grillas
$query="SELECT description, unit, notify, enabled, alerted, current FROM input WHERE shows = 1";
$result = mysqli_query($link,$query);
if (!$result) die('Error: ' . mysqli_error());
while($row = mysqli_fetch_assoc($result)) {
	if ($row["enabled"] and $row["alerted"]) {
		//envio mail
		$to = $row["notify"];
		$subject = "Recordatorio Alerta de Exxer Monitor";
		$body = $row["description"]." fuera de rango (".$row["current"]." ".$row["unit"].")";
		//echo "Notified to ".$to;
		//echo $body;
		mail($to, $subject, $body) or die('Error: no puede enviar mail');
	}
}

mysqli_close($link);
?>

