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
        $aviso = false;
        
        $enCupoExtra = [];

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_SESSION['evaluacion'];
        $carnet = $_SESSION['usuario'];
        $carrera = inscripcion_estudiante($db, $anio, $semestre, $carnet);

        // Asignaciones permitidas en Semestre
        if ($carrera > 3) {
            $asignaciones_permitidas = 20;
        } else {
            $asignaciones_permitidas = 7;
        }

        $cursos = $_POST['cursos'];

        // Verificacion de cupo lleno para mostrar mensaje.
        if ($seccion == '--') {
            $error = true;
            $mensaje = "El cupo en esta Asignatura esta lleno.";
            $url = $_SERVER[HTTP_REFERER];
        }

        // Verificar asignaciones en el ciclo
        /* $consulta = "SELECT COUNT(*) AS asignaciones
          FROM asignacion a
          WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion AND a.carnet = $carnet"; */
        $consulta = "
                SELECT count(*)
		FROM (
		SELECT a.`carnet`, a.`pensum`, a.`codigo`
		FROM asignacion a
		WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion AND a.carnet = $carnet
		union all
		SELECT aspd.`carnet`, aspd.`pensum`, aspd.`codigo`
		FROM asignacion_spd aspd
		WHERE aspd.extension = $extension AND aspd.anio = $anio AND aspd.semestre = $semestre AND aspd.evaluacion = $evaluacion AND aspd.carnet = $carnet
		) as asignaciones";
        $asignaciones = & $db->getRow($consulta);
        if ($db->isError($asignaciones)) {
            $error = true;
            $mensaje = "Hubo un error al verificar las asignaciones en este ciclo.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Asignaciones ya realizadas en este ciclo.
            $consulta_asignados = "
            SELECT 1 AS prioridad, a.extension, a.anio, a.semestre, a.evaluacion, a.pensum, a.codigo, 
            a.seccion, TRIM(c.nombre) AS asignatura, a.preasignacion
            FROM asignacion a
            INNER JOIN curso c
            ON c.codigo = a.codigo AND c.pensum = a.pensum
            WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion
            AND a.carnet = $carnet
            
                    ";
            $asignados = & $db->getAll($consulta_asignados);
            if ($db->isError($asignados)) {
                $error = true;
                $mensaje = "Hubo un error en la verificacion de la asignatura seleccionada.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                if ($cursos) {

                    $mes_actual = DATE("m");

                    // Seleccion de asignaturas realizada
                    $consulta_seleccion = "SELECT 2 AS prioridad, s.extension, s.anio, s.semestre, s.evaluacion, s.pensum, s.codigo, 
                    s.seccion, TRIM(c.nombre_abreviado) AS asignatura, 0 AS preasignacion
                    FROM seccion s
                    INNER JOIN curso c
                    ON c.codigo = s.codigo AND c.pensum = s.pensum
                    WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
                    AND CONCAT(s.pensum, '__', s.codigo, '__', s.seccion) IN (";
                    foreach ($_POST['cursos'] AS $cur) {
                        if (strpos($cur, "spd_") > -1) {
                            $enCupoExtra[] = $cur;
                            $cur = str_replace("spd_", "", $cur);
                        }
                        $consulta_seleccion = $consulta_seleccion . "'" . $cur . "'";
                        $consulta_seleccion = $consulta_seleccion . ",";
                    }
                    $consulta_seleccion = $consulta_seleccion . "'')";
                    $seleccionados = & $db->getAll($consulta_seleccion);
                    if ($db->isError($seleccionados)) {
                        $error = true;
                        $mensaje = "Hubo un error al obtener las Asignaturas de la seleccion";
                        $url = $_SERVER[HTTP_REFERER];
                    } else {

                        // Verificar la carrera actual del estudiante
                        $consulta = "SELECT *
                        FROM inscripcion i
                        WHERE i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet";
                        $carreras = & $db->getAll($consulta);
                        if ($db->isError($carreras)) {
                            $error = true;
                            $mensaje = "Hubo un error durante la consulta de las carreras actuales.";
                            $url = $_SERVER[HTTP_REFERER];
                        } else {

                            foreach ($carreras AS $ca) {

                                if ($ca[carrera] <= 3) {
                                    $verificacion_horario = 1;
                                }

                                if ($ca[carrera] > 3) {
                                    $verificacion_horario = 0;
                                }
                            }
                        }
                    }
                }

                //verificando secciones SPD
                foreach ($_POST['cursos'] AS $cur) {
                    $datos_curso = explode("__", $cur);
                    if ($datos_curso[2] == 'SPD') {
                        //consultando la informacion general del curso...
                        $consulta_seleccion2 = "SELECT 2 AS prioridad, $extension, $anio, $semestre, $evaluacion, pensum, codigo, 
                                                'SPD' as seccion, TRIM(c.nombre_abreviado) AS asignatura, 0 AS preasignacion
                                                FROM curso c
                                                WHERE c.codigo = '$datos_curso[1]' AND c.pensum = $datos_curso[0]
                                                LIMIT 1
                                                ";
                        $seleccionado_spd = & $db->getAll($consulta_seleccion2);
                        if ($db->isError($carreras)) {
                            $error = true;
                            $mensaje = "Hubo un error durante la consulta de las secciones SPD.";
                            $url = $_SERVER[HTTP_REFERER];
                        } else {
                            foreach ($seleccionado_spd as $value) {
                                $seleccionados[] = $value;
                            }
                        }
                    }
                }

                if (count($seleccionados) == 0) {
                    $aviso = true;
                    $mensaje = "Debe seleccionar por lo menos una seccion en las Asignaturas disponibles.";
                    $url = $_SERVER[HTTP_REFERER];
                }

                $union_consultas = $consulta_asignados . " UNION ALL " . $consulta_seleccion;
                $verificacion = & $db->getAll($union_consultas);

                $query_en_spd = "SELECT COUNT(*) AS cantidad
		FROM asignacion_spd aspd
		WHERE aspd.extension = $extension AND aspd.anio = $anio AND aspd.semestre = $semestre AND aspd.evaluacion = $evaluacion AND aspd.carnet = $carnet";
                $query_en_spd_data = & $db->getRow($query_en_spd);

                // Asignaturas Seleccionadas y verificacion de no pasar el limite permitido de asignaciones en el Ciclo actual
                if ($cursos) {
                    if ((count($verificacion) + intval($query_en_spd_data['cantidad'])) <= $asignaciones_permitidas) {

                        $mes_actual = DATE('m');

                        if ($verificacion_horario == 1) {
                            $consulta = "SELECT sel.*,
							IF(
								EXISTS
								(
								select * 
								from 	
									( " . $union_consultas . "
									) as sel1,
									( " . $union_consultas . "
									) as sel2
									WHERE sel1.extension = sel2.extension and sel1.anio = sel2.anio and sel1.semestre = sel2.semestre and sel1.evaluacion = sel2.evaluacion and
									sel1.pensum = sel.pensum and sel1.codigo = sel.codigo and sel1.seccion = sel.seccion and
									concat(rtrim(sel1.pensum),rtrim(sel1.codigo)) <> concat(rtrim(sel2.pensum),rtrim(sel2.codigo)) and
									EXISTS
									(
										select *
										FROM horario h1, pensum p1, periodo_ciclo pc1,
										horario h2, pensum p2, periodo_ciclo pc2
										WHERE h1.extension = sel1.extension and h1.anio = sel1.anio and h1.semestre = sel1.semestre and h1.evaluacion = sel1.evaluacion and
										h1.pensum = sel1.pensum and h1.codigo = sel1.codigo and h1.seccion = sel1.seccion and 
										h1.pensum = p1.pensum and
										pc1.carrera = p1.carrera and pc1.extension = h1.extension and pc1.anio = h1.anio and pc1.semestre = h1.semestre 
										and pc1.evaluacion = h1.evaluacion and pc1.periodo = h1.periodo and
										h2.extension = sel2.extension and h2.anio = sel2.anio and h2.semestre = sel2.semestre and h2.evaluacion = sel2.evaluacion 
										and h2.pensum = sel2.pensum and h2.codigo = sel2.codigo and h2.seccion = sel2.seccion and 
										h2.pensum = p2.pensum and
										pc2.carrera = p2.carrera and pc2.extension = h2.extension and pc2.anio = h2.anio and pc2.semestre = h2.semestre and 
										pc2.evaluacion = h2.evaluacion and pc2.periodo = h2.periodo and
										h1.dia = h2.dia and
										str_to_date(pc1.hora_ini,'%H:%i') < str_to_date(pc2.hora_fin,'%H:%i') and
										str_to_date(pc1.hora_fin,'%H:%i') > str_to_date(pc2.hora_ini,'%H:%i')
									)
							),\"Traslape de Horario\",
							if
							(
								EXISTS
								(
									select *
									from prerrequisito p
									where	p.pensum = sel.pensum and p.codigo = sel.codigo and
										(
											exists 
											(
												select *
												from nota n
												where 	n.carnet = $carnet and 
													n.pensum = p.pensum_prerrequisito and n.codigo = p.codigo_prerrequisito and
													n.estado in (3,4)
											)
											or exists 
											(
												select *
												from equivalencia e
												where 	e.pensum = p.pensum_prerrequisito and e.codigo = p.codigo_prerrequisito and 
													e.pensum_equivalente = if(p.pensum = 5,3,if(p.pensum = 3,1,if(p.pensum = 16,4,if(p.pensum = 4,2,if(p.pensum = 18,0,if(p.pensum = 20,16,0)))))) and
													exists 
													(
														select *
														from nota n
														where	n.carnet = $carnet and 
															n.pensum = e.pensum_equivalente and n.codigo = e.codigo_equivalente and 
															n.estado in (3,4)
													)
											)
										)
								),\"Prerrequisito aprobado en fechas posteriores\",\"Asignatura sin problemas\"
							)
							) as observacion
							from 	
								( " . $union_consultas . "
								) as sel
							order by observacion ASC";
                        } else {

                            $consulta = "SELECT sel.*,
							IF(
								EXISTS
								(
								select * 
								from 	
									( " . $union_consultas . "
									) as sel1,
									( " . $union_consultas . "
									) as sel2
									WHERE sel1.extension = sel2.extension and sel1.anio = sel2.anio and sel1.semestre = sel2.semestre and sel1.evaluacion = sel2.evaluacion and
									sel1.pensum = sel.pensum and sel1.codigo = sel.codigo and sel1.seccion = sel.seccion and
									concat(rtrim(sel1.pensum),rtrim(sel1.codigo)) <> concat(rtrim(sel2.pensum),rtrim(sel2.codigo)) and
									EXISTS
									(
										select *
										FROM horario h1, pensum p1, periodo_ciclo pc1, seccion sec1,
										horario h2, pensum p2, periodo_ciclo pc2, seccion sec2
										WHERE h1.extension = sel1.extension and h1.anio = sel1.anio and h1.semestre = sel1.semestre and h1.evaluacion = sel1.evaluacion and
										h1.pensum = sel1.pensum and h1.codigo = sel1.codigo and h1.seccion = sel1.seccion and 
										h1.pensum = p1.pensum and
										pc1.carrera = p1.carrera and pc1.extension = h1.extension and pc1.anio = h1.anio and pc1.semestre = h1.semestre 
										and pc1.evaluacion = h1.evaluacion and pc1.periodo = h1.periodo and
										h2.extension = sel2.extension and h2.anio = sel2.anio and h2.semestre = sel2.semestre and h2.evaluacion = sel2.evaluacion 
										and h2.pensum = sel2.pensum and h2.codigo = sel2.codigo and h2.seccion = sel2.seccion and 
										h2.pensum = p2.pensum and
										pc2.carrera = p2.carrera and pc2.extension = h2.extension and pc2.anio = h2.anio and pc2.semestre = h2.semestre and 
										pc2.evaluacion = h2.evaluacion and pc2.periodo = h2.periodo and
										h1.dia = h2.dia and
										str_to_date(pc1.hora_ini,'%H:%i') < str_to_date(pc2.hora_fin,'%H:%i') and
										str_to_date(pc1.hora_fin,'%H:%i') > str_to_date(pc2.hora_ini,'%H:%i')
										AND sec1.extension = sel1.extension AND sec1.anio = sel1.anio AND sec1.semestre = sel1.semestre AND sec1.evaluacion = sel1.evaluacion
										AND sec1.pensum = sel1.pensum AND sec1.codigo = sel1.codigo AND sec1.seccion = sel1.seccion
										AND sec2.extension = sel2.extension AND sec2.anio = sel2.anio AND sec2.semestre = sel2.semestre AND sec2.evaluacion = sel2.evaluacion
										AND sec2.pensum = sel2.pensum AND sec2.codigo = sel2.codigo AND sec2.seccion = sel2.seccion
										AND sec1.mes <> sec2.mes AND sec1.mes = $mes_actual AND sec2.mes = $mes_actual
									)
							),\"Traslape de Horario\",
							if
							(
								EXISTS
								(
									select *
									from prerrequisito p
									where	p.pensum = sel.pensum and p.codigo = sel.codigo and
										(
											exists 
											(
												select *
												from nota n
												where 	n.carnet = $carnet and 
													n.pensum = p.pensum_prerrequisito and n.codigo = p.codigo_prerrequisito and
													n.estado in (3,4)
											)
											or exists 
											(
												select *
												from equivalencia e
												where 	e.pensum = p.pensum_prerrequisito and e.codigo = p.codigo_prerrequisito and 
													e.pensum_equivalente = if(p.pensum = 5,3,if(p.pensum = 3,1,if(p.pensum = 16,4,if(p.pensum = 4,2,if(p.pensum = 18,0,if(p.pensum = 20,16,0)))))) and
													exists 
													(
														select *
														from nota n
														where	n.carnet = $carnet and 
															n.pensum = e.pensum_equivalente and n.codigo = e.codigo_equivalente and 
															n.estado in (3,4)
													)
											)
										)
								),\"Prerrequisito aprobado en fechas posteriores\",\"Asignatura sin problemas\"
							)
							) as observacion
							from 	
								( " . $union_consultas . "
								) as sel
							order by observacion ASC";
                        }

                        $seleccion = & $db->getAll($consulta);
                        if ($db->isError($seleccion)) {
                            $error = true;
                            $mensaje = "Hubo un error al verificar los datos de la asignatura seleccionada.";
                            $url = $_SERVER[HTTP_REFERER];
                        }
                    } else {
                        $error = true;
                        $mensaje = "No se permite asignarse mas de $asignaciones_permitidas Asignaturas en semestre.";
                        $url = $_SERVER[HTTP_REFERER];
                    }

                    // Envio del formulario de Asignacion 
                    // -> Verificacion de la carrera del estudiante para determinar a donde se enviara el formulario 
                    // -> Si las carreras son de Pregrado el formulario se enviara a asignacion_semestre_confirmacion.php
                    // -> Si las carreras son de Pregrado el formulario se enviara a asignacion_semestre_ordenpago.php
                    $consulta = "SELECT i.carrera
					FROM inscripcion i
					WHERE i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet
					AND i.carrera NOT IN (1,2,3)";
                    $maestria = & $db->getAll($consulta);
                    if ($db->isError($maestria)) {
                        $error = true;
                        $mensaje = "Hubo un error al determinar la carrera actual del estudiante";
                        $url = $_SERVER[HTTP_REFERER];
                    }
                }
            }
        }

        if (!$error && !$aviso) {

            // Verificacion de que aun no se haya llegado a las asignaciones permitidas.
            if ($asignaciones[asignaciones] <= $asignaciones_permitidas) {

                // Cargando la pagina de verificacion de la asignatura seleccionada.
                $template = new HTML_Template_Sigma('../templates');
                $template->loadTemplateFile('asignacion_semestre_verificacion.html');

                $template->setVariable(array(
                    'regresar' => $_SERVER['HTTP_REFERER']
                ));

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
                            $mensaje = "Hubo un error al comprobar el monto a pagar para las Asignaciones realizadas.";
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
                                'seccion' => $as[seccion],
                                'imprimir_orden' => "<input id='btn_derecha' type='button' value='Pre-Asignado'>"
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
                                'seccion' => $as[seccion],
                                'imprimir_orden' => "<input id='btn_derecha' type='button' value='Asignado'>"
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
                        'sin_asignaciones_disponibles' => "<div class='alert alert-danger'>No cuentas con cursos asignados.</div>"
                    ));
                }

                // Asignatura seleccionada.

                foreach ($seleccionados as $value) {
                    if ($value['seccion'] == 'SPD') {
                        $seleccion[] = $value;
                    }
                }

                foreach ($seleccion AS $res) {

                    // Cantidad de asignaciones realizadas por el estudiante
                    $consulta = "SELECT COUNT(*) AS cantidad
					FROM asignacion a
					WHERE a.pensum = $res[pensum] AND a.codigo = '$res[codigo]' AND a.carnet = $carnet AND a.status = 1";
                    $veces_asignado = & $db->getRow($consulta);
                    if ($db->isError($veces_asignado)) {
                        $error = true;
                        $mensaje = "Error al verificar el numero de veces que se ha asignado el curso.";
                        $url = $_SERVER[HTTP_REFERER];
                    }

                    if (!empty($res[codigo])) {

                        // Horarios de las Asignaturas seleccionadas.
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
						AND s.pensum = '$res[pensum]' AND s.codigo = '$res[codigo]' AND s.seccion = '$res[seccion]'";
                        $horarios = & $db->getAll($consulta);
                        if ($db->isError($horarios)) {
                            $error = true;
                            $mensaje = "Hubo un error al verificar los horarios en las Asignaturas seleccionadas." . mysql_error();
                            $url = $_SERVER[HTTP_REFERER];
                        }

                        if ($res[prioridad] <> 1) {

                            // Verificacion de Asignaturas que pagan uso de Laboratorio.
                            if (
                                    ($res[codigo] == '1.07.4' ||
                                    $res[codigo] == '1.03.4' ||
                                    $res[codigo] == '1.04.4' ||
                                    $res[codigo] == '30211' ||
                                    $res[codigo] == '30311' ||
                                    $res[codigo] == '30411' ||
                                    $res[codigo] == '30511' ||
                                    $res[codigo] == '30611' ||
                                    $res[codigo] == '30712' ||
                                    $res[codigo] == '30711' ||
                                    $res[codigo] == '30811' ||
                                    $res[codigo] == '30911' ||
                                    $res[codigo] == '30812' ||
                                    ($res[codigo] == '30812' && $res[seccion] == 'F') ||
                                    ($res[codigo] == '30812' && $res[seccion] == 'I')) && $extension == 0
                            ) {
				//modificación 2020, no pagan lab...
                                $template->SetVariable(array(
                                    'preasignacion' => "0"
                                ));
                            } else {

                                $template->SetVariable(array(
                                    'preasignacion' => "0"
                                ));
                            }

                            /* $template->SetVariable(array(
                              'preasignacion' => "0"
                              )); */

                            if (count($maestria) <> 0) {

                                $template->setVariable(array(
                                    'formulario_envio' => "asignacion_semestre_ordenpago.php"
                                ));
                            } else {

                                $template->setVariable(array(
                                    'formulario_envio' => "asignacion_semestre_confirmacion.php"
                                ));
                            }
                            
                            //verificar si el curso es con solicitud de cupo extra..
                            $observacion_seccion = (in_array($res["pensum"] . "__" . $res["codigo"] . "__" . "spd_" . $res["seccion"], $enCupoExtra)) ? "CUPO - " : "";

                            $template->setVariable(array(
                                'veces_asignado' => "<div class='btn btn-info btn-xs' title='Veces asignada'>" . $veces_asignado[cantidad] . "</div>",
                                'pensum' => $res["pensum"],
                                'codigo' => $res["codigo"],
                                'asignatura' => $res["asignatura"],
                                'seccion' => $observacion_seccion . $res["seccion"],
                                'inicio' => $res["hora_ini"],
                                'fin' => $res["hora_fin"]
                            ));


                            // Verificando la existencia de algun traslape para definir la opcion de Asignacion
                            // -> Si existe traslape se desabilita el proceso de asignacion y el estudiante debera regresar al paso anterior.
                            // -> Si no existe el traslape podra asignarse sin problema las Asignaturas seleccionadas.
                            // -> Modificacion al procedimiento:
                            // 		Si existe traslape en las asignaturas estas no seran asignadas pero si se podra asignar las que no tienen traslape
                            if ($res[observacion] == 'Traslape de Horario') {
                                $traslape = true;
                            }

                            if ($traslape) {

                                $template->setVariable(array(
                                    'btn_siguiente' => "
								<script>
									function confirmar(){
										var confirmacion = confirm('Existe Traslape de Horario en las Asignaturas seleccionadas, únicamente las asignaturas sin Traslape de Horario serán asignadas.');
										if (confirmacion){
											submit();
										} else {
											return false;
										}
									}
								</script>
								<input class='btn btn-primary' type='submit' value='Asignación' OnClick='return confirmar();'>"
                                ));
                            } else {

                                $template->setVariable(array(
                                    'btn_siguiente' => "
								<script>
									function confirmar(){
										var confirmacion = confirm('¿Estoy seguro de que elegí las asignaturas y secciones que deseo cursar y estoy enterado de que no podré hacer ningún cambio a las mismas.?');
										if (confirmacion){
											submit();
										} else {
											return false;
										}
									}
								</script>
								<input class='btn btn-primary' type='submit' value='Asignación' OnClick='return confirmar();'>",
                                ));
                            }

                            if ($res[observacion] == 'Traslape de Horario') {
                                $template->setVariable(array(
                                    'observacion' => "alert-danger",
                                    'observacion_traslape' => "Traslape"
                                ));
                            } else {

                                $template->setVariable(array(
                                    'observacion' => "alert-success",
                                    'observacion_traslape' => "SinTraslape"
                                ));
                            }

                            foreach ($horarios AS $ho) {

                                $template->setVariable(array(
                                    'dia' => $ho[dia],
                                    'inicio' => $ho[hora_ini],
                                    'fin' => $ho[hora_fin]
                                ));
                                $template->parse('horarios_seleccion');
                            }
                        }
                    }

                    $template->parse('cursos_seleccionados');
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
            } else {
                $error = true;
                $mensaje = "No puedes asignarte mas de $asignaciones_permitidas cursos en este ciclo.";
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
