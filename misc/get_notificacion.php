<?php

/*
  Estructura del menu
  -> Se tomara en cuenta el rol de cada usuario para mostrar la informacion
 */

require_once "../misc/funciones.php";
require_once "DB.php";
require_once "XML/Query2XML.php";

session_start();
$host = (isset($_SESSION['host'])) ? $_SESSION['host'] : NULL;
$user = (isset($_SESSION['user'])) ? $_SESSION['user'] : NULL;
$pass = (isset($_SESSION['pass'])) ? $_SESSION['pass'] : NULL;
$rol = (isset($_SESSION['rol'])) ? $_SESSION['rol'] : NULL;

if (isset($_SESSION['user'])) {

    // Preparar la conexion a la base de datos
    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    $constructXML = XML_Query2XML::factory($db);

    $db->setfetchmode(DB_FETCHMODE_ASSOC);
    $rol = (isset($_SESSION['rol'])) ? $_SESSION['rol'] : NULL;
    $usuario = $_SESSION['usuario'];

    // Estado de las notificaciones
    $consulta = "SELECT 
    (
        SELECT COUNT(*) 
        FROM notificaciones n2
        WHERE n2.leida = 0 AND n2.destinatario = $usuario
    ) AS sinleer,
    (
        SELECT COUNT(*) 
        FROM notificaciones n2
        WHERE n2.destinatario = $usuario
    ) AS total
    FROM notificaciones n
    WHERE n.destinatario = $usuario";
    $notificaciones = & $db->getRow($consulta);
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
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    header("location: /index.php");
    //mostrarErrorLogin($mensaje);
}
?>