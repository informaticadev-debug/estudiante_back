<?php

/*
  Mensajes enviados de  forma dinamica
  -> Revision a la base y actualizacion de mensajes entrantes
 */
session_start();
require_once 'DB.php';
require_once '../misc/funciones.php';
require_once 'XML/Query2XML.php';

if (isset($_SESSION[usuario])) {

    // Preparando la conexion a la base de datos
    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];
    $dsn = "mysql://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    $query2xml = XML_Query2XML::factory($db);
    if (DB::isError($db)) {
        $errorLogin = true;
        $mensaje = "En sistema se encuentra fuera de linea temporalmente, disculpe las molestias.";
        mostrarErrorLogin($mensaje);
    } else {

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;
        $errorLogin = false;
        $carnet = $_GET[carnet];

        // Consulta de mensajes del estudiante
        $consulta = "SELECT e.carnet, rtrim(e.nombre) AS name
		FROM estudiante e
		WHERE e.extension = 12
		LIMIT 8";
        $mensajes = & $db->getAll($consulta);
        $dom = $query2xml->getFlatXML($consulta, 'mensajes_nuevos', 'mensajes');
        header('Content-Type: application/xml');
        $dom->formatOutput = true;
        print $dom->saveXML();
    }

    $db->disconnect();
} else {
    $errorLogin = true;
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese de nuevo.";
    mostrarErrorLogin($mensaje);
}

if ($errorLogin) {
    header("location: ../index.php?error=true");
}
?>