<?php

/*
  Proceso de Asignacion para Fin de Semestre
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../config/local.php';

$array_habilitados = [
];

$array_liberar_repitencia = [
    201531938 // Punto TERCERO, Inciso 3.1, subincisos del 3.1.1 al 3.1.4, del Acta 27-2024
   /* 200610785, //providencia 011-2019,
    200321108, //providencia 167-2019,
    201122701, //providencia pendiente, dibujo constructivo con código que ya no se imparte..
    /*201213802,
    201131617,
    200811035,
    201803315,
    201500812,
    201025288,
    201315170,*/
];

$habilitarCarnet = [
/*202099999*/
];

function verificarIngreso($db, $carnet, $anio, $semestre, $evaluacion)
{
    $query = "SELECT *
    FROM asignacion_encuesta
    WHERE anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion AND carnet = $carnet";
    $result = ejecutarQuery($db, $query);
    if (!empty($result)) {
        header("Location: ./asignacion_semestre_seleccio_arq.php?evaluacion=$evaluacion");
        exit;
    }
}

function guardarDatos($db, $carnet, $anio, $semestre, $evaluacion)
{
    if (isset($_POST["pregunta1"])) {
        $consulta = "INSERT INTO asignacion_encuesta
                (anio, semestre, evaluacion, carnet, pregunta1, pregunta2, pregunta3, pregunta4, pregunta5)
                VALUES (
                        $anio,
                        $semestre,
                        $evaluacion,
                        $carnet,
                        '{$_POST["pregunta1"]}',
                        '{$_POST["pregunta2"]}',
                        '{$_POST["pregunta3"]}',
                        '{$_POST["pregunta4"]}',
                        '{$_POST["pregunta5"]}'
                )";
        $result = &$db->Query($consulta);
        //var_dump($consulta); die;
        if ($db->isError($result)) {
            $error = true;
            $mensaje = "Hubo un problema al registrar la bitacora del tema.";
            $url = "../proyecto_graduacion/proyecto_graduacion_gestion.php";
        }
    }
}

