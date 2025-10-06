<?php

/*
  Proceso de Asignacion para Interciclos
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 * 
 * pruebas 9317930
 * 
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
        $url = "../menus/contenido.php";
        mostrarErrorLogin($mensaje);
    } else {

        $db->setFetchMode(DB_FETCHMODE_ASSOC);

        $curso = $_POST['curso'];
        //                                  0               1                2                  3                                       4
        $curso_array = preg_split("/_/", $curso); //$anio . "_" . $semestre . "_" . $evaluacion . "_" . $curso_disponible['pensum'] . "_" . $curso_disponible['codigo']
        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $curso_array[0];
        $semestre = $curso_array[1];
        $evaluacion = $curso_array[2];
        $pensum = $curso_array[3];
        $codigo = $curso_array[4];
        $carnet = $_SESSION['usuario'];
        $codigoTemp = $codigo;

        $consulta = "
                SELECT 1
                FROM asignacion_solicitud a
                WHERE a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = 2 AND a.carnet = $carnet
            ";
        $solicitud_previa = & $db->getAll($consulta);
        if (!$db->isError($solicitud_previa) && count($solicitud_previa) < 1) {
            $consulta = "
                INSERT INTO asignacion_solicitud
                            (anio,
                             semestre,
                             evaluacion,
                             pensum,
                             codigo,
                             carnet,
                             fecha_solicitud)
                VALUES ($anio,
                        $semestre,
                        $evaluacion,
                        $pensum,
                        '$codigo',
                        $carnet,
                        NOW());
                ";

            if (!empty($_POST["curso2"])) {
                $curso_array = preg_split("/_/", $_POST["curso2"]); //$anio . "_" . $semestre . "_" . $evaluacion . "_" . $curso_disponible['pensum'] . "_" . $curso_disponible['codigo']
                // Datos de la session actual
                $extension = $_SESSION['extension'];
                $anio = $curso_array[0];
                $semestre = $curso_array[1];
                $evaluacion = $curso_array[2];
                $pensum = $curso_array[3];
                $codigo = $curso_array[4];
                $carnet = $_SESSION['usuario'];
                if ($codigoTemp != $codigo) {
                    $consulta2 = "
                INSERT INTO asignacion_solicitud
                            (anio,
                             semestre,
                             evaluacion,
                             pensum,
                             codigo,
                             carnet,
                             fecha_solicitud)
                VALUES ($anio,
                        $semestre,
                        $evaluacion,
                        $pensum,
                        '$codigo',
                        $carnet,
                        NOW());
                ";
                    $resultado_guardado2 = & $db->query($consulta2);
                }
            }

            $resultado_guardado = & $db->query($consulta);
            if ($db->isError($resultado_guardado)) {
                $mensaje = "Se ha producido un error al intentar guardar su solicitud, por favor intente de nuevo más tarde.";
                $error = true;
                $url = '../asignacion/asignacion_interciclos_solicitud_pre.php';
            } else {
                $mensaje = "Su respuesta ha sido guardada con éxito. Gracias por su colaboración.";
                $aviso = true;
                $url = '../asignacion/asignacion_interciclos_solicitud_pre.php';
            }
        } else {
            $error = true; var_dump("afasdf"); die;
            $mensaje = "Ya ha creado una solicitud anteriormente.";
            $url = '../menus/inicio.php';
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
    aviso($mensaje, "../menus/index.php");
}
?>
