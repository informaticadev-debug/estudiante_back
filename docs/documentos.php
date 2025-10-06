<?php

/*
  Document   : certificacion_solicitud_formulario.php
  Created on : 30-sep-2014, 10:23
  Author     : Angel Caal
  Description:
        Solicitud de certificaciones de cursos
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();

verificarActualizarDatos();

if (isset($_SESSION[usuario])) {
	// Cargando la pagina para mostrar las ordenes de Pago.
	$template = new HTML_Template_Sigma('../templates');
	$template->loadTemplateFile('documentos.html');
	$template->show();
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
