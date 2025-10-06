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
        $carnet = $_SESSION['usuario'];

        // Consulta de la carrera de solicitud
        $consulta = "SELECT *
		FROM examen_publico p
		WHERE p.carnet = $carnet AND p.acta_publico IS NULL";
        $datos_publico = & $db->getRow($consulta);
        if ($db->isError($datos_publico)) {
            $error = true;
            $mensaje = "Hubo un problema al determinar la carrera del estudiante.";
            $url = $_SERVER[HTTP_REFERER];
        }
        
        if (!$error) {
            
                // Cargar template de formulario para actualizacion
                $template = new HTML_Template_Sigma('../templates');
                $template->loadTemplateFile('examen_publico_instructivo_publico.html');
                
            if ($datos_publico[carrera] == 1) {

                $template->setVariable(array(
                    'variacion_calusac' => "4.	Para los que cerraron con pensum 2002
                                    <ul>
                                        <li>I. Original de certificación de dominio de un segundo idioma extendido por CALUSAC. En el caso del idioma inglés, el certificado es por dominar como mínimo el nivel 10.</li>
                                        <li>II.	Constancia de dominio de programas de computación extendido por INTECAP con un mínimo de 40 horas.</li>
                                    </ul>"
                ));
            } else if ($datos_publico[carrera] == 3) {
                $template->setVariable(array(
                    'variacion_calusac' => "4.	Original de certificación de dominio de un segundo idioma extendido por CALUSAC. En el caso del idioma inglés, el certificado es por dominar como mínimo el nivel 10."
                ));
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
