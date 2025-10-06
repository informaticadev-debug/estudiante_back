<?php

/*
  Documento  : proyecto_graduacion_solicitud_verificacion.php
  Creado el  : 04 de junio de 2014, 16:21
  Author     : Angel Caal
  Description:
  -> Palabras clave para identificar que el proyecto no exista
 * 
 * 20171002 eliminadas las palabras clave...
 * 
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
        $primer_nombre = $_POST['primer_nombre'];
        $segundo_nombre = $_POST['segundo_nombre'];
        $tercer_nombre = $_POST['tercer_nombre'];
        $primer_apellido = $_POST['primer_apellido'];
        $segundo_apellido = $_POST['segundo_apellido'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $nombre_semestre = $_SESSION['nombre_semestre'];
        $email_fda = $_POST['email_fda'];
        $proyecto_graduacion = $_POST['proyecto_graduacion'];
        $departamento = $_POST['departamento'];
        $municipio = $_POST['municipio'];
        $descripcion = $_POST['descripcion'];
        $tema_enmarcado = $_POST['tema_enmarcado'];
        $modalidad = $_POST['modalidad'];
        
        if (!empty($_POST['tercer_nombre'])) {
            $nombre_estudiante = $_POST['primer_nombre'] . " " . $_POST['segundo_nombre'] . " " . $_POST['tercer_nombre'] . " " . $_POST['primer_apellido'] . " " . $_POST['segundo_apellido'];
        } else {
            $nombre_estudiante = $_POST['primer_nombre'] . " " . $_POST['segundo_nombre'] . " " . $_POST['primer_apellido'] . " " . $_POST['segundo_apellido'];
        }

        // Consulta de datos del departamento 
        $consulta = "SELECT *
        FROM departamento d
        WHERE d.departamento = $departamento";
        $datos_departamento = & $db->getRow($consulta);
        if ($db->isError($datos_departamento)) {
            $error = true;
            $mensaje = "Hubo un problema durante la consulta de los datos del departamento selecionado. (E1.0)";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Consulta de los datos del municipio
            $consulta = "SELECT *
            FROM municipio m
            WHERE m.departamento = $departamento AND m.municipio = $municipio";
            $datos_municipio = & $db->getRow($consulta);
            if ($db->isError($datos_municipio)) {
                $error = true;
                $mensaje = "Hubo un problema durante la consulta de los datos del municipio seleccionado.(E1.1)";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                // Consultar el tema enmarcado 
                $consulta = "SELECT t.nombre
                        FROM proyecto_graduacion_tema_enmarcado t
                        WHERE t.tema_enmarcado = $tema_enmarcado";
                $datos_tema_enmarcado = & $db->getRow($consulta);
                if ($db->isError($datos_tema_enmarcado)) {
                    $error = true;
                    $mensaje = "Hubo un problema al obtener el detalle del tema enmarcado.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {
                    // Crear sesiones temporales con los datos ingresados
                    $_SESSION['primer_nombre'] = $_POST['primer_nombre'];
                    $_SESSION['segundo_nombre'] = $_POST['segundo_nombre'];
                    $_SESSION['tercer_nombre'] = $_POST['tercer_nombre'];
                    $_SESSION['primer_apellido'] = $_POST['primer_apellido'];
                    $_SESSION['segundo_apellido'] = $_POST['segundo_apellido'];
                    $_SESSION['email_fda'] = $_POST['email_fda'];
                    $_SESSION['proyecto_graduacion'] = $_POST['proyecto_graduacion'];
                    $_SESSION['departamento'] = $datos_departamento['departamento'];
                    $_SESSION['municipio'] = $datos_municipio['municipio'];
                    $_SESSION['palabra_clave'] = $_POST['palabra_clave'];
                    $_SESSION['descripcion'] = $_POST['descripcion'];
                    $_SESSION['tema_enmarcado'] = $_POST['tema_enmarcado'];
                    $_SESSION['modalidad'] = $_POST['modalidad'];
                }
            }
        }

        if (!$error && !$aviso) {

            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('proyecto_graduacion_solicitud_verificacion.html');

            $template->setVariable(array(
                'nombre' => $nombre_estudiante,
                'correo' => $email_fda,
                'proyecto_graduacion' => $proyecto_graduacion,
                'localizacion' => $datos_departamento[nombre] . " - " . $datos_municipio[nombre],
                'descripcion' => $descripcion,
                'tema_enmarcado' => $datos_tema_enmarcado[nombre]
            ));

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_error'])) {
                $mensaje_error = $_SESSION['mensaje_error'];
                $template->setVariable(array(
                    'mensaje_error' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #DF0101; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Error en proceso</h4>
									</div>
									<div class='modal-body'>
										$mensaje_error
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-danger' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
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
                    'mensaje_proceso_finalizado' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #084B8A; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Proceso finalizado</h4>
									</div>
									<div class='modal-body'>
										$proceso_finalizado
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-primary' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
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