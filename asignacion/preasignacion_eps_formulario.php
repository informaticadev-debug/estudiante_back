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

        // Verificacion para preasignarse EPS
        $consulta = "SELECT a.carnet
		FROM asignacion a		
		WHERE a.carnet = $carnet AND a.codigo = '1.10.1' AND a.status = 1
		AND EXISTS(
			SELECT i.carnet 
			FROM inscripcion i
			WHERE i.anio = $anio AND i.semestre = $semestre AND i.carnet = a.carnet
		)";
        $inscripcion = & $db->getAll($consulta);
        if ($db->isError($inscripcion)) {
            $error = true;
            $mensaje = "Error al consultar el estado de la inscripcion.";
        } else {

            // Verificacion de Diseño Arquitectonico en Pensum 82
            $consulta = "SELECT n.nota
			FROM nota n
			WHERE n.carnet = $carnet AND n.codigo IN ('1.10.1', 091)";
            $pensum82 = & $db->getRow($consulta);
            if ($db->isError($consulta)) {
                $error = true;
                $mensaje = "Hubo un error al verificar la aprobacion de Diseño Arquitectonico 9 en Pensum 82";
            }
        }

        // Estado civil dependiendo del genero del estudiante
        $consulta = "SELECT e.sexo, s.estado_civil AS cod_est_civil, s.nombre AS estado_civil
		FROM estudiante e
		INNER JOIN estado_civil s
		ON s.sexo = e.sexo
		WHERE e.carnet = $carnet";
        $estudiante = & $db->getAll($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Error en la consulta de los datos del estudiante.";
        } else {

            // Verificacion Estudiantes con Cierre de Pensum pueden asignarse EPS.
            $consulta = "SELECT c.carnet
			FROM carrera_estudiante c
			WHERE c.carnet = $carnet AND c.fecha_cierre IS NOT NULL";
            $con_cierre = & $db->getRow($consulta);
            if ($db->isError($con_cierre)) {
                $error = true;
                $mensaje = "Hubo un error al verificar el cierre del estudiante.";
            } else {

                // Mostrar los datos ingresados cuando el estudiante haya ingresado la Pre-Asignacion
                $consulta = "SELECT e.direccion, e.telefono, e.celular, e.email_fda, e.dpi, e.estado_civil, p.opcion_eps
				FROM estudiante e
				INNER JOIN preasignacion_eps p
				ON p.anio = $anio AND p.semestre = $semestre AND p.evaluacion = 1 AND p.carnet = e.carnet 
				WHERE e.carnet = $carnet";
                $almacenado = & $db->getRow($consulta);
                if ($db->isError($almacenado)) {
                    $error = true;
                    $mensaje = "Hubo un error al determinar los datos almacenados de la Pre-Asignacion a EPS";
                }
            }
        }

        if (!empty($inscripcion) || count($con_cierre) <> 0 || count($pensum82) <> 0) {

            if (!$error) {

                // Cargar pagina de preasignacion
                $template = new HTML_Template_Sigma('../templates');
                $template->loadTemplateFile('preasignacion_eps_formulario.html');

                // Estado civil estudiante
                foreach ($estudiante AS $es) {
                    $template->setVariable(array(
                        'cod_est_civil' => $es[cod_est_civil],
                        'est_civil' => $es[estado_civil]
                    ));
                    $template->parse('estado_civil');
                }

                // Datos si la preasignacion ha sido realizada
                if ($almacenado <> 0) {

                    $template->setVariable(array(
                        'direccion' => $almacenado[direccion],
                        'telefono' => $almacenado[telefono],
                        'celular' => $almacenado[celular],
                        'email_fda' => $almacenado[email_fda],
                        'dpi' => $almacenado[dpi]
                    ));
                }

                $template->show();
                exit();
            }
        } else {
            $error = true;
            $mensaje = "Lo sentimos no cuenta con alguno de los prerrequisitos para poder pre-asignarse EPS: 
				Inscrito en el ciclo Actual, Tener asignado o aprobado Diseño Arquitectónico 9.";
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