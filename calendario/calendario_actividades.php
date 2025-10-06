<?php

/*
  Document   : calendario_actividades.php
  Created on : 08-Feb-2016, 11:37
  Author     : Angel Caal
  Description:
  -> Agenda de actividades del mes activo para los estudiatnes
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

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;

        $mes = DATE("m");

        $db->Query("SET lc_time_names = es_ES");

        // Calendario del mes actual
        $consulta = "SELECT DATE_FORMAT(c.fecha, '%d') AS dia, DATE_FORMAT(c.fecha, '%W') AS nombre_dia,
        c.actividad
        FROM calendario_actividades c
        WHERE c.registro_personal = 20010595 AND DATE_FORMAT(c.fecha, '%m') = $mes
        ORDER BY c.fecha ASC";
        $listado_actividades = & $db->getAll($consulta);
        if ($db->isError($listado_actividades)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener las actividades del mes actual.";
        }

        if (!$error) {

            // Cargando la pagina para mostrar las ordenes de Pago.
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('calendario_actividades.html');

            $template->setVariable(array(
                'nombre_mes' => nombre_mes($mes)
            ));

            foreach ($listado_actividades AS $la) {

                $template->setVariable(array(
                    'dia' => $la[dia],
                    'nombre_dia' => $la[nombre_dia],
                    'actividad' => $la[actividad]
                ));
                $template->parse("listado_actividades");
            }

            $template->show();
            exit();
        }

        if ($error) {
            mostrarError($mensaje);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>