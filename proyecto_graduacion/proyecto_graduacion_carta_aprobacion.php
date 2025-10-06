<?php

require_once 'DB.php';
require_once 'HTML/Template/Sigma.php';
require_once '../misc/funciones.php';
require_once "../misc/pdf/html2pdf.class.php";
session_start();

if (isset($_SESSION[usuario])) {

    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];

    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::Connect($dsn);
    if (DB::isError($db)) {
        $mensaje = "La Plataforma esta temporalmente fuera de línea";
        echo $mensaje;
    } else {

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $error = false;

        $carnet = $_SESSION['usuario'];
        $numero_solicitud = $_GET['numero_solicitud'];

        $db->Query("SET NAMES utf8");

        // Consulta de datos de carta de aprobación de proyecto
        $consulta = "SELECT p.numero_tema, e.carnet, e.sexo, p.primer_nombre, p.segundo_nombre, p.tercer_nombre, p.primer_apellido,
		p.segundo_apellido, p.proyecto_graduacion, p.acuerdo_decanato
		FROM proyecto_graduacion p
		INNER JOIN estudiante e
		ON e.carnet = p.carnet
		WHERE p.numero_tema = $numero_solicitud";
        $datos_proyecto = & $db->getRow($consulta);
        if ($db->isError($datos_proyecto)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener los datos de la carta de aprobación de proyecto.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Datos de los asesores para este proyecto de graduacion
            $consulta = "SELECT d.titulo, (CONCAT(d.nombre, ' ',d.apellido)) AS nombre, a.asesor_externo
		FROM proyecto_graduacion_asesores a
		INNER JOIN docente d
		ON d.registro_personal = a.registro_personal
		WHERE a.numero_tema = $numero_solicitud";
            $asesores = & $db->getAll($consulta);
            if ($db->isError($asesores)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener los datos de los asesores.";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

        if (!$error) {

            $fecha_actual = fechaEnTextoNumero(DATE('o-m-d'));

            if ($datos_proyecto[sexo] == 1) {
                $sexo = "del";
            } else {
                $sexo = "de la";
            }

            ob_start();

            echo "
                <style>
                p {
                    text-align: justify;
                    font-size: 17px;
                    font-family: Arial;
                    line-height: 17px;
                }
                
                #tabla_ad {
                    position: absolute;
                    width: 100%;
                    top: 200px;
                    font-size: 17px;
                    text-align: justify;
                }
                
                #logo_usac {
                    position: absolute;
                    float: left;                    
		}
					
		#logo_arq {
                    position: absolute;                    
                    float: right;						
		}
                </style>
            ";

            // Nombre estudiante segun lo ingresado de DPI
            if (!empty($datos_proyecto[tercer_nombre])) {
                $nombre_estudiante = $datos_proyecto[primer_nombre] . " " . $datos_proyecto[segundo_nombre] . " " . $datos_proyecto[tercer_nombre] . " " . $datos_proyecto[primer_apellido] . " " . $datos_proyecto[segundo_apellido];
            } else {
                $nombre_estudiante = $datos_proyecto[primer_nombre] . " " . $datos_proyecto[segundo_nombre] . " " . $datos_proyecto[primer_apellido] . " " . $datos_proyecto[segundo_apellido];
            }

            echo "<page backtop='10mm' backbottom='10mm' backleft='20mm' backright='20mm'>";
            echo "<img id='logo_usac' src='../images/logofarusac.png' width='200' align='left'>";
            echo "<table id='tabla_ad' align='center'>";
            echo "<tr>";
            echo "<td align='right'>Guatemala, $fecha_actual<br><br><br><br></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='left'>
                             <p>
                                Arquitecto<br>
                                Sergio Francisco Castillo Bonini<br>
                                Decano<br>
                                Facultad de Arquitectura<br>
                                Presente<br><br>
                                Estimado Arquitecto Castillo:<br><br>
                                De conformidad con el Acuerdo de Decanato No. $datos_proyecto[acuerdo_decanato], procedimos; a revisar y asesorar el proyecto de graduaci&oacute;n: 
                                \"$datos_proyecto[proyecto_graduacion]\" $sexo estudiante $nombre_estudiante, carn&eacute;:  $carnet.<br><br>
                                    
                                Dicho proyecto ha sido concluido a mi satisfacci&oacute;n y el contenido cumple lo que exige el art&iacute;culo 17 del Normativo para el Sistema de Graduaci&oacute;n,
                                por lo que solicitamos se haga el tr&aacute;mite respectivo para el nombramiento del Tribunal Examinador y se fije fecha y hora para el examen privado 
                                de sustentaci&oacute;n del mismo.
                            </p>
                        </td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='center'>Atentamente,<br><br><br><br><br><br><br><br></td>";
            echo "</tr>";
            echo "</table>";
            echo "<table border=0 align='center'>";
            echo "<tr>";

            foreach ($asesores AS $as) {

                if ($as[asesor_externo] == 1) {
                    echo "<td valign='top' align='center' width='200'>$as[titulo] <font style=''>$as[nombre]</font> <br>Asesor externo</td>";
                } else {
                    echo "<td valign='top' align='center' width='200'>$as[titulo] <font style=''>$as[nombre]</font> <br>Asesor</td>";
                }
            }

            echo "</tr>";
            echo "</table>";
            echo "</page>";

            try {

                $contenido_pdf = ob_get_clean();
                $pdf = new HTML2PDF('P', 'Letter', 'es', array(mL, mT, mR, mB));
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->WriteHTML($contenido_pdf);
                $pdf->Output("Aprobacion_Proyecto_" . $carnet . '.pdf', 'D');
            } catch (HTML2PDF_exception $e) {
                echo $e;
                exit;
            }
        }

        if ($error) {
            echo $mensaje;
        }

        $db->disconnect();
    }
} else {
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    echo $mensaje;
    echo "
	<script>
		
	</script>
	";
}
?>
