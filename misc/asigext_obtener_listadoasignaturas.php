<?php

/*
  Document   : asigext_obtener_listadoasignaturas.php
  Created on : 03-Jun-2016, 15:24
  Author     : Angel Caal
  Description:
  -> OBtener listado de asignaturas para el estudiante
 */

require_once "../misc/funciones.php";
require_once "DB.php";
require_once "XML/Query2XML.php";

session_start();
$user = $_SESSION['user'];
$pass = $_SESSION['pass'];
$host = $_SESSION['host'];

function obtenerCursosASolicitarAE($db, $carnet, $carrera, $anio, $semestre, $extension, $evaluacion) {
    $consulta = "
                SELECT 1 AS estado, ao.pensum, ao.codigo, TRIM(c.nombre) AS asignatura
                FROM seccion ao
                   INNER JOIN curso c ON c.pensum = ao.pensum AND c.codigo = ao.codigo
                   INNER JOIN pensum p on p.pensum = c.pensum
                WHERE ao.anio = $anio AND ao.semestre = $semestre AND ao.evaluacion = $evaluacion AND p.carrera = $carrera AND ao.extension = $extension
                    AND (ao.pensum, ao.codigo) NOT IN (
                        SELECT pensum, codigo
                        FROM asignacion
                        WHERE anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion AND codigo = ao.codigo AND seccion = ao.seccion AND extension = $extension AND carnet = $carnet
                    )
                    AND (ao.pensum, ao.codigo) NOT IN (
                        SELECT pensum, codigo
                        FROM nota
                        WHERE carnet = $carnet AND pensum = ao.pensum AND codigo = ao.codigo
                    )
					AND (ao.pensum, ao.codigo) NOT IN (
                        SELECT pensum, codigo
                        FROM asignacion_spd
                        WHERE anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion AND codigo = ao.codigo AND seccion = ao.seccion AND extension = $extension AND carnet = $carnet
                    )
                GROUP BY ao.pensum, ao.codigo
            ";
    $cursos = & $db->getAll($consulta);
    $cursosAHabilitar = array();
    foreach ($cursos as $curso) {
        $data = [
            "auth" => [
                "user" => "arqws",
                "passwd" => "a08!¡+¿s821!kdui23#kd$"
            ],
            "id" => $carnet,
            "fecha" => '2018-08-30',
            "pensum" => $curso['pensum'],
            "codigo" => $curso['codigo']
        ];
        //consumiendo el servicio Rest de Inscripciones
        $respuesta = postRequest('http://arquitectura.usac.edu.gt/rest/Prerrequisitos', $data);
        //var_dump($curso['codigo'] . ":::" . $respuesta); die;
        $array_resp = json_decode($respuesta);
        if ($array_resp->validacion == true) {
            $cursosAHabilitar[] = $curso;
        } else {
            /* if ($carnet == 201122738 and $curso['codigo'] == '3.10.4') {
              var_dump($respuesta); die;
              } */
            //var_dump($array_resp); die;
        }
    }
    return $cursosAHabilitar;
}

if (isset($_SESSION['usuario'])) {

    // Preparar la conexion a la base de datos
    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    $constructXML = XML_Query2XML::factory($db);

    $db->setfetchmode(DB_FETCHMODE_ASSOC);

    $carnet = $_SESSION['usuario'];
    $extension = $_SESSION['extension'];
    $anio = $_SESSION['anio'];
    $semestre = $_SESSION['semestre'];
    $evaluacion = 1;
    $carrera = $_GET['carrera'];

    // Obtener los requisitos del motivo seleccionado
    $cursos_array = obtenerCursosASolicitarAE($db, $carnet, $carrera, $anio, $semestre, $extension, $evaluacion);

    header("Content-Type: application/xml");

    echo '<?xml version="1.0" encoding="UTF-8"?>
        <listado_cursos>';

    foreach ($cursos_array as $curso) {
        echo '<curso>
                <estado>' . $curso['estado'] . '</estado>
                <pensum>' . $curso['pensum'] . '</pensum>
                <codigo>' . $curso['codigo'] . '</codigo>
                <asignatura>' . $curso['asignatura'] . '</asignatura>
              </curso>';
    }
    echo '</listado_cursos>';

    $db->disconnect();
} else {
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    errorLogin($mensaje);
}
?>