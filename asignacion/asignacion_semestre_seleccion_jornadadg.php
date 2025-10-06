<?php

/*
  Proceso de Asignacion para Fin de Semestre
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../config/local.php';

$array_habilitados = [
];

$array_liberar_repitencia = [
	/* 200610785, //providencia 011-2019,
	 200321108, //providencia 167-2019,
	 201122701, //providencia pendiente, dibujo constructivo con código que ya no se imparte..
	 /*201213802,
	 201131617,
	 200811035,
	 201803315,
	 201500812,
	 201025288,
	 201315170,*/
];

$habilitarCarnet = [
	/*202099999*/
];

/*
 * validacion para los dias especiales de asignacion
 */

function validarDia1($db, $carrera, $extension, $anio, $semestre, $evaluacion, $carnet)
{
	if (
		($carrera == 1 || $carrera == 3) //validar carreras habilitadas...
		&& ( // validar...
			(validarEnLimpio($db, $carnet, $carrera, $semestre)) || //opcion 1, va en limpio
			(validarEtrabajadorAutorizado($db, $extension, $anio, $semestre, $evaluacion, $carnet)) //opcion 2, tiene autorizacion por trabajar, autorizacion por la direccion de escuela
		)
	) {
		return true;
	}
	return false;
}

function validarDia2($db, $carnet, $carrera, $semestre)
{
	if (
		($carrera == 1 || $carrera == 3) //validar carreras habilitadas...
		&& ( // validar...
			(validarNivelado($db, $carnet, $carrera, /* $semestre */ 1))//opcion 1, va nivelado
		)
	) {
		return true;
	}
	return false;
}

function validarDia3($db, $carnet, $carrera, $semestre)
{
	if ($carrera == 1) {
		return true;
	}
	return false;
}

function validarDia4($db, $carnet, $carrera, $semestre)
{
	if ($carrera == 3) {
		return true;
	}
	return false;
}

function verificarSeleccionJornada($db, $carnet, $anio, $semestre, $evaluacion)
{
	$query = "
        SELECT jornada
        FROM asignacion_seleccion_disenio
        WHERE anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion AND carnet = $carnet AND activo = '1' 
            ";
			
	$result = ejecutarQuery($db, $query);
	if (count($result) > 0) {
		header("Location: ./asignacion_semestre_seleccion.dg.php?evaluacion=$evaluacion");
		exit;
	}
}

