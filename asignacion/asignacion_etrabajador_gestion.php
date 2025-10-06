<?php

/*
  Documento  : asignacion_extemporanea_gestion.php
  Creado el  : 06-Jul-2016 12:28
  Author     : Angel Caal
  Description:
  Estado de solicitud de asignación extemporanea esta llega a la dirección correspondiente
 */

require_once "DB.php";
require_once "../misc/funciones.php";
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

        $db->setfetchmode(DB_FETCHMODE_ASSOC);

        //extraccion de variables basicas...
        $extension = $_SESSION['extension'];
        $carnet = $_SESSION['usuario'];
        $anio = DATE("o");
        $semestre = $_SESSION['semestre'];
        $evaluacion = 1;
        
        $anioRegistro = 2019;
        $semestreRegistro = 1;
        $evaluacionRegistro = 1;
        
        //verificando solicitudes previas o falta de autorizacion
        $solicitud = getAsignacionETrabajadorSolicitud($db, $extension, $anioRegistro, $semestreRegistro, $evaluacionRegistro, $carnet);
        /* A PARTIR DEL 1ER. SEMESTRE 2018 SE LIBERO PARA TODOS LOS ESTUDIANTES...
        if (empty($solicitud)) {
            aviso("Usted no tiene permitido el ingreso a esta opción.", "../menus/contenido.php");
            exit;
        } else*/ 
        if (!empty($solicitud) && array_key_exists('estado', $solicitud) && $solicitud['estado'] > 0) {
            aviso("Usted ya realizo una solicitud, la resolución será enviada por un mensaje en su perfil de estudiante.", "../menus/contenido.php");
            exit;
        }

        // Verificar la inscripcion del estudiante 
        $consulta = "SELECT i.carrera, trim(c.nombre) AS nombre
        FROM inscripcion i
        INNER JOIN carrera c
        ON c.carrera = i.carrera
        WHERE i.anio = $anio AND i.semestre = $semestre AND i.carrera IN (1,3) AND i.carnet = $carnet";
        $inscripcion = & $db->getAll($consulta);
        if ($db->isError($inscripcion)) {
            $error = true;
            $mensaje = "Hubo un problema al verificar la inscripción en el ciclo actual";
            $url = "../menus/contenido.php";
        } else {

            if (count($inscripcion) == 0) {
                $aviso = true;
                $mensaje = "Usted no cuenta con registro de inscripción en pregrado para este semestre";
                $url = "../menus/contenido.php";
            }
        }

        if (!$error && !$aviso) {

            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('asignacion_etrabajador_gestion.html');


            if (count($sol_prev) == 0) {

                $template->parse("sin_solicitud_pendiente");
            } else {

                $template->setVariable(array(
                    'solicitud' => $sol_prev['id_solicitud'],
                ));

                $template->parse("sin_solicitud_pendiente");
            }

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
