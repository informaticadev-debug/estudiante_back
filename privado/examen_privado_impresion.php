<?php

/*
  Document   : examen_privado_impresion.php
  Created on : 20-mar-2014, 10:38:10
  Author     : Angel Caal
  Description:
  Impresion de Acta Privado
 */

require_once 'DB.php';
require_once 'HTML/Template/Sigma.php';
require_once '../misc/funciones.php';
require_once "../misc/pdf/html2pdf.class.php";
session_start();

if (isset($_SESSION['usuario'])) {

    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];

    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::Connect($dsn);
    if (DB::isError($db)) {
        $mensaje = "La Plataforma esta temporalmente fuera de línea, por favor intente en un momento. Si el problema persiste comuníquese con el Programador (Angel Caal | 3070 1746)";
        errorLoginInicio($mensaje);
    } else {

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $error = false;

        $carnet = $_GET['carnet'];
        $numero_privado = $_GET['numero_privado'];

        $db->Query("SET NAMES utf8");

        // Consulta de los datos del Acta de examen Privado
        $consulta = "SELECT p.acta_privado, TRIM(e.nombre) AS nombre, p.numero_recibo,
        p.proyecto_graduacion, LEFT(p.hora_inicio,5) AS hora_inicio, p.fecha_privado,
        IF(p.carrera = 1,
            'arquitecto',
            IF(p.carrera = 3, 
                'dise&ntilde;ador gr&aacute;fico','')
        ) AS carrera, g.primer_nombre, g.segundo_nombre, g.tercer_nombre, g.primer_apellido,
        g.segundo_apellido,
        IF(p.aprobado = 1,'Si','No') AS requisitos, IF(p.aprobado = 1,'Aprobar','Reprobar') AS aprobacion,
        p.nota, LEFT(p.hora_fin,5) AS hora_fin, p.salon,
        IF(p.observacion = '','* Sin observaciones.',p.observacion) AS observacion, e.sexo, g.acuerdo_decanato
        FROM examen_privado p
        INNER JOIN estudiante e
        ON e.carnet = p.carnet
        INNER JOIN proyecto_graduacion g
        ON g.carnet = p.carnet AND g.carrera = p.carrera
        WHERE p.carnet = $carnet AND p.numero_privado = $numero_privado AND p.fecha_confirmacion IS NOT NULL
		AND p.aprobado = 1
        AND p.numero_recibo IS NOT NULL";
        $datos_acta = & $db->getRow($consulta);
        if ($db->isError($datos_acta)) {
            $error = true;
            $mensaje = "Hubo un error durante la consulta de los datos del acta de examen privado, por favor intente nuevamente. Si el problema persiste contacte al programador." . mysql_error();
            $url = $_SERVER[HTTP_REFERER];
        } else {

            if ($datos_acta[sexo] == 1) {
                $sexo = "del";
                $sexo2 = "el";
                $sexo3 = "al";
            } else {
                $sexo = "de la";
                $sexo2 = "la";
                $sexo3 = "a la";
            }

            // Nombre estudiante segun lo ingresado de DPI
            if (!empty($datos_acta[tercer_nombre])) {
                $nombre_estudiante = $datos_acta[primer_nombre] . " " . $datos_acta[segundo_nombre] . " " . $datos_acta[tercer_nombre] . " " . $datos_acta[primer_apellido] . " " . $datos_acta[segundo_apellido];
            } else {
                $nombre_estudiante = $datos_acta[primer_nombre] . " " . $datos_acta[segundo_nombre] . " " . $datos_acta[primer_apellido] . " " . $datos_acta[segundo_apellido];
            }

            // Apellidos
            $nombre_estudiante_apellidos = $datos_acta[primer_apellido] . " " . $datos_acta[segundo_apellido];

            $nota_letras = decena($datos_acta[nota]);

            if (count($datos_acta) == 0) {
                $error = true;
                $mensaje = "El examen privado consultado no ha sido calificado, por lo tanto no existe detalle de acta.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                // Consulta de los examinadores
                $consulta = "SELECT e.nombre, e.titulo, IF(e.registro_personal = 201301, '',e.registro_personal) AS registro_personal, LEFT(e.titulo,3) AS titulo_abreviado
                FROM examen_privado_examinadores e
                WHERE e.numero_privado = $numero_privado AND e.registro_personal NOT IN (15491)";
                $datos_examinadores = & $db->getAll($consulta);
                if ($db->isError($datos_examinadores)) {
                    $error = true;
                    $mensaje = "Hubo un error durante la consulta de los datos de los examinadores, por favor intente nuevamente. Si el problema persiste contacte al programador.";
                    $url = "../templates/examen_privado_ingresoActaManual.php";
                } else {

                    // Consulta de los examinadores
                    $consulta = "SELECT e.nombre, e.titulo, LEFT(e.titulo,3) AS titulo_abreviado
                    FROM examen_privado_examinadores e
                    WHERE e.numero_privado = $numero_privado AND e.registro_personal NOT IN (15491) AND e.examinador_externo NOT IN (1)";
                    $datos_examinadores_boleta = & $db->getAll($consulta);
                    if ($db->isError($datos_examinadores_boleta)) {
                        $error = true;
                        $mensaje = "Hubo un error durante la consulta de los datos de los examinadores, por favor intente nuevamente. Si el problema persiste contacte al programador.";
                        $url = "../templates/examen_privado_ingresoActaManual.php";
                    }
                }
            }
        }

        if (!$error) {

            $fecha_privado = fechaEnTexto($datos_acta[fecha_privado]);
            $fecha_privado_numeros = fechaEnTextoNumero($datos_acta[fecha_privado]);

            ob_start();

            echo "
                <style>
                p {
                    text-align: justify;
                    font-size: 16px;
                    font-family: Arial;
                    line-height: 25px;
                }
                
                #observaciones {
                    position: relative;
                    width: 600px;
                }
                
                #nombres {
                    font-size: 16px;
                    font-family: Arial;
                }
                
                #examinador {                    
                }
                
                #tabla_ad {
                    position: absolute;
                    top: 65px;
                    width: 100%;
                }
                
                #tabla_ad2 {
                    position: absolute;
                    top: 90px;
                    width: 100%;
                }
                
                #boleta {
                    font-size: 16px;
                    font-family: Arial;
                    line-height: 25px;
                    position: absolute;
                    top: 75px;
                    width: 100%;
                }
                
                #boleta td {
                    border-bottom: 1px solid #DDDDDD;
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

            echo "<page backtop='30mm' backbottom='0mm' backleft='16mm' backright='16mm'>";
            echo "<table id='tabla_ad' border='0' width='100%'>";
            echo "<tr>";
            echo "<td align='right'><font style='font-size: 18px'>ACTA DE EXAMEN PRIVADO No. $datos_acta[acta_privado]</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='right'><font style='font-size: 16px'>Estudiante: $nombre_estudiante</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td align='right'><font style='font-size: 16px'>Acuerdo: $datos_acta[acuerdo_decanato] Recibo: $datos_acta[numero_recibo]</font></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><p>El $fecha_privado,  a las $datos_acta[hora_inicio] horas, en el sal&oacute;n $datos_acta[salon] de  la  Facultad de Arquitectura de  la 
                            Universidad de San Carlos de Guatemala, Ciudad Universitaria, Zona 12, se encuentra reunido el Jurado Examinador, 
                            designado por el Se&ntilde;or Decano, e integrado por los siguientes profesionales:<font id='nombres'>";

            foreach ($datos_examinadores AS $dex) {
                echo $dex[titulo] . " " . $dex[nombre] . ", ";
            }

            echo "</font>MSc. Edgar Armando L&oacute;pez Pazos Decano y Arq. Marco Antonio de Le&oacute;n Vilaseca Secretario; para realizar el examen privado $sexo  
                            estudiante $nombre_estudiante, carn&eacute; $carnet quien presenta el proyecto de graduaci&oacute;n: \"$datos_acta[proyecto_graduacion]\",
                            de conformidad con el Normativo del Sistema de Graduaci&oacute;n, para optar al t&iacute;tulo  de  $datos_acta[carrera] en grado de Licenciado.</p></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><p><b>Primero:</b> Se instruye y exhorta a los examinadores, a velar porque se cumplan los mandatos de la tricentenaria  Universidad de 
                            San Carlos de Guatemala, en cuanto a verificar y certificar que el proyecto de graduaci&oacute;n que hoy se presenta, cumpla con las calidades 
                            profesionales pertinentes a la misi&oacute;n y visi&oacute;n de la primera Facultad de Arquitectura de Centro Am&eacute;rica, y que a trav&eacute;s del mismo, 
                            se demuestre que  $sexo2  estudiante ha desarrollado las competencias que requiere el oficio de $datos_acta[carrera].</p></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><p><b>Segundo:</b> <font style=''>$sexo2</font> estudiante $nombre_estudiante_apellidos procede a presentar el proyecto desarrollado, 
                            puntualizando en los siguientes aspectos: la necesidad detectada, los objetivos del proyecto, la metodolog&iacute;a utilizada y la propuesta de soluci&oacute;n.                             
                            </p></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td><p><b>Tercero:</b> Finalizada la presentaci&oacute;n y defensa del proyecto, los infrascritos Miembros del Jurado Examinador, deliberamos sobre la 
                            calificaci&oacute;n del mismo, y considerando que <b>$datos_acta[requisitos]</b> llena los requisitos, acordamos <b>$datos_acta[aprobacion]</b>
                            $sexo3 estudiante $nombre_estudiante_apellidos con la nota de $nota_letras ($datos_acta[nota]) puntos.</p></td>";
            echo "</tr>";
            echo "</table>";
            echo "</page>";

            $examinador0 = $datos_examinadores[0];
            $examinador1 = $datos_examinadores[1];
            $examinador2 = $datos_examinadores[2];
            $examinador3 = $datos_examinadores[3];

            if (!empty($examinador0)) {
                $exam0 = "<br>Examinador";
            }

            if (!empty($examinador1)) {
                $exam1 = "<br>Examinador";
            }

            if (!empty($examinador2)) {
                $exam2 = "<br>Examinador";
            }

            if (!empty($examinador3)) {
                $exam3 = "<br>Examinador";
            }

            echo "<page backtop='25mm' backbottom='0mm' backleft='15mm' backright='15mm'>";
            echo "<table id='tabla_ad' border='0' width='100%'>";
            echo "<tr>";
            echo "<td><p><b>Cuarto:</b> <font style=''>$sexo2</font> estudiante $nombre_estudiante_apellidos, deber&aacute; realizar las correcciones siguientes, previo a la impresi&oacute;n final 
                            del proyecto:</p></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>                    
                    <div id='observaciones'>
                        <p>$datos_acta[observacion]</p>
                    </div>
                </td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td height='140' valign='top'>
                            <p>No habiendo m&aacute;s que hacer constar, se cierra el examen a las $datos_acta[hora_fin] horas, en el mismo lugar y fecha de su inicio.  DAMOS  FE: </p>
                        </td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>
                            <table align='center' width='100%'>
                                <tr>
                                    <td align='center' width='270' height='120' valign='top'>MSc. Edgar Armando L&oacute;pez Pazos<br>Decano</td>
                                    <td align='center' width='270' height='120' valign='top'>Arq. Marco Antonio de Le&oacute;n Vilaseca<br>Secretario</td>
                                </tr>
                                <tr>
                                    <td align=center width='270' height='120' valign='top'><font id='examinador'>$examinador0[titulo] $examinador0[nombre] $exam0</font></td>
                                    <td align=center width='270' height='120' valign='top'><font id='examinador'>$examinador1[titulo] $examinador1[nombre] $exam1</font></td>
                                </tr>
                                <tr>
                                    <td align=center width='270' height='120' valign='top'><font id='examinador'>$examinador2[titulo] $examinador2[nombre] $exam2</font></td>
                                    <td align=center width='270' height='120' valign='top'><font id='examinador'>$nombre_estudiante <br>Sustentante</font></td>
                                </tr>
                            </table>
                            </td>";
            echo "</tr>";
            echo "</table>";
            echo "</page>";

            try {

                $contenido_pdf = ob_get_clean();
                $pdf = new HTML2PDF('P', 'Letter', 'es', array(mL, mT, mR, mB));
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->WriteHTML($contenido_pdf);
                $pdf->Output("ActaPrivado_" . $carnet . '.pdf', 'D');
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
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    errorLoginInicio($mensaje);
}
?>
