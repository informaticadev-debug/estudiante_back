<?php

/*
  Estructura del menu
  -> Se tomara en cuenta el rol de cada usuario para mostrar la informacion
 */

require_once "../misc/funciones.php";
require_once "DB.php";
require_once "XML/Query2XML.php";

session_start();
$host = $_SESSION['host'];
$user = $_SESSION['user'];
$pass = $_SESSION['pass'];
$rol = $_SESSION['rol'];

if (isset($_SESSION[user])) {
    // Preparar la conexion a la base de datos
    $dsn = "mysql://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    $constructXML = XML_Query2XML::factory($db);

    $db->setfetchmode(DB_FETCHMODE_ASSOC);
    $carnet = $_SESSION['usuario'];

    // Estado de las notificaciones
    $consulta = "SELECT 
				(
					SELECT COUNT(*)
					FROM notificaciones n2
					WHERE n2.destinatario = n.destinatario AND n2.leida = 0
				) AS sinleer
			FROM notificaciones n
			WHERE n.destinatario = $carnet
			ORDER BY n.leida ASC";
    $notificaciones = & $db->getAll($consulta);
    if ($db->isError($notificaciones)) {
        echo "Error en la consulta";
    } else {
        $dom = $constructXML->getFlatXML($consulta, 'mensajes_nuevos', 'mensajes');
        header("Content-Type: application/xml");
        $dom->formatOutput = true;
        print $dom->saveXML();
    }

    $db->disconnect();
} else {
    header("location: ../index.php?error=true");
    $mensaje = "La sesion ha caducado por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>