<?php

/*
  Proceso de Asignacion para Interciclos
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 * 
 * pruebas 9317930
 * 
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../config/local.php';

$permitir_ingreso = '';

function getSolicitudRealizada($db, $carnet, $anio, $semestre, $evaluacion) {
    $consulta = "
                SELECT c.codigo, c.nombre
                FROM asignacion_solicitud a
                    INNER JOIN curso c ON c.pensum = a.pensum and c.codigo = a.codigo
                WHERE a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion AND a.carnet = $carnet
            ";
    $solicitud_previa = & $db->getAll($consulta);
    if ($db->isError($solicitud_previa)) {
        return false;
    } else {
        return $solicitud_previa;
    }
}

function getAsignaciones($db, $carnet, $anio, $semestre, $evaluacion) {
    $consulta = "
                SELECT a.pensum, a.codigo
                FROM asignacion a
                WHERE a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion AND a.carnet = $carnet AND false
            ";
    $asignaciones = & $db->getAll($consulta);
    if ($db->isError($asignaciones)) {
        return false;
    } else {
        return $asignaciones;
    }
}

function obtenerCursosASolicitar($db, $carnet, $carrera, $anio, $semestre, $extension, $evaluacion) {
    $consulta = "
                SELECT ao.pensum, ao.codigo, c.nombre
                FROM asignacion_oferta ao
                   INNER JOIN curso c ON c.pensum = ao.pensum AND c.codigo = ao.codigo
                   INNER JOIN pensum p on p.pensum = c.pensum
                WHERE ao.anio = $anio AND ao.semestre = $semestre AND ao.evaluacion = $evaluacion AND p.carrera = $carrera
                    AND (ao.pensum, ao.codigo) NOT IN (
                        SELECT pensum, codigo
                        FROM nota
                        WHERE carnet = $carnet AND pensum = ao.pensum AND codigo = ao.codigo
		    ) -- verificar que no la tenga asignada
		    AND NOT EXISTS (select 1 from asignacion where anio = $anio and semestre = $semestre and evaluacion = $evaluacion and codigo = ao.codigo)
	";
    $cursos = & $db->getAll($consulta);
    $cursosAHabilitar = array();
    foreach ($cursos as $curso) {
        $data = [
            "auth" => [
                "user" => "arqws",
                "passwd" => "a08!¡+¿s821!kdui23#kd$"
            ],
            "id" => $carnet,
            "fecha" => '2019-06-30',
            "pensum" => $curso['pensum'],
            "codigo" => $curso['codigo']
        ];
        //consumiendo el servicio Rest de Inscripciones
        $respuesta = postRequest('http://arquitectura.usac.edu.gt/rest/Prerrequisitos', $data);
        //var_dump($curso['codigo'] . ":::" . $respuesta); die;
        $array_resp = json_decode($respuesta);
        if ($array_resp->validacion == true) {
            $cursosAHabilitar[] = $curso;
        } else {
            if ($curso['codigo'] == '30112') {
                echo("<!--  AQUIIII");
                /* echo($curso['codigo'] . ":::" . $respuesta); 
                  echo($respuesta); */
                echo(" -->");
            }
        }
    }
    return $cursosAHabilitar;
}

function obtenerCursosAsignados($db, $anio, $semestre, $extension, $evaluacion, $carnet) {
    $consulta = "
                SELECT a.carnet, a.codigo, a.seccion
                FROM asignacion a
                WHERE a.anio = $anio and a.semestre = $semestre and a.extension = $extension and a.evaluacion = $evaluacion and a.carnet = $carnet
            ";
    $cursos = & $db->getAll($consulta);
    if ($db->isError($cursos)) {
        $error = true;
        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
        $url = "../menus/contenido.php";
        return false;
    }
    $curso_array = array();
    foreach ($cursos as $curso) {
        $curso_array[$curso['codigo']] = $curso['seccion'];
    }
    return $curso_array;
}

function obtenerInscripciones($db, $extension, $anio, $semestre, $carnet) {
    $consulta = "SELECT i.carnet, IF (
			EXISTS(
				SELECT i2.carrera 
				FROM inscripcion i2
				WHERE i2.extension = i.`extension` AND i2.anio = i.`anio` AND i2.semestre = i.`semestre` AND i2.carnet = i.`carnet` AND i2.carrera > 3
			),
				(
					SELECT i3.carrera 
					FROM inscripcion i3
					WHERE i3.extension = i.`extension` AND i3.anio = i.`anio` AND i3.semestre = i.`semestre` AND i3.carnet = i.`carnet`
					AND i3.carrera > 3
				)
			,i.carrera
		) AS carrera
        FROM inscripcion i
        WHERE i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet
        GROUP BY i.carrera";
    $inscripcion = & $db->getAll($consulta);
    return $inscripcion;
}

