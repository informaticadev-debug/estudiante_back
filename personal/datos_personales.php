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

        // Cargando la pagina para mostrar las ordenes de Pago.
        $template = new HTML_Template_Sigma('../templates');
        $template->loadTemplateFile('datos_personales.html');

        $carnet = $_SESSION['usuario'];
        $anio = DATE("o");

        $mensaje_error = "";
        $mensaje_aviso = "";

        //previo a la consulta de datos, verificar que no hayan cambios...
        if (!empty($_POST)) {
            //actualizar información personal
            if (isset($_POST["email_fda"])) {
                $correo = str_replace("'", "\"", $_POST["email_fda"]);
                $telefono = str_replace("'", "\"", $_POST["telefono"]);
		$celular = str_replace("'", "\"", $_POST["celular"]);
		$direccion = str_replace("'", "\"", $_POST["direccion"]);
                $result = $db->query("UPDATE estudiante SET email_fda = '$correo', telefono = '$telefono', celular = '$celular', direccion='$direccion', actualizar_datos = 0, fecha_act_datos = NOW() WHERE carnet = $carnet");
                if ($db->isError($result)) {
                    $mensaje_error .= "Hubo un error al actualizar la información personal.";
                } else {
                    $mensaje_aviso .= "La información personal ha sido actualizada correctamente.";
                }
            }
            //actualizar contraseña..
            if (isset($_POST["passwd"])) {
                $passwd = str_replace("'", "\"", $_POST["passwd"]);
                $passwd1 = str_replace("'", "\"", $_POST["passwd1"]);
                $passwd2 = str_replace("'", "\"", $_POST["passwd2"]);
                //verificar password...
                $estudiante = & $db->getRow("SELECT * FROM estudiante WHERE carnet = $carnet AND password = MD5('$passwd')");
                if (empty($estudiante)) {
                    $mensaje_error .= "La contraseña actual es incorrecta.";
                } else {
                    if ($passwd1 != $passwd2) {
                        $mensaje_error .= "La contraseña no coincide con la contraseña de verificación.";
                    } else {
                        //creando el bcrypt con costo 12...
                        $hashBcrypt = password_hash($passwd1, PASSWORD_BCRYPT, ['cost' => 10,]);
                        $result = $db->query("UPDATE estudiante SET password = MD5('$passwd1'), passwd = '{bcrypt}$hashBcrypt', actualizar_passwd = 0, fecha_act_pass = NOW() WHERE carnet = $carnet");
                        if ($db->isError($result)) {
                            $mensaje_error .= "Hubo un error al actualizar la contraseña.";
                        } else {
                            $mensaje_aviso .= "La contraseña fue actualizada correctamente.";
                        }
                    }
                }
            }
        }

        // Consulta de datos actuales...
        $consulta = "SELECT * FROM estudiante WHERE carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener el detalle de carrera del estudiante.";
            $url = "";
        }

        if (!$error && !$aviso) {
            $template->setVariable(array(
                'est_ra' => $estudiante["carnet"],
                'est_cui' => $estudiante["dpi"],
                'est_nombre' => $estudiante["nombre"],
                'est_email_fda' => $estudiante["email_fda"],
                'est_celular' => $estudiante["celular"],
		'est_telefono' => $estudiante["telefono"],
		"est_direccion" => $estudiante["direccion"],
                'mensaje_aviso' => $mensaje_aviso,
                'mensaje_error' => $mensaje_error,
                'mensaje_obligatorio_passwd' => ($estudiante["actualizar_passwd"] == 1) ? "*** Actualización obligatoria ***" : "",
                'mensaje_obligatorio_datos' => ($estudiante["actualizar_datos"] == 1) ? "*** Actualización obligatoria ***" : "",
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
