<?php

/*
  EPS
  -> Proceso de registro de solicitud de EPS
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
        $semestre = "2";
        $evaluacion = $_POST['evaluacion'];
        $pensum = $_POST['pensum'];
        $codigo = $_POST['codigo'];
        $carnet = $_POST['carnet'];
        $carrera = $_POST['carrera'];
        $region_eps = $_POST['region_eps'];

        // Registro de solicitud de EPS
        $consulta = "INSERT INTO eps_solicitud
        (extension, anio, semestre, evaluacion, carnet, carrera, pensum, codigo, region_eps, fecha_solicitud)
        VALUES (
            $extension,
            $anio,
            $semestre,
            $evaluacion,
            $carnet,
            $carrera,
            $pensum,
            '$codigo',
            $region_eps,
            NOW()
        )";
        $registrar_solicitud = & $db->Query($consulta);
        if ($db->isError($registrar_solicitud)) {
            $error = true;
            $mensaje = "Hubo un problema al registrar la solicitud de E.P.S. Por favor, intente nuevamente o notifique al programador.";
            $url = $_SERVER[HTTP_REFERER];
            $db->rollback();
        } else {

            $db->commit();
        }

        if (!$error) {

            $_SESSION['proceso_finalizado'] = "La solicitud de asignación a sido procesada con éxito.";

            if (isset($_SESSION['proceso_finalizado'])) {

                echo "
                    <script>
                        window.open('../eps/eps_gestion.php','contenido');
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