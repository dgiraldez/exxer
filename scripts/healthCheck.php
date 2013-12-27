<?php
//necesitamos que envie un mail por dia que diga sistema funcionando 
//y con el estado de las alarmas y las temperaturas (freezers y ambiente) 
//si se puede que lo envie al mediodia

$link = mysqli_connect("localhost","monitor","monitor","capture_db") or
	die("Could not connect: " . mysqli_error());
//consulto parametros del backup
$query="SELECT notify, enabled FROM input WHERE id = -1";
$result = mysqli_query($link,$query);
if (!$result) die('Error: ' . mysqli_error());
if (mysqli_num_rows($result) != 1) die('Error: filas distinto de 1');
$row = mysqli_fetch_assoc($result);

//analizo si tengo que alertar, lo pueden deshabilitar por configuracion
if ($row["enabled"]) {
	$to = $row["notify"];
	$subject = "Sistema funcionando";
	//obtengo valores actuales, los mismos que se muestran en las grillas
	$query="SELECT description, current, unit, last FROM input WHERE shows = 1";
	$result = mysqli_query($link,$query);
	if (!$result) die('Error: ' . mysqli_error());
	$body = "Valores actuales\n\n";
	while($row = mysqli_fetch_assoc($result)) {
		$body = $body.sprintf("%s: %d %s (%s)\n",$row["description"],$row["current"],$row["unit"],$row["last"]);
	}
	//envio mail con valores
	//echo $body;
	mail($to, $subject, $body) or die('Error: no puede enviar mail');
	
	//fecha = now - 180 minutos - 30 minutos
	//los 180 son por las 3 hs del timezone
	$ahora = date("Y-m-d H:i:s", time() - (180*60));
	//marco que envie mail
	$update="UPDATE input SET last = '".$ahora."' WHERE id=-1";
	mysqli_query($link,$update) or die('Error: ' . mysqli_error());
}
		
mysqli_close($link);
?>

