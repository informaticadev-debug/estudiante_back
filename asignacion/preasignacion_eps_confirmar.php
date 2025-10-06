<?php

/*
  Proceso de preasignacion EPS
  -> Verificacion de algunos datos para actualizar en el sistema
  -> Eleccion de tipo de asignacion que hara el estudiante
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

        // Datos de la session actual
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carnet = $_SESSION['usuario'];

        // Formulario de actualizacion de datos
        /* $direccion = $_POST['direccion'];
          $telefono = $_POST['telefono'];
          $celular = $_POST['celular'];
          $email_fda = $_POST['email_fda'];
          $dpi = $_POST['dpi'];
          $estado_civil = $_POST['estado_civil']; */
        $opcion_eps = $_POST['opcion_eps'];
        //$sede_financiamiento = $_POST['sede_financiamiento'];
        // Actalizacion de datos para el estudiante
        /* $proceso = "UPDATE estudiante e
          SET e.direccion = '$direccion', e.telefono = $telefono, e.celular = $celular, e.email_fda = '$email_fda', e.dpi = '$dpi',
          e.estado_civil = $estado_civil
          WHERE e.carnet = $carnet";
          $actualizar_datos =& $db->Query($proceso);
          if ($db->isError($actualizar_datos)){
          $error = true;
          $mensaje = "Error en la actualización de datos.";
          } else { */

        // Verificacion de existencia de preasignacion en el ciclo actual
        $consulta = "SELECT e.carnet
			FROM preasignacion_eps e
			WHERE e.anio = $anio AND e.semestre = $semestre AND e.evaluacion = 1 AND e.carnet = $carnet";
        $existencia_preasignacion = & $db->getRow($consulta);
        if ($db->isError($existencia_preasignacion)) {
            $error = true;
            $mensaje = "Error al consultar la existencia de la Pre-Asignación.";
        } else {

            // Pre-Asignacion a EPS
            if ($existencia_preasignacion == 0) {

                $proceso = "INSERT INTO preasignacion_eps
					(anio, semestre, evaluacion, carnet, opcion_eps, fecha_preasignacion)
					VALUES (
						$anio, 
						$semestre, 
						1, 
						$carnet, 
						$opcion_eps,
						NOW()
					)";
                $preasignacion = & $db->Query($proceso);
                if ($db->isError($preasignacion)) {
                    $error = true;
                    $mensaje = "Error al registrar la preasignación.";
                }
            } else {

                $error = true;
                $mensaje = "Ya existe una Pre-Asignación en el ciclo actual.";
            }
        }
        /* } */

        if (!$error) {
            $mensaje = "El proceso de Pre-Asignación a EPS se ha completado con éxito.";
            procesoCompletado($mensaje);
        }

        if ($error) {
            mostrarError($mensaje);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>