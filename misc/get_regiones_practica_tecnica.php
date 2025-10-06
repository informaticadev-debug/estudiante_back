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
    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    $constructXML = XML_Query2XML::factory($db);

    $db->setfetchmode(DB_FETCHMODE_ASSOC);

    // Estado de las notificaciones
    $consulta = "SELECT r.extension, r.anio, r.semestre, r.departamento, d.nombre AS nombre_departamento, r.municipio, m.nombre AS nombre_municipio,
    r.cupo
    FROM practica_tecnica_region r
    INNER JOIN departamento d
    ON d.departamento = r.departamento
    INNER JOIN municipio m
    ON m.departamento = r.departamento AND m.municipio = r.municipio
    WHERE r.cupo > (
        SELECT COUNT(*)
        FROM practica_tecnica_asignacion a
        WHERE a.departamento = r.departamento AND a.municipio = r.municipio
    )";
    $regiones = & $db->getAll($consulta);
    if ($db->isError($regiones)) {
        echo "Error en la consulta de los municipios";
    } else {
        $dom = $constructXML->getFlatXML($consulta, 'listado_regiones', 'region');
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