<?php

/*
  Document   : aucas_gestion.php
  Created on : 14-Nov-2014, 18:44
  Author     : Angel Caal
  Description:
  -> Gestión general de las AUCAS
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "../config/local.php";
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
        mostrarErrorLogin($mensaje);
    } else {
        $db->setfetchmode(DB_FETCHMODE_ASSOC);

        $carnet = $_SESSION['usuario'];
        $anio = $_SESSION['anio'];

        //informacion para gestion de periodos
        $semestre = $_SESSION['semestre'];

        $fecha_actual = DATE("o-m-d H:i");

        // Prácticas aperturadas
        $consulta = "SELECT c.codigo, trim(c.nombre) AS asignatura
			FROM curso c
			WHERE c.pensum = 5 AND c.codigo IN ('1.05.0', '1.08.0')";
        $practicas = & $db->getAll($consulta);
        if ($db->isError($practicas)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener las prácticas intermedias activas";
            $url = "../menus/contenido.php";
        } else {

            // Comprobar si existen practicas registradas sin aprobacion
            $consulta = "SELECT a.codigo, TRIM(c.nombre) AS asignatura, d.nombre AS departamento, m.nombre AS municipio,
				DATE_FORMAT(a.fecha_asignacion, '%m%d%H%i') AS hora_asignacion, per.descripcion as periodo_descripcion, reg.observacion as region_observacion, per.periodo, reg.requisito
				FROM practica_tecnica_asignacion a
				INNER JOIN departamento d ON d.departamento = a.departamento
				INNER JOIN municipio m ON m.departamento = a.departamento AND m.municipio = a.municipio
				INNER JOIN curso c ON c.pensum = a.pensum AND c.codigo = a.codigo
                                INNER JOIN practica_tecnica_periodo per ON per.anio = a.anio AND per.semestre = a.semestre AND per.periodo = a.periodo 
                                INNER JOIN practica_tecnica_region reg ON reg.anio = a.anio AND reg.semestre = a.semestre AND reg.periodo = a.periodo AND reg.departamento = a.departamento AND reg.municipio = a.municipio 
				WHERE a.carnet = $carnet AND a.anio = $anio AND a.aprobado IS NULL AND a.correlativo = reg.correlativo ";

            $practica_activa = & $db->getAll($consulta);
            if ($db->isError($practica_activa)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener el estado de practicas asignadas.";
                $url = "../menus/contenido.php";
            }
            //extraer los registros de los periodos activos y su informacion...
            $consulta = "SELECT *
			FROM practica_tecnica_periodo c
			WHERE c.anio = $anio AND c.semestre = $semestre";
            $periodos = & $db->getAll($consulta);
            
            if ($db->isError($periodos)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener los periodos disponibles.";
                $url = "../menus/contenido.php";
            }

            if (empty($practica_activa)) {
                if (($fecha_actual < $habilitar_eps_pt[0] || $fecha_actual >= $habilitar_eps_pt[1]) && $carnet != 200314128) {
                    $aviso = true;                    
                    //$mensaje = "La asignación de Prácticas Técnicas Intermedias no está habilita, verifique la programación del semestre actual.";
                    $mensaje = "La asignación de Prácticas Técnicas no está habilitada";
                    $url = "../menus/contenido.php";
                }
            }
        }

        if (!$error && !$aviso) {

            $template = new HTML_Template_Sigma('../templates');

            if (count($practica_activa) == 0) {
                $template->loadTemplateFile('aucas_gestion.html');

                foreach ($practicas AS $pa) {
                    $template->setVariable(array(
                        'codigo' => $pa[codigo],
                        'asignatura' => $pa[asignatura],
                        'departamento' => $pa[departamento],
                        'municipio' => $pa[municipio],
                    ));
                    $template->parse("practicas_disponibles");
                }
                foreach ($periodos AS $data) {
                    $template->setVariable(array(
                        'periodo_codigo' => $data['periodo'],
                        'periodo_descripcion' => $data['descripcion'],
                    ));
                    $template->parse("periodos_disponibles");
                }
                $template->parse("solicitud_aucas");
            } else {
                $template->loadTemplateFile('aucas_gestion_enproceso.html');
                foreach ($practica_activa AS $pr) {
                    $template->setVariable(array(
                        'codigo' => $pr[codigo],
                        'asignatura' => $pr[asignatura],
                        'departamento' => $pr[departamento],
                        'municipio' => $pr[municipio],
                        'periodo_descripcion' => $pr[periodo_descripcion],
                        'region_observacion' => $pr[region_observacion],
                        'requisito' => $pr[requisito],
                    ));
                    $template->parse("listado_practicas_activas");
                }
                /*if (count($practica_activa) == 1 && strpos($practica_activa[0]["region_observacion"], 'AMG') !== 0) {
                    $codigo_extra = ($practica_activa[0]["codigo"] == "1.05.0") ? '1.08.0' : '1.05.0';
                    $periodo_extra = ($practica_activa[0]["periodo"] == 1) ? 2 : 1;
                    $template->setVariable(array(
                        'codigo_extra' => $codigo_extra,
                        'periodo_extra' => $periodo_extra,
                        'texto_asignacion_extra' => "Asignar Práctica Técnica " . (($codigo_extra == '1.05.0') ? 1 : 2) . " en la " . (($periodo_extra == 1) ? 'primera' : 'segunda') . " fecha.",
                    ));
                    $template->parse("asignacion_extra");
                }*/
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
