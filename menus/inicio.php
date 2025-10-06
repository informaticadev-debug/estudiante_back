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

$fecha_habilitar_etrabajador = ["2018-12-25", "2018-12-01"];

$fecha_habilitar_ext = ["2025-01-16 06:00", "2025-01-16 13:00"];

$fecha_activa_asigext =    "2025-07-17 06:00";
$fecha_desactiva_asigext = "2025-07-17 10:00";

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
        errorLogin($mensaje);
    } else {
        
        $db->setfetchmode(DB_FETCHMODE_ASSOC);

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio =  $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = "1";
        $carnet = $_SESSION['usuario'];

        unset($_SESSION['encuesta']);

        // Datos estudiante
        $consulta = "SELECT e.fecha_actualizacion, DATE(DATE_ADD(NOW(),INTERVAL -18 YEAR)) AS calculo_edad, 
	e.fecha_nacimiento, e.nombre, e.dpi
        FROM estudiante e
        WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al obtener los datos del estudiante";
            $url = "../index.php";
        }
        
        // Verificacion para preasignarse EPS
        $consulta = "SELECT a.carnet
		FROM asignacion a		
		WHERE a.carnet = $carnet AND a.codigo IN ('1.10.1','091') AND a.status = 1
		AND EXISTS(
			SELECT i.carnet 
            FROM inscripcion i
            WHERE i.anio = $anio AND i.semestre = $semestre AND i.carnet = a.carnet
        )";
        $inscripcion = & $db->getAll($consulta);
        if ($db->isError($inscripcion)) {
            $error = true;
            $mensaje = "Error al consultar el estado de la inscripcion.";
            $url = "../index.php";
        } else {

            // Verificacion de Diseño Arquitectonico en Pensum 82
            $consulta = "SELECT n.nota
            FROM nota n
            WHERE n.carnet = $carnet AND n.codigo IN ('1.10.1', 091)";
            $pensum82 = & $db->getRow($consulta);
            if ($db->isError($consulta)) {
                $error = true;
                $mensaje = "Hubo un error al verificar la aprobacion de Diseño Arquitectonico 9 en Pensum 82";
                $url = "../index.php";
            }

            // Verificacion Estudiantes con Cierre de Pensum pueden asignarse EPS.
            $consulta = "SELECT c.carnet
            FROM carrera_estudiante c
            WHERE c.carnet = $carnet AND c.fecha_cierre AND status_estudiante = '1' IS NOT NULL
            AND c.carrera = (
                SELECT i.carrera
                FROM inscripcion i
                WHERE i.anio = $anio AND i.carnet = c.carnet
                LIMIT 1
            )";
            $con_cierre = & $db->getRow($consulta);
            if ($db->isError($con_cierre)) {
                $error = true;
                $mensaje = "Hubo un error al verificar el cierre del estudiante.";
                $url = "../index.php";
            } else {

                // Verificacion de ordenes de Pago de cursos.
                $consulta_ordenes_cursos = "SELECT a.carnet, a.orden_pago
		FROM asignacion a
		WHERE a.anio = $anio AND a.preasignacion = 1 AND a.orden_pago IS NOT NULL AND a.carnet = $carnet";

                // Verificacion de ordenes de Pago de examenes y otrso.
                $consulta_ordenes_otros = "SELECT o.carnet, o.orden_pago
		FROM orden_pago o
		WHERE o.anio = $anio AND o.carnet = $carnet AND o.no_boleta_deposito IS NULL AND rubro = 9";
                $orden_pago = $consulta_ordenes_cursos . " UNION ALL " . $consulta_ordenes_otros;
                //print_r($orden_pago);
                $ordenes_pago = & $db->getRow($orden_pago);
                if ($db->isError($ordenes_pago)) {
                    $error = true;
                    $mensaje = "Hubo un error al comprobar el estado de cuenta.";
                    $url = "../index.php";
                } else {

                    // Consulta para habilitar la opcion de Proyecto de Graduación
                    $consulta = "SELECT c.fecha_privado
                    FROM carrera_estudiante c
                    WHERE c.carnet = $carnet AND c.carrera IN (1,2,3)";
                    $proyecto_graduacion = & $db->getAll($consulta);
                    if ($db->isError($proyecto_graduacion)) {
                        $error = true;
                        $mensaje = "Hubo un error al consultar el estado de solicitud de proyecto de graduación.";
                        $url = "../index.php";
                    } else {

                        // Verificación para aperturar el bloque de AUCAS para estudiantes de la Licenciatura en Arquitectura
                        // -> 1. Verificar inscripcion en el ciclo actual en Arquitectura
                        $consulta = "SELECT *
                        FROM inscripcion i
                        WHERE i.anio = $anio AND i.carnet = $carnet AND i.semestre = $semestre AND i.carrera = 1";
                        $inscripcion_arq = & $db->getRow($consulta);
                        if ($db->isError($inscripcion_arq)) {
                            $error = true;
                            $mensaje = "Hubo un problema al obtener el detalle de inscripción para estudiantes de la Licenciatura en Arquitectura.";
                            $url = "../index.php";
                        } else {

                            // Habilitar fecha evaluación docente PAI
                            $fecha_habilita_encuesta = "2025-07-01 07:00";
                            $fecha_desabilita_encuesta = "2025-07-02 14:00";

                            // Encuestas
                            $consulta_encuesta = "SELECT 1 AS prioridad, a.encuesta, e.nombre, a.completada
                            FROM encuestas_asignacion a
                            INNER JOIN encuestas e 
                            ON e.encuesta = a.encuesta
                            WHERE a.carnet = $carnet AND a.completada = 0 AND e.estado = 1";

                            $consulta_evaluacion_docente = "SELECT 2 AS prioridad, CONCAT(a.codigo,a.seccion) AS encuesta, CONCAT(a.codigo, ' - ', TRIM(c.nombre), ' - ', a.seccion, ' ', UCASE(CONCAT(TRIM(d.titulo), ' ', TRIM(d.nombre), ' ', TRIM(d.apellido)))) AS nombre,
                            0 AS completada    
                            FROM asignacion a
                            INNER JOIN curso c
                            ON c.pensum = a.pensum AND c.codigo = a.codigo
                            INNER JOIN staff s
                            ON s.extension = a.extension AND s.anio = a.anio AND s.semestre = a.semestre
                            AND s.evaluacion = a.evaluacion AND s.pensum = a.pensum AND s.codigo = a.codigo
                            AND s.seccion = a.seccion
                            INNER JOIN docente d
                            ON d.registro_personal = s.registro_personal
                            WHERE a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = 2 
			        AND a.carnet = $carnet AND a.status = 1 AND a.extension in (0)
                            AND NOT EXISTS(
                                SELECT 1
                                FROM evaluacion_docente v
                                WHERE v.anio = a.anio AND v.semestre = a.semestre AND v.evaluacion = 2
                                AND v.carnet = a.carnet AND v.codigo = a.codigo AND v.seccion = a.seccion
                            )";
                            
                            //evaluacion, doble de tipologia
                            /*$consulta_evaluacion_docente = "SELECT 2 AS prioridad, CONCAT(a.codigo,a.seccion) AS encuesta, CONCAT(a.codigo, ' - ', TRIM(c.nombre), ' - ', s.seccion, ' ', UCASE(CONCAT(TRIM(d.titulo), ' ', TRIM(d.nombre), ' ', TRIM(d.apellido)))) AS nombre,
                            0 AS completada    
                            FROM asignacion a
                            INNER JOIN curso c
                            ON c.pensum = a.pensum AND c.codigo = a.codigo
                            INNER JOIN staff s
                            ON s.extension = a.extension AND s.anio = a.anio AND s.semestre = a.semestre
                            AND s.evaluacion = a.evaluacion AND s.pensum = a.pensum AND s.codigo = a.codigo
                            -- AND s.seccion = a.seccion
                            INNER JOIN docente d
                            ON d.registro_personal = s.registro_personal
                            WHERE a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = 2
                            AND a.carnet = $carnet AND a.status = 1 AND a.extension in (0)
                            AND NOT EXISTS(
                                SELECT *
                                FROM evaluacion_docente v
                                WHERE v.anio = a.anio AND v.semestre = a.semestre AND v.evaluacion = 2
                                AND v.carnet = a.carnet AND v.codigo = a.codigo AND v.seccion = s.seccion
                            ) AND (s.seccion = a.seccion OR (s.codigo = '3.06.8' AND (s.`seccion` = 'A' OR s.`seccion` = 'B')))";*/
                            
                            
                            //EVALUACIÓN ESPECIAL PARA EL AREA DE DISEÑOS...
                            /*$consulta_evaluacion_docente = "SELECT 2 AS prioridad, CONCAT(a.codigo,a.seccion) AS encuesta, CONCAT(a.codigo, ' - ', TRIM(c.nombre), ' - ', a.seccion, ' ', UCASE(CONCAT(TRIM(d.titulo), ' ', TRIM(d.nombre), ' ', TRIM(d.apellido)))) AS nombre,
                            0 AS completada    
                            FROM asignacion a
                            INNER JOIN curso c
                            ON c.pensum = a.pensum AND c.codigo = a.codigo
                            INNER JOIN staff s
                            ON s.extension = a.extension AND s.anio = a.anio AND s.semestre = a.semestre
                            AND s.evaluacion = a.evaluacion AND s.pensum = a.pensum AND s.codigo = a.codigo
                            AND s.seccion = a.seccion
                            INNER JOIN docente d
                            ON d.registro_personal = s.registro_personal
                            WHERE a.anio = 2019 AND a.semestre = 2 AND a.evaluacion = 1 and a.status = 1
                            AND a.carnet = $carnet AND a.status = 1 AND a.extension in (0) 
                            /* AND a.`codigo` IN ('2.02.5','1.01.1', '1.02.1', '1.03.1', '1.04.1', '1.05.1', '1.06.1', '1.07.1', '1.08.1', '1.09.1', '1.10.1') /
                            AND a.`codigo` IN ('2.02.5', '1.01.1', '1.02.1', '1.03.1', '1.04.1', '1.05.1')
                            AND NOT EXISTS(
                                SELECT *
                                FROM evaluacion_docente v
                                WHERE v.anio = a.anio AND v.semestre = a.semestre AND v.evaluacion = a.evaluacion
                                AND v.carnet = a.carnet AND v.codigo = a.codigo AND v.seccion = a.seccion
                            )";*/

                            $union_consultas = $consulta_encuesta . " UNION ALL " . $consulta_evaluacion_docente;
			    $encuestas = & $db->getAll($union_consultas);
                            if ($db->isError($encuestas)) {
                                $error = true;
                                $mensaje = "Hubo un problema al verificar si existen encuestas pendientes de realizar. " . mysql_error();
                                $url = "../index.php";
                            } else {

                                // Departamentos
                                $consulta = "SELECT *
                                FROM departamento";
                                $departamentos = & $db->getAll($consulta);
                                if ($db->isError($departamentos)) {
                                    $error = true;
                                    $mensaje = "Hubo un problema al obtener los departamentos.";
                                    $url = "../index.php";
                                } else {

                                    /* // Validación de encuestas externas
                                      $consulta = "SELECT *
                                      FROM actualizaciones.validar_encuesta_externa v
                                      WHERE v.carnet = $carnet";
                                      $encuesta_externa = & $db->getRow($consulta);
                                      if ($db->isError($encuesta_externa)) {
                                      $error = true;
                                      $mensaje = "Hubo un problema al validar la existencia de encuestas externas.";
                                      $url = "../index.php";
                                      } */
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$error) {

            $fecha_actual = date("o-m-d H:i");

            // Cargando el template para la pagina inicial.
            $template = new HTML_Template_Sigma('../templates');

            if (count($encuestas) <> 0 && $fecha_actual >= $fecha_habilita_encuesta && $fecha_actual <= $fecha_desabilita_encuesta) {
                $template->loadtemplatefile('encuestas.html');
            } else if ($estudiante[fecha_actualizacion] == NULL && $extension == 0 && false) {
                $template->loadtemplatefile('personal_registro_correo_formulario.html');
            } else {
                $template->loadtemplatefile('inicio.html');
            }

            //$template->loadtemplatefile('inicio.html');

            if ($fecha_actual >= $fecha_habilita_encuesta && $fecha_actual <= $fecha_desabilita_encuesta) {

                foreach ($encuestas AS $en) {

                    if ($en[prioridad] == 1) {
                        $template->setVariable(array(
                            'url_encuesta' => "../encuestas/encuesta_alimentarencuesta_formulario.php",
                            'encuesta_encriptada' => MD5($en[encuesta])
                        ));
                    } else if ($en[prioridad] == 2) {
                        $template->setVariable(array(
                            'url_encuesta' => "../encuestas/encuesta_evaluacion_docente.php",
                            'encuesta_encriptada' => MD5($en[encuesta])
                        ));
                    }

                    $template->setVariable(array(
                        'encuesta' => $en[nombre]
                    ));
                    $template->parse("listado_encuestas");
                }
            }

            
            $template->setVariable(array(
                'mostrar_etrabajador' =>
                (
                /*getAsignacionETrabajadorSolicitud($db, $extension, $anio, $semestre, 1, $carnet) &&
                A partir del primer semestre del 2018, ya no es necesario verificar que este habilitado el estudiante para poder realizar habilitar la opcion
                */ (date("Y-m-d") >= $fecha_habilitar_etrabajador[0] && date("Y-m-d") <= $fecha_habilitar_etrabajador[1] && $extension == 0)
                ) ? '' : 'hidden',
                'mostrar_ext' =>
                (
                (date("Y-m-d H:i") >= $fecha_habilitar_ext[0] && date("Y-m-d H:i") <= $fecha_habilitar_ext[1])
                ) ? '' : 'hidden',
            ));

            $nombre_mes_nacimiento = nombre_mes(substr($estudiante[fecha_nacimiento], 5, 2));

            $template->setVariable(array(
                'carnet' => $carnet,
                'nombre_estudiante' => $estudiante[nombre],
                'dia_nacimiento' => substr($estudiante[fecha_nacimiento], 8, 2),
                'mes_nacimiento' => substr($estudiante[fecha_nacimiento], 5, 2),
                'nombre_mes_nacimiento' => $nombre_mes_nacimiento,
                'anio_nacimiento' => substr($estudiante[fecha_nacimiento], 0, 4),
                'dpi1' => substr($estudiante[dpi], 0, 4),
                'dpi2' => substr($estudiante[dpi], 5, 5),
                'dpi3' => substr($estudiante[dpi], 11, 4)
            ));

            if ($inscripcion_arq['carrera'] == 1 && $inscripcion_arq['extension'] == 0) {
                $template->setVariable(array(
                    'asignacion_semestre_seleccion' => "asignacion_semestre_seleccion"
                ));
            } else {
                $template->setVariable(array(
                    'asignacion_semestre_seleccion' => "asignacion_semestre_seleccion"
                ));
            }

            if ($estudiante[fecha_nacimiento] < $estudiante[calculo_edad]) {
                $template->setVariable(array(
                    'mayor_edad' => " "
                ));
                $template->parse('desbloquear_dpi');
            }

            foreach ($departamentos AS $de) {

                $template->setVariable(array(
                    'departamento' => $de[departamento],
                    'nombre_departamento' => $de[nombre]
                ));
                $template->parse('listado_departamentos');
            }

            $template->setVariable(array(
                'inicio' => "<a href='../menus/contenido.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa  fa-home fa-fw'></i> <font style='font-size: 12px'>Inicio</font></a>"
            ));

            // Notas (Aprobadas y reprobadas)
            $template->setvariable(array(
                'notas' => "<a href='../personal/notas_aprobadas.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa fa-bars fa-fw'></i> <font style='font-size: 12px'>Notas</font></a>"
            ));

            // Mostrar boton de estado de cuenta si existen ordenes pendientes de pago
            // -> Fechas para habilitar el boton de impresion de ordenes de pago.            
            //$fecha_habilita_pagos = "2013-11-18";
            //if (count($ordenes_pago) <> 0 AND $fecha_actual >= $fecha_habilita_pagos) {
            // if ($inscripcion_arq['carrera'] > 3) {
            // Para las carreras de posgrado
            $template->setVariable(array(
                'estado_cuenta' => "<a href='../financiero/estado_cuenta.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa  fa-money fa-fw'></i> <font style='font-size: 12px'>Estado de cuenta</font></a>"
            ));
            /* } else {

              // Para las carreras de licenciatura
              $template->setVariable(array(
              'estado_cuenta' => "<a href='../financiero/estado_cuenta.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa  fa-money fa-fw'></i> <font style='font-size: 12px'>Estado de cuenta</font></a>"
              ));
              } */

            if (count($proyecto_graduacion) <> 0) {
                $template->setVariable(array(
                    'proyecto_graduacion' => "<a href='../proyecto_graduacion/proyecto_graduacion_gestion.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa  fa-graduation-cap fa-fw'></i> <font style='font-size: 12px'>Proyecto de graduación - SPG</font></a>"
                ));
            }

            // Habilitando el módulo de AUCAS
            if ($inscripcion_arq[carrera] == 1 || $carnet == 99999999999999999999999) {
                $template->setVariable(array(
                    'aucas' => "<a href='../aucas/aucas_gestion.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa fa-briefcase fa-fw'></i> <font style='font-size: 12px'>Práctica técnica</font></a>"
                ));
            }

            $template->setVariable(array(
                'certificaciones' => "<a href='../certificaciones/certificacion_solicitud_formulario_nf.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa fa-th-list fa-fw'></i> <font style='font-size: 12px'>Certificaciones de cursos</font></a>"
            ));

            if (count($inscripcion) <> 0 || count($con_cierre) <> 0 || count($pensum82) <> 0) {
                $template->setVariable(array(
                    //'eps' => "<a href='../eps/eps_gestion.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa fa-university fa-fw'></i>EPS</a>"
                    'eps' => "
                    <a href='https://sisep.farusac.edu.gt/inscripcion' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa fa-university fa-fw'></i>EPS - Arquitectura</a>
                    <a href='https://sisep.farusac.edu.gt/' target='_blank'><i class='fa fa-university fa-fw'></i>EPS - Plataforma -SISEP-</a>
                    "
                ));
            }

            $template->setVariable(array(
                'cierre' => "<a href='../cierres/cierre_solicitud_formulario.php' target='contenido' OnClick='ocultar_menu_izquierdo()'><i class='fa fa-check fa-fw'></i>Constancia de cierre</a>"
            ));

            if ($inscripcion_arq['carrera'] == 3 && $encuesta_externa == 0) {

                $template->setVariable(array(
                    'activar_encuesta_externa' => "
                        <script>                                
                                $('#encuesta_externa').modal({ 
                                    backdrop: 'static', 
                                    keyboard: false,
                                    show: true
                                });
                            </script>"
                ));
            }

            

            if (DATE("o-m-d h:m") >= $fecha_activa_asigext && DATE("o-m-d h:m") <= $fecha_desactiva_asigext ) {

                $template->setVariable(array(
                    'label_aisgext' => "success",
                    'estado_asigext' => "Activa",
                    'url_asign_ext' => "../asignacion/asignacion_extemporanea_gestion.php"
                ));
            } else {

                $template->setVariable(array(
                    'label_aisgext' => "danger",
                    'estado_asigext' => "Inactiva",
                    'url_asign_ext' => "../menus/contenido.php"
                ));
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
    errorLogin($mensaje);
}
?>
