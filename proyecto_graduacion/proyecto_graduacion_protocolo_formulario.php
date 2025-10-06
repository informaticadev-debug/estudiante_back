<?php

/*
  Documento  : proyecto_graduacion_solicitudformulario.php
  Creado el  : 03 de junio de 2014, 17:02
  Author     : Angel Caal
  Description:
  Formulario para solicitud de aprobacion de proyecto de graduación
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "../config/Conexion.php";
require_once "HTML/Template/Sigma.php";

$conexionDB = new Conexion();

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

        $carnet = $_SESSION['usuario'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $nombre_semestre = $_SESSION['nombre_semestre'];

        $antecedentes = $_SESSION['antecedentes'];
        $justificacion = $_SESSION['justificacion'];
        $objetivos = $_SESSION['objetivos'];
        $planteamiento_problema = $_SESSION['planteamiento_problema'];
        $delimitacion = $_SESSION['delimitacion'];
        $grupo_objetivo = $_SESSION['grupo_objetivo'];
        $metodos = $_SESSION['metodos'];
        $bibliografia = $_SESSION['bibliografia'];

        // Para filtrar por carrera
        $carrera = inscripcion_estudiante($db, $anio, $semestre, $carnet);

        if ($carrera == 1) {
            $pensum = "5";
            $asesorias = 40;
        } else if ($carrera == 3) {
            $pensum = "18,20";
            $asesorias = 40;
        }

        // -> Docentes de la Facutald de Arquitectura
        $consulta = "SELECT CONCAT(TRIM(d.nombre), ', ', TRIM(d.apellido)) AS nombre, d.registro_personal
		FROM docente d
		WHERE d.status = 'ALTA' AND d.registro_personal <> 0
        and d.registro_personal not in (20030689,20110177,20040722)
		AND (
			EXISTS(
				SELECT *
				FROM staff s
				WHERE s.registro_personal = d.registro_personal
				AND s.anio >= 2013 AND s.pensum IN ($pensum)
			)
			AND EXISTS(
				SELECT *
				FROM proyecto_graduacion_asesores a
				INNER JOIN proyecto_graduacion p
				ON p.`numero_tema` = a.`numero_tema` AND p.`fecha_vencimiento` > LEFT(NOW(),10)
				WHERE a.`registro_personal` = d.registro_personal
				AND NOT EXISTS(
				SELECT *
				FROM examen_privado p1
				WHERE p1.`carnet` = p.`carnet` AND p1.`carrera` = p.`carrera` AND p1.`aprobado` = 1
				)
				HAVING COUNT(*) < $asesorias
			)
			OR EXISTS (
				SELECT *
				FROM staff s
				WHERE s.`extension` = 0 AND s.`anio` = $anio AND s.`semestre` = $semestre 
				AND s.`evaluacion` = 1 AND s.`codigo` = '1.10.1' AND s.`registro_personal` = d.`registro_personal`
			)
			OR EXISTS (
				SELECT *
				FROM eps_seccion s
				WHERE s.`extension` = 0 AND s.`anio` >= 2015 AND s.`registro_personal` = d.`registro_personal`
			)
			OR EXISTS (
				SELECT *
				FROM staff s
				WHERE s.`extension` = 0 AND s.`anio` = $anio AND s.`semestre` = $semestre 
				AND s.`evaluacion` = 1 AND s.`codigo` = '4.10.6' AND s.`registro_personal` = d.`registro_personal`
			)
			OR EXISTS(
				SELECT *
				FROM docente d1
				WHERE d1.estado = 'ACTIVO' AND d1.registro_personal = d.registro_personal
			)
		)
		ORDER BY d.nombre ASC";
        $asesores = & $db->getAll($consulta);
        if ($db->isError($asesores)) {
            $error = true;
            $mensaje = "Hubo un error durante la consulta de los datos de los examinadores.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Numero del tema; el tema es el mismo numero que la solicitud aprobada
            $consulta = "SELECT g.numero_tema, g.carrera, g.tema_enmarcado
            FROM proyecto_graduacion g
            WHERE g.carnet = $carnet";
            $detalle_tema = & $db->getRow($consulta);
            if ($db->isError($detalle_tema)) {
                $error = true;
                $mensaje = "Hubo un problema al momento de obtener el número de tema aprobado anteriormente.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                if ($detalle_tema == 0) {
                    $error = true;
                    $mensaje = "No existe tema aprobado anteriormente el proceso de solicitud de aprobación de protocolo no puede continuar.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    $_SESSION['numero_tema'] = $detalle_tema['numero_tema'];
                    $_SESSION['carrera'] = $detalle_tema['carrera'];
                    $_SESSION['modalidad'] = $detalle_tema['modalidad'];
                    $_SESSION['tema_enmarcado'] = $detalle_tema['tema_enmarcado'];
                    // Modalidades de tema
                    $consulta = "SELECT *
                    FROM proyecto_graduacion_modalidad m
                    WHERE m.area = $carrera";
                    $datos_modalidades = & $db->getAll($consulta);
                    if ($db->isError($datos_modalidades)) {
                        $error = true;
                        $mensaje = "Hubo un problema al obtener el listado de modalidades de proyecto de graduación.";
                        $url = $_SERVER[HTTP_REFERER];
                    }
                }
            }
        }

        if (!$error) {

            $template = new HTML_Template_Sigma('../templates');
            //print_r($detalle_tema);die;
            if ($detalle_tema['carrera'] == 1) {
                $template->loadTemplateFile('proyecto_graduacion_protocolo_formulario_arq.html');
            } else if ($detalle_tema['carrera'] == 3 && $detalle_tema['tema_enmarcado'] == 8) {
                $template->loadTemplateFile('proyecto_graduacion_protocolo_formulario_dg.html');
            } else if ($detalle_tema['carrera'] == 3 && $detalle_tema['tema_enmarcado'] == 9) {
                $template->loadTemplateFile('proyecto_graduacion_protocolo_formulario_dg_produccion_de_conociminentos.html');
            }
           

            /**
             * Cargar asesores si esta asignado a los cursos de proyectos 2 y eps y si es de DG
             */
            if ($detalle_tema['carrera'] == 3) {
                $datos_pg2 = $conexionDB->queryList("
                    SELECT d.`registro_personal`, CONCAT(d.nombre, ', ', d.apellido) AS nombre
                    FROM asignacion a
                            INNER JOIN staff s ON s.`extension` = a.`extension` AND s.`anio` = a.`anio` AND s.`semestre` = a.`semestre` AND s.`evaluacion` = a.`evaluacion` AND a.`codigo` = s.`codigo` AND s.`seccion` = a.`seccion`
                            INNER JOIN docente d ON d.`registro_personal` = s.`registro_personal`
                    WHERE a.`anio` = $anio AND a.`semestre` = $semestre AND a.`evaluacion` = 1 AND a.`carnet` = $carnet AND a.`codigo` = 31021
                    ");
                $datos_eps = $conexionDB->queryList("
                    SELECT d.`registro_personal`, CONCAT(d.nombre, ', ', d.apellido) AS nombre
                    FROM asignacion a
                            INNER JOIN staff s ON s.`extension` = a.`extension` AND s.`anio` = a.`anio` AND s.`semestre` = a.`semestre` AND s.`evaluacion` = a.`evaluacion` AND a.`codigo` = s.`codigo` AND s.`seccion` = a.`seccion`
                            INNER JOIN docente d ON d.`registro_personal` = s.`registro_personal`
                    WHERE a.`anio` = $anio AND a.`semestre` = $semestre AND a.`evaluacion` = 1 AND a.`carnet` = $carnet AND a.`codigo` = 31041
                    ");
                $template->setVariable(array(
                    'nombre_asesor_default1' => (!empty($datos_pg2)) ? $datos_pg2[0]['nombre'] : '',
                    'rg_asesor_default1' => (!empty($datos_pg2)) ? $datos_pg2[0]['registro_personal'] : '',
                    'nombre_asesor_default2' => (!empty($datos_eps)) ? $datos_eps[0]['nombre'] : '',
                    'rg_asesor_default2' => (!empty($datos_eps)) ? $datos_eps[0]['registro_personal'] : '',
                    'desc_am' => (!empty($datos_pg2)) ? '' : 'hidden',
                    'desc_ag' => (!empty($datos_eps)) ? '' : 'hidden',
                ));
            }

            $template->setVariable(array(
                'antecedentes' => $antecedentes,
                'justificacion' => $justificacion,
                'objetivos' => $objetivos,
                'planteamiento_problema' => $planteamiento_problema,
                'delimitacion' => $delimitacion,
                'grupo_objetivo' => $grupo_objetivo,
                'metodos' => $metodos,
                'bibliografia' => $bibliografia
            ));

            foreach ($asesores AS $as) {
                if ($detalle_tema['carrera'] == 1) {
                    $estudiantesAsesorados = getProyectosAsesorados($db, $as["registro_personal"]);
                    //si es de arquitectura y tiene mas de 15 estudiantes asesorados... descartar 2019-03-11
                    if (count($estudiantesAsesorados) > 16) {
                        continue;
                    }
                }
                $template->setVariable(array(
                    'registro_personal' => $as["registro_personal"],
                    'nombre_asesor' => $as["nombre"]
                ));
                $template->parse('listado_asesores_primero');
            }

            foreach ($asesores AS $as) {
                if ($detalle_tema['carrera'] == 1) {
                    $estudiantesAsesorados = getProyectosAsesorados($db, $as["registro_personal"]);
                    //si es de arquitectura y tiene mas de 15 estudiantes asesorados... descartar 2019-03-11
                    if (count($estudiantesAsesorados) > 16) {
                        continue;
                    }
                }
                $template->setVariable(array(
                    'registro_personal' => $as["registro_personal"],
                    'nombre_asesor' => $as["nombre"]
                ));
                $template->parse('listado_asesores_segundo');
            }

            foreach ($asesores AS $as) {
                if ($detalle_tema['carrera'] == 1) {
                    $estudiantesAsesorados = getProyectosAsesorados($db, $as["registro_personal"]);
                    //si es de arquitectura y tiene mas de 15 estudiantes asesorados... descartar 2019-03-11
                    if (count($estudiantesAsesorados) > 16) {
                        continue;
                    }
                }
                $template->setVariable(array(
                    'registro_personal' => $as["registro_personal"],
                    'nombre_asesor' => $as["nombre"]
                ));
                $template->parse('listado_asesores_tercero');
            }

            foreach ($datos_modalidades AS $mo) {

                $template->setVariable(array(
                    'modalidad' => $mo["modalidad"],
                    'nombre_modalidad' => $mo["nombre"]
                ));
                $template->parse('listado_modalidades');
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
