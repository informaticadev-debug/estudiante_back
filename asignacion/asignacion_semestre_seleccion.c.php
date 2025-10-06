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

session_start();

$array_liberar_repitencia = [
 /*   200610785, //providencia 011-2019,
    200321108, //providencia 167-2019,
    201122701, //providencia pendiente, dibujo constructivo con código que ya no se imparte..
    /*
	-- autorización Dirección, 2020-1
    201213802,
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

function validarDia1($db, $carrera, $extension, $anio, $semestre, $evaluacion, $carnet) {
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

function validarDia2($db, $carnet, $carrera, $semestre) {
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

function validarDia3($db, $carnet, $carrera, $semestre) {
    if ($carrera == 1) {
        return true;
    }
    return false;
}

function validarDia4($db, $carnet, $carrera, $semestre) {
    if ($carrera == 3) {
        return true;
    }
    return false;
}

function validarDia5($db, $carnet, $carrera, $semestre) {
    if (($carrera == 1 || $carrera == 3) && $carnet > 202000000) {
        return true;
    }
    return false;
}

function validarDia100($db, $extension, $carnet, $carrera, $semestre) {
    if ($extension == 12) {
        return true;
    }
    return false;
}

function getSecciones($db, $extension, $anio, $semestre, $evaluacion, $jornada, $pensum, $codigo) {
    if ($jornada != false && $extension == 0 && ($pensum == 5 || $pensum == 20)) {
        // Verificacion de secciones disponibles para asignacion.
        $consulta = "SELECT s.pensum, s.codigo, s.seccion
                            FROM seccion s
                            INNER JOIN horario h
                                ON h.extension = s.extension AND h.anio = s.anio AND h.semestre = s.semestre AND h.evaluacion = s.evaluacion AND h.pensum = s.pensum
                                AND h.codigo = s.codigo AND h.seccion = s.seccion
                            INNER JOIN pensum p
                                ON p.pensum = s.pensum
                            INNER JOIN periodo_ciclo c
                                ON c.extension = s.extension AND c.carrera = p.carrera AND c.anio = s.anio AND c.semestre = s.semestre AND c.evaluacion = s.evaluacion
                                AND c.periodo = h.periodo AND c.jornada = '$jornada'
                            WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
                                AND s.pensum = $pensum AND s.codigo = '$codigo' AND s.status = 'A'
                                AND s.cupo > (SELECT COUNT(*)
                                    FROM asignacion a
                                    WHERE a.extension = s.extension AND a.anio = s.anio AND a.semestre = s.semestre AND a.evaluacion = s.evaluacion
                                    AND a.pensum = s.pensum AND a.codigo = s.codigo AND a.seccion = s.seccion AND a.status = 1
                                )
                            GROUP BY s.seccion";
    } else {
        if ($extension == 0) {
            // Verificacion de secciones disponibles para asignacion.
            $consulta = "SELECT s.pensum, s.codigo, s.seccion
                                FROM seccion s
                                WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
                                    AND s.pensum = $pensum AND s.codigo = '$codigo' AND s.status = 'A' AND s.fecha_ingreso_enlinea IS NULL
                                    AND s.cupo > (SELECT COUNT(*)
                                        FROM asignacion a
                                        WHERE a.extension = s.extension AND a.anio = s.anio AND a.semestre = s.semestre AND a.evaluacion = s.evaluacion
                                        AND a.pensum = s.pensum AND a.codigo = s.codigo AND a.seccion = s.seccion AND a.status = 1
                                    ) 
                                GROUP BY s.seccion";
        } else {
            $consulta = "SELECT s.pensum, s.codigo, s.seccion
                            FROM seccion s
                            WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
                                AND s.pensum = $pensum AND s.codigo = '$codigo' AND s.status = 'A' AND s.fecha_ingreso_enlinea IS NULL
                                AND s.cupo > (SELECT COUNT(*)
                                    FROM asignacion a
                                    WHERE a.extension = s.extension AND a.anio = s.anio AND a.semestre = s.semestre AND a.evaluacion = s.evaluacion
                                    AND a.pensum = s.pensum AND a.codigo = s.codigo AND a.seccion = s.seccion AND a.status = 1
                                )";
        }
    }
    $data = ejecutarQuery($db, $consulta);
    if (empty($data) && $jornada != false) {
        //$data2 = getSecciones($db, $extension, $anio, $semestre, $evaluacion, false, $pensum, $codigo);
        /*
         * SEGUN EL REQUERIMIENTO DE DIRECCION DE ESCUELA, YA NO HABILITAR LA OTRA JORNADA + SPD, AHORA SOLO LA OTRA JORNADA...
         * if (!empty($data2)) {
          $data2[] = ["pensum" => $pensum, "codigo" => $codigo, "seccion" => "SPD"];
          } */
        //return $data2;
        return $data;
    }
    return (!empty($data)) ? $data : [];
}

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

        //$db->Query("SET lc_time_names = 'es_ES'");
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
				WHERE i2.extension = i.`extension` AND i2.anio = i.`anio` AND i2.semestre = i.`semestre` AND i2.carnet = i.`carnet` AND i2.carrera > 3 AND i2.carrera = i.`carrera`
				LIMIT 1
			),
			(
				SELECT i3.carrera 
				FROM inscripcion i3
				WHERE i3.extension = i.`extension` AND i3.anio = i.`anio` AND i3.semestre = i.`semestre` AND i3.carnet = i.`carnet` AND i3.carrera > 3 AND i3.carrera = i.`carrera`
				LIMIT 1
			)
			,i.carrera
		) AS carrera
        FROM inscripcion i
			INNER JOIN carrera_estudiante ce on ce.carrera = i.carrera and ce.carnet = i.carnet and ce.fecha_cierre IS NULL
        WHERE i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet $asignacion_restringir_carrera
        GROUP BY i.carrera";
        //if ($carnet == 202099999) {var_dump($consulta); die;}
        $inscripcion = & $db->getAll($consulta);
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
            //if($carnet = 201530100){var_dump($consulta); die; var_dump($extension)}
            foreach ($inscripcion AS $in) {
                //SI ES DE PREGRADO, VERIFICAR QUE YA SELECCIONO JORNADA, si no es asi redirigir..
                //Ya no verificar jornada..
                //$carrera = $in[carrera];
                //if ($extension == 0 && in_array($carrera, [1, 3]) && !getJornadaSeleccionada($db, $extension, $anio, $semestre, $evaluacion, $carnet)) {
                //    header("Location: ./asignacion_semestre_seleccion" . (($carrera == 1) ? '_diseno' : '_jornadadg') . ".php?evaluacion=$evaluacion");
                //    exit;
                //}

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
                    $ciclo = & $db->getRow($consulta);
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

                                foreach ($repitencia AS $re) {
                                    $mensaje = $mensaje . "$re[codigo] $re[asignatura] ($re[perdidas] veces reprobada) <br>";
                                }

                                if (count($inscripcion) == 2) {
                                    $mensaje = $mensaje . "<br>* En su caso cuenta con carrera simultanea, deberá solicitar la baja de la carrera donde tiene repitencia, 
                                        para poder asignarse los cursos de la carrera sin problemas<br><br>";
                                }

                                $url = "../menus/contenido.php";
                            } else {

                                // Verificacion del periodo de Asignacion.
                                if ($ciclo == 0) {
                                    $error = true;
                                    $mensaje = "El período de asignación de cursos no se encuentra habilitado, por favor verifique en el calendario oficial. E01.";
                                    $url = "../menus/contenido.php";
                                } else {

                                    if ($ciclo[fecha_inicio_asignacion] > $ciclo[fecha_actual] && !in_array($carnet, $habilitarCarnet)) {
                                        $aviso = true;

                                        $mensaje = "El período de asignación de cursos no se encuentra habilitado, por favor verifique en el calendario oficial. E02.";

                                        $url = "../menus/contenido.php";
                                    } else {

                                        if ($ciclo[fecha_fin_asignacion] < $ciclo[fecha_actual] && !in_array($carnet, $habilitarCarnet)) {
                                            $error = true;
                                            $mensaje = "El período de asignación de cursos no se encuentra habilitado, por favor verifique en el calendario oficial. E03.";
                                            $url = "../menus/contenido.php";
                                        } else {
//if ($carnet == 59) {var_dump($consulta); die;}
                                            if (($ciclo[fecha_inicio_asignacion] < $ciclo[fecha_actual] && $ciclo[fecha_fin_asignacion] > $ciclo[fecha_actual]) || in_array($carnet, $habilitarCarnet)) {

                                                if (
                                                        $in['carrera'] > 3 || // si es maestria permitir
                                                        /* ($in['carrera'] == 1 && $extension == 12) */ //habilitar las asignaciones de cunoc...
                                                        ($dia_asignacion == 1 && validarDia1($db, $in['carrera'], $extension, $anio, $semestre, $evaluacion, $carnet)) ||
                                                        ($dia_asignacion == 2 && validarDia2($db, $carnet, $in['carrera'], $semestre)) ||
                                                        ($dia_asignacion == 3 && validarDia3($db, $carnet, $in['carrera'], $semestre)) ||
                                                        ($dia_asignacion == 4 && validarDia4($db, $carnet, $in['carrera'], $semestre)) ||
                                                        ($dia_asignacion == 5 && validarDia5($db, $carnet, $in['carrera'], $semestre)) ||
                                                        ($dia_asignacion == 100 && validarDia100($db, $extension, $carnet, $in['carrera'], $semestre))
                                                ) {
                                                    $carrera = $in["carrera"];
                                                    if ($carrera == 3) {

                                                        // Consulta de cursos a Asignar en la Licenciatura en Diseño Gráfico
                                                        $consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre,
(SELECT GROUP_CONCAT(seccion) FROM seccion WHERE STATUS = 'A' AND extension = cc.extension AND anio = cc.anio AND semestre = cc.semestre AND evaluacion = cc.evaluacion AND pensum = cc.pensum AND codigo = cc.codigo GROUP BY cc.codigo) AS secciones,
(SELECT GROUP_CONCAT(secx.seccion)
FROM seccion secx
    INNER JOIN (SELECT * FROM horario WHERE extension = $extension AND anio = $anio AND evaluacion = $evaluacion AND semestre = $semestre GROUP BY extension, anio, semestre, evaluacion, pensum, codigo, seccion) AS hx ON
        hx.`extension` = secx.`extension` AND hx.`anio` = secx.`anio` AND hx.`semestre` = secx.`semestre` AND hx.`evaluacion` = secx.`evaluacion`
        AND hx.`pensum` = secx.`pensum` AND hx.`codigo` = secx.`codigo` AND hx.`seccion` = secx.`seccion`
    INNER JOIN pensum px ON px.pensum = secx.`pensum`
    INNER JOIN periodo_ciclo pcx ON pcx.`extension` = secx.`extension` AND pcx.`anio` = secx.`anio` AND pcx.`semestre` = secx.`semestre` AND pcx.`evaluacion` = secx.`evaluacion`
        AND pcx.carrera = px.`carrera` AND pcx.`periodo` = hx.periodo
WHERE secx.`extension` = $extension AND secx.`anio` = $anio AND secx.`semestre` = $semestre AND secx.`evaluacion` = $evaluacion AND secx.`pensum` = cc.pensum AND secx.codigo = cc.codigo AND pcx.`jornada` = 'V' and secx.status = 'A'
GROUP BY secx.`codigo`) as secciones_v,
(SELECT GROUP_CONCAT(secx.seccion)
FROM seccion secx
    INNER JOIN (SELECT * FROM horario WHERE extension = $extension AND anio = $anio AND evaluacion = $evaluacion AND semestre = $semestre GROUP BY extension, anio, semestre, evaluacion, pensum, codigo, seccion) AS hx ON
        hx.`extension` = secx.`extension` AND hx.`anio` = secx.`anio` AND hx.`semestre` = secx.`semestre` AND hx.`evaluacion` = secx.`evaluacion`
        AND hx.`pensum` = secx.`pensum` AND hx.`codigo` = secx.`codigo` AND hx.`seccion` = secx.`seccion`
    INNER JOIN pensum px ON px.pensum = secx.`pensum`
    INNER JOIN periodo_ciclo pcx ON pcx.`extension` = secx.`extension` AND pcx.`anio` = secx.`anio` AND pcx.`semestre` = secx.`semestre` AND pcx.`evaluacion` = secx.`evaluacion`
        AND pcx.carrera = px.`carrera` AND pcx.`periodo` = hx.periodo
WHERE secx.`extension` = $extension AND secx.`anio` = $anio AND secx.`semestre` = $semestre AND secx.`evaluacion` = $evaluacion AND secx.`pensum` = cc.pensum AND secx.codigo = cc.codigo AND pcx.`jornada` = 'M' and secx.status = 'A'
GROUP BY secx.`codigo`) as secciones_m
													FROM curso_ciclo cc, pensum p, curso c
													WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
														cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND
														NOT EXISTS 
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
                                                                                                                AND NOT EXISTS 
														(
															SELECT *
															FROM asignacion_spd	a
															WHERE 	a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
																a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
														)
													ORDER BY c.ciclo,c.codigo";
                                                        $cursos_a_asignar = $consulta_cursos_asignar;
                                                        if ($carnet == 200314337) {
                                                            //print_r($consulta_cursos_asignar); die;
                                                        }
                                                    }

                                                    if ($carrera == 1) {
                                                        // Verificacion de linea de Herramientas Digitales
                                                        // -> Para ver que linealinea del pensum ha elegido el estudiante a seguir
                                                        $consulta = "SELECT n.codigo 
                                                        FROM nota n
                                                        WHERE n.codigo IN ('1.03.4','1.04.4','1.07.4','1.09.4') AND n.carnet = $carnet AND n.estado IN (1,2,9)";
                                                        $linea_herramientas = & $db->getAll($consulta);
                                                        if ($db->isError($linea_herramientas)) {
                                                            $error = true;
                                                            $mensaje = "Hubo un error al determinar la linea del pensum que ha seguido el estudiante.";
                                                            $url = "../menus/contenido.php";
                                                        } else {

                                                            // Consulta de cursos a Asignar en la Licenciatura en Diseño Gráfico
                                                            // 	-> Tomando en cuenta la nueva linea de Herramientas Digitales que el estudiante siga o no
                                                            if (count($linea_herramientas) == 0 || true) {

                                                                $consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre,
(SELECT GROUP_CONCAT(seccion) FROM seccion WHERE STATUS = 'A' AND extension = cc.extension AND anio = cc.anio AND semestre = cc.semestre AND evaluacion = cc.evaluacion AND pensum = cc.pensum AND codigo = cc.codigo GROUP BY cc.codigo) AS secciones,
(SELECT GROUP_CONCAT(secx.seccion)
FROM seccion secx
    INNER JOIN (SELECT * FROM horario WHERE extension = $extension AND anio = $anio AND evaluacion = $evaluacion AND semestre = $semestre GROUP BY extension, anio, semestre, evaluacion, pensum, codigo, seccion) AS hx ON
        hx.`extension` = secx.`extension` AND hx.`anio` = secx.`anio` AND hx.`semestre` = secx.`semestre` AND hx.`evaluacion` = secx.`evaluacion`
        AND hx.`pensum` = secx.`pensum` AND hx.`codigo` = secx.`codigo` AND hx.`seccion` = secx.`seccion`
    INNER JOIN pensum px ON px.pensum = secx.`pensum`
    INNER JOIN periodo_ciclo pcx ON pcx.`extension` = secx.`extension` AND pcx.`anio` = secx.`anio` AND pcx.`semestre` = secx.`semestre` AND pcx.`evaluacion` = secx.`evaluacion`
        AND pcx.carrera = px.`carrera` AND pcx.`periodo` = hx.periodo
WHERE secx.`extension` = $extension AND secx.`anio` = $anio AND secx.`semestre` = $semestre AND secx.`evaluacion` = $evaluacion AND secx.`pensum` = cc.pensum AND secx.codigo = cc.codigo AND pcx.`jornada` = 'V' and secx.status = 'A'
GROUP BY secx.`codigo`) as secciones_v,
(SELECT GROUP_CONCAT(secx.seccion)
FROM seccion secx
    INNER JOIN (SELECT * FROM horario WHERE extension = $extension AND anio = $anio AND evaluacion = $evaluacion AND semestre = $semestre GROUP BY extension, anio, semestre, evaluacion, pensum, codigo, seccion) AS hx ON
        hx.`extension` = secx.`extension` AND hx.`anio` = secx.`anio` AND hx.`semestre` = secx.`semestre` AND hx.`evaluacion` = secx.`evaluacion`
        AND hx.`pensum` = secx.`pensum` AND hx.`codigo` = secx.`codigo` AND hx.`seccion` = secx.`seccion`
    INNER JOIN pensum px ON px.pensum = secx.`pensum`
    INNER JOIN periodo_ciclo pcx ON pcx.`extension` = secx.`extension` AND pcx.`anio` = secx.`anio` AND pcx.`semestre` = secx.`semestre` AND pcx.`evaluacion` = secx.`evaluacion`
        AND pcx.carrera = px.`carrera` AND pcx.`periodo` = hx.periodo
WHERE secx.`extension` = $extension AND secx.`anio` = $anio AND secx.`semestre` = $semestre AND secx.`evaluacion` = $evaluacion AND secx.`pensum` = cc.pensum AND secx.codigo = cc.codigo AND pcx.`jornada` = 'M' and secx.status = 'A'
GROUP BY secx.`codigo`) as secciones_m
																FROM curso_ciclo cc, pensum p, curso c
																WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
																	cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND
																	NOT EXISTS 
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
                                                                                                                                        AND NOT EXISTS 
																	(
																		SELECT *
																		FROM asignacion_spd	a
																		WHERE 	a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
																			a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
																	)
																ORDER BY c.ciclo,c.codigo";


                                                                $cursos_a_asignar = $consulta_cursos_asignar;
                                                            } else {


                                                                $consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre,
(SELECT GROUP_CONCAT(seccion) FROM seccion WHERE STATUS = 'A' AND extension = cc.extension AND anio = cc.anio AND semestre = cc.semestre AND evaluacion = cc.evaluacion AND pensum = cc.pensum AND codigo = cc.codigo GROUP BY cc.codigo) AS secciones,
(SELECT GROUP_CONCAT(secx.seccion)
FROM seccion secx
    INNER JOIN (SELECT * FROM horario WHERE extension = $extension AND anio = $anio AND evaluacion = $evaluacion AND semestre = $semestre GROUP BY extension, anio, semestre, evaluacion, pensum, codigo, seccion) AS hx ON
        hx.`extension` = secx.`extension` AND hx.`anio` = secx.`anio` AND hx.`semestre` = secx.`semestre` AND hx.`evaluacion` = secx.`evaluacion`
        AND hx.`pensum` = secx.`pensum` AND hx.`codigo` = secx.`codigo` AND hx.`seccion` = secx.`seccion`
    INNER JOIN pensum px ON px.pensum = secx.`pensum`
    INNER JOIN periodo_ciclo pcx ON pcx.`extension` = secx.`extension` AND pcx.`anio` = secx.`anio` AND pcx.`semestre` = secx.`semestre` AND pcx.`evaluacion` = secx.`evaluacion`
        AND pcx.carrera = px.`carrera` AND pcx.`periodo` = hx.periodo
WHERE secx.`extension` = $extension AND secx.`anio` = $anio AND secx.`semestre` = $semestre AND secx.`evaluacion` = $evaluacion AND secx.`pensum` = cc.pensum AND secx.codigo = cc.codigo AND pcx.`jornada` = 'V' and secx.status = 'A'
GROUP BY secx.`codigo`) as secciones_v,
(SELECT GROUP_CONCAT(secx.seccion)
FROM seccion secx
    INNER JOIN (SELECT * FROM horario WHERE extension = $extension AND anio = $anio AND evaluacion = $evaluacion AND semestre = $semestre GROUP BY extension, anio, semestre, evaluacion, pensum, codigo, seccion) AS hx ON
        hx.`extension` = secx.`extension` AND hx.`anio` = secx.`anio` AND hx.`semestre` = secx.`semestre` AND hx.`evaluacion` = secx.`evaluacion`
        AND hx.`pensum` = secx.`pensum` AND hx.`codigo` = secx.`codigo` AND hx.`seccion` = secx.`seccion`
    INNER JOIN pensum px ON px.pensum = secx.`pensum`
    INNER JOIN periodo_ciclo pcx ON pcx.`extension` = secx.`extension` AND pcx.`anio` = secx.`anio` AND pcx.`semestre` = secx.`semestre` AND pcx.`evaluacion` = secx.`evaluacion`
        AND pcx.carrera = px.`carrera` AND pcx.`periodo` = hx.periodo
WHERE secx.`extension` = $extension AND secx.`anio` = $anio AND secx.`semestre` = $semestre AND secx.`evaluacion` = $evaluacion AND secx.`pensum` = cc.pensum AND secx.codigo = cc.codigo AND pcx.`jornada` = 'M' and secx.status = 'A'
GROUP BY secx.`codigo`) as secciones_m
																FROM curso_ciclo cc, pensum p, curso c
																WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
																	cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND
																	NOT EXISTS 
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
                                                                                                                                        AND NOT EXISTS 
																	(
																		SELECT *
																		FROM asignacion_spd	a
																		WHERE 	a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
																			a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
																	)
																ORDER BY c.ciclo,c.codigo";


                                                                $cursos_a_asignar = $consulta_cursos_asignar;
                                                            }
                                                        }
                                                    }
                                                    $cursos_asignar = & $db->getAll($cursos_a_asignar);
                                                    //print_r($cursos_asignar); die;
                                                    if ($db->isError($cursos_asignar)) {
                                                        //var_dump($cursos_a_asignar); die;    
                                                        $error = true;
                                                        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.<!-- " . $cursos_a_asignar . "-->";
                                                        $url = "../menus/contenido.php";
                                                    }

                                                    if (count($cursos_asignar) <> 0) {
                                                        foreach ($cursos_asignar as $ca) {
                                                            $a[] = array("pensum" => $ca['pensum'], "anio_pensum" => $ca['anio_pensum'], "codigo" => $ca['codigo'], "nombre" => $ca['nombre'], "secciones" => $ca["secciones"], "secciones_m" => $ca["secciones_m"], "secciones_v" => $ca["secciones_v"]);
                                                        }
                                                    }
                                                } else {
                                                    $error = true;
                                                    $mensaje = "El período de asignación de cursos no se encuentra habilitado, por favor verifique en el calendario oficial. E04.";
                                                    $url = "../menus/contenido.php";
                                                }
                                            } else {
                                                $error = true;
                                                $mensaje = "El sistema no se encuentra habilitado en estos momentos.";
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

        if (!$error && !$aviso) {

            // Cargando la pagina de seleccion de Asignatura para Semestre
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('asignacion_semestre_seleccion.html');

            if (!empty($a)) {
                $determinar_jornada = false; //getJornadaSeleccionada($db, $extension, $anio, $semestre, $evaluacion, $carnet);
                //var_dump($a); die;
                foreach ($a AS $cu) {
                    if (in_array($cu["codigo"], [31021, 31041])) {
                        continue;
                    }
                    /*
                     * YA NO ES NECESARIO VERIFICAR JORNADAS NI EXCLUIR DISEÑOS..
                     * if (verificarEnSPD($db, $extension, $anio, $semestre, $evaluacion, $carnet, $cu['pensum'], $cu['codigo']) || in_array($cu['codigo'], ['1.01.1', '1.02.1', '1.03.1', '1.04.1', '1.05.1', '1.06.1', '1.07.1', '1.08.1', '1.09.1'])) {
                      continue;
                      }
                     */
                    // obtener secciones sin importar criterios (SPD, disponibles, etc...)
                    //$seccion = getSecciones($db, $extension, $anio, $semestre, $evaluacion, $determinar_jornada, $cu["pensum"], $cu["codigo"]);
                    $seccion = getSecciones($db, $extension, $anio, $semestre, $evaluacion, false, $cu[pensum], $cu[codigo]);
                    // Lista de cursos aperturados.
                    $template->setVariable(array(
                        'anio_pensum' => $cu[anio_pensum],
                        'pensum' => $cu[pensum],
                        'codigo' => $cu[codigo],
                        'asignatura' => $cu[nombre]
                    ));

                    if (!empty($seccion)) {

                        foreach ($seccion AS $se) {

                            if ($se[codigo] == $cu[codigo]) {

                                $template->setVariable(array(
                                    'pensum_sec' => $se["pensum"],
                                    'codigo_sec' => $se["codigo"],
                                    'seccion_sec' => $se["seccion"],
                                    'seccion_sec_desc' => $se["seccion"]
                                ));
                                $template->parse('secciones_disponibles');
                            }
                        }
                    } else {
                        /* $seccionesCupo = "";
                          if ($determinar_jornada == 'M') {
                          $seccionesCupo = $cu["secciones_m"];
                          } else if ($determinar_jornada == 'V') {
                          $seccionesCupo = $cu["secciones_v"];
                          }
                          $seleccionesCupo = $cu["secciones"];
                          foreach (explode(",", $seccionesCupo) as $seccionCE) {
                          $template->setVariable(array(
                          'pensum_sec' => $cu["pensum"],
                          'codigo_sec' => $cu["codigo"],
                          'seccion_sec' => 'spd_' . $seccionCE,
                          'seccion_sec_desc' => 'Cupo - ' . $seccionCE,
                          ));
                          $template->parse('secciones_disponibles');
                          } */
                        $template->setVariable(array(
                            'pensum_sec' => $cu["pensum"],
                            'codigo_sec' => $cu["codigo"],
                            'seccion_sec' => '--',
                            'seccion_sec_desc' => '--',
                        ));
                        $template->parse('secciones_disponibles');
                    }

                    $template->parse('seleccion_asignaturas');

                    if ($error) {
                        error($mensaje, $url);
                    }
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
