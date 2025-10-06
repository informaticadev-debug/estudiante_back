<?php

/*
  Document   : eps_gestion.php
  Created on : 15-oct-2014, 21:22
  Author     : Angel Caal
  Description:
  Gestión general del EPS
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

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;

        $evaluacion = 1;
        $pensum = 5;
        $codigo = "1.11.1";
        $carnet = $_SESSION['usuario'];
        $carrera = 1;
        
        $fecha_actual = DATE("o-m-d");
        $fecha_habilitar = "2018-07-18";
        $fecha_desabilita = "2018-07-19";
        $mensaje_habilitacion = "La solicitud de asignación de EPS dara inicio el 18 y 19 de Julio.";
        
        // Verificación de solicitudes anteriores que no hayan sido aprobadas
        $consulta = "SELECT *
        FROM eps_solicitud s
        WHERE s.carnet = $carnet AND s.carrera = $carrera";
        $solicitudes = & $db->getRow($consulta);
        if ($db->isError($solicitudes)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener el estado de solicitudes de E.P.S. Por favor intente nuevamente si el problema persiste notifique al programador.";
            $url = "../menus/contenido.php";
        } else {

            // Asignacion de estudiante a EPS
            $consulta = "SELECT d.nombre AS nombre_departamento, m.nombre AS nombre_municipio
            FROM eps_asignacion e
			INNER JOIN departamento d
			ON d.departamento = e.departamento
			INNER JOIN municipio m
			ON m.departamento = e.departamento AND m.municipio = e.municipio
            WHERE e.carnet = $carnet AND e.carrera = $carrera";
            $asignacion = & $db->getRow($consulta);
            if ($db->isError($asignacion)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener el detalle de asignación del estudiante.";
                $url = "../menus/contenido.php";
            }
        }

        if (!$error) {

            // Cargando la pagina para mostrar las ordenes de Pago.
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('eps_gestion.html');

            if ($solicitudes == 0) {
                if ($fecha_actual < $fecha_habilitar || $fecha_actual > $fecha_desabilita) {
                    $aviso = true;
                    $url = "../menus/contenido.php";
                    error($mensaje_habilitacion, $url);
                    exit;
                }
                $template->setVariable(array(
                    'evaluacion' => $evaluacion,
                    'pensum' => $pensum,
                    'codigo' => $codigo,
                    'carnet' => $carnet,
                    'carrera' => $carrera
                ));
                $template->parse("solicitudes_eps");
            } else {
                SWITCH ($solicitudes[estado]) {
                    case 0 : {
                            // Formulario de solicitud de EPS
                            $template->setVariable(array(
                                'estado' => "<b>Solicitud realizada</b><br><br>El trámite de solicitud de EPS está registrado, debe esperar a que la Unidad de Control Académico verifique
								los requisitos que debe cumplir para realizar E.P.S."
                            ));
                            $template->parse("estado_eps");
                            break;
                        }
                    case 1 : {

                            // El estudiante cuenta con todos los prerrequisitos 
                            // -> Revisión que realiza UDICA
                            $template->setVariable(array(
                                'estado' => "<b>Asignación</b><br><br>Cumple con los requisitos establecidos, la Unidad de E.P.S."
                            ));
                            $template->parse("estado_eps");

                            break;
                        }
                    case 2 : {

                            // El estudiante cuenta con todos los prerrequisitos 
                            // -> Revisión que realiza UDICA
                            $template->setVariable(array(
                                'estado' => "<b>Asignado</b><br><br>$asignacion[nombre_departamento] - $asignacion[nombre_municipio]"
                            ));
                            $template->parse("estado_eps");

                            break;
                        }
                }
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

            // Proceso culminado con exito
            if (isset($_SESSION['proceso_finalizado'])) {
                $proceso_finalizado = $_SESSION['proceso_finalizado'];
                $template->setVariable(array(
                    'mensaje_proceso_finalizado' => "
                        <div id='base_proceso_finalizado'>
                            <div id='finalizado'>
                            $proceso_finalizado<br><br>
                            <div id='acciones'>
                                <input id='btn_azul' type='button' value='Aceptar' OnClick='window.location.reload()' autofocus>
                            </div>
                        </div>
                    </div>"
                ));
                unset($_SESSION['proceso_finalizado']);
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