session_start();
if (isset($_SESSION[usuario])) {

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

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;
        $aviso = false;

        $db->Query("SET lc_time_names = 'es_ES'");

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_GET['evaluacion'];
        $carnet = $_SESSION['usuario'];

        //guardar si es el caso:
        guardarDatos($db, $carnet, $anio, $semestre, $evaluacion);
        //VERIFICANDO SI YA SELECCIONO
        verificarIngreso($db, $carnet, $anio, $semestre, $evaluacion);

        // Verificacion de la inscripcion del estudiante en el ciclo actual
        $consulta = "SELECT i.carnet, IF (
                    EXISTS(
                            SELECT i2.carrera 
                            FROM inscripcion i2
                            WHERE i2.extension = i.`extension` AND i2.anio = i.`anio` AND i2.semestre = i.`semestre` AND i2.carnet = i.`carnet` AND i2.carrera > 3
                            LIMIT 1
                    ),
                            (
                                    SELECT i3.carrera 
                                    FROM inscripcion i3
                                    WHERE i3.extension = i.`extension` AND i3.anio = i.`anio` AND i3.semestre = i.`semestre` AND i3.carnet = i.`carnet`
                                    AND i3.carrera > 3
                                    LIMIT 1
                            )
                    ,i.carrera
            ) AS carrera
        FROM inscripcion i
        WHERE i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet 
        GROUP BY i.carrera";
        //var_dump($consulta); die;
        $inscripcion = &$db->getAll($consulta);
        if ($db->isError($inscripcion)) {
            $error = true;
            $mensaje = "Hubo un error al determinar tu inscripción en el ciclo actual. E01.";
            $url = "../menus/contenido.php";
        } else {

            if (empty($inscripcion)) {
                $error = true;
                $mensaje = "Hubo un error al determinar tu inscripción en el ciclo actual, no parece estar inscrito.";
                $url = "../menus/contenido.php";
            }
            // Verificacion de la existencia de repitencia del estudiante
            foreach ($inscripcion as $in) {

                /**
                 * Seccion para verificar REPITENCIA...
                 */
                //variable de control para verificar la cantidad de cursos en repitencia...
                $cursos_repitencia = [];
                //verificar repitencia general...
                $data = array(
                    "auth" => array(
                        "user" => "arqws",
                        "passwd" => "a08!¡+¿s821!kdui23#kd$"
                    ),
                    'id' => $carnet,
                );
                $dataRepitencia = json_decode(postRequest($api_uri . 'Repitencia', $data), true);

                // Verificacion de la existencia de repitencia del estudiante
                foreach ($inscripcion as $in) {
                    foreach ($dataRepitencia as $pensum => $cursos) {
                        //verificar que sea un pensum actual valido
                        if (($pensum == 5 && $in["carrera"] == 1) || ($pensum == 20 && $in["carrera"] == 3)) {
                            foreach ($cursos as $codigo => $cursoInfo) {
                                //verificar que el curso aun no se haya aprobado y que no haya entrado en repitencia en el semestre actual...
                                if ($cursoInfo["resultado"]["aprobado"] == 0 && $cursoInfo["resultado"]["ciclo_repitencia"] != "$anio-$semestre") {
                                    $cursos_repitencia[$codigo] = $cursoInfo;
                                }
                            }
                        }
                    }
                }

                if (!empty($cursos_repitencia) && !in_array($carnet, $array_liberar_repitencia)) {
                    $error = true;
                    $mensaje = "Lo sentimos pero usted no puede asignarse por tener repitencia en los siguientes cursos: <br /><br />";
                    foreach ($cursos_repitencia as $codigo => $cursoInfo) {
                        $mensaje .= "<b>" . $codigo . " - " . $cursoInfo["nombre"] . "</b><br />";
                        $mensaje .= "<span style='margin-left: 20px;'>Ciclo en el que entro en repitencia: <b>" . $cursoInfo["resultado"]["ciclo_repitencia"] . "</b></span><br /><br />";
                    }
                    $url = "../menus/contenido.php";
                    aviso($mensaje, $url);
                    exit;
                } else {

                    //$habilitarCarnet[] = verificarPrioridad($db, $in['carrera'], $extension, $anio, $semestre, $carnet);



                    // Verificacion del periodo de Asignacion.
                    $consulta = "SELECT c.fecha_inicio_asignacion, c.fecha_fin_asignacion, NOW() AS fecha_actual,
                    e.nombre AS evaluacion, s.nombre AS semestre, DATE_FORMAT(c.fecha_inicio_asignacion, '%d de %M de %Y a las %Hhrs.') AS inicio_asignacion,
                    DATE_FORMAT(c.fecha_fin_asignacion, '%d de %M de %Y a las %Hhrs.') AS fin_asignacion
                    FROM ciclo c
                    INNER JOIN evaluacion e
                    ON e.evaluacion = c.evaluacion
                    INNER JOIN semestre s
                    ON s.semestre = c.semestre
                    WHERE c.anio = $anio AND c.semestre = $semestre AND c.evaluacion = $evaluacion AND c.asignacion = 1";
                    $ciclo = &$db->getRow($consulta);


                    if (count($inscripcion) == 0) {
                        $error = true;
                        $mensaje = "Usted no esta inscrito en el ciclo actual. Por favor verifique esta información";
                        $url = "../menus/contenido.php";
                    } else {

                        if (count($repitencia) <> 0) {
                            $aviso = true;
                            $mensaje = "Por el momento no puede asignarse cursos en este ciclo, debido a la repitencia en: <br><br>";

                            foreach ($repitencia as $re) {
                                $mensaje = $mensaje . "$re[codigo] $re[asignatura] ($re[perdidas] veces reprobada) <br>";
                            }

                            if (count($inscripcion) == 2) {
                                $mensaje = $mensaje . "<br>* En su caso cuenta con carrera simultanea debera solicitar la baja de la carrera donde tiene repitencia, 
		                        para poder asignarse los cursos de la carrera sin problemas<br><br>";
                            }

                            $url = "../menus/contenido.php";
                        } else {

                        }
                    }

                }
            }
        }

        if (!$error && !$aviso) {

            // Cargando la pagina de seleccion de Asignatura para Semestre
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('asignacion_encuesta.html');

            // Listado de departamentos de Guatemala
            $consulta = "SELECT *
            FROM departamento d
            WHERE departamento <> 0 AND departamento < 100
            ";
            $listado_departamentos = &$db->getAll($consulta);

            foreach ($listado_departamentos as $de) {
                $template->setVariable(
                    array(
                        'departamento' => $de[departamento],
                        'nombre_departamento' => $de[nombre]
                    )
                );
                $template->parse('listado_departamento');
            }

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_aviso'])) {
                $mensaje_aviso = $_SESSION['mensaje_aviso'];
                $template->setVariable(
                    array(
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
                    )
                );
                unset($_SESSION['mensaje_aviso']);
            }

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_error'])) {
                $mensaje_error = $_SESSION['mensaje_error'];
                $template->setVariable(
                    array(
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
                    )
                );
                unset($_SESSION['mensaje_error']);
            }

            // Proceso culminado con exito
            if (isset($_SESSION['proceso_finalizado'])) {
                $proceso_finalizado = $_SESSION['proceso_finalizado'];
                $template->setVariable(
                    array(
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
                    )
                );
                unset($_SESSION['proceso_finalizado']);
            }

            $template->show();
            exit;
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