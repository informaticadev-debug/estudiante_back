<?php

/*
  Document   : cierre_solicitud_formulario.php
  Created on : 08-Jun-2015, 16:41
  Author     : Angel Caal
  Description:
  -> Solicitud de constancias de cierre
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "./DeppaApiClient.php";
require_once "../config/local.php";
require_once "HTML/Template/Sigma.php";

session_start();

function getAsignacionesParaEvaluacionDocente($db, $extension, $anio, $semestre, $evaluacion, $carnet) {
    $query = "SELECT a.*, d.`nombre`, d.`apellido`, c.`numero_boleta`, c.`nombre` AS nombre_curso
                FROM asignacion a
                        INNER JOIN staff s ON s.`extension` = a.`extension` AND s.`anio` = a.`anio` AND s.`semestre` = a.`semestre` AND s.`evaluacion` = a.`evaluacion` AND s.`pensum` = a.`pensum` AND s.`codigo` = a.`codigo`  AND s.`seccion` = a.`seccion` 
                        INNER JOIN docente d ON d.`registro_personal` = s.`registro_personal`
                        INNER JOIN curso c ON c.`pensum` = a.`pensum` AND c.`codigo` = a.`codigo`
                        -- AGREGAR FILTRO DE ESTUDIANTES QUE FUERON REGISTRADOS COMO AUSENTES
                WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.`evaluacion` = $evaluacion AND a.`carnet` = $carnet AND a.`abandono`= 0"; 
    $asignaciones = & $db->getAll($query);
    if (!$db->isError($asignaciones)) {
        return $asignaciones;
    }
    return [];
}

function getAsignacionesParaEvaluacionDocenteEPS($db, $carnet) {
    $query = "SELECT '1.11.1' AS codigo, 'Ejercicio Profesional Supervisado' AS nombre_curso, CONCAT(\"G\", s.grupo) AS seccion, p.id_promocion, p.anio as anio, -- CAMBIAR ANIO POR EVALUACION EN 2023, RESTABLECER A p.anio (esto es cuando la evaluación de promosión no corresponde al año en curso)
                            d.`registro_personal`, d.nombre, d.apellido, a.`carne`,  a.`ed_url`, a.`ed_sid`, a.`ed_token`, '71' as numero_boleta, a.carne as carnet
		                FROM epsda_asignacion a
		                        INNER JOIN `epsda_promocion` p ON p.`id_promocion` = a.`id_promocion`
			                    INNER JOIN `epsda_registro` r ON r.`id_asignacion` = a.`id_asignacion`
			                        INNER JOIN `epsda_sede` s ON s.`id_sede` = r.`id_sede`
			                        INNER JOIN `epsda_asesor` aa ON aa.`id_asesor` = s.`id_asesor`
			                    INNER JOIN `docente` d ON d.`registro_personal` = aa.`registro_personal`
		                    WHERE a.`requisitos` = 1 AND a.carne = $carnet AND a.`id_promocion` = 31 AND  r.`estado` NOT IN (0,5) AND a.extension = 0";			    
    $asignaciones = & $db->getAll($query);
    if (!$db->isError($asignaciones)) {
        return $asignaciones;
    } 
    return [];
}

if (isset($_SESSION["usuario"])) {

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
        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;

        $carnet = $_SESSION['usuario'];
        $fecha_actual = date("o-m-d");

        $template = new HTML_Template_Sigma('../templates');
        $template->loadtemplatefile('evaluaciondocente.html');
        
        //verificar si existe un periodo abierto para la evaluación docente en DEPPA..
        $deppaClient = new DeppaApiClient("https://deppa.usac.edu.gt/sistema", "ws_farusac", "deppa", "3", []);
        $dataEvaluacionAbierta = $deppaClient->verificarPeriodoDeEvaluacion(date("o-m-d"));
        //var_dump($dataEvaluacionAbierta); die;
        if (($dataEvaluacionAbierta["error"] == false && $dataEvaluacionAbierta["data"]["exito"] == false) || $dataEvaluacionAbierta["error"] != false) {
            header("location: ../menus/inicio.php");
            exit;
        }
        //verificar si el estudiante tiene asignaciones..
        $asignaciones = getAsignacionesParaEvaluacionDocente($db, $periodo_evaluacion_docente["extension"], $periodo_evaluacion_docente["anio"], $periodo_evaluacion_docente["semestre"], $periodo_evaluacion_docente["evaluacion"], $carnet);
        //verificar que ya tenga boleta vinculada o vincular..
        
	$asignaciones_finales = [];
        foreach ($asignaciones as $a) {
            //si ya tienen url.. agregar a la sección de evaluar...
	        if (!empty($a["ed_url"])) {
		        //verificar si ya lleno la encuesta...
		        $estadoEvaluacion = $deppaClient->verificarEstadoEvaluacion($a["ed_sid"],$a["ed_token"]);
		        if($estadoEvaluacion["error"] == false && $estadoEvaluacion["data"]["exito"] == true && $estadoEvaluacion["data"]["mensaje"] == "finalizada"){
		            continue;
		        }
                    $asignaciones_finales[] = $a;
                    continue;
            }
            //si no tiene url.. proceder a vincular y generar boleta en DEPPA
            $token = md5($a["carnet"] . $a["carnet"] . $a["extension"] . $a["anio"] . $a["semestre"] . $a["evaluacion"] . $a["codigo"] . $a["seccion"] . $a["carnet"] . $a["carnet"]);
	    if ($a["codigo"] == '1.10.1' && in_array($a["seccion"], ["PGRAD", "PGRAD1", "PGRAD2", "PGRAD3"])) {
                $a["boleta"] = "61";
            }
	    $dataGenerarBoleta = $deppaClient->generarEnlaceEvaluacionEnLinea($a["anio"], $dataEvaluacionAbierta["data"]["codigoPeriodo"], $a["codigo"], $a["seccion"], $token, $a["numero_boleta"]);
            if ($dataGenerarBoleta["error"] == false && $dataGenerarBoleta["data"]["exito"] == true) {
                $consulta = "UPDATE asignacion SET ed_url = '{$dataGenerarBoleta["data"]["mensaje"]}', ed_sid = '{$dataGenerarBoleta["data"]["sid"]}', ed_token = '{$dataGenerarBoleta["data"]["token"]}'"
                        . " WHERE extension = {$a["extension"]} AND anio = {$a["anio"]} AND semestre = {$a["semestre"]} AND evaluacion = {$a["evaluacion"]} AND pensum = {$a["pensum"]} AND codigo = '{$a["codigo"]}' AND seccion = '{$a["seccion"]}' AND carnet = {$a["carnet"]}";
                $actualizarAsignacion = & $db->Query($consulta);
                if (!$db->isError($actualizarAsignacion)) {
                    $a["ed_url"] = $dataGenerarBoleta["data"]["mensaje"];
                    $a["ed_sid"] = $dataGenerarBoleta["data"]["sid"];
                    $a["ed_token"] = $dataGenerarBoleta["data"]["token"];
                    $asignaciones_finales[] = $a;
                }
	        } else {
	        	//if ($carnet == 201902533) {var_dump($dataGenerarBoleta,$a["anio"], $dataEvaluacionAbierta["data"]["codigoPeriodo"], $a["codigo"], $a["seccion"], $token, $a["numero_boleta"]); die;}
	        }
        }
        
        $asignacionEPS = getAsignacionesParaEvaluacionDocenteEPS($db, $carnet);
        foreach ($asignacionEPS as $a) {
            //si ya tienen url.. agregar a la sección de evaluar...
            if (!empty($a["ed_url"])) {
	            //verificar si ya lleno la encuesta...
	            $estadoEvaluacion = $deppaClient->verificarEstadoEvaluacion($a["ed_sid"],$a["ed_token"]);
	            if($estadoEvaluacion["error"] == false && $estadoEvaluacion["data"]["exito"] == true && $estadoEvaluacion["data"]["mensaje"] == "finalizada"){
	                continue;
	            }
                $asignaciones_finales[] = $a;
                continue;
            }
            //si no tiene url.. proceder a vincular y generar boleta en DEPPA
            $token = md5($a["carnet"] . $a["carnet"] . $a["id_promocion"] . $a["codigo"] . $a["seccion"] . $a["carnet"] . $a["carnet"]);
            $dataGenerarBoleta = $deppaClient->generarEnlaceEvaluacionEnLinea($a["anio"], $dataEvaluacionAbierta["data"]["codigoPeriodo"], $a["codigo"], $a["seccion"], $token, $a["numero_boleta"]);
            if ($dataGenerarBoleta["error"] == false && $dataGenerarBoleta["data"]["exito"] == true) {
                $consulta = "UPDATE epsda_asignacion SET ed_url = '{$dataGenerarBoleta["data"]["mensaje"]}', ed_sid = '{$dataGenerarBoleta["data"]["sid"]}', ed_token = '{$dataGenerarBoleta["data"]["token"]}'"
                        . " WHERE carne = {$a["carnet"]}";
                $actualizarAsignacion = & $db->Query($consulta);
                if (!$db->isError($actualizarAsignacion)) {
                    $a["ed_url"] = $dataGenerarBoleta["data"]["mensaje"];
                    $a["ed_sid"] = $dataGenerarBoleta["data"]["sid"];
                    $a["ed_token"] = $dataGenerarBoleta["data"]["token"];
                    $asignaciones_finales[] = $a;
                }
            } else {
            	
            }
        }
        
        
        if (empty($asignaciones_finales)) {
            header("location: ../menus/inicio.php");
            exit;
        }
        
        foreach ($asignaciones_finales AS $en) {
            $template->setVariable(array(
                'url_encuesta' => $en["ed_url"],
                'encuesta' => $en["codigo"] . " " . $en["nombre_curso"] . " '" . $en["seccion"] . "' - " . $en["nombre"] . " " . $en["apellido"]
            ));
            $template->parse("listado_encuestas");
        }

        $template->show();
        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
