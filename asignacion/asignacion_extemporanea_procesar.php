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
        $url = "../index.php";
        error($mensaje, $url);
    } else {

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $db->autoCommit(false);

        $carrera = $_POST['carrera'];
        $extension = $_SESSION['extension'];
        $anio = DATE("o");
        $semestre = $_SESSION['semestre'];
        $evaluacion = 1;
        $asignaturas = $_POST['asignaturas'];
        $carnet = $_SESSION['usuario'];
        $motivo = $_POST['motivo'];
        $detalle = $_POST['detalle'];
        $solicitud = substr(MD5($carnet . DATE("omdhis")), 8, 6);

        if (empty($asignaturas) && false) {
            $aviso = true;
            $mensaje = "Debe seleccionar al menos una asignatura para procesar la solicitud";
            $url = $_SERVER['HTTP_REFERER'];
        } else {

            // Cargar primero el archivo para respaldar los requisitos
            /* $ext_archivo = explode(".", strtolower($_FILES['files']['name']));

              if ($ext_archivo[count($ext_archivo) - 1] <> "pdf") {
              $aviso = true;
              $mensaje = "El formato de archivo permitido es: <b>PDF</b>, usted está intentando subir un formato <b>" . $ext_archivo[count($ext_archivo) - 1];
              $url = $_SERVER['HTTP_REFERER'];
              } else { */

            //if (is_uploaded_file($_FILES['files']['tmp_name'])) {
            //move_uploaded_file($_FILES['files']['tmp_name'], "../docs/asignacion_extemporanea_requisitos/" . $solicitud . "_" . $carnet . "." . $ext_archivo[count($ext_archivo) - 1]);

            /* foreach ($asignaturas AS $as) {

              // Armar arreglo para procesar solicitud
              $consulta = "SELECT c.pensum, c.codigo, trim(c.nombre) AS asignatura
              FROM curso c
              WHERE CONCAT(c.pensum,c.codigo) = '$as'";
              $curso = & $db->getRow($consulta);
              if ($db->isError($curso)) {
              $error = true;
              $mensaje = "Hubo un problema al obtener el detalle de los cursos para realizar la solicitud";
              $url = $_SERVER['HTTP_REFERER'];
              } else {

              $cursos[] = array(
              'pensum' => $curso['pensum'],
              'codigo' => $curso['codigo'],
              'asignatura' => $curso['asignatura']
              );
              }
              } */

            //if (!empty($cursos) || true) {
            // Registrar la solicitud
            $consulta = "INSERT INTO asignacion_extemporanea_solicitudes
                        (solicitud, extension, anio, semestre, evaluacion, carnet, carrera, motivo, detalle, fecha_solicitud)
                        VALUES (
                            '$solicitud',
                            $extension,
                            $anio,
                            $semestre,
                            $evaluacion,
                            $carnet,
							$carrera,
                            $motivo,
                            '$detalle',
                            NOW()
                        )";
            $registrar_solicitud = & $db->Query($consulta);
            $db->commit();
            if ($db->isError($registrar_solicitud)) {
                $error = true;
                $mensaje = "Hubo un problema al registrar la solicitud";
                $url = $_SERVER['HTTP_REFERER'];
                $db->rollback();
            } else {

                $i = 1;

                foreach ($cursos AS $cu) {

                    // Almacenar el arreglo de cursos en la solicitud
                    $consulta = "INSERT INTO asignacion_extemporanea_solicitudes_detalles
                                (solicitud, pensum, codigo)
                                VALUES (
                                    '$solicitud',
                                    $cu[pensum],
                                    '$cu[codigo]'
                                )";
                    $registrar_detallesolicitud = & $db->Query($consulta);
                    if ($db->isError($registrar_detallesolicitud)) {
                        $error = true;
                        $mensaje = "Hubo un problema al registrar el detalle de la solicitud";
                        $url = $_SERVER['HTTP_REFERER'];
                        $db->rollback();
                    } else {
                        $db->commit();

                        $detalle_solicitud .= $i . ". " . $cu['codigo'] . " - " . $cu['asignatura'] . "<br><br>";
                    }

                    $i++;
                }

                // Enviar correo electrónico con detalle de la solicitud
                /* $asunto = "Solicitud - Asignación Extemporanea";
                  $correo = "<![CDATA[
                  <body style='width: 100% !important;min-width: 100%;-webkit-text-size-adjust: 100%;-ms-text-size-adjust: 100% !important;margin: 0;padding: 0;background-color: #FFFFFF'>
                  <table border='0' width='100%' style='position: absolute; font-family: Helvetica; width: 100%'>
                  <tr>
                  <td style='background: #E6E6E6; padding: 10px; text-align: center; font-size: 20px; font-family: Helvetica'>
                  <img src='http://arquitectura.usac.edu.gt/estudiante/images/farusac.png' style='width: 100%;max-width: 140px'><br>
                  <b>Facultad de Arquitectura</b><br>
                  <small>UNIVERSIDAD DE SAN CARLOS DE GUATEMALA</small>
                  </td>
                  </tr>
                  <tr>
                  <td style='padding: 20px; font-family: Helvetica;'>
                  <font style='font-size: 18px;'><b>Estimado estudiante</b></font><br>
                  <font style='font-size: 16px;'>Le saludamos cordialmente, su solicitud de asignación extemporanea ha sido realizada exitosamente,
                  debe estar pendiente de la resolución que enviaremos a este correo. Los detalles de la solicitud son:<br><br>

                  $detalle_solicitud</font>
                  </td>
                  </tr>
                  <tr>
                  <td style='background: #2E2E2E; padding: 20px; font-size: 12px; font-family: Helvetica; color: #FFFFFF'>
                  <a style='text-decoration: none; background: #FFFFFF; padding: 5px; border-radius: 3px; color: #2E2E2E; font-weight: bold' href='http://www.arquitectura.usac.edu.gt/estudiante/'>SIAE</a>
                  <a style='text-decoration: none; background: #FFFFFF; padding: 5px; border-radius: 3px; color: #2E2E2E; font-weight: bold' href='http://farusac.com/'>Información</a>
                  <br><br>
                  Ciudad Universitaria, Edificio T2 Zona 12
                  </td>
                  </tr>
                  <body>    
                  ";

                  // Enviar datos de acceso a los usuarios que
                  $wsdl = "http://107.170.77.252/ws/ws_correo.php?wsdl";
                  $client = new nusoap_client($wsdl, 'wsdl');
                  $err = $client->getError();

                  $data = array(
                  'nombre' => $carnet,
                  'nombre_remitente' => "Facultad de Arquitectura - USAC",
                  'correo' => $carnet . "@farusac.edu.gt",
                  'correo_remitente' => "noreply@farusac.edu.gt",
                  'asunto' => $asunto,
                  'mensaje' => $correo
                  ); */

                //$res = $client->call('envio_correo', $data);
            }
            //}
            //}
            //}
        }

        if (!$error && !$aviso) {

            //$_SESSION['proceso_finalizado'] = "La solicitud de asignación extemporánea ha sido registrada exitosamente, hemos enviado un correo a su cuenta $carnet@farusac.edu.gt.";
            $_SESSION['proceso_finalizado'] = "La solicitud de asignación extemporánea ha sido registrada exitosamente, este pendiente a la resolución de la misma.";
            echo "
          <script>
          window.open('../menus/contenido.php','contenido');
          </script>
          ";
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
