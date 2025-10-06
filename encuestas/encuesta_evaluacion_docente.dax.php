<?php

/*
  Document   :encuesta_evaluacion_docente.php
  Created on : 20-may-2015, 11:21
  Author     : Angel Caal
  Description:
  -> Formulario de evaluacion docente
 */

require_once 'DB.php';
require_once 'HTML/Template/Sigma.php';
require_once '../misc/funciones.php';
session_start();

if (isset($_SESSION['usuario'])) {

    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];

    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::Connect($dsn);
    if (DB::isError($db)) {
        $mensaje = "La Plataforma esta temporalmente fuera de línea, por favor intente en un momento. Si el problema persiste comuníquese con el Programador (Angel Caal | 3070 1746)";
        errorLoginInicio($mensaje);
    } else {

        $rol = $_SESSION['rol'];

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;
        $aviso = false;

        $anio = $_SESSION['anio'];
        $semestre = 2;
        $evaluacion = "1";
        $carnet = $_SESSION['usuario'];
        $encuesta = $_GET['encuesta'];

        // Consultar la encuesta para el estudiante
        $consulta = "SELECT a.anio, a.semestre, a.evaluacion, a.carnet, a.codigo, a.seccion, 
		LCASE(CONCAT(TRIM(d.titulo), ' ', TRIM(d.nombre), ' ', TRIM(d.apellido))) AS docente
        FROM asignacion a
		INNER JOIN staff s
		ON s.extension = a.extension AND s.anio = a.anio AND s.semestre = a.semestre
		AND s.evaluacion = a.evaluacion AND s.pensum = a.pensum AND s.codigo = a.codigo
		AND s.seccion = a.seccion
		INNER JOIN docente d
		ON d.registro_personal = s.registro_personal
        WHERE a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion AND a.carnet = $carnet AND MD5(CONCAT(a.codigo,a.seccion)) = '$encuesta'";

        $datos_encuesta = & $db->getRow($consulta);
        if ($db->isError($datos_encuesta)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener los datos de la encuesta actual.";
            $url = $_SERVER[HTTP_REFERER];
        }

        if (!$error && !$aviso) {

            $template = new HTML_Template_Sigma("../templates");
            $template->LoadTemplateFile("encuesta_especial.html");

            $template->setVariable(array(
                'anio' => $datos_encuesta[anio],
                'semestre' => $datos_encuesta[semestre],
                'evaluacion' => $datos_encuesta[evaluacion],
                'carnet' => $datos_encuesta[carnet],
                'codigo' => $datos_encuesta[codigo],
                'seccion' => $datos_encuesta[seccion],
                'docente' => $datos_encuesta[docente]
            ));

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_aviso'])) {
                $mensaje_aviso = $_SESSION['mensaje_aviso'];
                $template->setVariable(array(
                    'mensaje_aviso' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #DF7401; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Aviso</h4>
									</div>
									<div class='modal-body'>
										$mensaje_aviso
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-warning' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
								</div>
							</div>
						</div>"
                ));
                unset($_SESSION['mensaje_aviso']);
            }

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_error'])) {
                $mensaje_error = $_SESSION['mensaje_error'];
                $template->setVariable(array(
                    'mensaje_error' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #DF0101; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Error en proceso</h4>
									</div>
									<div class='modal-body'>
										$mensaje_error
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-danger' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
								</div>
							</div>
						</div>"
                ));
                unset($_SESSION['mensaje_error']);
            }

            // Proceso culminado con exito
            if (isset($_SESSION['proceso_finalizado'])) {
                $proceso_finalizado = $_SESSION['proceso_finalizado'];
                $template->setVariable(array(
                    'mensaje_proceso_finalizado' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #084B8A; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Proceso finalizado</h4>
									</div>
									<div class='modal-body'>
										$proceso_finalizado
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-primary' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
								</div>
							</div>
						</div>"
                ));
                unset($_SESSION['proceso_finalizado']);
            }

            $template->show();
            exit;
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
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    errorLoginInicio($mensaje);
}
?>
