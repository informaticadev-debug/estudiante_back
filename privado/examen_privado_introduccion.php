<?php

/*
  EXAMEN PRIVADO
  -> Proceso para obtener examen privado
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
        $error = false;

        $carnet = $_SESSION['usuario'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];

        $inscripcion = verificarInscripcion($db, $anio, $semestre, $carnet);

        if ($inscripcion == 0) {
            $error = true;
            $mensaje = "Debe estar inscrito en el ciclo actual para poder solicitar Exámen Privado.";
        } else {

            // Datos del estudiante
            $consulta = "SELECT *
			FROM estudiante e
			WHERE e.carnet = $carnet";
            $estudiante = & $db->getRow($consulta);
            if ($db->isError($estudiante)) {
                $error = true;
                $mensaje = "Hubo un error al obtener los datos del estudiante." . $consulta;
            } else {

                // Inscripciones en las que se puede solicitar examen privado
                $consulta = "SELECT i.carrera AS cod_carrera, IF(i.carrera <= 3,TRIM(c.nombre), TRIM(c.nombre_abreviado)) AS carrera
				FROM inscripcion i
				INNER JOIN carrera c
				ON c.carrera = i.carrera
				WHERE i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet
				AND NOT EXISTS(
					SELECT *
					FROM examen_privado p
					WHERE p.carnet = i.carnet AND p.carrera = i.carrera
				)";
                $inscripciones = & $db->getAll($consulta);
                if ($db->isError($inscripciones)) {
                    $error = true;
                    $mensaje = "Hubo un error al verificar las carreras activas en este ciclo.";
                } else {

                    // Consulta de las solicitudes realizadas de examen privado.
                    $consulta = "SELECT e.carrera, IF(e.carrera <= 3,TRIM(c.nombre), TRIM(c.nombre_abreviado)) AS nombre_carrera,
					TRIM(e.proyecto_graduacion) AS proyecto_graduacion, e.fecha_confirmacion, o.no_boleta_deposito
					FROM examen_privado e
					INNER JOIN carrera c
					ON c.carrera = e.carrera
					LEFT OUTER JOIN bitacora_orden_pago b
					ON b.carnet = e.carnet AND b.rubro = 9 AND b.variante_rubro = 1
					LEFT OUTER JOIN orden_pago o
					ON o.carnet = e.carnet AND o.carrera = e.carrera AND o.no_boleta_deposito IS NOT NULL
					AND o.orden_pago = b.orden_pago
					WHERE e.carnet = $carnet";
                    $solicitudes = & $db->getAll($consulta);
                    if ($db->isError($solicitudes)) {
                        $error = true;
                        $mensaje = "Hubo un error al consultar las solicitudes realizadas." . mysql_error();
                    }
                }
            }
        }

        if (!$error) {

            // Cargar template de formulario para actualizacion
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('examen_privado_introduccion.html');

            // Datos estudiante
            $template->setVariable(array(
                'carnet' => $estudiante[carnet],
                'nombre' => $estudiante[nombre]
            ));

            foreach ($inscripciones AS $in) {
                $template->setVariable(array(
                    'cod_carrera' => $in[cod_carrera],
                    'carrera' => $in[carrera]
                ));
                $template->parse('inscripcion_estudiante');
            }
            $template->parse('datos_estudiante');

            if (count($inscripciones) <> 0) {
                $template->setVariable(array(
                    'datos_requeridos' => "Ingrese los datos requeridos"
                ));
                $template->parse('formulario_solicitud');
            }

            if (count($solicitudes) <> 0) {

                if (count($inscripciones) <> 0) {
                    $template->setVariable(array(
                        'color_tabla' => "#F2F2F2"
                    ));
                } else {
                    $template->setVariable(array(
                        'color_tabla' => "#FFFFFF"
                    ));
                }

                // Solicitudes Realizadas.
                foreach ($solicitudes AS $sol) {

                    $template->setVariable(array(
                        'carrera' => $sol[carrera],
                        'nombre_carrera' => $sol[nombre_carrera],
                        'proyecto_graduacion' => $sol[proyecto_graduacion],
                    ));

                    if ($sol[fecha_confirmacion] == NULL) {
                        $template->setVariable(array(
                            'fecha_privado' => "<div id='sin_fechaDefinida'>No se ha definido fecha de Examen</div>"
                        ));
                    } else {
                        $template->setVariable(array(
                            'fecha_privado' => "<div id='fechaDefinida'>Fecha de Exámen " . $sol[fecha_confirmacion] . "</div>"
                        ));
                    }

                    if ($sol[no_boleta_deposito] == NULL) {
                        $template->setVariable(array(
                            'pago_privado' => "<div id='sin_pagoConfirmado'>Pendiente de pago</div>"
                        ));
                    } else {
                        $template->setVariable(array(
                            'pago_privado' => "<div id='fechaDefinida'>Boleta de Pago " . $sol[no_boleta_deposito] . "</div>"
                        ));
                    }
                    $template->parse('solicitudes_privado');
                }

                $template->parse('solicitudes_disponibles');
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
            mostrarError($mensaje);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>