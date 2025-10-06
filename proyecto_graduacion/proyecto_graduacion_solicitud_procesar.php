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
if (isset($_SESSION['usuario'])) {

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

        $extension = $_SESSION['extension'];
        $db->Query("SET NAMES utf8");

        $carnet = $_SESSION['usuario'];
        $carrera = $_SESSION['carrera'];
        $numero_tema = numero_tema($db, $carnet, $carrera);
        $primer_nombre = $_SESSION['primer_nombre'];
        $segundo_nombre = $_SESSION['segundo_nombre'];
        $tercer_nombre = $_SESSION['tercer_nombre'];
        $primer_apellido = $_SESSION['primer_apellido'];
        $segundo_apellido = $_SESSION['segundo_apellido'];
        $email_fda = $_SESSION['email_fda'];
        $proyecto_graduacion = $_SESSION['proyecto_graduacion'];
        $departamento = $_SESSION['departamento'];
        $municipio = $_SESSION['municipio'];
        $descripcion = $_SESSION['descripcion'];
        $tema_enmarcado = $_SESSION['tema_enmarcado'];
        $modalidad = $_SESSION['modalidad'];
        if($carrera == 3){
            $modalidad = 6;
        }
        $signos = array("'");
        $reemplazo = array('"');

        $descripcion = str_replace($signos, $reemplazo, $descripcion);
        $proyecto_graduacion = str_replace($signos, $reemplazo, $proyecto_graduacion);

        // Almacenar solicitud pero no confirmar hasta el siguiente paso 
        $consulta = "INSERT INTO proyecto_graduacion
        (numero_tema, extension, carnet, carrera, primer_nombre, segundo_nombre, tercer_nombre, primer_apellido, segundo_apellido, email_fda, 
        proyecto_graduacion, modalidad, departamento, municipio, descripcion, tema_enmarcado, fecha_creacion, estado)
        VALUES(
            $numero_tema,
            $extension,
            $carnet,
            $carrera,
            '$primer_nombre',
            '$segundo_nombre',
            '$tercer_nombre',
            '$primer_apellido',
            '$segundo_apellido',
            '$email_fda',
            '$proyecto_graduacion',
            $modalidad,
            $departamento,
            $municipio,
            '$descripcion',
            '$tema_enmarcado',
            NOW(),
            0
        )";

        //if ($carnet == 20023164) {
        //       var_dump($descripcion); 
        //	echo "<br />";
        //	echo "<br />";
        //	var_dump($dsn);
        //        echo "<br />";
        //       echo "<br />";
//		var_dump($proyecto_graduacion);
//		echo "<br />";
//		echo "<br />";
//		echo $consulta;
//		die;
        //mysqli://estudiante:estudiante@192.168.10.251/satu
        //      }
        //var_dump($consulta); die;
        $correcto = true;
        $conn = new mysqli($host, $user, $pass, "satu");
        if ($conn->connect_errno) {
            $correcto = false;
        } else {
            $correcto = $conn->query($consulta);
        }

        //$registro_solicitud = & $db->Query($consulta);
        //anterior  $db->isError($registro_solicitud
        if (!$correcto) {
            $error = true;
            $mensaje = "Hubo un problema durante el registro de la solicitud de tema para proyecto de graduación. (E1.0)";
            $url = "../proyecto_graduacion/proyecto_graduacion_gestion.php";
        } else {

            // Consulta de los datos actualmente almacenados
            $consulta = "SELECT *
            FROM proyecto_graduacion g
            WHERE g.numero_tema = $numero_tema AND g.carnet = $carnet";
            $datos_solicitud = & $db->getRow($consulta);
            if ($db->isError($datos_solicitud)) {
                $error = true;
                $mensaje = "Hubo un problema durante la consulta de los datos de la solicitud realizada.";
                $url = "../proyecto_graduacion/proyecto_graduacion_gestion.php";
            } else {

                // Registrar bitacora tema
                $consulta = "INSERT INTO proyecto_graduacion_bitacora
                (numero_tema, extension, carnet, carrera, fecha_creacion)
                VALUES (
                        $numero_tema,
                        $extension,
                        $carnet,
                        $carrera,
                        NOW()
                )";
                $registrar_bitacora_tema = & $db->Query($consulta);
                if ($db->isError($registrar_bitacora_tema)) {
                    $error = true;
                    $mensaje = "Hubo un problema al registrar la bitacora del tema.";
                    $url = "../proyecto_graduacion/proyecto_graduacion_gestion.php";
                } else {

                    //$db->commit();
                    //ingresar en el historial de los temas...
                    // Registrar historial del tema
                    //construccion del contenido...
                    $mysqli_conn = mysqli_connect($host, $user, $pass, 'satu');
                    $contenido = mysqli_real_escape_string($mysqli_conn, json_encode([
                        "proyecto_graduacion" => $proyecto_graduacion,
                        "modalidad" => $modalidad,
                        "departamento" => $departamento,
                        "municipio" => $municipio,
                        "descripcion" => $descripcion,
                        "tema_enmarcado" => $tema_enmarcado,
                        "fecha_creacion" => date("Y-m-d H:i:s")
                            ])
                    );
                    $consulta = "INSERT INTO proyecto_graduacion_historial
                        (numero_tema, carnet, carrera, fecha, etapa, contenido, correcciones, fecha_correccion)
                        VALUES (
                                $numero_tema,
                                $carnet,
                                $carrera,
                                NOW(),
                                1,
                                '$contenido',
                                NULL,
                                NULL
                        )";
                    $registrar_bitacora_tema = & $db->Query($consulta);
                    if ($db->isError($registrar_bitacora_tema)) {
                        $error = true;
                        $mensaje = "Hubo un problema al registrar el historial del tema.";
                        $url = "../proyecto_graduacion/proyecto_graduacion_gestion.php";
                    } else {
                        $db->commit();
                    }

                    // Quitamos los datos almacenados en la session ya que ya no se utilizaran
                    if (isset($_SESSION['primer_nombre'])) {
                        unset($_SESSION['primer_nombre']);
                        unset($_SESSION['segundo_nombre']);
                        unset($_SESSION['tercer_nombre']);
                        unset($_SESSION['primer_apellido']);
                        unset($_SESSION['segundo_apellido']);
                        unset($_SESSION['email_fda']);
                        unset($_SESSION['proyecto_graduacion']);
                        unset($_SESSION['departamento']);
                        unset($_SESSION['municipio']);
                        unset($_SESSION['descripcion']);
                        unset($_SESSION['tema_enmarcado']);
                        unset($_SESSION['modalidad']);
                    }
                }
            }
        }


        if (!$error) {

            $proceso_finalizado = "Se ha recibido su solicitud. La Comisión de Proyecto de Graduación enviará el dictamen por este medio, en el transcurso del siguiente mes.";
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
