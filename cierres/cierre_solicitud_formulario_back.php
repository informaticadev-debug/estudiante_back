<?php

/*
  Document   : cierre_solicitud_formulario.php
  Created on : 08-Jun-2015, 16:41
  Author     : Angel Caal
  Description:
  -> Solicitud de constancias de cierre
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
	$fecha_desabilita = "2023-11-23 23:59";
    // cambiar formato y habilitar para que solo sea tramitado para usos personales, los cierres no son necesarios porque registro y estadistica no los solicita porque se alimenta la base de datos

        if ($fecha_actual >= $fecha_desabilita && $carnet <> 202099999) {
            $aviso = true;
            $mensaje = "Las solicitud en línea de cierres de pensum será habilitada según el calendario 2023.";
            $url = "../menus/contenido.php";
        } else if(in_array($carnet, [])) {
            $aviso = true;
            $mensaje = "Limite de solicitudes de cierre de pensum excedidas.";
            $url = "../menus/contenido.php";
        } else {

            // Carreras en las que se ha inscrito el estudiante
            $consulta = "SELECT c.carrera AS codigo_carrera, TRIM(ce.nombre) AS nombre_carrera
			FROM carrera_estudiante c
			INNER JOIN carrera ce
			ON ce.carrera = c.carrera
			WHERE c.carnet = $carnet AND c.fecha_cierre IS NOT NULL";
            $carreras = & $db->getAll($consulta);
            if ($db->isError($carreras)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener el detalle de carrera del estudiante.";
                $url = "../menus/contenido.php";
            } else {

                if (count($carreras) == 0) {
                    $aviso = true;
                    $mensaje = "No existe cierre de pensum registrado, haga sus consultas al correo informatica@farusac.edu.gt.";
                    $url = "../menus/contenido.php";
                } else {

                    // Verificar si existen solicitudes de constancia de cierre en cola de impresión
                    $consulta = "SELECT *
					FROM constancias_cierre c
					WHERE c.carnet = $carnet AND c.estado = 0";
                    $solicitudes_cola = & $db->getAll($consulta);
                    if ($db->isError($solicitudes_cola)) {
                        $error = true;
                        $mensaje = "Hubo un problema al obtener el estado de cola de impresión para constancias de cierre de pensum.";
                        $url = "../menus/contenido.php";
                    } else {

                        if (count($solicitudes_cola) <> 0) {
                            $aviso = true;
                            $mensaje = "Tiene solicitudes de cierre de pensum en cola de impresión por favor, culmine el proceso para poder solicitar nuevas constancias de cierre de pensum.";
                            $url = "../menus/contenido.php";
                        }
                    }
                }
            }
        }

        if (!$error && !$aviso) {

            // Cargando la pagina para mostrar las ordenes de Pago.
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('cierre_solicitud_formulario.html');

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
