<?php

/*
  NOTAS
  -> Notas aprobadas
  -> Notas reprobadas
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();

verificarActualizarDatos();

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
        $carrera = $_POST[carrera];
        $carrera_reprobadas = $_POST[carrera_reprobadas];

        // Verificando las inscripciones del estudiante
        $consulta = "SELECT e.carrera, c.nombre AS nombre_carrera
		FROM carrera_estudiante e
		INNER JOIN carrera c
		ON c.carrera = e.carrera
		WHERE e.carnet = $carnet
		GROUP BY e.carrera";
        $carreras = & $db->getAll($consulta);
        if ($db->isError($carreras)) {
            $error = true;
            $mensaje = "Hubo un error al consultar la inscripcion del estudiante.";
        } else {

            if (!empty($carrera)) {

                // Consulta de las notas aprobadas en la carrera seleccionada por el estudiante
                $consulta = "SELECT n.estado, CONCAT(n.pensum, ' - ', n.codigo) AS codigo, c.no_creditos,
				c.caracter, TRIM(c.nombre_abreviado) AS asignatura, 
				IF(
					p.carrera > 3, n.fecha, CONCAT(IF(ci.fecha_mes < 10,CONCAT('0',ci.fecha_mes),ci.fecha_mes),'/',ci.fecha_anio)
				) AS fecha_ciclo,
				IF(n.estado = 2, CONCAT('EQ-',n.nota), n.nota) AS nota, TRIM(s.nombre) AS nombre_estado
				FROM nota n
				INNER JOIN pensum p
				ON p.carrera = $carrera
				INNER JOIN curso c
				ON c.codigo = n.codigo AND c.pensum = n.pensum
				INNER JOIN ciclo ci
				ON ci.anio = n.anio AND ci.semestre = n.semestre AND ci.evaluacion = n.evaluacion
				INNER JOIN estado s
				ON s.estado = n.estado
				WHERE n.pensum = p.pensum AND n.carnet = $carnet AND n.aprobado = 1 AND n.estado <> 10
				ORDER BY RIGHT(fecha_ciclo,4), LEFT(fecha_ciclo,2) ASC";
                $notas_aprobadas = & $db->getAll($consulta);
                if ($db->isError($notas_aprobadas)) {
                    $error = true;
                    $mensaje = "Hubo un error al consultar las notas aprobadas.";
                } else {

                    // Estadistica de las notas aprobadas.
                    $consulta = "SELECT
						(
							SELECT AVG(n2.nota)
							FROM nota n2
							INNER JOIN pensum p2
							ON p2.carrera = $carrera
							WHERE n2.pensum = p2.pensum AND n2.carnet = n.carnet
							AND n2.estado IN (1,2,9) AND n2.nota <> 0 AND n2.aprobado = 1 AND n2.codigo NOT IN ('80.12', '80.13')
						) AS promedio,
						(
							SELECT SUM(c2.no_creditos)							
							FROM nota n2
							INNER JOIN curso c2
							ON c2.pensum = n2.pensum AND c2.codigo = n2.codigo
							INNER JOIN pensum p2
							ON p2.carrera = $carrera
							WHERE n2.pensum = p2.pensum AND n2.carnet = n.carnet
							AND n2.estado IN (1,2,9) AND n2.aprobado = 1
						) AS creditos,
						(
							SELECT SUM(c2.no_creditos)							
							FROM nota n2
							INNER JOIN curso c2							
							ON c2.pensum = n2.pensum AND c2.codigo = n2.codigo
							INNER JOIN pensum p2
							ON p2.carrera = $carrera
							WHERE n2.pensum = p2.pensum AND n2.carnet = n.carnet AND c2.caracter = 'F'
							AND n2.estado IN (1,2,9) AND n2.aprobado = 1
						) AS fundamentales,
						(
							SELECT SUM(c2.no_creditos)							
							FROM nota n2
							INNER JOIN curso c2
							ON c2.pensum = n2.pensum AND c2.codigo = n2.codigo
							INNER JOIN pensum p2
							ON p2.carrera = $carrera
							WHERE n2.pensum = p2.pensum AND n2.carnet = n.carnet AND c2.caracter IN ('E','C')
							AND n2.estado IN (1,2,9) AND n2.aprobado = 1
						) AS electivos,
						(
							SELECT COUNT(*)
							FROM nota n2
							INNER JOIN pensum p2
							ON p2.carrera = $carrera
							WHERE n2.pensum = p2.pensum AND n2.carnet = n.carnet
							AND n2.estado IN (1,2,9) AND n2.aprobado = 1
						) AS asignaturas,
						(
							SELECT COUNT(*)
							FROM nota n2
							INNER JOIN curso c2
							ON c2.pensum = n2.pensum AND c2.codigo = n2.codigo
							INNER JOIN pensum p2
							ON p2.carrera = $carrera
							WHERE n2.pensum = p2.pensum AND n2.carnet = n.carnet AND c2.caracter = 'F'
							AND n2.estado IN (1,2,9) AND n2.aprobado = 1
						) AS fundamental,
						(
							SELECT COUNT(*)
							FROM nota n2
							INNER JOIN curso c2
							ON c2.pensum = n2.pensum AND c2.codigo = n2.codigo
							INNER JOIN pensum p2
							ON p2.carrera = $carrera
							WHERE n2.pensum = p2.pensum AND n2.carnet = n.carnet AND c2.caracter IN ('E','C')
							AND n2.estado IN (1,2,9) AND n2.aprobado = 1
						) AS electiva
					FROM nota n
					INNER JOIN pensum p
					ON p.carrera = $carrera
					WHERE n.pensum = p.pensum AND n.carnet = $carnet AND n.estado IN (1,2,9) AND n.aprobado = 1";
                    $estadistica = & $db->getRow($consulta);
                    if ($db->isError($estadistica)) {
                        $error = true;
                        $mensaje = "Hubo un error al obtener los datos estadisticos de las notas aprobadas.";
                    }
                }
            }

            if (!empty($carrera_reprobadas)) {

                // Consulta de las notas aprobadas en la carrera seleccionada por el estudiante
                $consulta = "SELECT CONCAT(a.pensum, ' - ', a.codigo) AS codigo, c.no_creditos,
				c.caracter, TRIM(c.nombre) AS asignatura, 
				IF(
					p.carrera > 3, CONCAT(s.mes, '/', s.anio), CONCAT(IF(ci.fecha_mes < 10,CONCAT('0',ci.fecha_mes),ci.fecha_mes),'/',ci.fecha_anio)
				) AS fecha_ciclo, a.nota
				FROM asignacion a
				INNER JOIN pensum p
				ON p.carrera = $carrera_reprobadas
				INNER JOIN curso c
				ON c.codigo = a.codigo AND c.pensum = a.pensum
				INNER JOIN ciclo ci
				ON ci.anio = a.anio AND ci.semestre = a.semestre AND ci.evaluacion = a.evaluacion
				INNER JOIN seccion s
				ON s.anio = a.anio AND s.semestre = a.semestre AND s.evaluacion = a.evaluacion AND s.pensum = a.pensum
				AND s.codigo = a.codigo AND s.seccion = a.seccion
				WHERE a.pensum = p.pensum AND a.carnet = $carnet AND a.aprobado = 0 AND a.nota_oficial = 1 AND a.status = 1
				AND NOT EXISTS(
					SELECT n.nota
					FROM nota n
					WHERE n.codigo = a.codigo AND n.pensum = a.pensum AND n.carnet = a.carnet
					AND n.nota = a.nota AND n.aprobado = 1
				)
				ORDER BY RIGHT(fecha_ciclo,4), LEFT(fecha_ciclo,2) ASC";
                $notas_reprobadas = & $db->getAll($consulta);
                //var_dump($consulta); die;
                if ($db->isError($notas_reprobadas)) {
                    $error = true;
                    $mensaje = "Hubo un error al consultar las notas reprobadas." . mysql_error();
                }
            }
        }

        if (!$error) {

            // Cargar la pagina para ver las notas aprobadas
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('notas_aprobadas.html');

            // Carreras cursadas por el estudiante
            foreach ($carreras AS $ca) {

                $template->setVariable(array(
                    'carrera' => $ca[carrera],
                    'nombre_carrera' => $ca[nombre_carrera]
                ));
                $template->parse('carreras_cursadas_na');
            }

            foreach ($carreras AS $ca) {

                $template->setVariable(array(
                    'carrera' => $ca[carrera],
                    'nombre_carrera' => $ca[nombre_carrera]
                ));
                $template->parse('carreras_cursadas_nr');
            }

            // Notas Aprobadas
            if (!empty($carrera)) {

                $num = 1;

                foreach ($notas_aprobadas AS $na) {

                    $template->setVariable(array(
                        'numeracion' => $num,
                        'estado' => $na[estado],
                        'nombre_estado' => $na[nombre_estado],
                        'codigo' => $na[codigo],
                        'creditos' => $na[no_creditos],
                        'caracter' => $na[caracter],
                        'asignatura' => $na[asignatura],
                        'fecha_ciclo' => $na[fecha_ciclo],
                        'nota' => $na[nota]
                    ));
                    $template->parse('notas_aprobadas');

                    $num++;
                }

                // Datos estadisticos

                $promedio = number_format($estadistica[promedio], 2);

                $template->setVariable(array(
                    'carrera' => $carrera,
                    'promedio' => $promedio,
                    'total_creditos' => $estadistica[creditos],
                    'fundamentales' => $estadistica[fundamentales],
                    'electivos' => $estadistica[electivos],
                    'asignaturas' => $estadistica[asignaturas],
                    'fundamental' => $estadistica[fundamental],
                    'electiva' => $estadistica[electiva],
                ));
                $template->parse('estadistica_notas');
            }

            // Notas Aprobadas
            if (!empty($carrera_reprobadas)) {

                $num = 1;

                foreach ($notas_reprobadas AS $nr) {

                    // Notas reporbadas
                    $template->setVariable(array(
                        'numeracion' => $num,
                        'estado' => $nr[estado],
                        'codigo' => $nr[codigo],
                        'creditos' => $nr[no_creditos],
                        'caracter' => $nr[caracter],
                        'asignatura' => $nr[asignatura],
                        'fecha_ciclo' => $nr[fecha_ciclo],
                        'nota' => $nr[nota]
                    ));
                    $template->parse('notas_reprobadas');

                    $num++;
                }
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
