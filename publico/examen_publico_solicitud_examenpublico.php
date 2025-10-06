<?php
/*
  Constancia de solicitud de Examen Publico
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

        $db->setFetchMode(DB_FETCHMODE_ASSOC);

        $anio = DATE('o');
        $carnet = $_SESSION['usuario'];

        // Conversion a UTF8		
        $codificacion = & $db->Query("SET NAMES utf8");

        // Solicitud del estudiante
        $consulta = "SELECT g.acuerdo_decanato, e.acta_privado, NOW() AS fecha_solicitud, numero_publico,
		g.primer_nombre, g.segundo_nombre, g.tercer_nombre, g.primer_apellido, g.segundo_apellido, s.celular, s.telefono,
		s.email_fda
        FROM examen_publico p
        LEFT OUTER JOIN proyecto_graduacion g
        ON g.carnet = p.carnet
        INNER JOIN examen_privado e
        ON e.carnet = p.carnet AND e.carrera = p.carrera
        INNER JOIN estudiante s
        ON s.carnet = p.carnet
        WHERE p.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al consultar los datos del estudiante.";
        } else {

            if (!empty($estudiante[tercer_nombre])) {
                $nombre_estudiante = $estudiante[primer_nombre] . " " . $estudiante[segundo_nombre] . " " . $estudiante[tercer_nombre] . " " . $estudiante[primer_apellido] . " " . $estudiante[segundo_apellido];
            } else {
                $nombre_estudiante = $estudiante[primer_nombre] . " " . $estudiante[segundo_nombre] . " " . $estudiante[primer_apellido] . " " . $estudiante[segundo_apellido];
            }

            // Determinar la red curricular de cierre del estudiante
            $consulta = "SELECT  
			CONCAT(
			IF(c.fecha_cierre IS NOT NULL AND c.carrera = 1 AND 
				(SELECT COUNT(*)
				FROM nota n 
				WHERE n.carnet = c.carnet AND n.codigo IN ('304','080','091','104','342','022','023','081','379','090','334','072')
				HAVING COUNT(*) >= 6)
				,'1982',''),
			IF(c.fecha_cierre IS NOT NULL AND c.carrera = 1 AND 
				(SELECT COUNT(*)
				FROM nota n 
				WHERE n.carnet = c.carnet AND n.codigo IN ('1.071','1.032','1.104','1.105','2.081','2.102','2.103','3.091','3.102','3.093')
				HAVING COUNT(*) >= 9)
				,'1995',''),
			IF(c.fecha_cierre IS NOT NULL AND c.carrera = 1 AND 
				(SELECT COUNT(*)
				FROM nota n 
				WHERE n.carnet = c.carnet AND n.codigo IN ('4.09.9','1.04.2','2.05.2','1.04.3','3.10.3','3.08.8','3.10.5','3.10.4', '1.10.1')
				HAVING COUNT(*) >= 7)
				,'2002','')
			) AS status_cierre
			FROM carrera_estudiante c
			WHERE c.carnet = $carnet";
            $cierre = & $db->getRow($consulta);
            if ($db->isError($cierre)) {
                $error = true;
                $mensaje = "Hubo un problema al determinar el pensum de cierre del estudiante.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                // Determinar la carrera en la que se solicitó el examen público
                $consulta = "SELECT *
				FROM examen_publico p
				WHERE p.carnet = $carnet AND acta_publico IS NULL";
                $datos_solicitud = & $db->getRow($consulta);
                if ($db->isError($datos_solicitud)) {
                    $error = true;
                    $mensaje = "Hubo un problema al obtener el detalle de la solicitud de público";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    // Verificar para D.G. El pensum con el que cerraron para aplicar el nivel de ingles
                    $consulta = "SELECT *
					FROM nota n
					WHERE n.carnet = $carnet
					ORDER BY n.fecha_ingreso DESC
					LIMIT 1";
                    $detalle_pensum = & $db->getRow($consulta);
                    if ($db->isError($detalle_pensum)) {
                        $error = true;
                        $mensaje = "Hubo un problema al obtener el pensum con que cerro el estudiante";
                        $url = $_SERVER['HTTP_REFERER'];
                    }
                }
            }
        }

        if (!$error) {

            ob_start();

            echo "
                <style type='text/css'>					
                    #tabla_asignaturas td {
                        border: 1px solid #999999;
                        padding: 3px;
                        font-size: 16px;
                    }
					
                    #tabla_constancia td {
                        position: relative;
                        top: 90px;
                        width: 100%;
                    }
					
                    #texto_16 {						
			font-size: 16px;
                    }

                    #texto_18 {
			font-size: 18px;
                        font-weight: bold;
                    }
                    
                    #borde {
                        border: 1px solid #000000;
                    }
                    
                    #logo_usac {
                        position: absolute;
                        float: left;                    
                    }
					
                    #logo_arq {
                        position: absolute;                    
                        float: right;						
                    }
                    
                    #tabla_ad {
                        position: absolute;
                        top: 90px;
                        width: 100%;
                    }
					
					p {
						line-height: 25px;
						text-align: justify;
						font-size: 16px;
					}
		</style>
            ";

            if (empty($estudiante[acuerdo_decanato])) {
                $acuerdo_decanato = "Sin registro";
            } else {
                $acuerdo_decanato = $estudiante[acuerdo_decanato];
            }

            // Tablas para generar PDF
            echo "<page backtop='10mm' backbottom='0mm' backleft='15mm' backright='15mm'>";
            echo "<img id='logo_usac' src='../images/logofarusac.png' width='300' align='left'>";
            echo "<table id='tabla_ad' border='0'>";
            echo "</table>";

            $fecha_solicitud = fechaEnTextoNumero($estudiante[fecha_solicitud]);

            if ($datos_solicitud[carrera] == 3) {
                $programa = "Dise&ntilde;o Gr&aacute;fico";
            } else if ($datos_solicitud[carrera] == 1) {
                $programa = "Arquitectura";
            }

            echo "<table id='tabla_constancia' border='0' align='center'>";
            echo "<tr>";
            echo "<td height='15' valign='top' align='right' colspan='2'><font id='texto_16'>Solicitud de examen p&uacute;blico: $estudiante[numero_publico]-$anio</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='15' valign='top' align='right' colspan='2'><font id='texto_16'>Acuerdo decanato: $acuerdo_decanato</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='15' valign='top' align='right' colspan='2'><font id='texto_16'>Acta privado: $estudiante[acta_privado]</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='15' valign='top' align='right' colspan='2'><font id='texto_16'>Guatemala, $fecha_solicitud</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='15' valign='top' colspan='2'><font id='texto_16'>Arquitecto</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='2'><font id='texto_16'>Sergio Francisco Castillo Bonini</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='2'><font id='texto_16'>Decano</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='2'><font id='texto_16'>Facultad de Arquitectura</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='2'><font id='texto_16'>Su Despacho</font><br></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td colspan='2'><p>$nombre_estudiante, estudiante del programa de $programa, carn&eacute; $carnet, solicito 
			se sirva autorizar la realizaci&oacute;n de mi examen p&uacute;blico y asignar la fecha del mismo, al haber cumplido con todos los 
			requisitos previos, para lo cual adjunto a la presente:
			<br><br></p></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Carta a la instituci&oacute;n beneficiaria del proyecto con sello y firma de recibido en original.</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Hoja original de constancia de recepci&oacute;n de ejemplares del Proyecto de Graduaci&oacute;n.</font></td>";
            echo "</tr>";

            $nivel_idioma = "(10mo. Nivel)";
            if ($cierre[status_cierre] == 2002 || $datos_solicitud[carrera] == 3) {

                if (/*$carnet >= 200200000 &&*/ $carnet <= 200899999) {
                    $nivel_idioma = "(8vo. Nivel)";
                } else if ($carnet >= 200900000 && $carnet <= 201499999) {
                    if ($detalle_pensum[pensum] == 18) {
                        $nivel_idioma = "(10mo. Nivel)";
                    } else if ($detalle_pensum[pensum] == 20) {
                        $nivel_idioma = "(10mo. Nivel)";
                    } else {
                        $nivel_idioma = "(10mo. Nivel)";
                    }
                } else if ($carnet >= 201500000) {
                    $nivel_idioma = "(10mo. Nivel)";
                }
                
                echo "<tr>";
                echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
                echo "<td><font id='texto_16'>Original de certificaci&oacute;n de dominio de un segundo idioma $nivel_idioma.</font></td>";
                echo "</tr>";
            }

            if ($cierre[status_cierre] == 2002 && $carnet < 201200000) {
                echo "<tr>";
                echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
                echo "<td><font id='texto_16'>Constancia de dominio de programas de computaci&oacute;n.</font></td>";
                echo "</tr>";
            }

            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Original de constancia de cr&eacute;ditos extracurriculares</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Solvencias originales de biblioteca central y biblioteca de la Facultad.</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Original de \"Constancia de expediente estudiantil completo\".</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Solvencia general de pago del año en curso.</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Copia del recibo de pago de examen p&uacute;blico.</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Orden de pago y copia del recibo para impresión de título.</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Constancia de inscripción como estudiante pendiente de exámenes generales PEG del año en curso.</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='15' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Copia del documento personal de indentificaci&oacute;n vigente.</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='40' colspan='2'><font id='texto_16'>Atentamente,</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='140' align='center' colspan='2'><font id='texto_16'>f. ______________________________<br>$nombre_estudiante<br>$estudiante[celular]<br>
			$estudiante[email_fda]</font></td>";
            echo "</tr>";
            echo "</table>";
            echo "<div style='position: absolute: top:100%; font-size: 10px;'>* Por favor, verifique que el nombre escrito en este documento sea exactamente igual
			al que aparece en su DPI (nombres, t&iacute;ldes)</div>";
            echo "</page>";

            try {

                $contenido_pdf = ob_get_clean();
                $pdf = new HTML2PDF('P', 'Letter', 'es', array(mL, mT, mR, mB));
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->WriteHTML($contenido_pdf);
                $pdf->Output("Constancia_Examen_Publico_" . $carnet . '.pdf', 'D');
            } catch (HTML2PDF_exception $e) {
                echo $e;
                exit;
            }
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
?>
