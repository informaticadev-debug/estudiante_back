<?php

/*
  Documento  : proyecto_graduacion_solicitud_procesar.php
  Creado el  : 12 de junio de 2014, 10:06
  Author     : Angel Caal
  Description:
  Confirmación de la solicitud para entrar a revisión para su futura aprobación o desaprobación
  por parte del comite de proyecto de graduación.
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

        $grupo_objetivo = $_POST['grupo_objetivo'];
        $numero_tema = $_POST['numero_tema'];


        // Registro de protocolo para proceder a verificación por el comité
        $consulta = "UPDATE proyecto_graduacion_protocolo p
		SET p.grupo_objetivo = '$grupo_objetivo'
		WHERE p.numero_tema = $numero_tema";
        $registrar_protocolo = & $db->Query($consulta);
        if ($db->isError($registrar_protocolo)) {
            $error = true;
            $mensaje = "Hubo un problema durante el registro del protocolo, por favor intente nuevamente.";
            $url = $_SERVER[HTTP_REFERER];
            $db->rollback();
        } else {
            $db->commit();
        }

        if (!$error) {

            $proceso_finalizado = "Se ha almacenado el grupo objetivo.";
            $_SESSION['proceso_finalizado'] = $proceso_finalizado;
            echo "
                <script>
                    window.open('../proyecto_graduacion/proyecto_graduacion_gestion.php','contenido');
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