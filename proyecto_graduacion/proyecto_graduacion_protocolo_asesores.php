<?php

/*
  Documento  : proyecto_graduacion_solicitudformulario.php
  Creado el  : 03 de junio de 2014, 17:02
  Author     : Angel Caal
  Description:
  Formulario para solicitud de aprobacion de proyecto de graduación
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

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

        $carnet = $_SESSION['usuario'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carrera = inscripcion_estudiante($db, $anio, $semestre, $carnet);

        // Consulta del protocolo en estado 4 del estudiante
        $consulta = "SELECT *
        FROM proyecto_graduacion g
        WHERE g.carnet = $carnet AND g.estado = 4";
        $protocolo = & $db->getRow($consulta);
        if ($db->isError($protocolo)) {
            $error = true;
            $mensaje = "Hubo un problema al obneter el protocolo del estudiante.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            $_SESSION['numero_solicitud'] = $protocolo[numero_tema];

            // Asesores que han sido desaprobados por el comité (Asesores internos)
            $consulta = "SELECT (CONCAT(TRIM(d.nombre), ' ', TRIM(d.apellido))) AS nombre, d.titulo        
            FROM proyecto_graduacion_asesores a            
            INNER JOIN docente d
            ON d.registro_personal = a.registro_personal
            WHERE a.numero_tema = $protocolo[numero_tema] AND a.aprobado = 2";
            $asesores = & $db->getAll($consulta);
            if ($db->isError($asesores)) {
                $error = true;
                $mensaje = "Hubo un problema durante la verificación del estado de los asesores.";
                $url = $_SERVER[HTTP_REFERER];
            } else {


                // -> Docentes de la Facutald de Arquitectura
                if ($carrera == 1) {
                    $pensum = "5";
                    $asesorias = 40;
                } else if ($carrera == 3) {
                    $pensum = "18,20";
                    $asesorias = 40;
                }

                // -> Docentes de la Facutald de Arquitectura
                $consulta = "SELECT CONCAT(TRIM(d.nombre), ', ', TRIM(d.apellido)) AS nombre, d.registro_personal
                        FROM docente d
                        WHERE d.status = 'ALTA' AND d.registro_personal <> 0
                         and d.registro_personal not in (20030689,20110177,20040722)
                        AND (
                                EXISTS(
                                        SELECT *
                                        FROM staff s
                                        WHERE s.registro_personal = d.registro_personal
                                        AND s.anio >= 2013 AND s.pensum IN ($pensum)
                                )
                                AND EXISTS(
                                        SELECT *
                                        FROM proyecto_graduacion_asesores a
                                        INNER JOIN proyecto_graduacion p
                                        ON p.`numero_tema` = a.`numero_tema` AND p.`fecha_vencimiento` > LEFT(NOW(),10)
                                        WHERE a.`registro_personal` = d.registro_personal
                                        AND NOT EXISTS(
                                        SELECT *
                                        FROM examen_privado p1
                                        WHERE p1.`carnet` = p.`carnet` AND p1.`carrera` = p.`carrera` AND p1.`aprobado` = 1
                                        )
                                        HAVING COUNT(*) < $asesorias
                                )
                                OR EXISTS (
                                        SELECT *
                                        FROM staff s
                                        WHERE s.`extension` = 0 AND s.`anio` = $anio AND s.`semestre` = $semestre 
                                        AND s.`evaluacion` = 1 AND s.`codigo` = '1.10.1' AND s.`registro_personal` = d.`registro_personal`
                                )
                                OR EXISTS (
                                        SELECT *
                                        FROM eps_seccion s
                                        WHERE s.`extension` = 0 AND s.`anio` >= 2015 AND s.`registro_personal` = d.`registro_personal`
                                )
                                OR EXISTS (
                                        SELECT *
                                        FROM staff s
                                        WHERE s.`extension` = 0 AND s.`anio` = $anio AND s.`semestre` = $semestre 
                                        AND s.`evaluacion` = 1 AND s.`codigo` = '4.10.6' AND s.`registro_personal` = d.`registro_personal`
                                )
                                OR EXISTS(
                                        SELECT *
                                        FROM docente d1
                                        WHERE d1.estado = 'ACTIVO' AND d1.registro_personal = d.registro_personal
                                )
                        )
                        ORDER BY d.nombre ASC";
                $datos_docentes = & $db->getAll($consulta);
                if ($db->isError($datos_docentes)) {
                    $error = true;
                    $mensaje = "Hubo un problema al obtener el listado de docentes que pueden dar asesoría.";
                    $url = $_SERVER[HTTP_REFERER];
                }
            }
        }

        if (!$error) {

            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('proyecto_graduacion_protocolo_asesores.html');

            foreach ($asesores AS $as) {

                $template->setVariable(array(
                    'titulo' => $as[titulo],
                    'nombre_asesor_noaprobado' => $as[nombre]
                ));

                foreach ($datos_docentes AS $ld) {
                    if ($protocolo["carrera"] == 1) {
                        $estudiantesAsesorados = getProyectosAsesorados($db, $ld["registro_personal"]);
                        //si es de arquitectura y tiene mas de 8 estudiantes asesorados... descartar
                        if (count($estudiantesAsesorados) > 15) {
                            continue;
                        }
                    }
                    $template->setVariable(array(
                        'registro_personal' => $ld["registro_personal"],
                        'nombre_asesor' => $ld["nombre"]
                    ));
                    $template->parse('listado_asesores');
                }

                $template->parse('asesores_reprobados');
            }



            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_error'])) {
                $mensaje_error = $_SESSION['mensaje_error'];
                $template->setVariable(array(
                    'mensaje_error' => "<div id='base_error_proceso'>
                        <div id='error'>
                            $mensaje_error<br><br>
                            <div id='acciones'>
                                <input id='btn_rojo' type='button' value='Aceptar' OnClick='window.location.reload()' autofocus>
                            </div>
                        </div>
                    </div>"
                ));
                unset($_SESSION['mensaje_error']);
            }

            $template->show();
            exit();
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
