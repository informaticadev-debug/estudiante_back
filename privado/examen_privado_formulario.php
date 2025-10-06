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
        errorLogin($mensaje);
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
            $url = $_SERVER[HTTP_REFERER];
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

                // Verificación de la aprobacion de los asesores del proyecto de graduación
                $consulta = "SELECT *
                FROM proyecto_graduacion p
                    INNER JOIN proyecto_graduacion_asesores a ON a.numero_tema = p.numero_tema
                WHERE p.estado = 5 AND p.carnet = $carnet AND a.aprobacion_proyecto = 1";
                $estado_aprobacion = & $db->getAll($consulta);
                if ($db->isError($estado_aprobacion)) {
                    $error = true;
                    $mensaje = "Hubo un problema al verificar el estado de aprobación de proyecto de graduación.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    // Verificar vencimiento de aprobacion de proyecto de graduación
                    $consulta = "SELECT DATE_FORMAT(g.fecha_vencimiento, '%Y%m%d') AS fecha_vencimiento, g.carrera, g.numero_tema
                    FROM proyecto_graduacion g
                    WHERE g.carnet = $carnet AND g.estado = 5";
                    $vencimiento = & $db->getRow($consulta);
                    if ($db->isError($vencimiento)) {
                        $error = true;
                        $mensaje = "Hubo un problema al verificar el vencimiento del proyecto de graduación";
                        $url = $_SERVER[HTTP_REFERER];
                    } else {

                        // Consulta de las solicitudes realizadas de examen privado.
                        $consulta = "SELECT e.carrera, IF(e.carrera <= 3,TRIM(c.nombre), TRIM(c.nombre_abreviado)) AS nombre_carrera,
                        TRIM(e.proyecto_graduacion) AS proyecto_graduacion, e.fecha_confirmacion, e.numero_recibo
                        FROM examen_privado e
                        INNER JOIN carrera c
                        ON c.carrera = e.carrera                                        
                        WHERE e.carnet = $carnet";
                        $solicitudes = & $db->getAll($consulta);
                        if ($db->isError($solicitudes)) {
                            $error = true;
                            $mensaje = "Hubo un error al consultar las solicitudes realizadas." . mysql_error();
                            $url = $_SERVER[HTTP_REFERER];
                        } else {

                            $fecha_actual = DATE('omd');

                            if ($vencimiento[fecha_vencimiento] < $fecha_actual && count($solicitudes) == 0) {
                                $error = true;
                                $mensaje = "El plazo para desarrollar su proyecto de graduación ha vencido, comunicarse con la coordinación del área de graduación";
                                $url = $_SERVER[HTTP_REFERER];
                            } else {

                                // Verificar aprobacion
                                if (count($estado_aprobacion) <> 3) {
                                    $error = true;
                                    if ($vencimiento[carrera] == 3) {
                                        //validar que los 2 asesores internos aprueben el proyecto para desplegar la carta de impresion de las 3 firmas..
                                        $conteo = 0;
                                        foreach ($estado_aprobacion as $ea) {
                                            if ($ea['asesor_externo'] == 0 && $ea['aprobacion_proyecto'] == 1) {
                                                $conteo++;
                                            }
                                        }
                                        if ($conteo == 2) {
                                            $mensaje = "Debe imprimir la carta para solicitar a cada asesor la firma de autorización, esta carta debe entregarla en recepción de Diseño Gráfico, esperar a que se confirme por dirección y proceder a solicitar examen privado.<br><br>
                                                        <input class='btn btn-primary' type='button' value='Imprimir carta' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_carta_aprobacion.php?numero_solicitud=$vencimiento[numero_tema]','contenido')\">";
                                        } else {
                                            $mensaje = "Debe imprimir la carta para solicitar a cada asesor la firma de autorización, esta carta debe entregarla en recepción de Diseño Gráfico, esperar a que se confirme por dirección y proceder a solicitar examen privado.<br><br>
                                                        <br />NOTA: La carta de solicitud aparecerá cuando los dos asesores internos aprueben, y confirmen que ha concluido correctamente, el proyecto de graduación en el sistema.
                                            ";
                                        }
                                        $url = $_SERVER[HTTP_REFERER];
                                    } else {
                                        $mensaje = "Todos los asesores deben aprobar previamente que el proyecto de graduación ha concluido correctamente. <input class='btn btn-primary btn-sm' type='button' value='Imprimir carta' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_carta_aprobacion.php?numero_solicitud=$vencimiento[numero_tema]','contenido')\">";
                                        $url = $_SERVER[HTTP_REFERER];
                                    }
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
										WHERE p.carnet = i.carnet AND p.carrera = i.carrera AND p.aprobado = 1
									) AND NOT EXISTS(
										SELECT *
										FROM examen_privado p
										WHERE p.carnet = i.carnet AND p.carrera = i.carrera AND p.acta_privado IS NULL
									)";
                                    $inscripciones = & $db->getAll($consulta);
                                    if ($db->isError($inscripciones)) {
                                        $error = true;
                                        $mensaje = "Hubo un error al verificar las carreras activas en este ciclo.";
                                        $url = $_SERVER[HTTP_REFERER];
                                    } else {

                                        // Datos del proyecto aprobado
                                        $consulta = "SELECT *
										FROM proyecto_graduacion g
										WHERE g.carnet = $carnet AND g.estado = 5";
                                        $proyecto = & $db->getRow($consulta);
                                        if ($db->isError($proyecto)) {
                                            $error = true;
                                            $mensaje = "Hubo un problema al obtener los datos del proyecto de graduación.";
                                            $url = $_SERVER[HTTP_REFERER];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$error) {
            // Cargar template de formulario para actualizacion
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('examen_privado_formulario.html');

            // Datos estudiante
            $template->setVariable(array(
                'carnet' => $estudiante[carnet],
                'nombre' => $estudiante[nombre],
                'numero_solicitud' => $estado_aprobacion[0]["numero_tema"],
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
                if ($proyecto['carrera'] <> 3) {
                    $template->setVariable(array(
                        'acuerdo_decanato' => $proyecto[acuerdo_decanato],
                        'proyecto_graduacion' => $proyecto[proyecto_graduacion]
                    ));
                    $template->parse('formulario_solicitud');
                } else {
                    $template->parse('formulario_solicitud_dg');
                }
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

                    if ($sol[numero_recibo] == NULL) {
                        $template->setVariable(array(
                            'pago_privado' => "<div id='sin_pagoConfirmado'>Pendiente de pago</div>"
                        ));
                    } else {
                        $template->setVariable(array(
                            'pago_privado' => "<div id='fechaDefinida'>Boleta de Pago " . $sol[numero_recibo] . "</div>"
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
            error($mensaje, $url);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>