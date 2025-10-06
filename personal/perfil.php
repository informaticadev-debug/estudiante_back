<?php

/*
  Perfil del estudiante
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

        // Consulta datos personales del estudiante
        $consulta = "SELECT e.carnet, trim(e.nombre) AS nombre, trim(e.direccion) AS direccion,
		e.telefono, e.celular, e.email_fda, e.dpi,
		IF(
			e.extension = 0, 'Campus Central', 'Centro Universitario de Occidente'
		) AS extension
		FROM estudiante e
		WHERE e.carnet = $carnet";
        $datos_personales = & $db->getRow($consulta);
        if ($db->isError($datos_personales)) {
            $error = true;
            $mensaje = "Hubo un error al intentar consultar tus datos personales";
        }

        if (!$error) {

            $template = new HTML_TEMPLATE_SIGMA('../templates');
            $template->loadTemplateFile('perfil.html');

            $template->setVariable(array(
                'carnet' => $carnet,
                'nombre' => $datos_personales[nombre],
                'direccion' => $datos_personales[direccion],
                'telefono' => $datos_personales[telefono],
                'celular' => $datos_personales[celular],
                'email_fda' => $datos_personales[email_fda],
                'dpi' => $datos_personales[dpi],
                'extension' => $datos_personales[extension]
            ));

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