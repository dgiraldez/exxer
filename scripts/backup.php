<?php
//backup y depuracion de muestras

function mail_attachment($to, $subject, $message, $from, $file) {
	// $file should include path and filename
	$filename = basename($file);
	$file_size = filesize($file);
	$content = chunk_split(base64_encode(file_get_contents($file)));
	$uid = md5(uniqid(time()));
	$from = str_replace(array("\r", "\n"), '', $from); // to prevent email injection
	$header = "From: ".$from."\r\n"
			."MIME-Version: 1.0\r\n"
			."Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n"
			."This is a multi-part message in MIME format.\r\n"
			."--".$uid."\r\n"
			."Content-type:text/plain; charset=iso-8859-1\r\n"
			."Content-Transfer-Encoding: 7bit\r\n\r\n"
			.$message."\r\n\r\n"
			."--".$uid."\r\n"
			."Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"
			."Content-Transfer-Encoding: base64\r\n"
			."Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n"
			.$content."\r\n\r\n"
			."--".$uid."--";
	return mail($to, $subject, "", $header);
}

$link = mysqli_connect("localhost","monitor","monitor","capture_db") or
	die("Could not connect: " . mysqli_error());

$to = "dgiraldez@gmail.com,dusgiraldez@gmail.com";
$subject = "Monitor - Depuracion de muestras";
$body = "El archivo adjunto contiene los valores de las muestras depuradas de la base.\n\nAtentamente, Exxer\n";
	
// $days son los dias que quiero mantener en la base
$days=90;
//fecha = now - 3 hs - days
$fecha_cutoff = date("Y-m-d 00:00:00", time()-(3600*3)-(3600*24*$days));

//obtengo las muestras a enviar
$query="SELECT 'id', 'input_id', 'value', 'created' UNION ALL
SELECT id, input_id, value, created
FROM samples
WHERE created < '".$fecha_cutoff."'";
$result = mysqli_query($link,$query) or die('Error: ' . mysqli_error($link));

//genero el archivo
$fp = fopen('/tmp/monitor0.csv', 'w');
while ($row = mysqli_fetch_assoc($result)) fputcsv($fp, $row, ';');
fclose($fp);

//envio mail
mail_attachment($to, $subject, $body, "monitor", "/tmp/monitor0.csv") 
	or die('Error enviando mail');

$query="DELETE FROM samples WHERE created < '".$fecha_cutoff."'";
$result = mysqli_query($link,$query) or die('Error: ' . mysqli_error($link));
		
mysqli_close($link);
?>

