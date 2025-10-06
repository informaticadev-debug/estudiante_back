<?php

/*
  Document   : encuesta_nuevaencuesta_procesar.php
  Created on : 16-mar-2015, 23:30
  Author     : Angel Caal
  Description:
  -> mysqli:// la encuesta respondida
 */

require_once 'DB.php';
require_once 'HTML/Template/Sigma.php';
require_once '../misc/funciones.php';
session_start();

if (isset($_SESSION['usuario'])) {

    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];

    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/actualizaciones";
    $db = DB::Connect($dsn);
    if (DB::isError($db)) {
        $mensaje = "La Plataforma esta temporalmente fuera de línea, por favor intente en un momento. Si el problema persiste comuníquese con el Programador (Angel Caal | 3070 1746)";
        errorLoginInicio($mensaje);
    } else {

        $rol = $_SESSION['rol'];

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;
        $carnet = $_SESSION['usuario'];

        // Validar encuesta respondida por el estudiante
        $consulta = "INSERT INTO validar_encuesta_externa
        (carnet, fecha_validacion)
        VALUES (
            $carnet,
            NOW()
        )";
        $registrar_validacion = & $db->Query($consulta);
        if ($db->isError($registrar_validacion)) {
            
        } else {
            header("location: https://docs.google.com/a/farusac.edu.gt/forms/d/166ACHKaNkMWWy5PjfnUOx5kEMMg41zCw_bgDhK0FcJk/viewform");
        }
    }
} else {
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    errorLoginInicio($mensaje);
}
?>