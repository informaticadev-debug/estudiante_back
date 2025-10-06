<?php

/*
  Datos personales del estudiante
  -> Actualizacion de informacion y datos perso.
  ->
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

        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];
        $celular = $_POST['celular'];
        $correo = $_POST['email_fda'];
        if (!empty($_POST['dpi1'])) {
            $dpi = $_POST['dpi1'] . " " . $_POST['dpi2'] . " " . $_POST['dpi3'];
        } else {
            $dpi = "0000 00000 0000";
        }
        $estado_civil = $_POST['estado_civil'];

        // Actualizacion de datos del estudiante
        $consulta = "UPDATE estudiante e
		SET e.direccion = '$direccion', e.telefono = $telefono, e.celular = $celular, e.email_fda = '$correo',
		e.dpi = '$dpi', e.estado_civil = $estado_civil, e.fecha_actualizacion = NOW()
		WHERE e.carnet = $carnet";
        $actualizacion_datos = & $db->Query($consulta);
        if ($db->isError($actualizacion_datos)) {
            $error = true;
            $mensaje = "Hubo un error durante la actualización de los datos del estudiante.";
        }

        if (!$error) {
            echo "
				<script>
					window.parent.document.getElementById('base_perfil').style.display = 'none';
					window.open('../personal/perfil.php','contenido');
				</script>
			";
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