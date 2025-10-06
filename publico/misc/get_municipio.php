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
    $departamento = $_GET['departamento'];

    // Estado de las notificaciones
    $consulta = "SELECT m.municipio, m.nombre
    FROM municipio m
    WHERE m.departamento = $departamento";
    $municipio = & $db->getAll($consulta);
    if ($db->isError($municipio)) {
        echo "Error en la consulta de los municipios";
    } else {
        $dom = $constructXML->getFlatXML($consulta, 'listado_municipios', 'municipios');
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