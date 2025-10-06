<?php

/*
  Proceso de Asignacion para Interciclos
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

function getTuplaEstatus($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigoAsignar) {
    //SE AGREGO HDD '1.04.4','1.03.4' 20250530
	    $consulta = "
                SELECT a.carnet, COUNT(*) AS cantidad_asignados, SUM(IF(codigo IN ('1.01.2', '1.01.3', '1.01.4', '1.02.2', '1.02.3', '1.02.4', '1.03.2', '1.04.2', '30111', '30112', '30212', '30312', '30412', '30413', '30512', '30642'), 1, 0)) AS cantidad_practicos
                FROM asignacion a
                WHERE a.anio = $anio and a.semestre = $semestre and a.extension = $extension and a.evaluacion = $evaluacion and a.carnet = $carnet
                GROUP BY a.carnet
            ";
    $estado = & $db->getRow($consulta);
    if ($db->isError($cursos)) {
        $error = true;
        $mensaje = "Hubo un error al consultar los cursos asignados en este interciclo.";
        $url = "../menus/contenido.php";
        return false;
    }
    return $estado;
}

function verificar2CursosPracticos($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigoAsignar) {
    $consulta = "
                SELECT a.codigo, a.seccion
                FROM asignacion a
                WHERE a.anio = $anio and a.semestre = $semestre and a.extension = $extension and a.evaluacion = $evaluacion and a.carnet = $carnet
            ";
    $cursos = & $db->getAll($consulta);
    if ($db->isError($cursos)) {
        $error = true;
        $mensaje = "Hubo un error al consultar los cursos asignados en este interciclo.";
        $url = "../menus/contenido.php";
        return false;
    }
    if (count($cursos) > 0) {
        $codigoAsignado = $cursos[0]['codigo'];
        $array_practicos = ['1.01.2', '1.01.3', '1.01.4', '1.02.2', '1.02.3', '1.02.4', '1.03.2', '1.04.2', '30111', '30112', '30212', '30312', '30412', '30413', '30512', '30642'];
        return (in_array($codigoAsignado, $array_practicos) && in_array($codigoAsignar, $array_practicos));
    }
    return false;
}

function getSolicitudRealizada($db, $carnet, $anio, $semestre) {
    //return [];
    $consulta = "
                SELECT c.codigo, c.nombre
                FROM asignacion_solicitud a
                    INNER JOIN curso c ON c.pensum = a.pensum and c.codigo = a.codigo
                WHERE a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = 2 AND a.carnet = $carnet AND false
            ";
    $solicitud_previa = & $db->getAll($consulta);
    if ($db->isError($solicitud_previa)) {
        return false;
    } else {
        return $solicitud_previa;
    }
}

function verificarCursoAsignado($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo) {
    $consulta = "
                SELECT a.codigo, a.seccion
                FROM asignacion a
                WHERE a.anio = $anio and a.semestre = $semestre and a.extension = $extension and a.evaluacion = $evaluacion and a.carnet = $carnet AND codigo = '$codigo'
            ";
    $cursos = & $db->getAll($consulta);
    if ($db->isError($cursos)) {
        $error = true;
        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
        $url = "../menus/contenido.php";
        return false;
    }
    if (count($cursos) > 0) {
        return $cursos[0];
    }
    return false;
}

function obtenerCargaCurso($db, $anio, $semestre, $extension, $evaluacion, $curso) {
    $codigo = $curso['codigo'];
    $seccion = $curso['seccion'];
    $consulta = "
                SELECT s.cupo, count(a.carnet) as asignados
                FROM seccion s
                    LEFT JOIN asignacion a on s.anio = a.anio and s.extension = a.extension and s.semestre = a.semestre and s.evaluacion = a.evaluacion 
                    and s.pensum = a.pensum and s.codigo = a.codigo and s.seccion = a.seccion
                WHERE s.anio = $anio and s.semestre = $semestre and s.extension = $extension and s.evaluacion = $evaluacion and s.codigo = '$codigo' and s.seccion = '$seccion'
            ";
    $cupo = & $db->getAll($consulta);
    if ($db->isError($cupo)) {
        $error = true;
        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
        $url = "../menus/contenido.php";
        return false;
    }
    return $cupo[0];
}

function desasignar($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo) {
    $consulta = "
                DELETE FROM asignacion
                WHERE anio = $anio and carnet = $carnet and semestre = $semestre and extension = $extension and evaluacion = $evaluacion and codigo = '$codigo'
            ";
    guardarEnBitacoraAsignacionEliminar($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo);
    $result = & $db->query($consulta);
    if ($db->isError($result)) {
        return false;
    }
    return true;
}

function guardarEnBitacoraAsignacionEliminar($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo) {
    try {
	    $observacion = "'Eliminación de asignación'";
	    $dataIP = json_encode(["REMOTE_ADDR" => $_SERVER['REMOTE_ADDR'], "HTTP_X_FORWARDED_FOR" => $_SERVER['HTTP_X_FORWARDED_FOR'], "HTTP_CLIENT_IP" => $_SERVER['HTTP_CLIENT_IP']]);

        $consulta = "INSERT INTO `bitacora_asignaciones`
            (`fecha_bitacora`,`extension`,`anio`,`semestre`,`evaluacion`,`pensum`,`codigo`,`seccion`,
             `carnet`,`id_asignacion`,`preasignacion`,`status`,`fecha_asignacion`,`observacion`,`usuario_asignacion`,`orden_pago`, data_request)
            (
                SELECT NOW(), a.extension, a.anio, a.semestre, a.evaluacion, a.pensum, a.codigo, a.seccion,
                        a.carnet, a.id_asignacion, a.preasignacion, a.status, a.fecha_asignacion, $observacion,a.usuario_asignacion,a.orden_pago, '$dataIP' as data_request
                FROM asignacion a
                WHERE
                    anio = $anio and carnet = $carnet and semestre = $semestre and extension = $extension and evaluacion = $evaluacion and codigo = '$codigo'
            )";
        //var_dump($consulta); die;
        $result = & $db->query($consulta);
        if ($db->isError($result)) {
            return false;
        }
    } finally {
        
    }
    return true;
}

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
        $aviso = false;

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_SESSION['evaluacion'];
        $carnet = $_SESSION['usuario'];

        // Asignaciones permitidas en Interciclos
        $asignaciones_permitidas = 3;

        // Datos de la asignatura seleccionada
        $pensum = $_POST['pensum'];
        $codigo = $_POST['codigo'];
        $seccion = $_POST['seccion'];

        if (!isset($_SESSION[pensum])) {

            $_SESSION['pensum'] = $_POST['pensum'];
            $_SESSION['codigo'] = $_POST['codigo'];
            $_SESSION['seccion'] = $_POST['seccion'];
        } else {
            $pensum = $_SESSION['pensum'];
            $codigo = $_SESSION['codigo'];
            $seccion = $_SESSION['seccion'];
        }

        // Verificacion de cupo lleno para mostrar mensaje.
        if ($seccion == '--') {
            $error = true;
            $mensaje = "El cupo en esta Asignatura esta lleno.";
            $url = $_SERVER[HTTP_REFERER];
        }

        //verificar desasignacion
        if ($seccion == 'no_asignar') {
            $respuesta = desasignar($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo);
            if ($respuesta) {
                $aviso = true;
                $mensaje = "Se ha eliminado la asignación con exito!";
                $url = $_SERVER[HTTP_REFERER];
            } else {
                $error = true;
                $mensaje = "Ha ocurrido un error durante la desasignación!";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

        /**
         * verificando cambio de seccion
         */
        $complemento_query_traslape = '';
        $curso_ya_asignado = verificarCursoAsignado($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo);
        if ($curso_ya_asignado) {
            if ($curso_ya_asignado[seccion] == $seccion) {
                $aviso = true;
                $mensaje = "No realizado un cambio en su asignación.";
                $url = $_SERVER[HTTP_REFERER];
            }
            $asignaciones_permitidas++;
            //complemento del query posterior ya que no tiene que hacer traslape con el mismo curso...
            $complemento_query_traslape = " AND a.codigo <> '$codigo' ";
        }

        /*
         * Verificar que no se intenten asignar 2 cursos practicos
         */
        if (verificar2CursosPracticos($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo)) {
            $aviso = true;
            $mensaje = "Usted no puede inscribirse en dos asignaturas prácticas. </br></br><b>Normativo del PAI: Artículo 4</b><br /><a target='_blank' href='http://estudiante.arquitectura.usac.edu.gt/docs/pai/Normativo_PAI.pdf'>Ver Normativo</a>";
            $url = $_SERVER[HTTP_REFERER];
	}
	 

	$tuplaEstado = getTuplaEstatus($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo);

	if ($tuplaEstado["cantidad_asignados"] > 1 && $tuplaEstado["cantidad_practicos"] > 0) {
		$aviso = true;
		$mensaje = "Usted cuenta con un curso práctico asignado, no puede inscribirse a más de dos cursos.";
		$url = $_SERVER[HTTP_REFERER];
	}

	if ($tuplaEstado["cantidad_asignados"] > 1 && in_array($codigo, ['1.01.2', '1.01.3', '1.01.4', '1.02.2', '1.02.3', '1.02.4', '1.03.2', '1.04.2','1.04.4','1.03.4', '30111', '30112', '30212', '30312', '30412', '30413', '30512', '30642'])) {
            $aviso = true;
            $mensaje = "Usted no puede asignarse a Z cursos si incluye un práctico.";
            $url = $_SERVER[HTTP_REFERER];
        }

        // Verificar asignaciones en el ciclo
        $consulta = "SELECT COUNT(*) AS asignaciones
		FROM asignacion a
		WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion
		AND a.carnet = $carnet";
        $asignaciones = & $db->getRow($consulta);
        if ($db->isError($asignaciones)) {
            $error = true;
            $mensaje = "Hubo un error al verificar las asignaciones en este ciclo.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Asignacion Seleccionada..
            $consulta = "SELECT s.pensum, s.codigo, TRIM(cu.nombre) AS asignatura, s.seccion, c.jornada, c.periodo, c.carrera, c.hora_ini, c.hora_fin,
			IF (
				EXISTS(
					SELECT *
					FROM asignacion a
					INNER JOIN horario h2
					ON h2.extension = a.extension AND h2.anio = a.anio AND h2.semestre = a.semestre
					AND h2.evaluacion = a.evaluacion AND h2.pensum = a.pensum AND h2.codigo = a.codigo
					AND h2.seccion = a.seccion
					INNER JOIN pensum p2
					ON p2.pensum = a.pensum
					INNER JOIN periodo_ciclo c2
					ON c2.extension = a.extension AND c2.anio = a.anio AND c2.semestre = a.semestre
					AND c2.evaluacion = a.evaluacion AND c2.periodo = h2.periodo
					AND c2.carrera = p2.carrera
					WHERE a.extension = s.extension AND a.anio = s.anio AND a.semestre = s.semestre $complemento_query_traslape
					AND a.evaluacion = s.evaluacion AND a.pensum = s.pensum AND a.carnet = $carnet					
					AND STR_TO_DATE(c2.hora_ini,'%H:%i') < STR_TO_DATE(c.hora_fin,'%H:%i') AND
					STR_TO_DATE(c2.hora_fin,'%H:%i') > STR_TO_DATE(c.hora_ini,'%H:%i')
				), 'Traslape', 'Asignacion sin problemas'
			) AS observacion
			FROM seccion s
			INNER JOIN horario h
			ON h.extension = s.extension AND h.anio = s.anio AND h.semestre = s.semestre
			AND h.evaluacion = s.evaluacion AND h.pensum = s.pensum AND h.codigo = s.codigo
			AND h.seccion = s.seccion
			INNER JOIN pensum p
			ON p.pensum = s.pensum
			INNER JOIN periodo_ciclo c
			ON c.extension = s.extension AND c.anio = s.anio AND c.semestre = s.semestre
			AND c.evaluacion = s.evaluacion AND c.periodo = h.periodo AND c.carrera = p.carrera
			INNER JOIN curso cu
			ON cu.codigo = s.codigo AND cu.pensum = s.pensum			
			WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
			AND s.pensum = $pensum AND s.codigo = '$codigo' AND s.seccion = '$seccion'";
            //var_dump($consulta); die;
            $seleccion = & $db->getRow($consulta);
            if ($db->isError($seleccion)) {
                $error = true;
                $mensaje = "Hubo un error al verificar los datos de la asignatura seleccionada.";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

        /* // Asignaciones ya realizadas en este ciclo.
          $consulta = "SELECT a.codigo, a.seccion, TRIM(c.nombre) AS asignatura, a.preasignacion, a.orden_pago, a.seccion
          FROM asignacion a
          INNER JOIN curso c
          ON c.codigo = a.codigo AND c.pensum = a.pensum
          WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion
          AND a.carnet = $carnet";
          $asignados = & $db->getAll($consulta);
          if ($db->isError($asignados)) {
          $error = true;
          $mensaje = "Hubo un error en la verificacion de la asignatura seleccionada.";
          $url = $_SERVER[HTTP_REFERER];
          } */

        if (!$error && !$aviso) {
            //si ya realizo la solicitud de apertura de cursos, puede asignarse solamente 1 curso...
            $solicitud_anterior = getSolicitudRealizada($db, $carnet, $anio, $semestre);
            if (count($solicitud_anterior) > 0) {
                $asignaciones_permitidas--;
            }

            // Verificacion de que aun no se haya llegado a las asignaciones permitidas.
            if ($asignaciones[asignaciones] < $asignaciones_permitidas) {

                // Cargando la pagina de verificacion de la asignatura seleccionada.
                $template = new HTML_Template_Sigma('../templates');
                $template->loadTemplateFile('asignacion_interciclos_verificacion.html');

                if (!empty($asignados)) {

                    // Cursos Asignados.
                    foreach ($asignados AS $as) {

                        // Ordenes de pago
                        $consulta = "SELECT o.monto_total
							FROM orden_pago o
							WHERE o.anio = $anio AND o.semestre = $semestre AND o.evaluacion = $evaluacion
							AND o.orden_pago = '$as[orden_pago]' AND o.carnet = $carnet";
                        $orden_pago = & $db->getRow($consulta);
                        if ($db->isError($orden_pago)) {
                            $error = true;
                            $mensaje = "Hubo un error al comprobar el monto a pagar para las Asignaciones o Pre-Asignaciones realizadas.";
                            $url = $_SERVER[HTTP_REFERER];
                        } else {

                            // Horarios de las Asignaciones o Pre-Asignaciones realizadas.
                            $consulta = "SELECT d.nombre_abreviado AS dia, p.hora_ini, p.hora_fin
								FROM seccion s
								INNER JOIN horario h
								ON h.extension = s.extension AND h.anio = s.anio AND h.semestre = s.semestre
								AND h.evaluacion = s.evaluacion AND h.pensum = s.pensum AND h.codigo = s.codigo 
								AND h.seccion = s.seccion
								INNER JOIN periodo_ciclo p
								ON p.extension = s.extension AND p.anio = s.anio AND p.semestre = s.semestre
								AND p.evaluacion = s.evaluacion AND p.periodo = h.periodo
								INNER JOIN pensum pe
								ON pe.carrera = p.carrera AND pe.pensum = s.pensum
								INNER JOIN dia d
								ON d.dia = h.dia		
								WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
								AND s.pensum = $as[pensum] AND s.codigo = '$as[codigo]' AND s.seccion = '$as[seccion]'";
                            $horario = & $db->getAll($consulta);
                            if ($db->isError($horario)) {
                                $error = true;
                                $mensaje = "Hubo un error al verificar los horarios.";
                                $url = $_SERVER[HTTP_REFERER];
                            }
                        }

                        if ($as[preasignacion] == 1) {

                            $template->setVariable(array(
                                'codigo' => $as[codigo],
                                'asignatura' => $as[asignatura],
                                'seccion' => $as[seccion]
                            ));

                            // Escribiendo los horarios de las asignaciones
                            foreach ($horario AS $ho) {

                                $template->setVariable(array(
                                    'dia' => $ho[dia],
                                    'inicio' => $ho[hora_ini],
                                    'fin' => $ho[hora_fin]
                                ));
                                $template->parse('horarios');
                            }

                            $template->parse('cursos_asignados');
                        } else {

                            $template->setVariable(array(
                                'codigo' => $as[codigo],
                                'asignatura' => $as[asignatura],
                                'seccion' => $as[seccion]
                            ));

                            // Escribiendo los horarios de las asignaciones
                            foreach ($horario AS $ho) {

                                $template->setVariable(array(
                                    'dia' => $ho[dia],
                                    'inicio' => $ho[hora_ini],
                                    'fin' => $ho[hora_fin]
                                ));
                                $template->parse('horarios');
                            }

                            $template->parse('cursos_asignados');
                        }

                        if ($error) {
                            mostrarError($mensaje);
                        }
                    }
                } else {

                    $template->setVariable(array(
                        'sin_asignaciones_disponibles' => "<div class='alert alert-danger'>Aun no te has Asignado o Pre-Asignado ningun curso.</div>"
                    ));
                }

                // Asignatura seleccionada.
                $template->setVariable(array(
                    'pensum' => $seleccion[pensum],
                    'codigo' => $seleccion[codigo],
                    'asignatura' => $seleccion[asignatura],
                    'seccion' => $seleccion[seccion],
                    'inicio' => $seleccion[hora_ini],
                    'fin' => $seleccion[hora_fin],
                ));


                // Calculo del costo de la Asignatura con o sin Inscripcion.
                $costo_curso = costo_curso($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion);
                $costo_inscripcion = 0; //costo_inscripcion($db, $extension, $anio, $semestre, $evaluacion, $carnet);
                $costo_total = number_format($costo_curso + $costo_inscripcion, 2);

                if ($seleccion[observacion] == 'Traslape') {

                    $template->setVariable(array(
                        'observacion' => "alert-danger",
                        'btn_siguiente' => "<input class='brt btn-default' type='button' value='Asignar'>",
                        'costo_total_asignatura' => $costo_total
                    ));
                } else {

                    $template->setVariable(array(
                        'observacion' => "alert-success",
                        'btn_siguiente' => "
						<script>
							function confirmar(){
								var confirmacion = confirm('¿Esta seguro que desea generar la Orden de Pago por un total de Q. $costo_total, para la Asignatura: $seleccion[asignatura]?');
								if (confirmacion){
                                    window.open('../asignacion/asignacion_interciclos_ordenpago.php','contenido');
								}
							}
						</script>
						<input class='btn btn-primary' type='button' value='Asignar' OnClick='confirmar();'>",
                        'costo_total_asignatura' => $costo_total
                    ));
                }

                $template->parse('cursos_seleccionados');

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
            } else {
                $error = true;
                $mensaje = "No está permitida la asignación que desa realizar, consulte el normativo. </br></br><b>Normativo del PAI: Artículo 5</b><br /><a target='_blank' href='http://estudiante.arquitectura.usac.edu.gt/docs/pai/Normativo_PAI.pdf'>Ver Normativo</a>";
                $url = $_SERVER[HTTP_REFERER];
            }
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
