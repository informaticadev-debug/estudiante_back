<?php

/*
  EXAMEN PRIVADO
  -> Proceso para obtener examen publico
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
        $error = false;

        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];

        if (!(isset($_SESSION['nombre_estudiante']))) {
            $carnet = $_POST['carnet'];
            $carrera = $_POST['carrera'];
            $nombre_estudiante = $_POST['nombre_estudiante'];
            $dpi = $_POST['dpi1'] . " " . $_POST['dpi2'] . " " . $_POST['dpi3'];
            $email_fda = $_POST['email_fda'];
            $telefono = $_POST['telefono'];
            $direccion = $_POST['direccion'];

            $_SESSION['carnet'] = $carnet;
            $_SESSION['carrera'] = $carrera;
            $_SESSION['nombre_estudiante'] = $nombre_estudiante;
            $_SESSION['dpi1'] = $_POST['dpi1'];
            $_SESSION['dpi2'] = $_POST['dpi2'];
            $_SESSION['dpi3'] = $_POST['dpi3'];
            $_SESSION['email_fda'] = $email_fda;
            $_SESSION['telefono'] = $telefono;
            $_SESSION['direccion'] = $direccion;
        }

        if (!$error) {

            // Cargar template de formulario para actualizacion
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('examen_publico_instructivo_impresion.html');

            if (empty($_POST['nombre_estudiante'])) {
                $accion_solicitud = "../publico/examen_publico_solicitud_impresion.php";
            } else {
                $accion_solicitud = "../publico/examen_publico_solicitudimpresion_procesar.php";
            }

            $template->setVariable(array(
                'accion_solicitud' => $accion_solicitud
            ));

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
            mostrarError($mensaje);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>