<?php

/*
  LISTADO DE NOTAS
  -> PDF con Detalle de Notas Aprobadas segun la carrera elegida
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once "../misc/pdf/html2pdf.class.php";

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
        $carrera = $_GET[carrera];
        $carrera_reprobadas = $_POST[carrera_reprobadas];

        // utf-8
        $db->Query("SET NAMES utf8");

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
				) AS electiva, NOW() AS fecha_actual
			FROM nota n
			INNER JOIN pensum p
			ON p.carrera = $carrera
			WHERE n.pensum = p.pensum AND n.carnet = $carnet AND n.estado IN (1,2,9) AND n.aprobado = 1";
            $estadistica = & $db->getRow($consulta);
            if ($db->isError($estadistica)) {
                $error = true;
                $mensaje = "Hubo un error al obtener los datos estadisticos de las notas aprobadas.";
            } else {

                // Datos de la carrera seleccionada
                $consulta = "SELECT TRIM(c.nombre) AS nombre
				FROM carrera c
				WHERE c.carrera = $carrera";
                $carrera = & $db->getRow($consulta);
                if ($db->isError($carrera)) {
                    $error = true;
                    $mensaje = "Hubo un error al determinar los datos de la carrera seleccionada.";
                } else {

                    // Datos del estudiante
                    $consulta = "SELECT TRIM(e.nombre) AS nombre
					FROM estudiante e
					WHERE e.carnet = $carnet";
                    $estudiante = & $db->getRow($consulta);
                    if ($db->isError($estudiante)) {
                        $error = true;
                        $mensaje = "Hubo un error al determinar los datos del estudiante.";
                    } else {

                        // Detalle de Estado Primera Parte
                        $consulta = "SELECT e.estado, TRIM(e.nombre) nombre
						FROM estado e
						LIMIT 1,5";
                        $estado_1 = & $db->getAll($consulta);
                        if ($db->isError($estado)) {
                            $error = true;
                            $mensaje = "Hubo un error al determinar el detalle de los estados en las Asignaturas";
                        } else {

                            // Detalle de Estado Segunda Parte
                            $consulta = "SELECT e.estado, TRIM(e.nombre) nombre
							FROM estado e
							LIMIT 6,10";
                            $estado_2 = & $db->getAll($consulta);
                            if ($db->isError($estado)) {
                                $error = true;
                                $mensaje = "Hubo un error al determinar el detalle de los estados en las Asignaturas";
                            }
                        }
                    }
                }
            }
        }

        if (!$error) {

            ob_start();

            echo "
				<style type='text/css'>					
					#tabla_asignaturas td {
						position: relative;
						border: 1px solid #999999;
						padding: 3px;
					}
					
					#titulos {
						background-color: #F2F2F2;
						font-weight: bold;
					}
					
					#comprobante_orientacion {
						position: absolute;
						bottom: 10px;
						width: 100%;
						border-top: 2px dashed #000000;						
					}
					
					#evaluacion_1 {						
						background-color: #81BEF7;					
						border-bottom: 2px solid #0080FF;
						padding: 2px;
					}

					#evaluacion_2 {
						background-color: #FF8000;					
						border-bottom: 2px solid #B43104;						
						padding: 2px;						
					}

					#evaluacion_3 {						
						background-color: #FA5858;					
						border-bottom: 2px solid #AE4040;
						padding: 2px;
					}
					
					#evaluacion_4 {						
						background-color: #FA5858;					
						border-bottom: 2px solid #AE4040;
						padding: 2px;
					}

					#evaluacion_5 {						
						background-color: #04B431;					
						border-bottom: 2px solid #0B610B;
						padding: 2px;
					}
				</style>
			";

            // Tablas para generar PDF
            echo "<img src='../images/logofarusac.png' style='position: absolute; top: 0; width: 200px; left: 0'>";
            echo "<table border='0' align='center' style='font-size: 15px;' style='position: absolute; top: 0'>";
            echo "<tr>";
            echo "<td align='center' valing='top' width='700'><b>UNIVERSIDAD SAN CARLOS DE GUATEMALA</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center'><b>Facultad de Arquitectura</b><br><br></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center'><b>LISTADO PROVISIONAL DE NOTAS APROBADAS</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><br><b>Carrera:</b> $carrera[nombre]</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td style='text-transform: uppercase'><b>$carnet</b> $estudiante[nombre]</td>";
            echo "</tr>";
            echo "</table><br>";

            $promedio = number_format($estadistica[promedio], 2);

            echo "<table id='tabla_asignaturas' align='center'>";
            echo "<tr>";
            echo "<td><font style='font-size: 16px'>Promedio</font></td>";
            echo "<td><font style='font-size: 16px'>Creditos</font></td>";
            echo "<td rowspan='2'>";
            echo "<font style='font-size: 16px'><b>F</b> $estadistica[fundamentales]</font><br><br>";
            echo "<font style='font-size: 16px'><b>E</b> $estadistica[electivos]</font>";
            echo "</td>";
            echo "<td><font style='font-size: 16px'>Asignaturas</font></td>";
            echo "<td rowspan='2'>";
            echo "<font style='font-size: 16px'><b>F</b> $estadistica[fundamental]</font><br><br>";
            echo "<font style='font-size: 16px'><b>E</b> $estadistica[electiva]</font>";
            echo "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><font style='font-size: 40px'>$promedio</font></td>";
            echo "<td><font style='font-size: 40px'>$estadistica[creditos]</font></td>";
            echo "<td><font style='font-size: 40px'>$estadistica[asignaturas]</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='5'><font style='font-size: 12px'><b>Fecha emisi&oacute;n:</b> $estadistica[fecha_actual]</font></td>";
            echo "</tr>";
            echo "</table><br>";

            echo "<table border='0' id='tabla_asignaturas' width='700' align='center' style='font-size: 12px;'>";
            echo "<tr>";
            echo "<td id='titulos' align='center' width='10'>#</td>";
            echo "<td id='titulos' align='center' width='40'>Estado</td>";
            echo "<td id='titulos' align='center' width='40'>C&oacute;digo</td>";
            echo "<td id='titulos' align='center' width='40'>Creditos</td>";
            echo "<td id='titulos' align='center' width='40'>Caracter</td>";
            echo "<td id='titulos' align='center' width='320'>Asignatura</td>";
            echo "<td id='titulos' align='center' width='40'>Fecha</td>";
            echo "<td id='titulos' align='center' width='40'>Nota</td>";
            echo "</tr>";

            $num = 1;

            foreach ($notas_aprobadas AS $na) {

                // Notas consultadas para mostrar
                echo "<tr>";
                echo "<td align='center'>$num</td>";
                echo "<td align='center'>$na[estado]</td>";
                echo "<td align='center'>$na[codigo]</td>";
                echo "<td align='center'>$na[no_creditos]</td>";
                echo "<td align='center'>$na[caracter]</td>";
                echo "<td>$na[asignatura]</td>";
                echo "<td align='center'>$na[fecha_ciclo]</td>";

                if ($na[codigo] == '27 - 80.12' || $na[codigo] == '27 - 80.13') {
                    echo "<td align='center'>Aprobado</td>";
                } else {
                    echo "<td align='center'>$na[nota]</td>";
                }

                echo "</tr>";
                $num++;
            }

            echo "</table><br>";

            echo "<table border='0'>";
            echo "<tr>";
            echo "<td valign='top'>";
            echo "<table id='tabla_asignaturas' align='left'>";
            echo "<tr>";
            echo "<td align='center'><b>Estado</b></td>";
            echo "<td align='center'><b>Detalle</b></td>";
            echo "</tr>";

            foreach ($estado_1 AS $es1) {

                echo "<tr>";
                echo "<td align='center'>$es1[estado]</td>";
                echo "<td>$es1[nombre]</td>";
                echo "</tr>";
            }

            echo "</table>";
            echo "</td>";
            echo "<td valign='top'>";
            echo "<table id='tabla_asignaturas' align='left'>";
            echo "<tr>";
            echo "<td align='center'><b>Estado</b></td>";
            echo "<td align='center'><b>Detalle</b></td>";
            echo "</tr>";

            foreach ($estado_2 AS $es2) {

                echo "<tr>";
                echo "<td align='center'>$es2[estado]</td>";
                echo "<td>$es2[nombre]</td>";
                echo "</tr>";
            }

            echo "</table>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";

            try {

                $contenido_pdf = ob_get_clean();
                $pdf = new HTML2PDF('P', 'Letter', 'es', array(mL, mT, mR, mB));
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->WriteHTML($contenido_pdf);
                $pdf->Output("Listado_Notas_Aprobadas_" . $carnet . '.pdf', 'D');
            } catch (HTML2PDF_exception $e) {
                echo $e;
                exit;
            }
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