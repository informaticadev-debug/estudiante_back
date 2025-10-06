<?php

/*
  Documento  : proyecto_graduacion_solicitud_introduccion.php
  Creado el  : 19 de agosto de 2014, 11:09
  Author     : Angel Caal
  Description:
  Introducción a la solicitud de tema de proyecto de graduacion.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();
if (isset($_SESSION[usuario])) {

    $errorLogin = false;
    $error = false;

    // Preparando la conexion a la base de datos.
    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];
    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    if (DB::isError($db)) {
        $errorLogin = true;
        $mensaje = "En sistema se encuentra fuera de linea temporalmente, disculpe las molestias.";
        mostrarErrorLogin($mensaje);
    } else {

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $anio = DATE("o");
        $carnet = $_SESSION['usuario'];

        // Determinar la carrera del estudiante
        $consulta = "SELECT *
        FROM inscripcion c
        WHERE c.anio = $anio AND c.carrera IN (1,3) AND c.carnet = $carnet
        GROUP BY c.carrera";
        $inscrito = & $db->getRow($consulta);
        if ($db->isError($inscrito)) {
            $error = true;
            $mensaje = "Hubo un problema al determinar la carrera actual";
            $url = "../menus/contenido.php";
        } else {

            if (count($inscrito) == 0) {
                $aviso = true;
                $mensaje = "Esta herramienta esta habilitada únicamente para estudiantes de licenciatura.";
                $url = "../menus/contenido.php";
            }
        }

        if (!$error && !$aviso) {

            $template = new HTML_Template_Sigma('../templates');

            if ($inscrito[carrera] == 1) {
                $template->loadTemplateFile('proyecto_graduacion_solicitud_introduccion_arq.html');
            } else if ($inscrito[carrera] == 3) {
                $template->loadTemplateFile('proyecto_graduacion_solicitud_introduccion_dg.html');
            }

            $template->show();
            exit();
        }

        if ($error) {
            error($mensaje, $url);
        }

        if ($aviso) {
            aviso($mensaje, $url);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>