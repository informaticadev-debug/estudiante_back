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
        $error = false;

        $carnet = $_SESSION['usuario'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];

        // Modificación por orde de Arquitecto Publio Rodríguez
        // Detalle: A partir de la fecha 08-05-2015 solo se permitirá un padrino por graduando
        $padrinos = $_POST['padrinos'];
        $tipo_examen = $_POST['tipo_examen'];

        if ($tipo_examen == 2 AND $padrinos == 3) {
            $error = true;
            $mensaje = "En un examen colectivo solo puede tener 2 padrinos.";
            $url = $_SERVER[HTTP_REFERER];
        }

        // Datos del estudiante
        $consulta = "SELECT p.primer_nombre, p.segundo_nombre, p.tercer_nombre, p.primer_apellido, p.segundo_apellido
		FROM proyecto_graduacion p
		WHERE p.carnet = $carnet
		LIMIT 1";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener los datos del estudiante.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Profesiones para seleccion
            $consulta = "SELECT p.profesion
			FROM examen_publico_padrinos p
			WHERE p.profesion <> ''
			GROUP BY p.profesion
			ORDER BY p.profesion ASC";
            $profesiones = & $db->getAll($consulta);
            if ($db->IsError($profesiones)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener el listado de profesiones.";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

        if (!empty($estudiante[tercer_nombre])) {
            $nombre_estudiante = $estudiante[primer_nombre] . " " . $estudiante[segundo_nombre] . " " . $estudiante[tercer_nombre] . " " . $estudiante[primer_apellido] . " " . $estudiante[segundo_apellido];
        } else {
            $nombre_estudiante = $estudiante[primer_nombre] . " " . $estudiante[segundo_nombre] . " " . $estudiante[primer_apellido] . " " . $estudiante[segundo_apellido];
        }

        if (!$error) {

            // Cargar template de formulario para actualizacion
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('examen_publico_padrinos.html');

            if ($tipo_examen == 1) {
                $template->setVariable(array(
                    'tipo_examen' => "Examen individual",
                    'id_tipo_examen' => $tipo_examen
                ));
            } else if ($tipo_examen == 2) {
                $template->setVariable(array(
                    'tipo_examen' => "Examen colectivo",
                    'id_tipo_examen' => $tipo_examen
                ));
            }

            $template->setVariable(array(
                'carnet' => $carnet,
                'nombre_estudiante' => $nombre_estudiante
            ));
            $template->parse('datos_estudiante');

            // Padrinos
            for ($i = 0; $i < $padrinos; $i++) {

                foreach ($profesiones AS $pr) {

                    $template->setVariable(array(
                        'padrino_profesion' => $pr[profesion]
                    ));
                    $template->parse('listado_profesiones');
                }

                $template->setVariable(array(
                    'padrino_nombre' => "<input class='form-control' type='text' value='' name='padrino[]' required placeholder='Nombre completo'>",
                    'padrino_colegiado' => "<input class='form-control' type='text' value='' name='colegiado[]' required placeholder='# Colegiado' pattern='^[0-9]{1,15}$'>"
                ));
                $template->parse('padrinos');
            }

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_error'])) {
                $mensaje_error = $_SESSION['mensaje_error'];
                $template->setVariable(array(
                    'mensaje_error' => "<div id='base_error_proceso'>
                        <div id='error'>
                            $mensaje_error<br><br>
                            <div id='acciones'>
                                <input id='btn_rojo' type='button' value='Aceptar' OnClick='window.location.reload()' autofocus>
                            </div>
                        </div>
                    </div>"
                ));
                unset($_SESSION['mensaje_error']);
            }

            // Proceso culminado con exito
            if (isset($_SESSION['proceso_finalizado'])) {
                $proceso_finalizado = $_SESSION['proceso_finalizado'];
                $template->setVariable(array(
                    'mensaje_proceso_finalizado' => "
                        <div id='base_proceso_finalizado'>
                            <div id='finalizado'>
                            $proceso_finalizado<br><br>
                            <div id='acciones'>
                                <input id='btn_azul' type='button' value='Aceptar' OnClick='window.location.reload()' autofocus>
                            </div>
                        </div>
                    </div>"
                ));
                unset($_SESSION['proceso_finalizado']);
            }

            $template->show();
            exit();
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