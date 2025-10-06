<?php

/*
  Document   : laboratorios_obtenercursos.php
  Created on : 03-Jun-2016, 12:53
  Author     : Angel Caal
  Description:
  -> Obtener el listado de cursos de laboratorio
 */

require_once "../misc/funciones.php";
require_once "DB.php";
require_once "XML/Query2XML.php";

session_start();
$user = $_SESSION['user'];
$pass = $_SESSION['pass'];
$host = $_SESSION['host'];

if (isset($_SESSION['usuario'])) {

    //habilita opciones y modulos del proceso en curso..
    $proceso = 1;

    // Preparar la conexion a la base de datos
    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    $constructXML = XML_Query2XML::factory($db);

    $db->setfetchmode(DB_FETCHMODE_ASSOC);
    $motivo = $_GET['motivo'];

    // Obtener los requisitos del motivo seleccionado
    $consulta = "SELECT 1 AS estado, d.requisito
    FROM asignacion_extemporanea_motivos_detalle d
    WHERE d.motivo = $motivo AND d.proceso = $proceso";
    $requisitos = & $db->getAll($consulta);
    if ($db->isError($requisitos)) {
        $consulta = "SELECT 0 AS estado, 'Hubo un problema al obtener el listado de requisitos' AS detalle";
    } else {

        if (count($requisitos) == 0) {
            $consulta = "SELECT 1 AS estado, 'Los que considere necesarios' AS requisito";
        }
    }

    $dom = $constructXML->getFlatXML($consulta, 'listado_cursos', 'curso');
    header("Content-Type: application/xml");
    $dom->formatOutput = true;
    print $dom->saveXML();

    $db->disconnect();
} else {
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    errorLogin($mensaje);
}
?>