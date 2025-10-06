<?php

/*
  Proceso de Asignacion para Fin de Semestre
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();

function verificarEliminacionDeCurso($db, $data, $extension, $anio, $semestre, $evaluacion, $carnet)
{
    if (empty($data["m6"]) && count($data) < 2) {
        return 0;
    }
    $observacion = "'Eliminación de asignación 2025-09 SA'";
    $descripcion = "";
    $descripcion .= (isset($data["m1"])) ? ";;;" . $data["m1"] : "";
    $descripcion .= (isset($data["m2"])) ? ";;;" . $data["m2"] : "";
    $descripcion .= (isset($data["m3"])) ? ";;;" . $data["m3"] : "";
    $descripcion .= (isset($data["m4"])) ? ";;;" . $data["m4"] : "";
    $descripcion .= (isset($data["m5"])) ? ";;;" . $data["m5"] : "";
    $descripcion .= (isset($data["m6"]) && !empty($data["m6"])) ? ";;;" . str_replace("'", '"', $data["m6"]) : "";

    $db->autoCommit(false);
    foreach ($data["seleccion"] as $curso) {
        $cursoData = explode("__", $curso); //formato pensum, codigo, seccion
        $dataIP = json_encode(["REMOTE_ADDR" => $_SERVER['REMOTE_ADDR'], "HTTP_X_FORWARDED_FOR" => $_SERVER['HTTP_X_FORWARDED_FOR'], "HTTP_CLIENT_IP" => $_SERVER['HTTP_CLIENT_IP']]);

        $insertarBitacora = "INSERT INTO `bitacora_asignaciones`
            (`fecha_bitacora`,`extension`,`anio`,`semestre`,`evaluacion`,`pensum`,`codigo`,`seccion`,
             `carnet`,`id_asignacion`,`preasignacion`,`status`,`fecha_asignacion`,`observacion`,`usuario_asignacion`,`orden_pago`,descripcion,data_request)
            (
                SELECT NOW(), a.extension, a.anio, a.semestre, a.evaluacion, a.pensum, a.codigo, a.seccion,
                        a.carnet, a.id_asignacion, a.preasignacion, a.status, a.fecha_asignacion, $observacion,a.usuario_asignacion,a.orden_pago, '$descripcion' AS descripcion, '$dataIP' as data_request
                FROM asignacion a
                WHERE
                    anio = $anio and carnet = $carnet and semestre = $semestre and extension = $extension and evaluacion = $evaluacion and codigo = '{$cursoData[1]}' and pensum = '{$cursoData[0]}' and seccion = '{$cursoData[2]}'
            )";
        $resultBitacora = $db->query($insertarBitacora);
        if ($resultBitacora != 1) {
            $db->rollback();
            return -1;
        }
        $eliminarAsignacion = "DELETE FROM asignacion "
            . "WHERE anio = $anio and carnet = $carnet and semestre = $semestre and extension = $extension and evaluacion = $evaluacion and codigo = '{$cursoData[1]}' and pensum = '{$cursoData[0]}' and seccion = '{$cursoData[2]}'";
        $resultEliminar = $db->query($eliminarAsignacion);
        if ($resultEliminar != 1) {
            $db->rollback();
            return -1;
        }
    }
    $db->commit();
    return 1;
}

if (isset($_SESSION[usuario])) {

    $error = false;

    //$carnet_autorizado = '202099999';

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

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = 1;
        $fecha_actual = date("o-m-d H:i:s");
        $carnet = $_SESSION['usuario'];
        $db->Query("SET lc_time_names = 'es_ES'");

        $ciclo = ["fecha_inicio_asignacion" => "2025-09-08 07:00:00", "fecha_fin_asignacion" => "2025-09-10 23:59:00"];

        // Verificacion de la inscripcion del estudiante en el ciclo actual
        $consulta = "SELECT i.carnet, i.carrera, i.extension
        FROM inscripcion i
        WHERE i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet";
        $inscripcion = &$db->getAll($consulta);
        if ($db->isError($inscripcion)) {
            var_dump($consulta);
            die;
            $error = true;
            $mensaje = "Hubo un error al determinar tu inscripcion en el ciclo actual.";
            $url = "../menus/contenido.php";
        } else {
            // para ambas carreras comentada extención e inscripción 
            //verificar la extension...
           /* if ($extension != 0) {
                error("El período no se encuentra habilitado, por favor, verifique el calendario oficial.", "../menus/contenido.php");
                exit();
            }
            if ($inscripcion[0]["extension"] != 0) {
                error("El período no se encuentra habilitado, por favor, verifique el calendario oficial. E11.", "../menus/contenido.php");
                exit();
            }*/

            if ($inscripcion == 0) {
                $error = true;
                $mensaje = "Usted no esta inscrito en el ciclo actual. Por favor verifique esta información";
                $url = "../menus/contenido.php";
            } else {
                if (($ciclo["fecha_inicio_asignacion"] <= $fecha_actual and $ciclo["fecha_fin_asignacion"] >= $fecha_actual) || $carnet == $carnet_autorizado) {
                    $resultEliminar = verificarEliminacionDeCurso($db, $_POST, $extension, $anio, $semestre, $evaluacion, $carnet); //proceso de verificación de eliminación de cursos aplicado en el formulario
                    if ($resultEliminar == -1) {
                        $error = true;
                        $mensaje = "Hubo un error al eliminar las asignaturas seleccionadas, por favor intente de nuevo o escriba al correo informatica@farusac.edu.gt.";
                        $url = "../menus/contenido.php";
                    } else if ($resultEliminar == 1) {
                        $_SESSION['proceso_finalizado'] = "Se han eliminado correctamente las asignaturas seleccionadas, por favor, verifique que ya no se encuentren en el recuadro de asignaciones.";
                        header("location: ../menus/contenido.php");
                        $db->disconnect();
                        exit;
                    }
                    foreach ($inscripcion as $in) {
                        $cursos_a_asignar = "
                                                        SELECT a.pensum, a.codigo, a.seccion, TRIM(c.nombre) AS nombre
                                                        FROM asignacion a
                                                            INNER JOIN curso c ON c.pensum = a.pensum AND c.codigo = a.codigo
                                                        WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion AND a.carnet = $carnet

                                                ";
                        $cursos_asignar = &$db->getAll($cursos_a_asignar);
                        if ($db->isError($cursos_asignar)) {
                            $error = true;
                            $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo." . mysql_error();
                            $url = "../menus/contenido.php";
                        }

                        if (count($cursos_asignar) <> 0) {
                            foreach ($cursos_asignar as $ca) {
                                $a[] = array("pensum" => $ca['pensum'], "codigo" => $ca['codigo'], "seccion" => $ca['seccion'], "nombre" => $ca['nombre']);
                            }
                        }
                    }
                } else {
                    $error = true;
                    if ($inscripcion[0]["extension"] != 0) {
                        $mensaje = "Eliminación de asignaciones - Segundo Semestre 2025 del $ciclo[fecha_inicio_asignacion]hrs al 2024-09-10 23:59:00hrs";
                    } else {
                        $mensaje = "Eliminación de asignaciones - Segundo Semestre 2025 del $ciclo[fecha_inicio_asignacion]hrs al $ciclo[fecha_fin_asignacion]hrs.";
                        //$mensaje = "El período no se encuentra habilitado, por favor, verifique el calendario oficial.";
                    }
                    $url = "../menus/contenido.php";
                }
            }
        }

        if (!$error && !$aviso) {

            // Cargando la pagina de seleccion de Asignatura para Segunda Recuperacion
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('asignacion_semestre_eliminacion.html');

            if (!empty($a)) {

                // Lista de cursos aperturados.
                foreach ($a as $cu) {
                    $template->setVariable(array(
                        'pensum' => $cu[pensum],
                        'codigo' => $cu[codigo],
                        'seccion' => $cu[seccion],
                        'asignatura' => $cu[nombre]
                    ));

                    $template->parse('seleccion_asignaturas');
                }

                $template->setVariable(array(
                    'boton_eliminar' => "<input class='btn btn-danger' type='button' value='Eliminar' onClick=\"ConfirmDialog('¿Está seguro que desea eliminar las asignaciones seleccionadas?')\">"
                ));

                if ($error) {
                    mostrarError($mensaje);
                }
                $template->parse('boton_eliminar');
            } else {
                $template->setVariable(array(
                    'sin_asignaciones_disponibles' => "<div class='alert alert-danger'>Sin secciones disponibles para eliminar</div>"
                ));
                $template->parse('seleccion_asignaturas');
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