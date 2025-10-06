<?php

/*
  Carta de retiro de Asignaturas
  -> Verificacion de Des-Asignaciones anteriores
  -> Listado de asignaturas con derecho a Des-Asignacion
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
        errorLogin($mensaje);
    } else {

        $db->setFetchMode(DB_FETCHMODE_ASSOC);

        $db->Query("SET names utf8");

        // Datos de la session acutal
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carnet = $_SESSION['usuario'];
        $evaluacion = $_POST['evaluacion'];
        $codigo = $_POST['codigo'];

        // Asignaturas seleccionadas
        if (!empty($codigo)) {

            // Datos del Estudiante
            $consulta = "SELECT e.carnet, TRIM(e.nombre) AS nombre, e.celular, e.email_fda
			FROM estudiante e
			WHERE e.carnet = $carnet";
            $estudiante = & $db->getRow($consulta);
            if ($db->isError($estudiante)) {
                $error = true;
                $mensaje = "Hubo un error al obtener los datos del estudiante.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                // Datos del ciclo actual
                $consulta = "SELECT s.nombre, NOW() AS fecha
				FROM semestre s
				WHERE s.semestre = $semestre";
                $ciclo = & $db->getRow($consulta);
                if ($db->isError($ciclo)) {
                    $error = true;
                    $mensaje = "Hubo un error al determinar el ciclo actual.";
                    $url = $_SERVER[HTTP_REFERER];
                }
            }
            foreach ($codigo AS $co) {

                // Verificacion que no exista Des-Asignaciones de las Asignaturas seleccionadas.
                $consulta = "SELECT a.codigo, TRIM(c.nombre) AS asignatura, CONCAT(a.semestre, '-', a.anio) AS ciclo
				FROM asignacion a
				INNER JOIN curso c 
				ON c.codigo = a.codigo AND c.pensum = a.pensum
				WHERE a.codigo = '$co' AND a.status = 0 AND a.carnet = $carnet";
                $desasignacion = & $db->getRow($consulta);
                if ($db->isError($desasignacion)) {
                    $error = true;
                    $mensaje = "Hubo un error al verificar las Des-Asignaciones anteriores.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    // Asignaturas con Des-Asignacion anterior.
                    if ($desasignacion <> 0) {

                        $desasignados[] = array(
                            'codigo' => $desasignacion[codigo],
                            'asignatura' => $desasignacion[asignatura],
                            'ciclo' => $desasignacion[ciclo]
                        );
                    }

                    if ($desasignacion == 0) {

                        $num = & $db->Query("SET @rownum = 0");

                        // Datos de las asignaturas para generar solicitud de Des-Asigancion
                        $consulta = "SELECT a.codigo, TRIM(c.nombre) AS asignatura, a.seccion, CONCAT(TRIM(d.apellido), ', ', TRIM(d.nombre)) AS docente
						FROM asignacion a
						INNER JOIN curso c
						ON c.codigo = a.codigo AND c.pensum = a.pensum
						INNER JOIN staff s
						ON s.extension = a.extension AND s.anio = a.anio AND s.semestre = a.semestre
						AND s.evaluacion = a.evaluacion AND s.pensum = a.pensum AND s.codigo = a.codigo
						AND s.seccion = a.seccion
						INNER JOIN docente d
						ON d.registro_personal = s.registro_personal
						WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.codigo = '$co'
						AND a.carnet = $carnet";
                        $solicitud = & $db->getRow($consulta);
                        if ($db->isError($solicitud)) {
                            $error = true;
                            $mensaje = "Hubo un error al obtener los datos de las asignaturas para solicitud de Des-Asignación";
                            $url = $_SERVER[HTTP_REFERER];
                        } else {
                            // Asignaturas para solicitar Des-Asignacion
                            if (count($solicitud) <> 0) {
                                $sol[] = array(
                                    'codigo' => $solicitud[codigo],
                                    'asignatura' => $solicitud[asignatura],
                                    'seccion' => $solicitud[seccion],
                                    'docente' => $solicitud[docente]
                                );
                            }
                        }
                    }
                }
            }

            if (!empty($desasignados)) {
                $aviso = true;
                $mensaje = "Existe solicitud de desasignación previa en la siguientes asignaturas<br><br>";

                foreach ($desasignados AS $de) {

                    $ciclo = explode("-", $de[ciclo]);

                    if ($ciclo[0] == "1") {
                        $nombre_ciclo = "primer semestre " . $ciclo[1];
                    } else {
                        $nombre_ciclo = "segundo semestre " . $ciclo[1];
                    }

                    $mensaje .= "<b>$de[codigo] - $de[asignatura]</b> en el $nombre_ciclo<br>";
                }
                $mensaje .= "<br>Por favor quite de su selección las asignaturas que se ha desasignado antes";
                $url = $_SERVER[HTTP_REFERER];
            }
        } else {
            $error = true;
            $mensaje = "<br>Por favor, seleccione al menos una asignatura para generar la solicitud de Des-Asignación.";
            $url = $_SERVER[HTTP_REFERER];
        }

        if (!$error && !$aviso) {

            ob_start();

            print_r($numeracion);

            echo "
				<style type='text/css'>					
					#tabla_asignaturas td {
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
				</style>
			";

            // Tablas para generar PDF
            echo "<img src='../images/logofarusac.png' style='position: absolute; top: 0; width: 200px; left: 0'>";
            echo "<table border='0' width='700' align='center' style='font-size: 15px;' style='position: absolute; top: 0'>";
            echo "<tr>";
            echo "<td colspan='3' align='center' valing='top'><b>UNIVERSIDAD SAN CARLOS DE GUATEMALA</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='3' align='center'><b>Facultad de Arquitectura</b><br><br></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='3' align='center'><b>CARTA DE RETIRO DE ASIGNATURAS</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='3' align='center' style='font-size: 10px;'><b>$ciclo[nombre] - $anio</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='3' align='right' style='font-size: 10px;'><font style='font-size: 15px;'>Form. UICA-05</font><br>$ciclo[fecha]</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='3'>Yo <font style='text-transform: capitalice;'>$estudiante[nombre]</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='100'>$estudiante[carnet]</td>";
            echo "<td width='100'>$estudiante[celular]</td>";
            echo "<td width='450'>$estudiante[email_fda]</td>";
            echo "</tr>";
            echo "<tr>
				<td width='710' style='font-size: 12px;' colspan='3'><br><br>
				Solicito la Des-Asignaci&oacute;n de las siguientes asignaturas y declaro conocer los articulos del Reglamento General de Evaluaci&oacute;n y
				Promoci&oacute;n del Estudiante de la Facultad de Arquitectura, que dicen:
				
				<p>
					<ul>
						<li>								
							<b>\"Articulo 30: Sobre asignar y cursar una asignatura.</b> Se considera que un estudiante se asign&oacute; una asignatura 
							cuando &eacute;ste se ha inscrito oficialmente en ella y por lo tanto la puede cursar. El estudiante puede presentar su carta de
							retiro o des-asignaci&oacute;n de una asignatura te&oacute;rica en la fecha programada despu&eacute;s del primer parcial, para que no
							le cuente como cursada. En las Asignaturas pr&aacute;cticas, deber&aacute; de presentar su carta de retiro antes de la mitad del ciclo
							lectivo. El estudiante podr&aacute; presentar carta de retiro para una misma asignatura una sola vez\".<br><br>
						</li>
						<li>
							<b>\"Articulo 36: Asignaci&oacute;n obligatoria.</b> Un estudiante deber&aacute; asignarse en las fechas programadas para ello.
							Fuera de esa fecha, ya no podr&aacute; asignarse para cursar la asignatura y perder&aacute; el derecho de las notas o puntos 
							acumulados si el profesor temporalmente lo hubiera consignado. Cualquier problema derivado de la asignaci&oacute;n, deber&aacute;n
							resolverlo en primera instancia en la Unidad de Orientaci&oacute;n Estudiantil, si el caso lo amerita por problemas ajenos al
							estudiante, dicha unidad lo trasladar&aacute; a la Direcci&oacute;n de Escuela\".
						</li>								
					</ul>
				</p>
				</td>
			</tr>";
            echo "</table><br>";

            echo "<table border='0' id='tabla_asignaturas' width='700' align='center' style='font-size: 12px;'>";
            echo "<tr>";
            echo "<td id='titulos' align='center' width='250'>Asignatura a retirar</td>";
            echo "<td id='titulos' align='center'>C&oacute;digo</td>";
            echo "<td id='titulos' align='center'>Secci&oacute;n</td>";
            echo "<td id='titulos' align='center' width='200'>Docente</td>";
            echo "<td id='titulos' align='center' width='100'>Uso exclusivo de Control Acad&eacute;mico</td>";
            echo "</tr>";

            // Resultado de Array de datos obtenidos.
            foreach ($sol AS $s) {

                echo "<tr>";
                echo "<td height='25'>$s[asignatura]</td>";
                echo "<td align='center'>$s[codigo]</td>";
                echo "<td align='center'>$s[seccion]</td>";
                echo "<td width='200'>$s[docente]</td>";
                echo "<td>&nbsp;</td>";
                echo "</tr>";
            }

            echo "</table><br>";
            echo "<table border='0' align='center'>";
            echo "<tr>";
            echo "<td align='center' height='150'>f.__________________________________<br>$estudiante[nombre]<br>$estudiante[carnet]</td>";
            echo "</tr>";
            echo "</table>";

            echo "<div id='comprobante_orientacion'>";
            echo "<table border='0'>";
            echo "<tr>";
            echo "<td colspan='2'><b>UNIDAD DE CONTROL ACADEMICO</b></td>";
            echo "<td rowspan='4' width='330' align='center' style='border: 1px solid #000000;'>Operaci&oacute;n UICA</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='2'><b>Facultad de Arquitectura</b><br><br><br><br><br><br></td>";
            echo "</tr>";
            echo "<tr>";

            if ($extension == 0) {
                echo "<td align='center' width='300'>Recibido por: 
					<input type='checkbox'> Susana 
					<input type='checkbox'> Maribel 
				</td>";
            }

            echo "<td align='center' width='100'>___________</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center'>&nbsp;</td>";
            echo "<td align='center'>Correlativo</td>";
            echo "</tr>";
            echo "</table>";
            echo "</div>";

            try {

                $contenido_pdf = ob_get_clean();
                $pdf = new HTML2PDF('P', 'Letter', 'es', array(mL, mT, mR, mB));
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->WriteHTML($contenido_pdf);
                $pdf->Output("Carta de retiro de Asignaturas_" . $carnet . '.pdf', 'D');
            } catch (HTML2PDF_exception $e) {
                echo $e;
                exit;
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