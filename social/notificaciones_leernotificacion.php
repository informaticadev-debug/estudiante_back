<?php

/*
  Lectura de notificaciones
  -> Marcar como leida y todos los detalles de la notificacion.
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

        $notificacion = $_GET['notificacion'];

        // Consulta de la notificacion seleccionada
        $consulta = "SELECT if(exists(select * from docente d where d.registro_personal = n.usuario_creacion and not exists(select * from empleado where registro_personal = d.registro_personal and status = 'alta')),'Docente',t.unidad) AS remitente, n.notificacion
        FROM notificaciones n
        LEFT JOIN empleado e
		ON e.registro_personal = n.usuario_creacion
        LEFT JOIN rol t
        ON t.rol = e.rol
        WHERE n.nro = $notificacion";
        $mostrar_notificacion = & $db->getRow($consulta);
        if ($db->isError($mostrar_notificacion)) {
            $error = true;
            $mensaje = "Hubo un error durante la consulta de la notificación seleccionada.";
        } else {

            // Actualizar estado de la notificacion a leida
            $consulta = "UPDATE notificaciones n
            SET n.leida = 1
            WHERE n.nro = $notificacion";
            $estado_notificacion = & $db->Query($consulta);
            if ($db->isError($estado_notificacion)) {
                $error = true;
                $mensaje = "Hubo un problema durante el cambio de estado de esta notificación.";
            }
        }

        if (!$error) {

            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('notificaciones_leernotificacion.html');

            if (strpos($mostrar_notificacion[notificacion], "youtube")) {
                $notificacion = texto_formateado($mostrar_notificacion[notificacion]);
            } else {
                $notificacion = texto_formateado($mostrar_notificacion[notificacion]);
            }

            $template->setVariable(array(
                'remitente' => $mostrar_notificacion[remitente],
                'notificacion' => $notificacion
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