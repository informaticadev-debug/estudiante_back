<?php

require_once 'DB.php';
require_once 'HTML/Template/Sigma.php';
require_once '../misc/funciones.php';
require_once "../config/Conexion.php";
require_once "../misc/pdf/html2pdf.class.php";
session_start();

$conexionDB = new Conexion();

if (isset($_SESSION['usuario'])) {

    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];

    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::Connect($dsn);
    if (DB::isError($db)) {
        $mensaje = "La Plataforma esta temporalmente fuera de línea, por favor intente en un momento. Si el problema persiste comuníquese con el Programador (Angel Caal | 3070 1746)";
        echo $mensaje;
    } else {

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $error = false;

        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carnet = $_SESSION['usuario'];
        $carrera = inscripcion_estudiante($db, $anio, $semestre, $carnet);
        $asesores = /* $_SESSION['asesores'] */ false;

        if (isset($_SESSION['numero_tema'])) {
            $numero_tema = $_SESSION['numero_tema'];
        } else {

            $consulta = "SELECT p.numero_tema
			FROM proyecto_graduacion p
			WHERE p.carnet = $carnet AND p.carrera = $carrera";
            $estado_pg = & $db->getRow($consulta);
            if ($db->isError($estado_pg)) {
                $error = true;
                $mensaje = "Hubo un problema durante la consulta del estado del Proyecto de Graduación.";
                $url = "../menus/contenido.php";
            } else {

                $numero_tema = $estado_pg[numero_tema];
            }
        }

        if (empty($asesores)) {
            // Consulta de los asesores del proyecto de graduación
            $consulta = "SELECT a.registro_personal
			FROM proyecto_graduacion_asesores a
			WHERE a.numero_tema = $numero_tema";
            $datos_asesores = & $db->getAll($consulta);
            if ($db->isError($datos_asesores)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener los datos de la solicitud";
            } else {
                $asesores = [];
                foreach ($datos_asesores AS $da) {
                    $asesores[] = $da['registro_personal'];
                }
            }
        }
        //var_dump($asesores); die;

        $db->Query("SET NAMES utf8");

        // Consulta de los datos del protocolo
        $consulta = "SELECT *
		FROM proyecto_graduacion a
		WHERE a.numero_tema = $numero_tema AND a.carnet = $carnet";
        $datos_protocolo = & $db->getRow($consulta);
        if ($db->isError($datos_protocolo)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener los datos de la solicitud";
        }

        if (!$error) {

            ob_start();

            echo "
                <style>
                p {
                    text-align: justify;
                    font-size: 16px;
                    font-family: Arial;
                    line-height: 20px;
                    margin-top: -4px;
                }
                
                #tabla_ad {
                    position: absolute;
                    top: 150px;
                    width: 100%;
                    font-size: 16px;
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
            if (!empty($datos_protocolo[tercer_nombre])) {
                $nombre_estudiante = $datos_protocolo[primer_nombre] . " " . $datos_protocolo[segundo_nombre] . " " . $datos_protocolo[tercer_nombre] . " " . $datos_protocolo[primer_apellido] . " " . $datos_protocolo[segundo_apellido];
            } else {
                $nombre_estudiante = $datos_protocolo[primer_nombre] . " " . $datos_protocolo[segundo_nombre] . " " . $datos_protocolo[primer_apellido] . " " . $datos_protocolo[segundo_apellido];
            }

            $fecha_actual = fechaEnTextoNumero(DATE("o-m-d"));

            $asesores_profesores = $conexionDB->queryList("
                   SELECT d.`registro_personal`
                   FROM asignacion a
                           INNER JOIN staff s ON s.`extension` = a.`extension` AND s.`anio` = a.`anio` AND s.`semestre` = a.`semestre` AND s.`evaluacion` = a.`evaluacion` AND a.`codigo` = s.`codigo` AND s.`seccion` = a.`seccion`
                           INNER JOIN docente d ON d.`registro_personal` = s.`registro_personal`
                   WHERE a.`anio` = $anio AND a.`semestre` = $semestre AND a.`evaluacion` = 1 AND a.`carnet` = $carnet AND a.`codigo` IN (31021,31041)
                   ");

            if (!empty($asesores_profesores)) {
                foreach ($asesores_profesores as $value) {
                    $asesores = array_diff($asesores, [$value['registro_personal']]);
                }
            }

            foreach ($asesores AS $as) {

                $asesor = $as;

                // Datos de los asesores propuestos por el estudiante para generar carta de aceptacion
                $consulta = "SELECT d.telefono, d.telefono_celular, (CONCAT(TRIM(d.nombre), ' ', TRIM(d.apellido))) AS nombre,
				d.correo_institucional, d.titulo, d.docenter, d.correo_personal
				FROM docente d
				WHERE d.registro_personal = $asesor";
                $datos_asesor = & $db->getRow($consulta);
                if (empty($datos_asesor['correo_institucional'])) {
                    $datos_asesor['correo_institucional'] = $datos_asesor['correo_personal'];
                }

                echo "<page backtop='5mm' backbottom='10mm' backleft='15mm' backright='15mm'>";
                echo "<img id='logo_usac' src='../images/logousac.jpg' width='240' align='left'>";
                echo "<img id='logo_usac' src='../images/logofarusac.png' width='250' align='right'>";
                echo "<table id='tabla_ad' align='center'>";
                echo "<tr>";
                echo "<td align='right'>$fecha_actual<br><br><br><br><br><br></td>";
                echo "</tr>";

                if ($carrera == 3) {

                    $nombre_carrera = "Licenciatura en Dise&ntilde;o Gr&aacute;fico";

                    echo "<tr>";
                    echo "<td align='left'>Licenciado</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Luis Gustavo Jurado Duarte</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Coordinador</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Unidad de Investigaci&oacute;n y Graduaci&oacute;n</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Facultad de Arquitectura<br><br></td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Estimado Coordinador:<br><br></td>";
                    echo "</tr>";
                } else if ($carrera == 1) {

                    $nombre_carrera = "Licenciatura de Arquitectura";

                    echo "<tr>";
                    echo "<td align='left'>Arquitecta</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Maria Isabel Cifuentes Soberanis</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Coordinadora</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>&Aacute;rea de Investigaci&oacute;n y Graduaci&oacute;n</td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Facultad de Arquitectura<br><br></td>";
                    echo "</tr>";
                    echo "<tr>";
                    echo "<td align='left'>Estimada Coordinadora:<br><br></td>";
                    echo "</tr>";
                }
                echo "<tr>";
                echo "<td align='left'><p>Sirva la presente para manifestar mi disposici&oacute;n a brindar asesor&iacute;a al estudiante 
					$nombre_estudiante con carn&eacute; $carnet, para el desarrollo de su proyecto de graduaci&oacute;n 
					\"$datos_protocolo[proyecto_graduacion]\".<br><br></p></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='left'><p>As&iacute; tambi&eacute;n, hago constar que conozco las regulaciones del Normativo para el Sistema de Graduaci&oacute;n de la 
				$nombre_carrera y que  pondr&eacute; especial cuidado para que el proyecto se desarrolle con la calidad que exige la Facultad de Arquitectura.</p><br></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='left'><p>Atentamente,</p><br></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='center'>\"Id y ense&ntilde;ad a todos\"<br><br><br><br><br><br><br><br></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'><font style=''>$datos_asesor[titulo] $datos_asesor[nombre]</font></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>$datos_asesor[correo_institucional]</td>";
                echo "</tr>";
                echo "</table>";
                echo "</page>";
            }

            try {

                $contenido_pdf = ob_get_clean();
                $pdf = new HTML2PDF('P', 'Letter', 'es', array(mL, mT, mR, mB));
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->WriteHTML($contenido_pdf);
                $pdf->Output("Protocolo_Aceptacion_Asesores" . $carnet . '.pdf', 'D');
            } catch (HTML2PDF_exception $e) {
                echo $e;
                exit;
            }
        }

        if ($error) {
            echo "Hubo un error al generar la solicitud de protocolo";
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
