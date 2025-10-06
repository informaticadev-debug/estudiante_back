<?php

/*
  Proceso de Asignacion para Interciclos
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
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

        // Datos de la session actual		
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carnet = $_SESSION['usuario'];
        $carrera = inscripcion_estudiante($db, $anio, $semestre, $carnet);
        if($carrera == null) $carrera = 1000;
        
        // Consultar la notificacion seleccionada
        $consulta = "SELECT LEFT(n.notificacion,40) AS notificacion_abrev,	
        n.fecha_creacion, n.leida, n.nro
        FROM notificaciones n
        WHERE n.destinatario = $carnet
        ORDER BY n.leida, n.fecha_creacion DESC";
        $notificaciones = & $db->getAll($consulta);
        if ($db->isError($notificaciones)) {
            $error = true;
            $mensaje = "Hubo un error al consultar las notificaciones";
        } else {

            // Remitente de las notificaciones
            $consulta_generales = "SELECT *
            FROM destinatarios_notificacion n
			WHERE n.carrera IS NULL and rol <> 13";

            $consulta_porcarrera = "SELECT *
            FROM destinatarios_notificacion n
			WHERE n.carrera = $carrera and rol <> 13";

            $union_consultas = $consulta_generales . " UNION ALL " . $consulta_porcarrera;
            $destinatario_notificacion = & $db->getAll($union_consultas);
            if ($db->isError($destinatario_notificacion)) {
                $error = true;
                $mensaje = "Hubo un error durante la consulta de los destinatarios para hacer consultas.";
            }
        }

        if (!$error) {

            // Cargar la pagina para mostrar la notificacion seleccionada.
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('notificaciones.html');

            foreach ($notificaciones AS $no) {

                if ($no[leida] == 0) {
                    $template->setVariable(array(
                        'leida' => "#FFFFFF"
                    ));
                } else {
                    $template->setVariable(array(
                        'leida' => "#F2F2F2"
                    ));
                }


                $template->setVariable(array(
                    'notificacion' => $no[nro],
                    'fecha' => $no[fecha_creacion],
                    'notificacion_abrev' => $no[notificacion_abrev]
                ));
                $template->parse('notificaciones');
            }

            foreach ($destinatario_notificacion AS $ti) {
                $template->setvariable(array(
                    'destinatario_notificacion' => $ti[rol],
                    'destinatario' => $ti[nombre]
                ));
                $template->parse('destinatarios_consultas');
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

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