session_start();
if (isset($_SESSION[usuario])) {

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

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_GET['evaluacion'];
        $carnet = $_SESSION['usuario'];

        // Verificacion de la inscripcion del estudiante en el ciclo actual
        $inscripcion = obtenerInscripciones($db, $extension, $anio, $semestre, $carnet);
        if ($db->isError($inscripcion)) {
            $error = true;
            $mensaje = "Hubo un error al determinar tu inscripcion en el ciclo actual.";
            $url = "../menus/contenido.php";
        } else {

            //variable de control para verificar la cantidad de cursos en repitencia...
            $cursos_repitencia = [];
            //verificar repitencia general...
            $data = array(
                "auth" => array(
                    "user" => "arqws",
                    "passwd" => "a08!¡+¿s821!kdui23#kd$"
                ),
                'id' => $carnet,
            );
            $dataRepitencia = json_decode(postRequest($api_uri . 'Repitencia', $data), true);

            // Verificacion de la existencia de repitencia del estudiante
            foreach ($inscripcion AS $in) {
                foreach ($dataRepitencia as $pensum => $cursos) {
                    //verificar que sea un pensum actual valido
                    if (($pensum == 5 && $in["carrera"] == 1) || ($pensum == 20 && $in["carrera"] == 3)) {
                        foreach ($cursos as $codigo => $cursoInfo) {
                            //verificar que el curso aun no se haya aprobado y que no haya entrado en repitencia en el semestre actual...
                            if ($cursoInfo["resultado"]["aprobado"] == 0 && $cursoInfo["resultado"]["ciclo_repitencia"] != "$anio-$semestre") {
                                $cursos_repitencia[$codigo] = $cursoInfo;
                            }
                        }
                    }
                }
            }

            if (!empty($cursos_repitencia)) {
                $error = true;
                $mensaje = "Lo sentimos pero usted no puede asignarse por tener repitencia en los siguientes cursos: <br /><br />";
                foreach ($cursos_repitencia as $codigo => $cursoInfo) {
                    $mensaje .= "<b>" . $codigo . " - " . $cursoInfo["nombre"] . "</b><br />";
                    $mensaje .= "<span style='margin-left: 20px;'>Ciclo en el que entro en repitencia: <b>" . $cursoInfo["resultado"]["ciclo_repitencia"] . "</b></span><br /><br />";
                }
                $url = "../menus/contenido.php";
                aviso($mensaje, $url);
                exit;
            } else {

                // Verificacion del periodo de Asignacion.
                $consulta = "SELECT c.fecha_inicio_asignacion, c.fecha_fin_asignacion, NOW() AS fecha_actual,
				DATE_FORMAT(c.fecha_inicio_asignacion, CONCAT(
					(SELECT
                      DATE_FORMAT(c.fecha_inicio_asignacion, '%d'))
                        , ' de ', 
                    (SELECT
                      CASE MONTH(c.fecha_inicio_asignacion)
                        WHEN 1  THEN 'Enero'
                        WHEN 2  THEN 'Febrero'
                        WHEN 3  THEN 'Marzo'
                        WHEN 4  THEN 'Abril'
                        WHEN 5  THEN 'Mayo'
                        WHEN 6  THEN 'Junio'
                        WHEN 7  THEN 'Julio'
                        WHEN 8  THEN 'Agosto'
                        WHEN 9  THEN 'Septiembre'
                        WHEN 10 THEN 'Octubre'
                        WHEN 11 THEN 'Noviembre'
                        WHEN 12 THEN 'Diciembre'
                    END),
                    ' del ',
					(SELECT
                      DATE_FORMAT(c.fecha_inicio_asignacion, '%Y'))					
					, ' a las ',
					(SELECT
						DATE_FORMAT(c.fecha_inicio_asignacion, '%H:%i'))
					)) AS inicio_asignacion, e.nombre AS evaluacion, s.nombre AS semestre
				FROM ciclo c
				INNER JOIN evaluacion e
				ON e.evaluacion = c.evaluacion
				INNER JOIN semestre s
				ON s.semestre = c.semestre
				WHERE c.anio = $anio AND c.semestre = $semestre AND c.evaluacion = $evaluacion AND c.asignacion = 1";
                $ciclo = & $db->getRow($consulta);
                if ($db->isError($ciclo)) {
                    $error = true;
                    $mensaje = "Hubo un error al determinar el ciclo actual. E.01";
                    $url = "../menus/contenido.php";
                } else {

                    if ($inscripcion == 0) {
                        $error = true;
                        $mensaje = "Usted no esta inscrito en el ciclo actual. Por favor verifique esta información";
                        $url = "../menus/contenido.php";
                    } else {
                        // Verificacion del periodo de Asignacion.
                        if ($ciclo == 0) {
                            $error = true;
                            $mensaje = "El período de asignación de cursos para Interciclos no ha sido definido.";
                            $url = "../menus/contenido.php";
                        } else {

                            if ($ciclo[fecha_inicio_asignacion] > $ciclo[fecha_actual] && $permitir_ingreso <> $carnet) {
                                $error = true;
                                $mensaje = "El período de asignación de cursos para $ciclo[evaluacion] $ciclo[semestre] dará inicio el: $ciclo[inicio_asignacion] horas.";
                                //$mensaje = "El período de asignación de cursos para $ciclo[evaluacion] $ciclo[semestre] dará inicio el: 6 de Junio a las 07:00 horas.";
                                $url = "../menus/contenido.php";
                            } else {

                                if ($ciclo[fecha_fin_asignacion] < $ciclo[fecha_actual] && false) {
                                    $error = true;
                                    $mensaje = "El período de asignación de cursos para $ciclo[evaluacion] $ciclo[semestre] ha finalizado.";
                                    $url = "../menus/contenido.php";
                                } else {

                                    if (($ciclo[fecha_inicio_asignacion] < $ciclo[fecha_actual] AND $ciclo[fecha_fin_asignacion] > $ciclo[fecha_actual]
                                            ) || $permitir_ingreso == $carnet || true) {
                                        //llenando array con las posibles solicitudes de asignacion
                                        $a = array();
                                        foreach ($inscripcion AS $in) {
                                            $carrera = $in[carrera];
                                            $cursos_asignar = obtenerCursosASolicitar($db, $carnet, $carrera, $anio, $semestre, $extension, $evaluacion);
                                            if (count($cursos_asignar) <> 0) {
                                                foreach ($cursos_asignar as $ca) {
                                                    $a[] = array("pensum" => $ca['pensum'], "codigo" => $ca['codigo'], "nombre" => $ca['nombre']);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$error && !$aviso) {

            // Cargando la pagina de seleccion de Asignatura para Interciclos
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('asignacion_interciclos_solicitud.html');

            $solicitud_anterior = getSolicitudRealizada($db, $carnet, $anio, $semestre, $evaluacion);
            $asignaciones = getAsignaciones($db, $carnet, $anio, $semestre, $evaluacion);

            if (!empty($a) && count($solicitud_anterior) < 1 && count($asignaciones) < 2) {
                foreach ($a as $curso_disponible) {
                    $template->setVariable(array(
                        'value_curso' => $anio . "_" . $semestre . "_" . $evaluacion . "_" . $curso_disponible['pensum'] . "_" . $curso_disponible['codigo'],
                        'descripcion_curso' => $curso_disponible['codigo'] . " - " . $curso_disponible['nombre']
                    ));
                    $template->parse('cursos_disponibles');
                }
            } else {
                if (count($asignaciones) > 1) {
                    $template->setVariable(array(
                        'sin_asignaciones_disponibles' => "<div class='alert alert-success'><b>Usted ya tiene 2 cursos asignados, no puede solicitar la apertura de asignaturas no programadas.</b>
						<!-- br /><br />
						<b>Artículo 5</b>
						<br /><a target='_blank' href='http://estudiante.arquitectura.usac.edu.gt/docs/pai/Normativo_PAI.pdf'>Ver Normativo</a -->
						</div>",
                        'ocultar_seleccion_cursos' => 'hidden'
                    ));
                } else if (count($solicitud_anterior) > 0) {
                    $template->setVariable(array(
                        'sin_asignaciones_disponibles' => "<div class='alert alert-success'>Ya realizo una solicitud anteriormente, ha solicitado: <b>" . $solicitud_anterior[0]['codigo'] . " - " . trim($solicitud_anterior[0]['nombre']) . "</b>.</div>",
                        'ocultar_seleccion_cursos' => 'hidden'
                    ));
                } else {
                    $template->setVariable(array(
                        'sin_asignaciones_disponibles' => "<div class='alert alert-danger'>No existen cursos disponibles.</div>",
                        'ocultar_seleccion_cursos' => 'hidden'
                    ));
                }
                $template->parse('seleccion_asignaturas');
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
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