session_start();
if (isset($_SESSION[usuario])) {

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

		$db->Query("SET lc_time_names = 'es_ES'");

		// Datos de la session actual
		$extension = $_SESSION['extension'];
		$anio = $_SESSION['anio'];
		$semestre = $_SESSION['semestre'];
		$evaluacion = $_GET['evaluacion'];
		$carnet = $_SESSION['usuario'];

		if (isset($_SESSION['pensum'])) {

			// Quitando la sesion para la asignatura seleccionada.			
			unset($_SESSION['pensum']);
			unset($_SESSION['codigo']);
			unset($_SESSION['seccion']);
		} else {

			$_SESSION['evaluacion'] = $evaluacion;
		}

		// Verificacion de la inscripcion del estudiante en el ciclo actual
		$consulta = "SELECT i.carnet, IF (
                    EXISTS(
                            SELECT i2.carrera 
                            FROM inscripcion i2
                            WHERE i2.extension = i.`extension` AND i2.anio = i.`anio` AND i2.semestre = i.`semestre` AND i2.carnet = i.`carnet` AND i2.carrera > 3
                            LIMIT 1
                    ),
                            (
                                    SELECT i3.carrera 
                                    FROM inscripcion i3
                                    WHERE i3.extension = i.`extension` AND i3.anio = i.`anio` AND i3.semestre = i.`semestre` AND i3.carnet = i.`carnet`
                                    AND i3.carrera > 3
                                    LIMIT 1
                            )
                    ,i.carrera
            ) AS carrera
        FROM inscripcion i
        WHERE i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet $asignacion_restringir_carrera
        GROUP BY i.carrera";
		$inscripcion = &$db->getAll($consulta);
		if ($db->isError($inscripcion)) {
			$error = true;
			$mensaje = "Hubo un error al determinar tu inscripcion en el ciclo actual.";
			$url = "../menus/contenido.php";
		} else {
			if (empty($inscripcion)) {
				$error = true;
				$mensaje = "Hubo un error al determinar tu inscripcion en el ciclo actual, no parece estar inscrito o no es la fecha programada para su carrera.";
				$url = "../menus/contenido.php";
			}
			// Verificacion de la existencia de repitencia del estudiante
			foreach ($inscripcion as $in) {

				/**
				 * Seccion para verificar REPITENCIA...
				 */
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
				foreach ($inscripcion as $in) {
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

				if (!empty($cursos_repitencia) && !in_array($carnet, $array_liberar_repitencia)) {
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

					//$habilitarCarnet[] = verificarPrioridad($db, $in['carrera'], $extension, $anio, $semestre, $carnet);

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
					$ciclo = &$db->getRow($consulta);
					if ($db->isError($ciclo)) {
						$error = true;
						$mensaje = "Hubo un error al determinar el ciclo actual.";
						$url = "../menus/contenido.php";
					} else {

						if (count($inscripcion) == 0) {
							$error = true;
							$mensaje = "Usted no esta inscrito en el ciclo actual. Por favor verifique esta información";
							$url = "../menus/contenido.php";
						} else {

							if (count($repitencia) <> 0) {
								$aviso = true;
								$mensaje = "Por el momento no puede asignarse cursos en este ciclo, debido a la repitencia en: <br><br>";

								foreach ($repitencia as $re) {
									$mensaje = $mensaje . "$re[codigo] $re[asignatura] ($re[perdidas] veces reprobada) <br>";
								}

								if (count($inscripcion) == 2) {
									$mensaje = $mensaje . "<br>* En su caso cuenta con carrera simultanea debera solicitar la baja de la carrera donde tiene repitencia, 
                                        para poder asignarse los cursos de la carrera sin problemas<br><br>";
								}

								$url = "../menus/contenido.php";
							} else {

								// Verificacion del periodo de Asignacion.
								if ($ciclo == 0) {
									$error = true;
									$mensaje = "El período de asignación de cursos no se encuentra habilitado, por favor verifique en el calendario oficial. JDG001";
									$url = "../menus/contenido.php";
								} else {

									if ($ciclo[fecha_inicio_asignacion] > $ciclo[fecha_actual] && !in_array($carnet, $habilitarCarnet)) {
										$aviso = true;

										$mensaje = "El período de asignación de cursos no se encuentra habilitado, por favor verifique en el calendario oficial. JDG002";

										$url = "../menus/contenido.php";
									} else {

										if ($ciclo[fecha_fin_asignacion] < $ciclo[fecha_actual] && !in_array($carnet, $habilitarCarnet)) {
											$error = true;
											$mensaje = "El período de asignación de cursos no se encuentra habilitado, por favor verifique en el calendario oficial. JDG003";
											$url = "../menus/contenido.php";
										} else {

											if (($ciclo[fecha_inicio_asignacion] < $ciclo[fecha_actual] && $ciclo[fecha_fin_asignacion] > $ciclo[fecha_actual]) || in_array($carnet, $habilitarCarnet)) {
												//var_dump($ciclo[fecha_inicio_asignacion] < $ciclo[fecha_actual], $ciclo[fecha_fin_asignacion] > $ciclo[fecha_actual], in_array($carnet, $habilitarCarnet)); die;
												// Liberacion de asignaciones dependiendo del parametro a utilizar
												//if ($extension = 12) {
												//if ($extension == 0 && $in[carrera] == 1 /* || $in[carrera] > 1 */) {
												//if (($extension == 0 && $carnet >= 201700000 && $carnet <= 201799999) /* OR $extension == 12 */) {
												//if ($in[carrera] == 3){
												//if ($carnet == 9317930) {
												//if ($in[carrera] > 3 OR $extension == 12) {
												if (
													$in['carrera'] > 3 || // si es maestria permitir
														/* ($in['carrera'] == 1 && $extension == 12) */ //habilitar las asignaciones de cunoc...
													($dia_asignacion == 1 && validarDia1($db, $in['carrera'], $extension, $anio, $semestre, $evaluacion, $carnet)) || // 
													($dia_asignacion == 2 && validarDia2($db, $carnet, $in['carrera'], $semestre)) || // 
													($dia_asignacion == 3 && validarDia3($db, $carnet, $in['carrera'], $semestre)) ||
													($dia_asignacion == 4 && validarDia4($db, $carnet, $in['carrera'], $semestre))
												) {

													$carrera = $in[carrera];

													if ($carrera == 3) {

														// Consulta de cursos a Asignar en la Licenciatura en Diseño Gráfico
														$consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
													FROM curso_ciclo cc, pensum p, curso c
													WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
														cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND c.area = 5
														AND NOT EXISTS 
														(
															SELECT *
															FROM nota n
															WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9)
														) AND
														NOT EXISTS 
														(
															SELECT *
															FROM prerrequisito p
															WHERE	p.pensum = c.pensum AND p.codigo = c.codigo AND
																NOT EXISTS
																(
																	SELECT *
																	FROM curso c2
																	WHERE	c2.pensum = p.pensum_prerrequisito AND c2.codigo = p.codigo_prerrequisito AND 
																		(
																			EXISTS 
																			(
																				SELECT *
																				FROM 	nota n, ciclo cic
																				WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9) AND
																					n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																					STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																					(
																						SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																						FROM ciclo
																						WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																					)
																			) 
																			OR EXISTS
																			(
																				SELECT * 
																				FROM curso c3 
																				WHERE 	c3.pensum = c2.pensum AND c3.codigo = c2.codigo AND 
																					EXISTS 
																					(
																						SELECT *
																						FROM equivalencia e
																						WHERE e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
																							e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																					) AND NOT EXISTS
																					(
																						SELECT * 
																						FROM equivalencia e
																						WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
																							e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0)))))) AND  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																							NOT EXISTS 
																							(
																								SELECT *
																								FROM nota n, ciclo cic
																								WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
																									n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																									STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																									(
																										SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																										FROM ciclo
																										WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																									)
																							)
																					)
																			)
																			OR EXISTS
																			(
																				SELECT *
																				FROM carrera_estudiante c, curso ci
																				WHERE c.carnet = $carnet AND c.carrera = 2 AND c.fecha_cierre IS NOT NULL
																				AND c.ciclo >= 7 AND c.pensum = 20
																			)																				
																		)
																)
														)
														AND 
														(
															NOT EXISTS 
															(
																SELECT *
																FROM nota n
																WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9)
															)
															AND NOT EXISTS 
															(
																SELECT *
																FROM curso c2
																WHERE	c2.pensum = c.pensum AND c2.codigo = c.codigo AND 
																(
													
																	EXISTS 
																	(
																		SELECT *
																		FROM equivalencia e
																		WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
																			e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																	) AND NOT EXISTS 
																	(
													
																		SELECT * 
																		FROM equivalencia e
																		WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
																			e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0)))))) AND -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																			NOT EXISTS 
																			(
																				SELECT *
																				FROM nota n, ciclo cic
																				WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
																					n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																					STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																					(
																						SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																						FROM ciclo
																						WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																					)
																			)
																	)	
																)
															)
														)
														AND NOT EXISTS 
														(
															SELECT *
															FROM asignacion	a
															WHERE 	a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
																a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
														)
													ORDER BY c.ciclo,c.codigo DESC
													LIMIT 1";
														$cursos_a_asignar = $consulta_cursos_asignar;
													}

													if ($carrera == 1) {

														// Verificacion de linea de Herramientas Digitales
														// -> Para ver que linealinea del pensum ha elegido el estudiante a seguir
														$consulta = "SELECT n.codigo 
                                                                        FROM nota n
                                                                        WHERE n.codigo IN ('1.03.4','1.04.4','1.07.4','1.09.4') AND n.carnet = $carnet AND n.estado IN (1,2,9)";
														$linea_herramientas = &$db->getAll($consulta);
														if ($db->isError($linea_herramientas)) {
															$error = true;
															$mensaje = "Hubo un error al determinar la linea del pensum que ha seguido el estudiante.";
															$url = "../menus/contenido.php";
														} else {


															// Consulta de cursos a Asignar en la Licenciatura en Diseño Gráfico
															// 	-> Tomando en cuenta la nueva linea de Herramientas Digitales que el estudiante siga o no
															if (count($linea_herramientas) == 0) {

																$consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
																FROM curso_ciclo cc, pensum p, curso c
																WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
																	cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND c.area = 5
																	AND NOT EXISTS 
																	(
																		SELECT *
																		FROM nota n
																		WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9)
																	) AND
																	NOT EXISTS 
																	(
																		SELECT *
																		FROM prerrequisito p
																		WHERE	p.pensum = c.pensum AND p.codigo = c.codigo AND
																			NOT EXISTS
																			(
																				SELECT *
																				FROM curso c2
																				WHERE	c2.pensum = p.pensum_prerrequisito AND c2.codigo = p.codigo_prerrequisito AND 
																					(
																						EXISTS 
																						(
																							SELECT *
																							FROM 	nota n, ciclo cic
																							WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9) AND
																								n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																								STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																								(
																									SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																									FROM ciclo
																									WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																								)
																						) 
																						OR EXISTS
																						(
																							SELECT * 
																							FROM curso c3 
																							WHERE 	c3.pensum = c2.pensum AND c3.codigo = c2.codigo AND 
																								EXISTS 
																								(
																									SELECT *
																									FROM equivalencia e
																									WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
																										e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																								) AND NOT EXISTS
																								(
																									SELECT * 
																									FROM equivalencia e
																									WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
																										e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0)))))) AND  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																										NOT EXISTS 
																										(
																											SELECT *
																											FROM nota n, ciclo cic
																											WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
																												n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																												STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																												(
																													SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																													FROM ciclo
																													WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																												)
																										)
																								)
																						)
																					)
																			)
																	)
																	AND 
																	(
																		NOT EXISTS 
																		(
																			SELECT *
																			FROM nota n
																			WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9)
																		)
																		AND NOT EXISTS 
																		(
																			SELECT *
																			FROM curso c2
																			WHERE	c2.pensum = c.pensum AND c2.codigo = c.codigo AND 
																			(
																
																				EXISTS 
																				(
																					SELECT *
																					FROM equivalencia e
																					WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
																						e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																				) AND NOT EXISTS 
																				(
																
																					SELECT * 
																					FROM equivalencia e
																					WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
																						e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0)))))) AND -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																						NOT EXISTS 
																						(
																							SELECT *
																							FROM nota n, ciclo cic
																							WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
																								n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																								STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																								(
																									SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																									FROM ciclo
																									WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																								)
																						)
																				)	
																			)
																		)
																	)
																	AND NOT EXISTS 
																	(
																		SELECT *
																		FROM asignacion	a
																		WHERE 	a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
																			a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
																	)
																ORDER BY c.ciclo,c.codigo DESC
																LIMIT 1";
																$cursos_a_asignar = $consulta_cursos_asignar;
															} else {

																$consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
																FROM curso_ciclo cc, pensum p, curso c
																WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
																	cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND c.area = 5
                                                                                                                                        AND NOT EXISTS 
																	(
																		SELECT *
																		FROM nota n
																		WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9)
																	) AND
																	NOT EXISTS 
																	(
																		SELECT *
																		FROM prerrequisito_tmp p
																		WHERE	p.pensum = c.pensum AND p.codigo = c.codigo AND
																			NOT EXISTS
																			(
																				SELECT *
																				FROM curso c2
																				WHERE	c2.pensum = p.pensum_prerrequisito_tmp AND c2.codigo = p.codigo_prerrequisito_tmp AND 
																					(
																						EXISTS 
																						(
																							SELECT *
																							FROM 	nota n, ciclo cic
																							WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9) AND
																								n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																								STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																								(
																									SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																									FROM ciclo
																									WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																								)
																						) 
																						OR EXISTS
																						(
																							SELECT * 
																							FROM curso c3 
																							WHERE 	c3.pensum = c2.pensum AND c3.codigo = c2.codigo AND 
																								EXISTS 
																								(
																									SELECT *
																									FROM equivalencia e
																									WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
																										e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																								) AND NOT EXISTS
																								(
																									SELECT * 
																									FROM equivalencia e
																									WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
																										e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0)))))) AND  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																										NOT EXISTS 
																										(
																											SELECT *
																											FROM nota n, ciclo cic
																											WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
																												n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																												STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																												(
																													SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																													FROM ciclo
																													WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																												)
																										)
																								)
																						)
																					)
																			)
																	)
																	AND 
																	(
																		NOT EXISTS 
																		(
																			SELECT *
																			FROM nota n
																			WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9)
																		)
																		AND NOT EXISTS 
																		(
																			SELECT *
																			FROM curso c2
																			WHERE	c2.pensum = c.pensum AND c2.codigo = c.codigo AND 
																			(
																
																				EXISTS 
																				(
																					SELECT *
																					FROM equivalencia e
																					WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
																						e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																				) AND NOT EXISTS 
																				(
																
																					SELECT * 
																					FROM equivalencia e
																					WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
																						e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0)))))) AND -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
																						NOT EXISTS 
																						(
																							SELECT *
																							FROM nota n, ciclo cic
																							WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
																								n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
																								STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
																								(
																									SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
																									FROM ciclo
																									WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
																								)
																						)
																				)	
																			)
																		)
																	)
																	AND NOT EXISTS 
																	(
																		SELECT *
																		FROM asignacion	a
																		WHERE 	a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
																			a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
																	)
																ORDER BY c.ciclo,c.codigo DESC
																LIMIT 1";
																$cursos_a_asignar = $consulta_cursos_asignar;
															}
														}
													}
													/* /var_dump($cursos_a_asignar); die;
													  $cursos_asignar = & $db->getAll($cursos_a_asignar);
													  if ($db->isError($cursos_asignar)) {
													  $error = true;
													  $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
													  $url = "../menus/contenido.php";
													  }

													  if (count($cursos_asignar) <> 0) {
													  foreach ($cursos_asignar as $ca) {
													  $a[] = array("pensum" => $ca['pensum'], "anio_pensum" => $ca['anio_pensum'], "codigo" => $ca['codigo'], "nombre" => $ca['nombre']);
													  }
													  }
													 */
												} else {
													$error = true;
													$mensaje = "El período de asignación de cursos no se encuentra habilitado, por favor verifique en el calendario oficial. JDG004";
													$url = "../menus/contenido.php";
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

			//VERIFICANDO SI YA SELECCIONO JORNADA PARA ENVIAR DIRECTAMENTE A LA ASIGNACION DE CURSOS...
			verificarSeleccionJornada($db, $carnet, $anio, $semestre, $evaluacion);

			// Cargando la pagina de seleccion de Asignatura para Semestre
			$template = new HTML_Template_Sigma('../templates');
			$template->loadTemplateFile('asignacion_semestre_seleccion_jornadadg.html');

			// validar si tiene pendiente algun curso de primer anio..., restringir solo a jornada vespertina...
			$consulta = "
                SELECT *
                FROM nota n
                WHERE n.`carnet` = $carnet AND n.`codigo` IN (30111,30112,30121,30131,30132,30141)
                GROUP BY n.`codigo`
                ";
			$aprobados_primer_ciclo = &$db->getAll($consulta);
			if (count($aprobados_primer_ciclo) < 6) {
				$template->setVariable(
					array(
						"ocultar_matutina" => ''
					)
				)
				;
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