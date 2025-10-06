<?php

/*
  Documento  : certificacion_solicitud_procesar.php
  Creado el  : 30-sep-2014, 11:45
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
        $ponderada = $_POST['ponderada'];

        for ($i = 0; $i < $cantidad; $i++) {

            // Registrar la solicitud de certificacion
            $consulta = "INSERT INTO reporte
            (anio, listado, carnet, carrera, no_reporte, entidad, fecha_solicitud, cantidad, ponderada)
            VALUES (
                $anio,
				1,
                $carnet,
                $carrera,
                1,
                0,
				NOW(),
				$cantidad,
				$ponderada
            )";
            //var_dump($consulta); die;
            $registrar_certificacion = & $db->Query($consulta);
            if ($db->isError($registrar_certificacion)) {
                $error = true;
                $mensaje = "Hubo un problema al registrar la certificación." . mysqli_error();
                $url = $_SERVER[HTTP_REFERER];
                $db->rollback();
            } else {
                $db->commit();
            }
        }


        if (!$error) {

            $proceso_finalizado = "Se ha registrado correctamente la solicitud de la certificación, por favor, descargar la boleta de pago (en la sección de \"Certificaciones de cursos->Historial de solicitudes\") y realizar el pago correspondiente.";
            $_SESSION['proceso_finalizado'] = $proceso_finalizado;
            echo "
                <script>
                    window.open('../certificaciones/certificacion_solicitud_formulario_nf.php','contenido');
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
