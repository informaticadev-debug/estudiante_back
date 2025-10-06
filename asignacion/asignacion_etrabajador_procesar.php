<?php

/*
  Documento  : asignacion_extemporanea_procesar.php
  Creado el  : 11-Jul-2016, 17:04
  Author     : Angel Caal
  Description:
  Procesar la solicitud para asignación extemporanea
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../lib/nusoap.php';

session_start();
if (isset($_SESSION["usuario"])) {

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
        $url = "../index.php";
        error($mensaje, $url);
    } else {

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $db->autoCommit(false);

        //extraccion de variables basicas...
        $extension = $_SESSION['extension'];
        $carnet = $_SESSION['usuario'];
        $anio = DATE("o");
        $semestre = $_SESSION['semestre'];
        $evaluacion = 1;
        
        $anioRegistro = 2019;
        $semestreRegistro = 1;
        $evaluacionRegistro = 1;

        //verificando solicitudes previas o falta de autorizacion
        $solicitud = getAsignacionETrabajadorSolicitud($db, $extension, $anioRegistro, $semestreRegistro, $evaluacionRegistro, $carnet);
        /* A PARTIR DEL 1ER. SEMESTRE 2018 SE LIBERO PARA TODOS LOS ESTUDIANTES...
        if (empty($solicitud)) {
            aviso("Usted no tiene permitido el ingreso a esta opción.", "../menus/contenido.php");
            exit;
        } else*/ 
        if (!empty($solicitud) && array_key_exists('estado', $solicitud) && $solicitud['estado'] > 0) {
            aviso("Usted ya realizo una solicitud, la resolución sera enviada por un mensaje en su perfil de estudiante.", "../menus/contenido.php");
            exit;
        }

        // Cargar primero el archivo para respaldar los requisitos
        $ext_archivo = explode(".", strtolower($_FILES['files']['name']));

        if ($ext_archivo[count($ext_archivo) - 1] <> "pdf") {
            $aviso = true;
            $mensaje = "El formato de archivo permitido es: <b>pdf</b>. Usted está intentando subir un formato <b>" . $ext_archivo[count($ext_archivo) - 1] . "</b>";
            $url = $_SERVER['HTTP_REFERER'];
        } else {

            if (is_uploaded_file($_FILES['files']['tmp_name'])) {
                move_uploaded_file($_FILES['files']['tmp_name'], "../docs/asignacion_etrabajador_requisitos/$extension-$anioRegistro-$semestreRegistro-$evaluacionRegistro-$carnet." . $ext_archivo[count($ext_archivo) - 1]);
                // Registrar la solicitud
                /* A PARTIR DEL 1ER SEMESTRE 2018, SE CREA EL REGISTRO
                 * $consulta = "UPDATE asignacion_etrabajador
                                SET estado = 1, fecha_carga = NOW()
                            WHERE extension = $extension AND anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion AND carnet = $carnet
                        ";*/
                $consulta = "INSERT INTO `satu`.`asignacion_etrabajador` (
                                `extension`,
                                `anio`,
                                `semestre`,
                                `evaluacion`,
                                `carnet`,
                                `fecha_registro`,
                                `fecha_carga`,
                                `fecha_resolucion`,
                                `usuario_registro`,
                                `usuario_revision`,
                                `estado`,
                                `observacion`,
                                `resolucion`
                              )
                              VALUES
                                (
                                  '$extension',
                                  '$anioRegistro',
                                  '$semestreRegistro',
                                  '$evaluacionRegistro',
                                  '$carnet',
                                  NOW(),
                                  NOW(),
                                  NULL,
                                  'estudiante',
                                  NULL,
                                  1,
                                  NULL,
                                  0
                                );
                              ";
                $registrar_solicitud = & $db->Query($consulta);
                if ($db->isError($registrar_solicitud)) {
                    $error = true;
                    $mensaje = "Hubo un problema al registrar la solicitud";
                    $url = $_SERVER['HTTP_REFERER'];
                    $db->rollback();
                }
                $db->commit();
            }
        }

        if (!$error && !$aviso) {
            $_SESSION['proceso_finalizado'] = "Se ha cargado exitosamente el documento. La dirección de escuela, le notificará la resolución a través de la plataforma de estudiantes";
            header("location: ../menus/contenido.php");
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
