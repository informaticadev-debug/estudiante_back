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
if (isset($_SESSION[usuario])) {

    $errorLogin = false;
    $error = false;
    $aviso = false;

    //habilita opciones y modulos del proceso en curso..
    $proceso = 1;

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

        $extension = $_SESSION['extension'];
        $carnet = $_SESSION['usuario'];
        $anio = DATE("o");
        $semestre = $_SESSION['semestre'];
        $evaluacion = 1;

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
            } else {

                // Verificar la existencia de solicitudes previas
                $consulta = "SELECT c.codigo, TRIM(c.nombre) AS asignatura
                FROM asignacion_extemporanea_solicitudes s
                LEFT JOIN asignacion_extemporanea_solicitudes_detalles d
                ON d.solicitud = s.solicitud
                LEFT JOIN curso c 
                ON c.codigo = d.codigo AND c.pensum = d.pensum
                WHERE s.carnet = $carnet AND s.estado <= 2 AND s.semestre = $semestre AND s.evaluacion = $evaluacion AND s.anio = $anio
                GROUP BY c.codigo";
                $sol_prev = & $db->getAll($consulta);
                if ($db->isError($sol_prev)) {
                    $error = true;
                    $mensaje = "Hubo un problema al verificar si existen solicitudes previas.";
                    $url = "../menus/contenido.php";
                } else {

                    if (count($sol_prev) == 0) {

                        // Motivos por los cuales no se asigno
                        $consulta = "SELECT *
                        FROM asignacion_extemporanea_motivos m
                        WHERE m.proceso = $proceso
                        ORDER BY m.motivo DESC
                        ";
                        $motivos = & $db->getAll($consulta);
                        if ($db->isError($motivos)) {
                            $error = true;
                            $mensaje = "Hubo un problema al obtener el listado de motivos por los que no pudo asignarse.";
                            $url = "../menus/contenido.php";
                        } else {

                            // Asignaciones previas
                            $consulta = "SELECT a.codigo, TRIM(c.nombre) AS asignatura, a.seccion
                            FROM asignacion a
                            INNER JOIN curso c
                            ON c.pensum = a.pensum AND c.codigo = a.codigo
                            WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion
                            AND a.carnet = $carnet";
                            $asignaciones = & $db->getAll($consulta);
                            if ($db->isError($asignaciones)) {
                                $error = true;
                                $mensaje = "Hubo un problema al obtener el listado de asignaciones previas.";
                                $url = "../menus/contenido.php";
                            }
                        }
                    } else {
                        $aviso = true;
                        $mensaje = "Ya realizó una solicitud anteriormente.";

                        /*$i = 1;

                        foreach ($sol_prev AS $sp) {
                            $mensaje .= $i . ". " . $sp['codigo'] . " - " . $sp['asignatura'] . "<br>";
                            $i++;
                        }*/

                        $url = "../menus/contenido.php";
                    }
                }
            }
        }

        if (!$error && !$aviso) {

            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('asignacion_extemporanea_gestion.html');

            $template->setVariable(array(
                'asignaciones_previas' => count($asignaciones)
            ));

            if (count($sol_prev) == 0) {

                foreach ($inscripcion AS $in) {

                    $template->setVariable(array(
                        'carrera' => $in['carrera'],
                        'nombre_carrera' => $in['nombre'],
                    ));
                    $template->parse("listado_carreras");
                }

                foreach ($motivos AS $mo) {

                    $template->setVariable(array(
                        'motivo' => $mo['motivo'],
                        'nombre_motivo' => $mo['nombre']
                    ));
                    $template->parse("listado_motivos_asignacion_extemporanea");
                }

                foreach ($asignaciones AS $as) {

                    $template->setVariable(array(
                        'codigo' => $as['codigo'],
                        'asignatura' => $as['asignatura'],
                        'seccion' => $as['seccion']
                    ));
                    $template->parse("listado_asignadas");
                }

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
