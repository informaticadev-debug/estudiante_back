<?php

/*
  Inicio de la aplicacion
  -> Datos principales para mostrar al estudiante
  -> Menu de opciones
  -> Mensajes directos
  ->
  -> Datos personales
  -> Asignaciones
  -> Estatus de inscripcion
  -> Bloque de anuncion, avisos, etc.
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

        // Datos de la session acutal
        $anio = $_GET['anio'];
        $semestre = $_GET['semestre'];
        $evaluacion = $_GET['evaluacion'];
        $pensum = $_GET['pensum'];
        $carnet = $_SESSION['usuario'];
        $codigo = $_GET['codigo'];

        // Verificacion para determinar la extension de asignaciones para el estudiante en Interciclos
        if ($evaluacion == 2) {

            $consulta = "SELECT a.extension
			FROM asignacion a
			WHERE a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion
			AND a.carnet = $carnet
			LIMIT 1";
            $ext_asignaciones = & $db->getRow($consulta);
            if ($db->isError($ext_asignaciones)) {
                $error = true;
                $mensaje = "Error al determinar la extension actual del estudiante.";
                $url = $_SERVER[HTTP_REFERER];
            }
            $extension = $ext_asignaciones[extension];
        } else {
            $extension = $_SESSION['extension'];
        }

        // Consulta de los datos de la asignatura
        $consulta = "SELECT c.codigo, TRIM(c.nombre_abreviado) AS asignatura, a.seccion, a.laboratorio, a.zona, a.final, a.nota,
		a.fecha_ingreso
		FROM asignacion a
		INNER JOIN curso c
		ON c.codigo = a.codigo AND c.pensum = a.pensum		
		WHERE /*a.extension = $extension AND*/ a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion
		AND a.pensum = $pensum AND a.codigo = '$codigo' AND a.carnet = $carnet";
	$asignatura = & $db->getRow($consulta);
        if ($db->isError($asignatura)) {
            $error = true;
            $mensaje = "Hubo un error al determinar los datos de la asignatura seleccionada.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Consulta de los datos de la seccion
            $consulta = "SELECT s.seccion, s.zona - s.laboratorio AS zona, s.examen, s.laboratorio
			FROM seccion s
			WHERE /*s.extension = $extension AND*/ s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion 
			AND s.pensum = $pensum AND s.codigo = '$codigo' AND s.seccion = '$asignatura[seccion]'";
            $datos_seccion = & $db->getRow($consulta);
            if ($db->isError($datos_seccion)) {
                $error = true;
                $mensaje = "Hubo un error al determinar los datos de la seccion de $asignatura[asignatura].";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                // Consulta del cuadro de zonas de la asignatura para el estudiante actual
                $consulta = "SELECT c.bloque, c.numero_bloque, TRIM(c.nombre) AS nombre, c.tipo_cuadro, c.ponderacion, c.seccion, 0 as es_lab
				FROM cuadro c
				WHERE /*c.extension = $extension AND*/ c.anio = $anio AND c.semestre = $semestre AND c.evaluacion = $evaluacion
				AND c.pensum = $pensum AND c.codigo = '$codigo' AND c.seccion = '$asignatura[seccion]'
				
                            UNION
                                SELECT bloque, numero_bloque * 100 as numero_bloque, CONCAT('*LABORATORIO',' - ',nombre), tipo_cuadro, ponderacion, seccion,  1 as es_lab
                                FROM cuadro_laboratorio cl
                                WHERE /*cl.extension = $extension AND*/ cl.anio = $anio AND cl.semestre = $semestre AND cl.evaluacion = $evaluacion
                                AND cl.pensum = $pensum AND cl.codigo = '$codigo' AND cl.seccion = '$asignatura[seccion]'
                            ORDER BY LENGTH(numero_bloque),numero_bloque ASC
                            ";
                $cuadro = & $db->getAll($consulta);
                if ($db->isError($cuadro)) {
                    $error = true;
                    $mensaje = "No se pudo consultar el detalle del cuadro para esta asignatura.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    $tipo_cuadro = $cuadro[0][tipo_cuadro];

                    SWITCH ($tipo_cuadro) {
                        CASE 1 : {
                                // Obtener la zona en cuadro ponderado                
                                foreach ($cuadro AS $cu) {
                                    if ($cu['es_lab'] == 1) {
                                        $consulta = "SELECT e.ponderacion as ponderacion
									FROM detalle_cuadro_laboratorio d2
									LEFT OUTER JOIN cuadro_estudiante_laboratorio e
									ON e.numero_casilla = d2.numero_casilla AND e.carnet = $carnet
									WHERE d2.bloque = $cu[bloque]";
                                    } else {
                                        $consulta = "SELECT e.ponderacion
									FROM detalle_cuadro d2
									LEFT OUTER JOIN cuadro_estudiante e
									ON e.numero_casilla = d2.numero_casilla AND e.carnet = $carnet
									WHERE d2.bloque = $cu[bloque]";
                                    }
                                    $zona = & $db->getAll($consulta);
                                    if ($db->isError($zona)) {
                                        $error = true;
                                        $mensaje = "Hubo un problema al obtener la zona del estudiante.";
                                        $url = $_SERVER[HTTP_REFERER];
                                    } else {
                                        foreach ($zona AS $zo) {
                                            $zona_sumada += $zo[ponderacion];
                                        }
                                    }
                                }
                                break;
                            }
                        CASE 2 : {
                                $zona_lab = 0;
                                // Obtener la zona en cuadro ponderado                
                                foreach ($cuadro AS $cu) {
                                    if ($cu['es_lab'] == 1) {
                                        $consulta = "SELECT AVG(e.ponderacion) * " . $cu['ponderacion'] . " / 100 as ponderacion, SUM(e.ponderacion) as suma
										FROM detalle_cuadro_laboratorio d2
										    LEFT OUTER JOIN cuadro_estudiante_laboratorio e
											ON e.numero_casilla = d2.numero_casilla AND e.carnet = $carnet
										WHERE d2.bloque = $cu[bloque]";
                                        $zona = & $db->getAll($consulta);
                                        if ($db->isError($zona)) {
                                            $error = true;
                                            $mensaje = "Hubo un problema al obtener la zona del estudiante.";
                                            $url = $_SERVER[HTTP_REFERER];
                                        } else {
                                            foreach ($zona AS $zo) {
                                            	if ($cu["tipo_cuadro"] == 1) {
                                            		$zona_lab += $zo[suma];
                                            	} else {
                                            		$zona_lab += $zo[ponderacion];
                                            	}
                                            }
                                        }
                                    }
                                }
                                // Obtener la zona en cuadro promediado
                                $consulta = "SELECT SUM(zona_casillas) AS total_zona
								FROM (
									SELECT AVG(IF(e.ponderacion IS NULL,'0',e.ponderacion))*c.ponderacion/d.ponderacion AS zona_casillas
									FROM cuadro c
									INNER JOIN detalle_cuadro d
									ON d.bloque = c.bloque
									LEFT OUTER JOIN cuadro_estudiante e
									ON e.numero_casilla = d.numero_casilla AND e.carnet = $carnet
									WHERE /*c.extension = $extension AND*/ c.anio = $anio AND c.semestre = $semestre AND c.evaluacion = $evaluacion AND c.pensum = $pensum
									AND c.codigo = '$codigo' AND c.seccion = '$asignatura[seccion]'
									GROUP BY c.numero_bloque
								) AS puntos";
                                $zona = & $db->getRow($consulta);
                                if ($db->isError($zona)) {
                                    $error = true;
                                    $mensaje = "Hubo un problema al obtener la zona del estudiante. ";
                                    $url = $_SERVER[HTTP_REFERER];
                                } else {
                                    $zona_sumada = $zona[total_zona] + $zona_lab;
                                }
                                break;
                            }
                    }
                }
            }
        }

        if (!$error) {

            // Cargando la pagina de detalle de la asignatura.
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('detalle_asignatura.html');

            // Detalle de la asignatura
            $template->setVariable(array(
                'codigo' => (isset($asignatura[codigo])) ? $asignatura[codigo] : '---',
                'asignatura' => (isset($asignatura[asignatura])) ? $asignatura[asignatura] : '---',
                'seccion' => (isset($datos_seccion[seccion])) ? $datos_seccion[seccion] : '---',
                'laboratorio' => (isset($datos_seccion[laboratorio])) ? $datos_seccion[laboratorio] : '---',
                'zona' => (isset($datos_seccion[zona])) ? $datos_seccion[zona] : '---',
                'examen' => (isset($datos_seccion[examen])) ? $datos_seccion[examen] : '---'
            ));
            $template->parse('datos_asignatura');

            // Datos del estado de la asignatura.
            if ($zona <> 0) {

                if ($tipo_cuadro == 1) {

                    // Obteniendo la nota real de la zona.
                    $suma_total = round($zona_sumada);

                    $template->setVariable(array(
                        'zona_alafecha' => "Zona a la fecha:",
                        'puntos_totales' => $suma_total . " Puntos.",
                        'nota_cuadro_promediado' => "<font id='texto-14'>Nota: La zona que ha acumulado actualmente esta promediada en base al total de casillas
							que el docente ha creado para sus calificaciones.</font>"
                    ));
                } else if ($tipo_cuadro == 2) {

                    // Obteniendo la nota real de la zona.
                    $promedio_total = round($zona_sumada);

                    $template->setVariable(array(
                        'zona_alafecha' => "Zona a la fecha:",
                        'puntos_totales' => $promedio_total . " Puntos.",
                        'nota_cuadro_promediado' => "<font id='texto-14'>Nota: La zona que ha acumulado actualmente es oficial, para correcciones por favor,
							consulde a su docente.</font>"
                    ));
                }

                // Datos de la nota en proceso si ha sido ingresada.
                if ($asignatura[fecha_ingreso] <> NULL) {

                    $template->setVariable(array(
                        'est_laboratorio' => $asignatura[laboratorio],
                        'est_zona' => $asignatura[zona],
                        'est_final' => $asignatura['final'],
                        'est_nota' => $asignatura[nota]
                    ));
                    $template->parse('nota_enproceso');
                }
                $template->parse('puntos_totales');
            }

            // Datos de la nota en proceso si ha sido ingresada en repitencia
            if ($evaluacion == 3 || $evaluacion == 4) {
                if ($asignatura[fecha_ingreso] <> NULL) {

                    $template->setVariable(array(
                        'est_laboratorio' => $asignatura[laboratorio],
                        'est_zona' => $asignatura[zona],
                        'est_final' => $asignatura['final'],
                        'est_nota' => $asignatura[nota]
                    ));
                    $template->parse('nota_enproceso');
                }
            }

            if (!empty($cuadro)) {

                // Cuadro de zonas para la asignatura.
                foreach ($cuadro AS $cu) {

                    // Bloques del cuadro.
                    $template->setVariable(array(
                        'numero_bloque' => $cu[numero_bloque],
                        'nombre_bloque' => $cu[nombre],
                        'ponderacion_bloque' => $cu[ponderacion]
                    ));

                    // Consulta para obtener las casillas de cada bloque.
                    if ($cu['es_lab'] == 1) {
                        $numero_bloque = $cu[numero_bloque] / 100;
                        $consulta = "SELECT d.bloque, d.numero_bloque * 100 as numero_bloque, d.nombre AS nombre_casilla, d.ponderacion, d.numero_casilla,
							(
								SELECT AVG(e.ponderacion) * " . $cu['ponderacion'] . " / 100 as ponderacion
									FROM detalle_cuadro_laboratorio d2
									LEFT OUTER JOIN cuadro_estudiante_laboratorio e
									ON e.numero_casilla = d2.numero_casilla AND e.carnet = $carnet
									WHERE d2.bloque = $cu[bloque]
							) AS zona_bloque,
							(
								SELECT sum(e.ponderacion)
									FROM detalle_cuadro_laboratorio d2
									LEFT OUTER JOIN cuadro_estudiante_laboratorio e
									ON e.numero_casilla = d2.numero_casilla AND e.carnet = $carnet
									WHERE d2.bloque = $cu[bloque]
							) AS zona_pro,
							(
								SELECT AVG(IF(e.ponderacion IS NULL,0,IF(e.ponderacion = '--',0,e.ponderacion)))
								FROM detalle_cuadro_laboratorio d2
								LEFT OUTER JOIN cuadro_estudiante_laboratorio e
								ON e.numero_casilla = d2.numero_casilla AND e.carnet = $carnet
								WHERE d2.bloque = d.bloque AND d2.numero_bloque = d.numero_bloque
							) AS zona_bloque_promedio
						FROM detalle_cuadro_laboratorio d
						WHERE d.bloque = $cu[bloque] AND d.numero_bloque = $numero_bloque";
                        //var_dump('<pre>'); var_dump($consulta); var_dump('</pre>'); die;
                    } else {
                        $consulta = "SELECT d.bloque, d.numero_bloque, d.nombre AS nombre_casilla, d.ponderacion, d.numero_casilla,
							(
								SELECT SUM(e.ponderacion)
								FROM detalle_cuadro d2
								LEFT OUTER JOIN cuadro_estudiante e
								ON e.numero_casilla = d2.numero_casilla AND e.carnet = $carnet
								WHERE d2.bloque = d.bloque AND d2.numero_bloque = d.numero_bloque
							) AS zona_bloque,
							(
								SELECT AVG(IF(e.ponderacion IS NULL,0,IF(e.ponderacion = '--',0,e.ponderacion)))
								FROM detalle_cuadro d2
								LEFT OUTER JOIN cuadro_estudiante e
								ON e.numero_casilla = d2.numero_casilla AND e.carnet = $carnet
								WHERE d2.bloque = d.bloque AND d2.numero_bloque = d.numero_bloque
							) AS zona_bloque_promedio
						FROM detalle_cuadro d
						WHERE d.bloque = $cu[bloque] AND d.numero_bloque = $cu[numero_bloque]";
                    }
                    $detalle_cuadro = & $db->getAll($consulta);
                    if ($db->isError($detalle_cuadro)) {
                        $error = true;
                        $mensaje = "No se ha podido obtener el detalle para los bloques de esta asignatura." . mysql_error();
                        $url = $_SERVER[HTTP_REFERER];
                    }

                    // Detalle de los bloques del cuadro de zona para la asignatura.
                    foreach ($detalle_cuadro AS $de) {

                        if ($cu[numero_bloque] == $de[numero_bloque] && $cu[bloque] == $de[bloque]) {

                            if ($tipo_cuadro == 1) {

                                $zona_bloque = number_format($de[zona_bloque], 2);
                                
                                if ($cu["es_lab"] == 1) {
                                	$zona_bloque = number_format($de[zona_pro], 2);
                                }

                                $template->setVariable(array(
                                    'nombre_casilla' => $de[nombre_casilla],
                                    'ponderacion_casilla' => $de[ponderacion] . "p.",
                                    'ponderacion_total_bloque' => "Total: " . $zona_bloque
                                ));

                                // Consulta para obtener las ponderaciones del estudiante en cada casilla
                                if ($cu['es_lab'] == 1) {
                                    $consulta = "SELECT e.ponderacion, e.numero_casilla
										FROM cuadro_estudiante_laboratorio e
										WHERE e.numero_casilla = $de[numero_casilla] AND e.carnet = $carnet";
                                } else {
                                    $consulta = "SELECT e.ponderacion, e.numero_casilla
										FROM cuadro_estudiante e
										WHERE e.numero_casilla = $de[numero_casilla] AND e.carnet = $carnet";
                                }
                                $puntos = & $db->getAll($consulta);
                                if ($db->isError($puntos)) {
                                    $error = true;
                                    $mensaje = "Hubo un error al mostrar los puntos que has obtenido en esta asignatura.";
                                    $url = $_SERVER[HTTP_REFERER];
                                }

                                // Puntos que el estudiante ha obtenido en esta asignatura
                                foreach ($puntos AS $pu) {

                                    if ($de[numero_casilla] == $pu[numero_casilla]) {

                                        $template->setVariable(array(
                                            'ponderacion' => $pu[ponderacion]
                                        ));
                                        $template->parse('puntos');
                                    }
                                }

                                $template->parse('detalle_bloque');
                            } else {

                                // Obtener nota en cuadro promediado.
                                $zona_bloque_promedio = number_format(($de[zona_bloque_promedio] * $cu[ponderacion]) / $de[ponderacion], 2);

                                $template->setVariable(array(
                                    'nombre_casilla' => $de[nombre_casilla],
                                    'ponderacion_casilla' => $de[ponderacion] . "%",
                                    'ponderacion_total_bloque' => "Parcial: " . $zona_bloque_promedio
                                ));

                                // Consulta para obtener las ponderaciones del estudiante en cada casilla
                                if ($cu['es_lab'] == 1) {
                                    $consulta = "SELECT e.ponderacion, e.numero_casilla
										FROM cuadro_estudiante_laboratorio e
										WHERE e.numero_casilla = $de[numero_casilla] AND e.carnet = $carnet";
                                } else {
                                    $consulta = "SELECT e.ponderacion, e.numero_casilla
										FROM cuadro_estudiante e
										WHERE e.numero_casilla = $de[numero_casilla] AND e.carnet = $carnet";
                                }
                                $puntos = & $db->getAll($consulta);
                                if ($db->isError($puntos)) {
                                    $error = true;
                                    $mensaje = "Hubo un error al mostrar los puntos que has obtenido en esta asignatura.";
                                    $url = $_SERVER[HTTP_REFERER];
                                }

                                // Puntos que el estudiante ha obtenido en esta asignatura
                                foreach ($puntos AS $pu) {

                                    if ($de[numero_casilla] == $pu[numero_casilla]) {

                                        $puntos_bloque = $pu[zona_bloque];

                                        $template->setVariable(array(
                                            'ponderacion' => $pu[ponderacion]
                                        ));
                                        $template->parse('puntos');
                                    }
                                }

                                $template->parse('detalle_bloque');
                            }
                        }
                    }

                    $template->parse('cuadro_asignatura');

                    if ($error) {
                        error($mensaje, $url);
                    }
                }
            } else {

                if ($evaluacion == 1 || $evaluacion == 2) {

                    // Informacion de asignatura sin cuadro creado.
                    $template->setVariable(array(
                        'sin_cuadro' => "<div id='msj_naranja'>Actualmente no se ha creado el cuadro de zonas para esta asignatura.<br>
							Puede consultar a su docente para verificar esta informaci�n.</div>"
                    ));
                } else {

                    // Informacion de asignatura sin cuadro creado.
                    $template->setVariable(array(
                        'sin_cuadro' => "<div id='msj_rojo'>Esta Asignatura no contiene detalle de cuadro ya que la zona se toma del semestre.</div>"
                    ));
                }
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
