<?php

/*
  Constancia de Asignaciones realizadas
  -> Estado de las asignaciones
  -> Horarios en cada asignatura seleccionada
  -> Verioficacion de datos dependiendo de la evaluacion
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once "../misc/pdf/html2pdf.class.php";

session_start();
if (isset($_SESSION["usuario"])) {

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

        // Conversion a UTF8		
        $codificacion = & $db->Query("SET NAMES utf8");

        // Datos de la session acutal
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carnet = $_SESSION['usuario'];
        $evaluacion = 1;
        // Datos del estudiante
        $consulta = "SELECT *
		FROM estudiante e
		WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);

        $seccionesParaHash = "";

        // Datos de las asignaturas seleccionadas
        $consulta = "
                SELECT a.evaluacion, a.codigo, TRIM(c.nombre_abreviado) AS asignatura, a.seccion, s.salon,a.observacion, a.id_asignacion
                FROM asignacion a 
                    INNER JOIN curso c ON c.codigo = a.codigo AND c.pensum = a.pensum
                    INNER JOIN seccion s ON s.extension = a.extension AND s.anio = a.anio AND s.semestre = a.semestre AND s.evaluacion = a.evaluacion
                        AND s.pensum = a.pensum AND s.codigo = a.codigo AND s.seccion = a.seccion
                WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre  AND a.carnet = $carnet -- AND a.codigo = '$se'
                ";
        // print_r($consulta); die;
        $asignaciones_array = & $db->getAll($consulta);
        if ($db->isError($asignaciones_array)) {
            $error = true;
            $mensaje = "Hubo un error al obtener los datos de las asignaturas seleccionadas.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Asignaturas para solicitar Des-Asignacion
            foreach ($asignaciones_array as $asignaciones) {
                $evaluacion = $asignaciones["evaluacion"];
                $seccionesParaHash .= $asignaciones["codigo"] . $asignaciones["seccion"] . $asignaciones["codigo"];
                $as[] = array(
                    'evaluacion' => $asignaciones["evaluacion"],
                    'codigo' => $asignaciones["codigo"],
                    'asignatura' => $asignaciones["asignatura"],
                    'seccion' => $asignaciones["seccion"],
                    'salon' => $asignaciones["salon"],
                    'observacion' => $asignaciones["observacion"],
                    'id_asignacion' => $asignaciones['id_asignacion']
                );
            }
        }
        //}
        /* } else {
          $error = true;
          $mensaje = "Por favor, seleccione al menos una asignatura para generar la constancia de Asignaciones";
          $url = $_SERVER[HTTP_REFERER];
          } */

        if (!$error) {

            ob_start();

            $fecha_impresion = DATE("d/m/o H:i:s");
            $hash_fecha_impresion = md5($estudiante["carnet"] . DATE("d/m/o H:i:s"));
            $hash_secciones = md5($estudiante["carnet"] . $seccionesParaHash . DATE("d/m/o H:i:s") . $estudiante["carnet"]);

            echo "
				<style type='text/css'>		
                                        
                #logo_arq {
                    position: absolute;                    
                    float: right;						
		}
                
                #logo_usac {
                    position: absolute;
                    float: left;                    
		}
                #logo_arq_transparente {
                    position: absolute;
                    top: 170px;
                    left: 50px;
                    width: 600px;
		}
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
            echo "<page backtop='10mm' backbottom='10mm' backleft='10mm' backright='10mm'>";
            echo "<img id='logo_usac' src='../images/logousac.jpg' width='200' align='left'>";
            echo "<img id='logo_arq' src='../images/logofarusac.png' width='200' align='right'><br><br><br><br><br><br>";
            echo "<img id='logo_arq_transparente' src='../images/logofarusac_transparencia.png' />";
            echo "<table border='0' align='center' style='font-size: 15px;' style='position: absolute; top: 0'>";
            echo "<tr>";
            echo "<td align='center' valing='top' width='700'><b>UNIVERSIDAD SAN CARLOS DE GUATEMALA</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center'><b>Facultad de Arquitectura</b><br><br></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center'><b>Detalle de asignaciones</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center' style='font-size: 10px;'><b>" . (($semestre == 1) ? "Primer Semestre" : "Segundo Semestre") . " - $anio</b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='right' style='font-size: 10px;'>Fecha de impresión: " . $fecha_impresion . " <br /> " . $hash_fecha_impresion . " <br /> " . $hash_secciones . "</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td style='text-transform: uppercase'><b>$estudiante[carnet]</b> $estudiante[nombre]</td>";
            echo "</tr>";
            echo "</table>";

            echo "<table border='0' id='tabla_asignaturas' width='700' align='center' style='font-size: 12px;'>";
            echo "<tr>";
            echo "<td id='titulos' align='center' width='180'>Asignatura</td>";
            echo "<td id='titulos' align='center'>Sal&oacute;n</td>";
            echo "<td id='titulos' align='center'>C&oacute;digo</td>";
            echo "<td id='titulos' align='center'>Secci&oacute;n</td>";
            echo "<td id='titulos' align='center' width='70'>Horario</td>";
            echo "<td id='titulos' align='center' width='200'>Observaci&oacute;n</td>";
            echo "</tr>";

            // Resultado de Array de datos obtenidos.
            if ($as) {
                foreach ($as AS $a) {

                    // Horarios de las Asignaciones o Pre-Asignaciones realizadas.
                    $consulta = "SELECT d.nombre_abreviado AS dia, p.hora_ini, p.hora_fin, h.salon
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
                    WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $a[evaluacion]
                    AND s.codigo = '$a[codigo]' AND s.seccion = '$a[seccion]'
					ORDER BY p.hora_ini ASC";
                    $horario = & $db->getAll($consulta);
                    if ($db->isError($horario)) {
                        $error = true;
                        $mensaje = "Hubo un error al verificar los horarios.";
                        $url = $_SERVER[HTTP_REFERER];
                    }
                    echo "<tr>";
                    echo "<td width='180'><span id='evaluacion_$a[evaluacion]'>&nbsp;</span> $a[asignatura]</td>";
                    echo "<td>" . ((!empty($horario[0][salon])) ? $horario[0][salon] : $a["salon"]) . "</td>";
                    echo "<td align='center'>$a[codigo]</td>";
                    echo "<td align='center'>$a[seccion]</td>";
                    echo "<td style='font-size: 8px;'>";

                    // Horario de cada asignatura.
                    foreach ($horario AS $ho) {
                        echo "<table border=0>";
                        echo "<tr>";
                        echo "<td width='10' align='center'>$ho[dia]</td>";
                        echo "<td>$ho[hora_ini] - $ho[hora_fin]</td>";
                        echo "</tr>";
                        echo "</table>";
                    }

                    echo "</td>";
                    echo "<td width='200' align='center'>$a[observacion] <br> $a[id_asignacion]</td>";
                    echo "</tr>";
                }
            }

            echo "</table><br>";
            echo "<br /><br /><span align='center'><b>Estoy seguro de que elegí las asignaturas y secciones que deseo cursar y estoy enterado que no podré hacer ningún cambio a las mismas</b></span>";
            echo "<br /><br /><span align='center'><b>*Cualquier alteración de este documento será motivo de sanción.</b></span>";
            echo "</page>";

            try {
                $dataAs = json_encode($as);
                $consulta = "
                    INSERT INTO `satu`.`bitacora_constancia_asignacion` (
                        `fecha_bitacora`, `extension`, `anio`, `semestre`,
                        `evaluacion`,`carnet`,`data`,`hash1`,`hash2`
                    ) 
                    VALUES
                        (
                            NOW(),
                            $extension,
                            $anio,
                            $semestre,
                            $evaluacion,
                            $carnet,
                            '{$dataAs}',
                            '$hash_fecha_impresion',
                            '$hash_secciones'
                        ) 
                        ";
                $result = $db->Query($consulta);

                $contenido_pdf = ob_get_clean();
                $pdf = new HTML2PDF('P', 'Letter', 'es', array(mL, mT, mR, mB));
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->WriteHTML($contenido_pdf);
                $pdf->Output("Asignaciones_" . $carnet . '.pdf', 'D');
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
