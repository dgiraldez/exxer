<?php

$con=mysqli_connect("localhost","monitor","monitor","capture_db");
// Check connection
if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

//fecha = now - 180 minutos - 30 minutos
//los 180 son por las 3 hs del timezone
$ahora = date("Y-m-d H:i:s", time() - (180*60));
$fecha = date("Y-m-d H:i:s", time() - (180*60) - (30*60));
$where=" WHERE created > '".$fecha."'";
$query="SELECT created FROM samples".$where;
$result = mysqli_query($con,$query);
if (!$result) die('Error: ' . mysqli_error($con));
$alert = (mysqli_num_rows($result) == 0); //no recibi muestras, disparar alerta
	
//consulto parametros del input 0: alarma de falta de muestras
$query="SELECT notify, enabled, alerted FROM input WHERE id = 0";
$result = mysqli_query($con,$query);
if (!$result) die('Error: ' . mysqli_error($con));
if (mysqli_num_rows($result) != 1) die('Error: filas distinto de 1');
$row = mysqli_fetch_assoc($result);

//analizo si tengo que generar la alerta
if ($row["enabled"]) {
	if ($alert) {
		if (!$row["alerted"]) {
			//envio mail
			$to = $row["notify"];
			$subject = "Exxer Monitor Alert";
			$body = "No se recibieron muestras en los ultimos 30 minutos";
			mail($to, $subject, $body) or die('Error: no puede enviar mail');

			//marco que envie mail, para que no repita a cada rato
			$update="UPDATE input SET alerted = 1, last = '".$ahora."' WHERE id=0";
			mysqli_query($con,$update) or die('Error: ' . mysqli_error($con));
		}
	} else {
		if ($row["alerted"]) {
			//echo "reset alert";
			//habilito nuevamente el envio de alertas
			$update="UPDATE input SET alerted = 0, last = '".$ahora."' WHERE id=0";
			mysqli_query($con,$update) or die('Error: ' . mysqli_error($con));
		}
	}
}
		
mysqli_close($con);
?>

