<?php

require_once("MyApi.php");

// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
    $API = new API($_REQUEST['request']);
    print($API->processAPI($_GET));
} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}

?>