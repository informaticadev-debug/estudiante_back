<?php

/*
  Constancia de solicitud de Examen Publico
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once "../misc/PDF/html2pdf.class.php";

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
        $consulta = "SELECT TRIM(e.nombre) AS nombre, p.fecha_solicitud, 
                CONCAT(IF(p.carrera = 1, 'ARQ', 'DG'), p.numero_publico, '-', p.anio) AS no_solicitud
                FROM estudiante e
                INNER JOIN examen_publico p
                ON p.carnet = e.carnet 
                WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al consultar los datos del estudiante.";
        }

        if (!$error) {

            ob_start();

            echo "
				<style type='text/css'>					
					#tabla_asignaturas td {
						border: 1px solid #999999;
						padding: 3px;
					}
					
                    #tabla_constancia td {
                        position: relative;
                        width: 650px;
					}
					
					#texto_16 {						
						font-size: 16px;
					}

                    #texto_18 {
						font-size: 18px;
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
            echo "<img id='logo_usac' src='../images/logofarusac.png' width='300' align='left'>";
            echo "<table border='0' align='center' style='font-size: 15px;' style='position: absolute; top: 0'>";
            echo "<tr>";
            echo "<td align='center' valing='top' width='700'><b><font id='texto_16'>UNIVERSIDAD SAN CARLOS DE GUATEMALA</font></b></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center' height='50' valign='top'><font id='texto_16'>Facultad de Arquitectura</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center'><font id='texto_18'>Solicitud de Examen P&uacute;blico</font></td>";
            echo "</tr>";
            echo "</table><br>";

            $fecha = $estudiante[fecha_solicitud];
            $laFecha = fechaEnTexto($fecha);

            echo "<table id='tabla_constancia' border='0' align='center'>";
            echo "<tr>";
            echo "<td height='20' valign='top' align='right'><font id='texto_18'>$estudiante[no_solicitud]</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='20' valign='top'><font id='texto_18'>Datos Estudiante:</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><b>Carnet:</b> $carnet</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><b>Nombre:</b> $estudiante[nombre]</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='25' valign='top'><b>Fecha de Solicitud:</b> $laFecha</td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><font id='texto_18'>Papeleria a presentar:</font></td>";
            echo "</tr>";
            echo "</table>";

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