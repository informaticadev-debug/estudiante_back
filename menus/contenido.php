<?php

/*
  Inicio de la aplicacion
  -> Datos principales para mostrar al estudiante
  -> Menu de opciones
  -> Mensajes directos
  ->
  -> Datos personales
  -> Asignaciones
  -> Estatus de inscripcion
  -> Bloque de anuncion, avisos, etc.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();

$array_bloqueados_sin_pago_ins = [
];



verificarActualizarDatos();

function obtenerCargaCurso($db, $anio, $semestre, $extension, $evaluacion, $codigo, $seccion) {
    $consulta = "
                SELECT s.cupo, count(a.carnet) as asignados
                FROM seccion s
                    LEFT JOIN asignacion a on s.anio = a.anio and s.extension = a.extension and s.semestre = a.semestre and s.evaluacion = a.evaluacion 
                    and s.pensum = a.pensum and s.codigo = a.codigo and s.seccion = a.seccion
                WHERE s.anio = $anio and s.semestre = $semestre and s.extension = $extension and s.evaluacion = $evaluacion and s.codigo = '$codigo' and s.seccion = '$seccion'
            ";
    //var_dump($consulta); die;
    $cupo = & $db->getAll($consulta);
    if ($db->isError($cupo)) {
        $error = true;
        $mensaje = "Hubo un error al obtener la carga del curso.";
        $url = "../menus/contenido.php";
        return false;
    }
    return $cupo[0];
}

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

        $carnet = $_SESSION['usuario'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $nombre_semestre = $_SESSION['nombre_semestre'];
        // Datos del estudiante logueado
        $consulta = "SELECT e.carnet, e.nombre, x.extension, x.nombre AS nombre_extension, e.sexo, e.correo_institucional, e.contrasena_temporal
		FROM estudiante e
		INNER JOIN extension x
		ON x.extension = e.extension /*{$_SESSION["extension"]}*/
		WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Error al consultar los datos del estudiante.";
        } else {

            // Datos del ciclo e inscripcion del estudiante
            $consulta = "SELECT i.anio, i.carrera, c.nombre AS nombre_carrera, s.nombre AS nombre_semestre
			FROM inscripcion i
			INNER JOIN carrera c
			ON c.carrera = i.carrera
			INNER JOIN semestre s
			ON s.semestre = i.semestre
			WHERE i.carnet = $carnet AND i.anio = $anio AND i.semestre = $semestre";
            $ciclo_inscripcion = & $db->getAll($consulta);
            if ($db->isError($ciclo_inscripcion)) {
                $error = true;
                $mensaje = "Error al consultar los datos del ciclo";
            } else {

                // Asignaciones actuales en el ciclo
                $consulta = "SELECT a.anio, a.semestre, a.codigo, TRIM(c.nombre) AS asignatura, a.evaluacion, a.pensum, a.codigo, a.seccion, a.preasignacion, a.extension, a.abandono
				FROM asignacion a
				INNER JOIN curso c ON c.codigo = a.codigo AND c.pensum = a.pensum
                INNER JOIN seccion s ON s.extension = a.extension AND s.anio = a.anio AND s.semestre = a.semestre AND s.evaluacion = a.evaluacion AND s.pensum = a.pensum AND s.codigo = a.codigo AND s.seccion = a.seccion
				WHERE a.carnet = $carnet AND a.evaluacion IN (1,2,3,4) AND s.tipo = 1
				AND NOT EXISTS(
					SELECT *
					FROM nota n
					WHERE n.carnet = a.carnet AND n.codigo = a.codigo AND n.nota_oficial = 1 AND n.aprobado = 1
					AND n.pensum = a.pensum
				) AND a.status = 1 AND a.anio >= 2015 AND a.nota_oficial = 0
                
UNION ALL 
                SELECT a.anio, a.semestre, a.codigo, TRIM(c.nombre) AS asignatura, a.evaluacion, a.pensum, a.codigo, CONCAT('(Solicitud de Cupo Extra) - ', a.seccion) as seccion, 0 as preasignacion, a.extension, 0 as abandono
                FROM asignacion_spd a
				INNER JOIN curso c
				ON c.codigo = a.codigo AND c.pensum = a.pensum
				WHERE a.carnet = $carnet AND a.status = 0 AND a.anio = $anio AND a.semestre = $semestre
						AND (a.carnet, a.anio, a.semestre, a.pensum, a.codigo) NOT IN
							(
								SELECT carnet, anio, semestre, pensum, codigo
								FROM asignacion
								WHERE carnet = a.carnet AND anio = a.anio AND semestre = a.semestre AND pensum = a.pensum AND codigo = a.codigo
							)
                            ORDER BY evaluacion DESC
							";
                            
							//var_dump($consulta);
		    $asignaciones = & $db->getAll($consulta);
		    //echo "<!-- $consulta -->";
                if ($db->isError($asignaciones)) {
                    $error = true;
                    $mensaje = "Error al consultar las asignaciones actuales.";
                } else {

                    // Consulta del estatus de la desasignacion
                    $consulta = "SELECT IF(c.fecha_inicio_desasignacion < NOW() AND c.fecha_fin_desasignacion > NOW(), 'ACTIVO', 'INACTIVO') AS periodo_desasignacion
					FROM ciclo c
					WHERE c.anio = $anio AND c.semestre = $semestre AND c.evaluacion = 1";
                    $desasignacion = & $db->getRow($consulta);
                    if ($db->isError($desasignacion)) {
                        $error = true;
                        $mensaje = "Hubo un error al verificar las fechas de Des-Asignacion.";
                    }
                }
            }
        }

        if (!$error) {
            $template = new HTML_Template_Sigma('../templates');
            // ingreso a encuesta temporal
            $template->loadTemplateFile('contenido.html');
                    /*$consulta = "SELECT *
                    FROM asignacion
                    WHERE anio = 2025 and semestre = 1 and evaluacion = 1 and extension = 0 and pensum = 5 and codigo in ('1.01.1','1.02.1','1.03.1','1.04.1','1.05.1','1.06.1,1.07.1','1.08.1','1.09.1','1.10.1') and carnet = $carnet
                    ";
                    $area_diseño = & $db->getRow($consulta);
            if($area_diseño <> 0 ){
            $template->loadTemplateFile('contenido_arq.html');
            }else { 
                $template->loadTemplateFile('contenido.html');
            }*/
              
            // Datos del estudiante
            if ($estudiante[sexo] == 1 || $estudiante[sexo] == NULL) {

                $template->setVariable(array(
                    'saludo' => "Bienvenido",
                    'carnet' => $estudiante[carnet],
                    'nombre' => $estudiante[nombre],
                    'extension' => $estudiante[extension] . " - " . $estudiante[nombre_extension],
                    'correo_institucional' => $estudiante[correo_institucional],
                    'contrasena_temporal' => $estudiante[contrasena_temporal]
                ));
                $template->parse('datos_estudiante');
            } else {

                $template->setVariable(array(
                    'saludo' => "Bienvenida",
                    'carnet' => $estudiante[carnet],
                    'nombre' => $estudiante[nombre],
                    'extension' => $estudiante[extension] . " - " . $estudiante[nombre_extension],
                    'correo_institucional' => $estudiante[correo_institucional],
                    'contrasena_temporal' => $estudiante[contrasena_temporal]
                ));
                $template->parse('datos_estudiante');
            }

            // Imagen para mostrar
            if ((file_exists("../images/fotos/" . $estudiante[carnet] . ".jpg"))) {
                $template->setVariable(array(
                    'fotografia' => "<img class='img-rounded img-responsive' width='150' src='../images/fotos/$estudiante[carnet].jpg'>"
                ));
            } else if ($estudiante[sexo] == 2) {
                $template->setVariable(array(
                    'fotografia' => "<img class='img-rounded img-responsive' width='150' src='../images/mujer.png'>"
                ));
            } else {
                $template->setVariable(array(
                    'fotografia' => "<img class='img-rounded img-responsive' width='150' src='../images/hombre.png'>"
                ));
            }

            // Datos del Ciclo e inscripcion
            if (!empty($ciclo_inscripcion)) {

                foreach ($ciclo_inscripcion AS $ci) {
                    $template->setVariable(array(
                        'anio' => $ci[anio],
                        'fondo_inscripcion' => "alert-success",
                        'carrera' => "<font class='text-success'>" . $ci[carrera] . " - " . $ci[nombre_carrera] . "</font>"
                    ));
                    $template->parse('datos_ciclo_inscripcion');
                }
            } else {

                $template->setVariable(array(
                    'anio' => $anio,
                    'fondo_inscripcion' => "alert-danger",
                    'carrera' => "<font class='text-danger'>Aún no esta inscrito en este ciclo.</font>"
                ));
                $template->parse('datos_ciclo_inscripcion');
            }

            if (in_array($carnet, $array_bloqueados_sin_pago_ins))
                $asignaciones = [];
            if (!empty($asignaciones)) {

                // Asignacioes del ciclo actual, Verificando si es preasignacion o no
                foreach ($asignaciones AS $as) {
                    $cupo_str = '';
                    if ($as['evaluacion'] == 2) {
                        $cupo = obtenerCargaCurso($db, $as['anio'], $as['semestre'], $as['extension'], $as['evaluacion'], $as['codigo'], $as['seccion']);
                        if ($cupo) {
                            $cupo_str = " (Cupo: " . $cupo['asignados'] . '/' . $cupo['cupo'] . ')';
                        }
                    }

                    if ($as[preasignacion] == 1) {

                        $template->setVariable(array(
                            'asignacion_anio' => $as[anio],
                            'asignacion_semestre' => $as[semestre],
                            'codigo' => $as[codigo],
                            'seccion' => $as[seccion],
                            'asignacion' => $as[codigo] . " - " . $as[asignatura] . "<b> " . $as[seccion] . $cupo_str . "</b> (Pago pendiente)"
                        ));
                    } else {
                        if($as[abandono]<> 1){

                        $template->setVariable(array(
                            'asignacion_anio' => $as[anio],
                            'asignacion_semestre' => $as[semestre],
                            'codigo' => $as[codigo],
                            'seccion' => $as[seccion],
                            'asignacion' => $as[codigo] . " - " . $as[asignatura] . "<b> " . $as[seccion] . $cupo_str . "</b>"
                        ));
                        } else{
                            $template->setVariable(array(
                                'asignacion_anio' => $as[anio],
                                'asignacion_semestre' => $as[semestre],
                                'codigo' => $as[codigo],
                                'seccion' => $as[seccion],
                                'asignacion' => $as[codigo] . " - " . $as[asignatura] . "<b> " . $as[seccion] . $cupo_str . " - Ausencia -</b>"
                            ));

                        }
                    }

                    if (isset($as[evaluacion]) && $as[evaluacion] == 1) {
                        $template->setVariable(array(
                            'asignacion_anio' => $as[anio],
                            'asignacion_semestre' => $as[semestre],
                            'evaluacion' => "<div class='btn btn-primary btn-xs'>S</div>",
                            'id_evaluacion' => $as[evaluacion],
                            'pensum' => $as[pensum]
                        ));
                    }

                    if (isset($as[evaluacion]) && $as[evaluacion] == 2) {
                        $template->setVariable(array(
                            'asignacion_anio' => $as[anio],
                            'asignacion_semestre' => $as[semestre],
                            'evaluacion' => "<div class='btn btn-warning btn-xs'>I</div>",
                            'id_evaluacion' => $as[evaluacion],
                            'pensum' => $as[pensum]
                        ));
                    }

                    if (isset($as[evaluacion]) && $as[evaluacion] == 3 || $as[evaluacion] == 4) {
                        $template->setVariable(array(
                            'asignacion_anio' => $as[anio],
                            'evaluacion' => "<div class='btn btn-danger btn-xs'>R</div>",
                            'id_evaluacion' => $as[evaluacion],
                            'pensum' => $as[pensum]
                        ));
                    }

                    if (isset($as[evaluacion]) && $as[evaluacion] == 5) {
                        $template->setVariable(array(
                            'asignacion_anio' => $as[anio],
                            'asignacion_semestre' => $as[semestre],
                            'evaluacion' => "E",
                            'id_evaluacion' => $as[evaluacion],
                            'pensum' => $as[pensum]
                        ));
                    }

                    $template->parse('asignaciones');
                }
            } else {
                $template->setVariable(array(
                    'sin_asignaciones' => (in_array($carnet, $array_bloqueados_sin_pago_ins)) ? "<center>Asignaciones bloqueadas hasta realizar el pago de inscripción del PAI (ver estado de cuenta).</center><br>" : "<center>Sin asignaciones en el ciclo actual.</center><br>"
                ));
            }

            /* Opciones para las asignaturas en general
              -> Generacion de constancia de asignacion.
              -> Solicitud de Des-Asignacion
             */
            if (!empty($asignaciones)) {

                if ($desasignacion[periodo_desasignacion] == 'ACTIVO' && $estudiante["extension"] == 12) {

                    $template->setVariable(array(
                        'desasignacion' => "<option value='1'>1. Carta para solicitud de Des-Asignaci&oacute;n</option>",
                        'constancia_asignacion' => "<option value='2'>2. Detalle de Asiganciones actuales.</option>"
                    ));
                    $template->parse('opciones_asignatura');
                } else {

                    $template->setVariable(array(
                        'constancia_asignacion' => "<option value='2'>1. Generar constancia de Asignaciones.</option>"
                    ));
                    $template->parse('opciones_asignatura');
                }
            }

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_aviso'])) {
                $mensaje_aviso = $_SESSION['mensaje_aviso'];
                $template->setVariable(array(
                    'mensaje_aviso' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #DF7401; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Aviso</h4>
									</div>
									<div class='modal-body'>
										$mensaje_aviso
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-warning' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
								</div>
							</div>
						</div>"
                ));
                unset($_SESSION['mensaje_aviso']);
            }

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
            var_dump($mensaje, $_SESSION["extension"]);
            mostrarError($mensaje);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
