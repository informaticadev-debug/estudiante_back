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
require_once "HTML/Template/Sigma.php";

function obtenerUltimaCorreccion($carnet, $carrera, $db) {
    // Consulta de los datos de la solicitud del estudiante
    $consulta = "SELECT *
                    FROM proyecto_graduacion_historial p
                    WHERE p.carnet = $carnet AND p.carrera = $carrera AND p.correcciones IS NOT NULL AND p.etapa = 1
                     AND EXISTS(SELECT * FROM proyecto_graduacion where p.numero_tema = numero_tema)
                    ORDER BY p.fecha DESC
                    LIMIT 1";
    $correccion = & $db->getRow($consulta);
   
    if ($db->isError($correccion)) {
        $error = true;
        $mensaje = "Hubo un problema durante la consulta de los datos de la solicitud seleccionada.";
        $url = $_SERVER['HTTP_REFERER'];
        $tipo_mensaje = 1;
        return false;
    } else {
        return $correccion;
        
    }
}

function obtenerCorreciones($carnet, $carrera, $db) {
    // Consulta de los datos de la solicitud del estudiante
    $consulta = "SELECT *
                    FROM proyecto_graduacion_historial p
                    WHERE p.carnet = $carnet AND p.carrera = $carrera AND p.correcciones IS NOT NULL AND p.etapa = 1
                    ORDER BY p.fecha DESC";
    $correcciones_array = & $db->getAll($consulta);
    if ($db->isError($correcciones_array)) {
        $error = true;
        $mensaje = "Hubo un problema durante la consulta de los datos de la solicitud seleccionada.";
        $url = $_SERVER['HTTP_REFERER'];
        $tipo_mensaje = 1;
    } else {
        for ($i = 0; $i < count($correcciones_array); $i++) {
            //recuperar el departamento
            $contenido = json_decode($correcciones_array[$i]['contenido']);
            $id_depto = $contenido->departamento;
            $id_muni = $contenido->municipio;
            $id_tema_enmarcado = $contenido->tema_enmarcado;
            $consulta = "SELECT d.`nombre` AS nom_depto, m.`nombre` AS nom_muni
                            FROM departamento d
                                    INNER JOIN municipio m ON m.`departamento` = d.`departamento`
                            WHERE d.`departamento` = $id_depto AND m.`municipio` = $id_muni";
            $ubicacion = & $db->getRow($consulta);
            $correcciones_array[$i]['departamento'] = ($db->isError($ubicacion)) ? '' : $ubicacion['nom_depto'];
            $correcciones_array[$i]['municipio'] = ($db->isError($ubicacion)) ? '' : $ubicacion['nom_muni'];
            $consulta = "SELECT *
                            FROM proyecto_graduacion_tema_enmarcado t
                            WHERE t.tema_enmarcado = $id_tema_enmarcado";
            $tema_enmarcador = & $db->getRow($consulta);
            $correcciones_array[$i]['nom_tema_enmarcado'] = ($db->isError($tema_enmarcador)) ? '' : $tema_enmarcador['nombre'];
        }
        return $correcciones_array;
    }
}

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
        $area = inscripcion_estudiante($db, $anio, $semestre, $carnet);
       
        /* seteando variables si existen correcciones */
        $correccion = obtenerUltimaCorreccion($carnet, $area /* asi estaba definida la variable de carrera... */, $db);
        
        if (!empty($correccion)) {
            
            $correccion_info_contenido = json_decode($correccion['contenido']);
            $_SESSION['proyecto_graduacion'] = $correccion_info_contenido->proyecto_graduacion;
            $_SESSION['descripcion'] = $correccion_info_contenido->descripcion;
            $_SESSION['departamento'] = $correccion_info_contenido->departamento;
            $_SESSION['municipio'] = $correccion_info_contenido->municipio;
            $_SESSION['tema_enmarcado'] = $correccion_info_contenido->tema_enmarcado;
            
        }
        
        $primer_nombre = $_SESSION['primer_nombre'];
        $segundo_nombre = $_SESSION['segundo_nombre'];
        $tercer_nombre = $_SESSION['tercer_nombre'];
        $primer_apellido = $_SESSION['primer_apellido'];
        $segundo_apellido = $_SESSION['segundo_apellido'];
        $email_fda = $_SESSION['email_fda'];
        $proyecto_graduacion = $_SESSION['proyecto_graduacion'];
        $departamento = $_SESSION['departamento'];
        $municipio = $_SESSION['municipio'];
        $descripcion = $_SESSION['descripcion'];
        $tema_enmarcado_s = $_SESSION['tema_enmarcado'];

       
        // Datos del estudiante
        $consulta = "SELECT *
        FROM estudiante e
        WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un problema durante la consulta de los datos del estudiante.";
        } else {

            // Listado de departamentos de Guatemala
            $consulta = "SELECT *
            FROM departamento d";
            $listado_departamentos = & $db->getAll($consulta);
            if ($db->isError($departamento)) {
                $error = true;
                $mensaje = "Hubo un problema durante la consulta de los departamentos.";
            } else {

                if (isset($_SESSION['departamento'])) {

                    // Consulta de datos del departamento 
                    $conuslta = "SELECT *
                    FROM departamento d
                    WHERE d.departamento = $departamento";
                    $datos_departamento = & $db->getRow($conuslta);
                    if ($db->isError($datos_departamento)) {
                        $error = true;
                        $mensaje = "Hubo un problema durante la consulta de los datos del departamento selecionado. " . mysql_error();
                    } else {

                        // Consulta de los datos del municipio
                        $consulta = "SELECT *
                        FROM municipio m
                        WHERE m.departamento = $departamento AND m.municipio = $municipio";
                        $datos_municipio = & $db->getRow($consulta);
                        if ($db->isError($datos_municipio)) {
                            $error = true;
                            $mensaje = "Hubo un problema durante la consulta de los datos del municipio seleccionado.";
                        }

                        // Consulta de los datos del municipio
                        $consulta = "SELECT *
                        FROM proyecto_graduacion_tema_enmarcado m
                        WHERE m.tema_enmarcado = $tema_enmarcado_s";
                        $datos_tema_enmarcado = & $db->getRow($consulta);
                        if ($db->isError($datos_tema_enmarcado)) {
                            $error = true;
                            $mensaje = "Hubo un problema durante la consulta de los datos del tema enmarcado seleccionado.";
                        }
                    }
                }

                // Temas enmarcados
                
                if($carnet == 201804464/*Resuelto por no presentar carrera con antiguedad de estudiante para solicitud de proyecto de graduación, selleccion de tema enmarcado*/){
                    $area = 1;
                }
                $consulta = "SELECT *
                FROM proyecto_graduacion_tema_enmarcado t
                WHERE t.area = $area";
                $datos_temas = & $db->getAll($consulta);
                if ($db->isError($datos_temas)) {
                    $error = true;
                    $mensaje = "Hubo un problema durante la consulta de los datos del municipio seleccionado.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    // Modalidades de tema
                    $consulta = "SELECT *
                    FROM proyecto_graduacion_modalidad m
                    WHERE m.area = $area
                    and m.modalidad not in (5)";
                    $datos_modalidades = & $db->getAll($consulta);
                    if ($db->isError($datos_modalidades)) {
                        $error = true;
                        $mensaje = "Hubo un problema al obtener el listado de modalidades de proyecto de graduación.";
                        $url = $_SERVER[HTTP_REFERER];
                    }
                }

                // asignado a EPS
                $consulta = "SELECT *
                                FROM `eps_asignacion` e
                                WHERE e.`carnet` = $carnet AND e.`anio` = $anio AND e.`semestre` = $semestre"
                ;
                $datos_eps = & $db->getAll($consulta);
                if ($db->isError($datos_temas)) {
                    $error = true;
                    $mensaje = "Hubo un problema durante la consulta de la asignacion a EPS.";
                    $url = $_SERVER[HTTP_REFERER];
                }

                // asignado a Investigacion 2
                $consulta = "SELECT *
                                FROM `asignacion` e
                                WHERE e.`carnet` = $carnet AND e.`anio` = $anio AND e.`semestre` = $semestre AND e.codigo = '4.09.9'"
                ;
                $datos_investigacion = & $db->getAll($consulta);
                if ($db->isError($datos_temas)) {
                    $error = true;
                    $mensaje = "Hubo un problema durante la consulta de la asignacion a EPS.";
                    $url = $_SERVER[HTTP_REFERER];
                }
            }
        }

        if (!$error) {

            
            //$template->loadTemplateFile('proyecto_graduacion_solicitud_formulario.html');
           
            if ($area == 1) {
                $template = new HTML_Template_Sigma('../templates');
                $template->loadTemplateFile('proyecto_graduacion_solicitud_formulario.html');
            } else if ($area == 3) {
                $template = new HTML_Template_Sigma('../templates');
                $template->loadTemplateFile('proyecto_graduacion_solicitud_formulario_dg.html');
            }

            //seteando variables de correcciones...
            if ($correccion) {
                $info_correcion = json_decode($correccion['correcciones']);
                $template->setVariable(array(
                    'ocultar_correccion' => '',
                    'correccion_tema' => $info_correcion->proyecto_graduacion,
                    'correccion_localizacion' => $info_correcion->ubicacion,
                    'correccion_descripcion' => $info_correcion->descripcion,
                    'correccion_tema_enmarcado' => $info_correcion->tema_enmarcado,
                ));
            } else {
                $template->setVariable(array(
                    'ocultar_correccion' => 'hidden',
                ));
            }
            
            $template->setVariable(array(
                'primer_nombre' => $primer_nombre,
                'segundo_nombre' => $segundo_nombre,
                'tercer_nombre' => $tercer_nombre,
                'primer_apellido' => $primer_apellido,
                'segundo_apellido' => $segundo_apellido,
                'email_fda' => $email_fda,
                'proyecto_graduacion' => $proyecto_graduacion,
                'departamento_seleccionado' => $datos_departamento[departamento],
                'nombre_departamento_seleccionado' => $datos_departamento[departamento] . " - " . $datos_departamento[nombre],
                'municipio' => $datos_municipio[municipio],
                'nombre_municipio' => $datos_municipio[nombre],
                'descripcion' => $descripcion,
                'tema_enmarcado_seleccionado' => $tema_enmarcado_s,
                'nombre_tema_enmarcado_seleccionado' => $datos_tema_enmarcado['nombre'],
            ));

            foreach ($listado_departamentos AS $de) {
                $template->setVariable(array(
                    'departamento' => $de[departamento],
                    'nombre_departamento' => $de[nombre]
                ));
                $template->parse('listado_departamento');
            }

            foreach ($datos_temas AS $td) {

                $template->setVariable(array(
                    'tema_enmarcado' => $td[tema_enmarcado],
                    'nombre_tema_enmarcado' => $td[nombre],
                    'tema_enmarcado_selected' => ($correccion_info_contenido && $correccion_info_contenido->tema_enmarcado == $td[tema_enmarcado]) ? 'selected' : ''
                ));
                $template->parse('listado_temas');
            }

            foreach ($datos_modalidades AS $mo) {
                /* if ($mo["modalidad"] == 2) {
                  var_dump($datos_investigacion); die;
                  } */
                //verificando la modalidad Cursos de investigacion, requisito es estar asignado a Investigacion 2
                if ($mo["modalidad"] == 2 && count($datos_investigacion) < 1) {
                    continue; //no agregar sino esta asignado
                    //caso de EPS, estar asignado a EPS
                } else if ($mo["modalidad"] == 3 && count($datos_eps) < 1 && false) {
                    continue; //no agregar sino esta asignado
                } else {
                    $template->setVariable(array(
                        'modalidad' => $mo[modalidad],
                        'nombre_modalidad' => $mo[nombre],
                        'modalidad_selected' => ($correccion_info_contenido && $correccion_info_contenido->modalidad == $mo[modalidad]) ? 'selected' : ''
                    ));
                    $template->parse('listado_modalidades');
                }
            }

            //extraccion de correciones
            $datos_correciones = obtenerCorreciones($carnet, $area, $db);
            $i = count($datos_correciones);
            foreach ($datos_correciones as $correccion) {
                $contenido = json_decode($correccion['contenido']);
                $desc_correcciones = json_decode($correccion['correcciones']);
                //var_dump($desc_correcciones); die;
                $template->setVariable(array(
                    'historial_titulo' => "Corrección " . $i--,
                    'historial_fecha_creacion' => $correccion['fecha'],
                    'historial_fecha_correccion' => $correccion['fecha_correccion'],
                    'historial_tema' => $contenido->proyecto_graduacion,
                    'historial_correccion_tema' => $desc_correcciones->proyecto_graduacion,
                    'historial_ubicacion' => $correccion['departamento'] . ' - ' . $correccion['municipio'],
                    'historial_correccion_ubicacion' => $desc_correcciones->ubicacion,
                    'historial_descripcion' => $contenido->descripcion,
                    'historial_correccion_descripcion' => $desc_correcciones->descripcion,
                    'historial_tema_enmarcado' => $correccion['nom_tema_enmarcado'],
                    'historial_correccion_tema_enmarcado' => $desc_correcciones->tema_enmarcado,
                ));
                $template->parse('bloque_historial');
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
