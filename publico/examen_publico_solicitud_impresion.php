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

        $carnet = $_SESSION['usuario'];

        // Conversion a UTF8		
        $codificacion = & $db->Query("SET NAMES utf8");

        // Solicitud del estudiante
        $consulta = "SELECT g.acuerdo_decanato, e.acta_privado, s.nombre, p.fecha_solicitud, e.proyecto_graduacion,
        g.primer_nombre, g.segundo_nombre, g.tercer_nombre, g.primer_apellido, g.segundo_apellido, p.carrera
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

            if ($estudiante[carrera] == 3) {
                $programa = "Dise&ntilde;o Gr&aacute;fico";
            } else if ($estudiante[carrera] == 1) {
                $programa = "Arquitectura";
            }

            // Tablas para generar PDF
            echo "<page backtop='15mm' backbottom='0mm' backleft='15mm' backright='15mm'>";
            echo "<img id='logo_usac' src='../images/logofarusac.png' width='300' align='left'>";
            echo "<table id='tabla_ad' border='0'>";
            echo "</table>";

            $fecha_solicitud = fechaEnTextoNumero($estudiante[fecha_solicitud]);

            echo "<table id='tabla_constancia' border='0' align='center'>";
            echo "<tr>";
            echo "<td height='20' valign='top' align='right' colspan='2'><font id='texto_16'>Solicitud de autorizaci&oacute;n de impresi&oacute;n</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='20' valign='top' align='right' colspan='2'><font id='texto_16'>Acuerdo decanato: $acuerdo_decanato</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='20' valign='top' align='right' colspan='2'><font id='texto_16'>Acta privado: $estudiante[acta_privado]</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='20' valign='top' align='right' colspan='2'><font id='texto_16'>Guatemala, $fecha_solicitud</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='20' valign='top' colspan='2'><font id='texto_16'>Arquitecto</font></td>";
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
            se sirva autorizar la impresi&oacute;n de mi proyecto de graduaci&oacute;n \"$estudiante[proyecto_graduacion]\", el cual 
            ha sido aprobado por el jurado examinador y su redacci&oacute;n ha sido revisada por un profesional en letras.
            Adjunto a la presente:
            <br><br></p></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='20' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Documento Impreso</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='20' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Resumen del proyecto</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='20' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Carta de aprobaci&oacute;n de examinadores (si deb&iacute;a hacer correcciones)</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='20' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Constancia de revisi&oacute;n de estilo</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='20' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Carta a la instituci&oacute;n beneficiaria del proyecto</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='20' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>P&aacute;gina de autorizaci&oacute;n de impresi&oacute;n (con firma de asesores y sustentante)</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td width='20' height='20' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Copia de Acta de Exámen Privado</font></td>";
            echo "</tr>";
             echo "<tr>";
            echo "<td width='20' height='20' id='borde'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td><font id='texto_16'>Copia o digitalización de documento de identificación DPI</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='40' colspan='2'><font id='texto_16'>Atentamente,</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='80' align='center' colspan='2'><font id='texto_16'>f.</font></td>";
            echo "</tr>";
            echo "</table>";
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
