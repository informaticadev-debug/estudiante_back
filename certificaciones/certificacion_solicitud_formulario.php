<?php

/*
  Document   : certificacion_solicitud_formulario.php
  Created on : 30-sep-2014, 10:23
  Author     : Angel Caal
  Description:
  Solicitud de certificaciones de cursos
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();

verificarActualizarDatos();

if (isset($_SESSION[usuario])) {

    $errorLogin = false;
    $error = false;
    $aviso = false;

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

        $carnet = $_SESSION['usuario'];
        $anio = DATE("o");

        $fecha_actual = DATE("o-m-d H:i");
        $fecha_desabilita = "2022-11-22 18:00";

        if ($fecha_actual >= $fecha_desabilita) {
            $aviso = true;
            $mensaje = "Las solicitud en línea de certificación de asignaturas aprobadas será habilitada según calendario de labores del año 2020.";
            $url = "../menus/contenido.php";
        } else {

            // Carreras en las que se ha inscrito el estudiante
            $consulta = "SELECT c.carrera AS codigo_carrera, TRIM(ce.nombre) AS nombre_carrera
			FROM carrera_estudiante c
			INNER JOIN carrera ce
			ON ce.carrera = c.carrera
			WHERE c.carnet = $carnet";
            $carreras = & $db->getAll($consulta);
            if ($db->isError($carreras)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener el detalle de carrera del estudiante.";
                $url = "../menus/contenido.php";
            } else {

                // Consulta de procesos sin finalizar
                $consulta = "SELECT *
				FROM reporte r
				WHERE r.carnet = $carnet AND r.anio = $anio AND r.listado = 1 AND r.fecha_impresion IS NULL";
                $certificacion_impresa = & $db->getAll($consulta);
                if ($db->isError($certificacion_impresa)) {
                    $error = true;
                    $mensaje = "Hubo un problema al verificar el estado de solicitudes de certificacion.";
                    $url = "../menus/contenido.php";
                } else {

                    if (count($certificacion_impresa)) {
                        $aviso = true;
                        $mensaje = "Actualmente tiene solicitudes de certificación no impresas, debe culminar la solicitud para poder realizar una nueva.";
                        $url = "../menus/contenido.php";
                    }
                }
            }
        }

        if (!$error && !$aviso) {

            // Cargando la pagina para mostrar las ordenes de Pago.
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('certificacion_solicitud_formulario.html');

            foreach ($carreras AS $ca) {

                $template->setVariable(array(
                    'codigo_carrera' => $ca[codigo_carrera],
                    'nombre_carrera' => $ca[nombre_carrera]
                ));
                $template->parse("listado_carreras");
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
