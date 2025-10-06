<?php

/*
  Document   : asignacion_aucas_revision.php
  Created on : 16-Nov-2014, 12:34
  Author     : Angel Caal
  Description:
  -> Revisión del aucas al que puede optar el estudiante
 * Según normativo el estudiante puede optar a realizar prácticas cuando este cursando asignaturas 
  mayores al 5to. ciclo y puede asignarse la práctica intermedia 1.
 * Cuando haya superado el 8vo. ciclo puede asignarse la práctica intermedia 1 o 2
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
        $anio = DATE("o");
        $semestre = $_SESSION['semestre'];
        $pensum = "5";
        $codigo = $_POST['codigo'];
        $periodo = $_POST['periodo'];
        $carnet = $_SESSION['usuario'];

        // Requisitos en práctica intermedia 1
        $consulta = "SELECT *
                        FROM `practica_tecnica_asignacion` r
                        WHERE r.`anio` = $anio AND r.`semestre` = $semestre AND r.`carnet` = $carnet AND r.`periodo` = 2";
        $asignadoEnSegundaFecha = $db->getRow($consulta);
        if ($db->isError($asignadoEnSegundaFecha)) {
            $error = true;
            $mensaje = "Hubo un problema al determinar si ya se encontraba asignado en la segunda fecha.";
            $url = $_SERVER[HTTP_REFERER];
        }

        // Comprobación de requisitos para poder optar a la práctica seleccionada
        if ($codigo == "1.05.0") {

            // Requisitos en práctica intermedia 1
            $consulta = "SELECT COUNT(*) AS total
            FROM nota n
            INNER JOIN pensum p
            ON p.pensum = n.pensum AND p.carrera = 1
            INNER JOIN curso c
            ON c.pensum = n.pensum AND c.codigo = n.codigo AND c.ciclo = 5
            WHERE n.carnet = $carnet			
			";
            $practica1 = & $db->getRow($consulta);
            if ($db->isError($practica1)) {
                $error = true;
                $mensaje = "Hubo un problema al verificar los requisitos para la práctica seleccionada.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                if ($practica1[total] < 7) {
                    $error = true;
                    $mensaje = "No cuenta con el 5to. ciclo del pensum aprobado por lo tanto no puede optar
                    a la asignación de la Práctica técnica intermedia 1";
                    $url = $_SERVER[HTTP_REFERER];
                }
            }
        } else if ($codigo == "1.08.0") {

            // Requisitos en práctica intermedia 2
            $consulta = "SELECT COUNT(*) AS total
            FROM nota n
            INNER JOIN pensum p
            ON p.pensum = n.pensum AND p.carrera = 1
            INNER JOIN curso c
            ON c.pensum = n.pensum AND c.codigo = n.codigo AND c.ciclo = 8
            WHERE n.carnet = $carnet";
            $practica2 = & $db->getRow($consulta);
            if ($db->isError($practica1)) {
                $error = true;
                $mensaje = "Hubo un problema al verificar los requisitos para la práctica seleccionada.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                if ($practica2[total] < 5) {
                    $error = true;
                    $mensaje = "No cuenta con el 8vo. ciclo del pensum aprobado por lo tanto no puede optar
                    a la asignación de la Práctica técnica intermedia 2";
                    $url = $_SERVER[HTTP_REFERER];
                }
            }
        }

        // Regiones aperturadas para mostrar a los estudiantes
        $columnaCupo = ($codigo == '1.05.0') ? "cupo" : "cupo2";
        $consulta = "SELECT r.extension, r.anio, r.semestre, r.departamento, d.nombre AS nombre_departamento, r.municipio, m.nombre AS nombre_municipio, r.observacion, r.requisito,
            r.{$columnaCupo} - (
                SELECT COUNT(*)
                FROM practica_tecnica_asignacion a
                WHERE a.anio = r.anio AND a.semestre = r.semestre AND a.departamento = r.departamento AND a.municipio = r.municipio AND a.codigo = '$codigo' AND a.periodo = $periodo
                	AND r.correlativo = a.correlativo
            ) AS cupo, r.correlativo
            FROM practica_tecnica_region r
                INNER JOIN departamento d ON d.departamento = r.departamento
                INNER JOIN municipio m ON m.departamento = r.departamento AND m.municipio = r.municipio
            WHERE r.anio = $anio AND r.semestre = $semestre AND r.periodo = $periodo AND r.{$columnaCupo} > (
                SELECT COUNT(*)
                FROM practica_tecnica_asignacion a
                WHERE a.anio = r.anio AND a.semestre = r.semestre AND a.departamento = r.departamento AND a.municipio = r.municipio AND a.codigo = '$codigo' AND a.periodo = $periodo
                	AND r.correlativo = a.correlativo
            )";

        $regiones_activas = & $db->getAll($consulta);
        if ($db->isError($regiones_activas)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener el detalle de regiones activas.";
            var_dump($consulta); die;
            $url = $_SERVER[HTTP_REFERER];
        }

        if (!$error) {

            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('asignacion_aucas_revision.html');

            foreach ($regiones_activas AS $re) {
                //si ya se asigno algo para segunda fecha, deshabilitar AMG
                if (strpos($re["observacion"], 'AMG') === 0 && !empty($asignadoEnSegundaFecha)) {
                    continue;
                }
                $template->setVariable(array(
                    'pensum' => $pensum,
                    'codigo' => $codigo,
                    'periodo' => $periodo,
                    'departamento' => $re["departamento"],
                    'correlativo' => $re["correlativo"],
                    'nombre_departamento' => $re["nombre_departamento"],
                    'municipio' => $re["municipio"],
                    'nombre_municipio' => $re["nombre_municipio"],
                    'cupo' => $re["cupo"],
                    'observacion' => (strpos($re["observacion"], 'AMG') === 0) ? $re["observacion"] . "<span class='text-warning'> </span>" : $re["observacion"],
                    'requisito' => $re["requisito"],
                ));
                $template->parse("practicas_activas");
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

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
