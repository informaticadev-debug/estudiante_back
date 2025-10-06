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
        $db->autoCommit(false);
        $error = false;

        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carrera = $_SESSION['carrera'];
        $carnet = $_SESSION['usuario'];
        $dpi = $_SESSION['dpi1'] . " " . $_SESSION['dpi2'] . " " . $_SESSION['dpi1'];
        $direccion = $_SESSION['direccion'];
        $telefono = $_SESSION['telefono'];
        $correo = $_SESSION['email_fda'];

        // Actualizacion de datos del estudiante
        $consulta = "UPDATE estudiante e
        SET e.dpi = '$dpi', e.email_fda = '$correo', e.telefono = $telefono, e.direccion = '$direccion'
        WHERE e.carnet = $carnet";
        $actualizar_datos = & $db->Query($consulta);
        if ($db->isError($actualizar_datos)) {
            $error = true;
            $mensaje = "Hubo un problema al actualizar los datos del estudiante." . mysql_error();
            $url = $_SERVER[HTTP_REFERER];
            $db->rollback();
        } else {

            // Creacioón de la solicitud de impresion
            $consulta = "INSERT INTO examen_publico
            (extension, anio, semestre, carrera, carnet, fecha_solicitud)
            VALUES (
            $extension,
            $anio, 
            $semestre,
            $carrera,
            $carnet,
            NOW()
            )";
            $registrar_solicitud = & $db->Query($consulta);
            if ($db->isError($registrar_solicitud)) {
                $error = true;
                $mensaje = "Hubo un problema al registrar la solicitud de impresión.";
                $url = $_SERVER[HTTP_REFERER];
                $db->rollback();
            } else {

                $db->commit();

                if (isset($_SESSION['nombre_estudiante'])) {
                    unset($_SESSION['carnet']);
                    unset($_SESSION['carrera']);
                    unset($_SESSION['nombre_estudiante']);
                    unset($_SESSION['dpi1']);
                    unset($_SESSION['dpi2']);
                    unset($_SESSION['dpi3']);
                    unset($_SESSION['email_fda']);
                    unset($_SESSION['telefono']);
                    unset($_SESSION['direccion']);
                }
            }
        }


        if (!$error) {

            $_SESSION['proceso_finalizado'] = "Se han actualizado sus datos personales y hemos registrado la solicitud de aprobación de impresión, se le notificará en la plataforma. Por favor este pendiente de la respuesta.";

            if (isset($_SESSION['proceso_finalizado'])) {

                echo "
                    <script>
                        window.open('../proyecto_graduacion/proyecto_graduacion_gestion.php','contenido');
                    </script>
                ";
            }
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