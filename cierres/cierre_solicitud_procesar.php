<?php

/*
  Documento  : cierre_solicitud_procesar.php
  Creado el  : 08-Jun-2015, 16:49
  Author     : Angel Caal
  Description:
  Procesar solicitud de certificacion
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
        $db->autoCommit(false);

        $carnet = $_SESSION['usuario'];
        $anio = DATE("o");
        $cantidad = $_POST['cantidad'];
        $carrera = $_POST['carrera'];

        for ($i = 0; $i < $cantidad; $i++) {

            // Registrar la solicitud de cierre de pensum
            $consulta = "INSERT INTO constancias_cierre
            (anio, carnet, carrera, fecha_solicitud)
            VALUES (
                $anio,
                $carnet,
                $carrera,
                NOW()
            )";
            $registrar_cierre = & $db->Query($consulta);
            if ($db->isError($registrar_cierre)) {
                $error = true;
                $mensaje = "Hubo un problema al registrar la solicitud de constancia de cierre.";
                $url = $_SERVER[HTTP_REFERER];
                $db->rollback();
            } else {
                $db->commit();
            }
        }

        if (!$error) {

            //$proceso_finalizado = "Se ha registrado la solicitud de constancia de cierre de pensum, por favor, la constancia le será enviada al correo electrónico registrado en está plataforma. Sí no ha actualizado su correo electrónico, por favor, realizar la actualización para que su documento le sea enviado a la dirección correcta.";
            $proceso_finalizado = "Se ha registrado la solicitud de constancia de cierre de pensum, por favor, presentarse a la ventanilla única de Control Académico 8 días habiles tras la solicitud,
                para obtener la constancia de cierre de pensum impresa.";
            $_SESSION['proceso_finalizado'] = $proceso_finalizado;
            echo "
                <script>
                    window.open('../menus/contenido.php','contenido');
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
