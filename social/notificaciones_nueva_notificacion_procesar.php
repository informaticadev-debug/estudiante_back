<?php

/*
  Proceso de Asignacion para Interciclos
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
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
        $error = false;

        $destinatario = $_POST['destinatario'];
        $notificacion = $_POST['consulta'];
        $usuario = $_SESSION['usuario'];
        $rol_destinatario = $_POST['rol_destinatario'];

        // Almacenar notificacion
        $consulta = "INSERT INTO notificaciones
        (destinatario, notificacion, rol, fecha_creacion, usuario_creacion)
        VALUES (
            $destinatario,
            '$notificacion',
            $rol_destinatario,
            NOW(),
            $usuario
        )";
        $enviar_notificacion = & $db->Query($consulta);
        if ($db->isError($enviar_notificacion)) {
            $error = true;
            $mensaje = "Hubo un error durante el envio de la notificación." . mysqli_error();
            $url = $_SERVER[HTTP_REFERER];
        }

        if (!$error && !$aviso) {

            $_SESSION['proceso_finalizado'] = "Se ha enviado la notificacion, por favor espere a que el destinatario responda.";

            echo "
                <script>
                    window.open('../social/notificaciones.php','contenido');
                </script>
            ";
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
