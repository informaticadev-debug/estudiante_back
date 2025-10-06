<?php

/*
  Document   : cierre_solicitud_formulario.php
  Created on : 08-Jun-2015, 16:41
  Author     : Angel Caal
  Description:
  -> Solicitud de constancias de cierre
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "../config/local.php";
require_once "HTML/Template/Sigma.php";

session_start();

if (isset($_SESSION["usuario"])) {

    $errorLogin = false;
    $error = false;
    $aviso = false;

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

        $carnet = $_SESSION['usuario'];
        $fecha_actual = date("o-m-d");

        $template = new HTML_Template_Sigma('../templates');
        $template->loadtemplatefile('evaluaciongeneral.html');

        $template->show();
        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
