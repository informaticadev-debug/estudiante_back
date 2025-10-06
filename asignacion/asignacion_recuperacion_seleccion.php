<?php

/*
  Proceso de Asignacion para Fin de Semestre
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();
if (isset($_SESSION[usuario])) {

    $error = false;

    $carnet_autorizado = '';

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
        $anio = 2025; //$_SESSION['anio'];
        $semestre = 1;
        $evaluacion = $_GET['evaluacion'];
        $carnet = $_SESSION['usuario'];
        $_SESSION['evaluacion'] = $evaluacion;
        $db->Query("SET lc_time_names = 'es_ES'");

        if (isset($_SESSION['pensum'])) {

            // Quitando la sesion para la asignatura seleccionada.			
            unset($_SESSION['pensum']);
            unset($_SESSION['codigo']);
            unset($_SESSION['seccion']);
        } else {

            $_SESSION['evaluacion'] = $evaluacion;
        }

        // Verificacion de la inscripcion del estudiante en el ciclo actual
        $consulta = "SELECT i.carnet, i.carrera
        FROM inscripcion i
        WHERE i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet";
        $inscripcion = & $db->getAll($consulta);
        if ($db->isError($inscripcion)) {
            $error = true;
            $mensaje = "Hubo un error al determinar tu inscripcion en el ciclo actual.";
            $url = "../menus/contenido.php";
        } else {

            // Verificacion de la existencia de repitencia del estudiante
            foreach ($inscripcion AS $in) {

                $consulta = "SELECT sel.codigo, sel.asignatura, total AS perdidas
                FROM (
                        SELECT CONCAT(a2.pensum,' - ',a2.`codigo`) AS codigo, TRIM(c.`nombre`) AS asignatura, COUNT(*) AS total
                        FROM asignacion a2
                        INNER JOIN curso c
                        ON c.`pensum` = a2.`pensum` AND c.`codigo` = a2.`codigo`
                        WHERE CONCAT(a2.anio,a2.`semestre`) >= 20052 AND a2.`nota` < 61 AND a2.`evaluacion` = 1 AND a2.`status` = 1
                        AND a2.carnet = $carnet
                        AND (
                                EXISTS (
                                        SELECT *
                                        FROM asignacion a3
                                        WHERE a3.`anio` = a2.`anio` AND a3.`semestre` = a2.`semestre` AND a3.`evaluacion` = 1
                                        AND a3.`carnet` = a2.`carnet` AND a3.`pensum` = a2.`pensum` AND a3.`codigo` = a2.`codigo`
                                        AND a3.`nota` <> 0 AND a3.nota IS NOT NULL
                                )
                                OR EXISTS ( 
                                        SELECT *
                                        FROM asignacion a3
                                        WHERE a3.`anio` = a2.`anio` AND a3.`semestre` = a2.`semestre` AND a3.`evaluacion` = 1
                                        AND a3.`carnet` = a2.`carnet` AND a3.`pensum` = a2.`pensum` AND a3.`codigo` = a2.`codigo`
                                        AND CONCAT(a3.anio,a3.`semestre`) < 20102 AND a3.nota IS NOT NULL
                                )
                        )
                        AND NOT EXISTS (
                                SELECT *
                                FROM nota n
                                WHERE n.`carnet` = a2.`carnet` AND n.`pensum` = a2.`pensum` AND n.`codigo` = a2.`codigo`
                        )
                        GROUP BY a2.`codigo`,a2.`carnet`
                ) sel
                WHERE sel.total >= 5";
                $repitencia = null;//& $db->getAll($consulta);
            }
            if ($db->isError($repitencia)) {
                $error = true;
                $mensaje = "Hubo un error al determinar el estado de repitencia.";
                $url = "../menus/contenido.php";
            } else {

                // Verificacion del periodo de Asignacion.
                $consulta = "SELECT c.fecha_inicio_asignacion, c.fecha_fin_asignacion, NOW() AS fecha_actual,
				e.nombre AS evaluacion, s.nombre AS semestre, DATE_FORMAT(c.fecha_inicio_asignacion, '%d de %M de %Y a las %Hhrs.') AS inicio_asignacion,
				DATE_FORMAT(c.fecha_fin_asignacion, '%d de %M de %Y a las %Hhrs.') AS fin_asignacion
                FROM ciclo c
                INNER JOIN evaluacion e
                ON e.evaluacion = c.evaluacion
                INNER JOIN semestre s
                ON s.semestre = c.semestre
                WHERE c.anio = $anio AND c.semestre = $semestre AND c.evaluacion = $evaluacion AND c.asignacion = 1";
                $ciclo = & $db->getRow($consulta);
                if ($db->isError($ciclo)) {
                    $error = true;
                    $mensaje = "Hubo un error al determinar el ciclo actual.";
                    $url = "../menus/contenido.php";
                } else {

                    if ($inscripcion == 0) {
                        $error = true;
                        $mensaje = "Usted no esta inscrito en el ciclo actual. Por favor verifique esta información";
                        $url = "../menus/contenido.php";
                    } else {

                        if (count($repitencia) <> 0) {
                            $aviso = true;
                            $mensaje = "Por el momento no puede asignarse cursos en este ciclo, debido a la repitencia en: <br><br>";

                            foreach ($repitencia AS $re) {
                                $mensaje = $mensaje . "$re[codigo] $re[asignatura] ($re[perdidas] veces reprobada) <br>";
                            }

                            $url = "../menus/contenido.php";
                        } else {

                            // Verificacion del periodo de Asignacion.
                            if ($ciclo == 0) {
                                $aviso = true;
                                $mensaje = "El período de asignación de cursos para recuperación no ha sido definido.";
                                $url = "../menus/contenido.php";
                            } else {

                                if ($ciclo[fecha_inicio_asignacion] > $ciclo[fecha_actual] && $carnet != $carnet_autorizado) {

                                    if ($evaluacion == "3") {
                                        $nombre_evaluacion = "Primera recuperación";
                                    } else {
                                        $nombre_evaluacion = "Segunda recuperación";
                                    }

                                    $aviso = true;
                                    $mensaje = "El período de asignación de cursos para $nombre_evaluacion dará inicio el: $ciclo[inicio_asignacion]
									y finaliza el $ciclo[fin_asignacion]";
                                    $url = "../menus/contenido.php";
                                } else {

                                    if ($ciclo[fecha_fin_asignacion] < $ciclo[fecha_actual]  && $carnet != $carnet_autorizado ) {
                                        $error = true;
                                        $mensaje = "El período de asignación de cursos para $ciclo[evaluacion] $ciclo[semestre] ha finalizado.";
                                        $url = "../menus/contenido.php";
                                    } else {

                                        if (($ciclo[fecha_inicio_asignacion] < $ciclo[fecha_actual] AND $ciclo[fecha_fin_asignacion] > $ciclo[fecha_actual]) || $carnet == $carnet_autorizado) {

                                            // Verificacion de Primera y Segunda Recuperacion 
                                            if ($evaluacion == 3) {

                                                foreach ($inscripcion AS $in) {

                                                    // Consulta para Primera Recuperacion
                                                    // -> Unicamente se toma en cuenta la recuperacion para los cursos que estan en repitencia en semestre
                                                    // -> No se podra hacer cambio de seccion el estudiante debera asignarse la recuperacion en la seccion
                                                    // 	  en la que ha cursado la asignatura anteriormente
                                                    // -> Se permite la solicitud de prueba aunque no exista la minima previa

                                                    $cursos_a_asignar = "SELECT s.pensum, s.codigo, s.seccion, TRIM(c.nombre) AS nombre,
                                                    s.minima
                                                    FROM seccion s
                                                    INNER JOIN curso c
                                                    ON c.codigo = s.codigo AND c.pensum = s.pensum
                                                    WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
                                                    AND EXISTS(
                                                        SELECT *
                                                        FROM asignacion a
                                                        WHERE a.extension = s.extension AND a.anio = s.anio AND a.semestre = s.semestre
                                                        AND a.evaluacion = 1 AND a.pensum = s.pensum AND a.codigo = s.codigo
                                                        AND a.seccion = s.seccion AND a.carnet = $carnet AND a.status = 1
                                                        AND IF(a.nota IS NULL,0,a.nota) < 70
                                                        -- AND a.nota >= s.minima
                                                    ) AND NOT EXISTS(
                                                        SELECT *
                                                        FROM asignacion a2
                                                        WHERE a2.extension = s.extension AND a2.anio = s.anio AND a2.semestre = s.semestre
                                                        AND a2.evaluacion = s.evaluacion AND a2.pensum = s.pensum AND a2.codigo = s.codigo 
                                                        AND a2.seccion = s.seccion AND a2.carnet = $carnet AND a2.status = 1
                                                    ) AND (s.`pensum`, s.`codigo`) NOT IN (
														SELECT pensum, codigo
														FROM nota
														WHERE carnet = $carnet and aprobado = 1
                                                    )
													
													";
                                                    //print_r($cursos_a_asignar);die;

                                                    $cursos_asignar = & $db->getAll($cursos_a_asignar);
                                                    if ($db->isError($cursos_asignar)) {
                                                        $error = true;
                                                        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo." . mysql_error();
                                                        $url = "../menus/contenido.php";
                                                    }

                                                    if (count($cursos_asignar) <> 0) {
                                                        foreach ($cursos_asignar as $ca) {
                                                            $a[] = array("pensum" => $ca['pensum'], "codigo" => $ca['codigo'], "seccion" => $ca['seccion'], "nombre" => $ca['nombre']);
                                                        }
                                                    }
                                                }
                                            }

                                            if ($evaluacion == 4) {

                                                foreach ($inscripcion AS $in) {

                                                    // Consulta para segunda Recuperacion
                                                    // -> Unicamente se podran asignar los cursos que se hayan asignado en la primera recuperacion
                                                    // -> No se podra hacer cambio de seccion el estudiante debera asignarse la recuperacion en la seccion
                                                    // 	  en la que ha cursado la asignatura anteriormente

                                                    $cursos_a_asignar = "SELECT s.pensum, s.codigo, s.seccion, TRIM(c.nombre) AS nombre, s.minima
                                                    FROM seccion s
                                                    INNER JOIN curso c
                                                    ON c.codigo = s.codigo AND c.pensum = s.pensum
                                                    WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
                                                    AND EXISTS(
                                                        SELECT *
                                                        FROM asignacion a
                                                        WHERE a.extension = s.extension AND a.anio = s.anio AND a.semestre = s.semestre
                                                        AND a.evaluacion = 1 AND a.pensum = s.pensum AND a.codigo = s.codigo
                                                        AND a.seccion = s.seccion AND a.carnet = $carnet AND a.nota >= s.minima
                                                        AND IF(a.nota IS NULL,0,a.nota) < 61
                                                    ) AND NOT EXISTS(
                                                        SELECT *
                                                        FROM asignacion a2
                                                        WHERE a2.extension = s.extension AND a2.anio = s.anio AND a2.semestre = s.semestre
                                                        AND a2.evaluacion = $evaluacion AND a2.pensum = s.pensum AND a2.codigo = s.codigo 
                                                        AND a2.seccion = s.seccion AND a2.carnet = $carnet AND a2.status = 1
                                                    )
                                                    /* QUEMADO SOLO PARA HABILITAR CIERTAS SECCIONES... */
                                                    /*AND (
                                                        (s.codigo = '3.01.6' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.01.6' AND s.seccion = 'B')
                                                        OR (s.codigo = '3.02.7' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.02.7' AND s.seccion = 'C')
                                                        OR (s.codigo = '2.02.6' AND s.seccion = 'A')
                                                        OR (s.codigo = '2.02.5' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.02.7' AND s.seccion = 'B')
                                                        OR (s.codigo = '2.02.6' AND s.seccion = 'B')
                                                        OR (s.codigo = '2.02.6' AND s.seccion = 'C')
                                                        OR (s.codigo = '2.02.5' AND s.seccion = 'B')
                                                        OR (s.codigo = '2.02.5' AND s.seccion = 'C')
                                                        OR (s.codigo = '2.02.5' AND s.seccion = 'D')
                                                        OR (s.codigo = '2.03.1' AND s.seccion = 'A')
                                                        OR (s.codigo = '2.01.5' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.04.6' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.04.6' AND s.seccion = 'B')
                                                        OR (s.codigo = '2.04.5' AND s.seccion = 'A')
                                                        OR (s.codigo = '2.04.1' AND s.seccion = 'B')
                                                        OR (s.codigo = '2.04.1' AND s.seccion = 'C')
                                                        OR (s.codigo = '2.04.1' AND s.seccion = 'D')
                                                        OR (s.codigo = '3.05.7' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.06.6' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.06.6' AND s.seccion = 'B')
                                                        OR (s.codigo = '2.06.3' AND s.seccion = 'A')
                                                        OR (s.codigo = '2.06.3' AND s.seccion = 'B')
                                                        OR (s.codigo = '3.06.5' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.06.5' AND s.seccion = 'B')
                                                        OR (s.codigo = '3.09.6' AND s.seccion = 'A')
                                                        OR (s.codigo = '3.09.6' AND s.seccion = 'B')
                                                        OR (s.codigo = '3.08.0' AND s.seccion = 'A')
                                                        OR (s.codigo = '2.08.5' AND s.seccion = 'A')
                                                        OR (s.codigo = '2.08.3' AND s.seccion = 'A')
                                                        OR (s.codigo = '2.08.2' AND s.seccion = 'A')
                                                        OR (s.codigo = '2.08.5' AND s.seccion = 'B')
                                                        OR (s.codigo = '3.10.3' AND s.seccion = 'A')
                                                        
                                                    )*/
                                                    ";
                                                    $cursos_asignar = & $db->getAll($cursos_a_asignar);
                                                    if ($db->isError($cursos_asignar)) {
                                                        $error = true;
                                                        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
                                                        $url = "../menus/contenido.php";
                                                    }

                                                    if (count($cursos_asignar) <> 0) {
                                                        foreach ($cursos_asignar as $ca) {
                                                            $a[] = array("pensum" => $ca['pensum'], "codigo" => $ca['codigo'], "seccion" => $ca['seccion'], "nombre" => $ca['nombre']);
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
            }
        }

        if (!$error && !$aviso) {

            // Cargando la pagina de seleccion de Asignatura para Segunda Recuperacion
            $template = new HTML_Template_Sigma('../templates');
            if ($evaluacion == 3) {
                $template->loadTemplateFile('asignacion_1rarecuperacion_seleccion.html');
            } else {
                $template->loadTemplateFile('asignacion_2darecuperacion_seleccion.html');
            }

            if (!empty($a)) {

                // Lista de cursos aperturados.
                foreach ($a AS $cu) {
                    $template->setVariable(array(
                        'pensum' => $cu[pensum],
                        'codigo' => $cu[codigo],
                        'seccion' => $cu[seccion],
                        'asignatura' => $cu[nombre]
                    ));

                    $template->parse('seleccion_asignaturas');
                }

                $template->setVariable(array(
                    'boton_ordenpago' => "<input class='btn btn-primary' type='submit' value='Orden de Pago'>"
                ));

                if ($error) {
                    mostrarError($mensaje);
                }
            } else {

                $template->setVariable(array(
                    'sin_asignaciones_disponibles' => "<div class='alert alert-danger'>Sin secciones disponibles para asignar</div>"
                ));
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
