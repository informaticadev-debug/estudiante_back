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
            //var_dump($consulta); die;
            $resultado_guardado = & $db->query($consulta);
            if ($db->isError($resultado_guardado)) {
                $mensaje = "Se ha producido un error al intentar guardar su solicitud, por favor intente de nuevo más tarde.";
                $error = true;
                $url = '../menus/inicio.php';
            } else {
                $mensaje = "Su solicitud ha sido guardada con éxito. Esté pendiente de la resolución.";
                $aviso = true;
                $url = '../menus/inicio.php';
            }
        } else {
            $error = true;
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
