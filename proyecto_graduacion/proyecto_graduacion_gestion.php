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
require_once "../config/local.php";

session_start();

verificarActualizarDatos();

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
        $semestre = $_SESSION['semestre'];
        $nombre_semestre = $_SESSION['nombre_semestre'];
        $carrera = inscripcion_estudiante($db, $anio, $semestre, $carnet);
        $fecha_actual = date('Y-m-d');

        // Quitando sesiones temporales
        unset($_SESSION['asesor_externo']);
        unset($_SESSION['padrinos']);

        // Consultar inscripcion del estudiante para poder iniciar y tener permiso sobre el sistema
        $consulta = "SELECT *
			FROM inscripcion i
			WHERE i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet AND i.carrera IN (1,2,3)";
        $inscripcion = & $db->getAll($consulta);
        if ($db->IsError($inscripcion)) {
            $error = true;
            $mensaje = "Hubo un problema durante la consulta de datos de inscripcion del estudiante actual.";
            $url = "../menus/contenido.php";
        } else {

            if (count($inscripcion) == 0) {
                $error = true;
                $mensaje = "Usted no esta inscrito en el ciclo actual, por lo tanto no puede no puede tener acceso a esta herramienta.";
                $url = "../menus/contenido.php";
            } else {
                $carrera2 = -1;

                // Estado en el sistema (proceso actual)

                $_SESSION['carrera'] = $inscripcion[0][carrera];
                if (count($inscripcion) > 1) {
                    $carreras = $inscripcion[0][carrera] . "," . $inscripcion[1][carrera];
                    $consulta = "SELECT *
					FROM proyecto_graduacion p
					LEFT OUTER JOIN proyecto_graduacion_protocolo g
					ON g.numero_tema = p.numero_tema
					WHERE p.carnet = $carnet AND p.carrera in ($carreras)";
                    $inscripcion = $inscripcion[1];
                } else {
                    $inscripcion = $inscripcion[0];
                    $consulta = "SELECT *
					FROM proyecto_graduacion p
					LEFT OUTER JOIN proyecto_graduacion_protocolo g
					ON g.numero_tema = p.numero_tema
					WHERE p.carnet = $carnet AND p.carrera = $inscripcion[carrera]";
                }

                $estado_pg = & $db->getAll($consulta);
                //if ($carnet == 200711091 ) {var_dump($consulta); die;}
                if ($db->isError($estado_pg)) {
                    $error = true;
                    $mensaje = "Hubo un problema durante la consulta del estado del Proyecto de Graduación.";
                    $url = "../menus/contenido.php";
                } else {

                    // Verificacion de examen privado aprobado en el sistema
                    $consulta = "SELECT *
						FROM examen_publico p
						WHERE p.carnet = $carnet AND p.carrera = $inscripcion[carrera]";
                    $publico = & $db->getRow($consulta);
                    if ($db->isError($publico)) {
                        $error = true;
                        $mensaje = "Hubo un problema al verificar el estado de solicitud de impresión.";
                        $url = "../menus/contenido.php";
                    } else {

                        // Verificacion de la parobacion de de examen privado
                        $consulta = "SELECT *
							FROM examen_privado e
							WHERE e.carnet = $carnet AND e.carrera = $inscripcion[carrera] AND e.aprobado = 1";
                        $privado_aprobado = & $db->getRow($consulta);
                        if ($db->isError($privado_aprobado)) {
                            $error = true;
                            $mensaje = "Hubo un problema al obtener el estado de aprobación de examen privado.";
                            $url = "../menus/contenido.php";
                        } else {

                            // Verificación de días para vencimiento 
                            $consulta = "SELECT *
								FROM proyecto_graduacion p
								INNER JOIN carrera c
								ON c.carrera = p.carrera
								WHERE p.estado = 5 AND ADDDATE(p.`fecha_vencimiento`, INTERVAL -30 DAY) <= LEFT(NOW(),10) AND p.carnet = $carnet
									-- AND DATEDIFF(p.`fecha_vencimiento`, p.`fecha_aprobacion_protocolo`) < IF(suspension_por_eps = 1, 460, 250)
									AND p.prorroga < 1
								AND NOT EXISTS (
									SELECT *
									FROM examen_privado e
									WHERE e.carnet = p.carnet AND e.carrera = p.carrera
								) 
								AND NOT EXISTS(
									SELECT *
									FROM carrera_estudiante c1
									WHERE c1.`carnet` = p.`carnet` AND c1.`carrera` = p.`carrera` AND c1.`acta_publico` <> ''
								)";
                            $validacion_prorroga = & $db->getRow($consulta);
                            if ($db->isError($validacion_prorroga)) {
                                $error = true;
                                $mensaje = "Hubo un problema al obtener al realizar el cálculo de los días faltas para vencimiento de proyecto de graduación.";
                                $url = "../menus/contenido.php";
                            }
                        }
                    }
                }
            }
        }
        //}

        if (!$error && !$aviso) {

            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('proyecto_graduacion_gestion.html');

            if (count($estado_pg) <> 0) {

                foreach ($estado_pg AS $es) {

                    SWITCH ($es[estado]) {
                        case 0: {
                                //verificando si está habilitado el procleso de ingreo para proyecto de graduación... ARQUITECTURA
                                if ($inscripcion[carrera] == 1 && !in_array($carnet, $habilitar_para_temas_arq)) {
                                    if (!verificarFechaHabilitada($habilitar_fechas_arq, $fecha_actual)) {
                                        aviso(
                                                "Actualmente no está habilitado el ingreso de temas y protocolos. Las fechas en las que está habilitada la plataforma son:"
                                                . "<br> Verificar el calendario de actividades <br /> REF.1", "../menus/contenido.php"
                                        );
                                        exit;
                                    }
                                }
                                // Creacion iniciar de la solicitud para esperar el proximo proceso
                                $template->setVariable(array(
                                    'mensaje_estado' => "Ha registrado una solicitud para aprobación de tema de proyecto de graduación, será revisada por 
                                la Comisión de Proyecto de Graduación y se le notificará el estado."
                                ));
                                break;
                            }
                        case 1: {
                                //verificando si está habilitado el procleso de ingreo para proyecto de graduación... ARQUITECTURA
                                if ($inscripcion[carrera] == 1 && !in_array($carnet, $habilitar_para_temas_arq)) {
                                    if (!verificarFechaHabilitada($habilitar_fechas_arq, $fecha_actual)) {
                                        aviso(
                                                "Actualmente no está habilitado el ingreso de temas y protocolos. Las fechas en las que está habilitada la plataforma son:"
                                                . "<br> Verificar el calendario de actividades <br />REF.2", "../menus/contenido.php"
                                        );
                                        exit;
                                    }
                                }
                                // Se ha solicitado ya el proyecto de graduación y esta pendiente de aprobacion
                                $template->setVariable(array(
                                    'mensaje_estado' => "El tema propuesto esta en proceso de revisión por la Comisión de Proyecto de Graduación."
                                ));
                                break;
                            }
                        case 2: {
                                //verificando si está habilitado el procleso de ingreo para proyecto de graduación... ARQUITECTURA
                                if ($inscripcion[carrera] == 1 && !in_array($carnet, $habilitar_para_temas_arq) && !in_array($carnet, $habilitar_para_prot_arq)) {
                                    if (!verificarFechaHabilitada($habilitar_fechas_arq, $fecha_actual)) {
                                        aviso(
                                                "Actualmente no está habilitado el ingreso de temas y protocolos. Las fechas en las que está habilitada la plataforma son:"
                                                . "<br> Verificar el calendario de actividades <br />REF.3", "../menus/contenido.php"
                                        );
                                        exit;
                                    }
                                }
                                $template->setVariable(array(
                                    'mensaje_estado' => "Ya se ha verificado por la Comisión de Proyecto de Graduación, y la solicitud ha sido aprobada.",
                                    'btn_estado' => "<input class='btn btn-primary' type='button' value='Solicitud de aprobación de protocolo' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_protocolo_formulario.php', 'contenido')\">"
                                ));
                                break;
                            }
                        case 3: {
                                //verificando si está habilitado el procleso de ingreo para proyecto de graduación... ARQUITECTURA
                                if ($inscripcion[carrera] == 1 && !in_array($carnet, $habilitar_para_temas_arq) && !in_array($carnet, $habilitar_para_prot_arq)) {
                                    if (!verificarFechaHabilitada($habilitar_fechas_arq, $fecha_actual)) {
                                        aviso(
                                                "Actualmente no está habilitado el ingreso de temas y protocolos. Las fechas en las que está habilitada la plataforma son:"
                                                . "<br> Verificar el calendario de actividades <br />REF.4", "../menus/contenido.php"
                                        );
                                        exit;
                                    }
                                }
                                if ($inscripcion['carrera'] == 3) {
                                    $template->setVariable(array(
                                        'mensaje_estado' => "Se ha registrado la solicitud de aprobación de protocolo. Recibirá respuesta en el siguiente mes.<br><br>
                                        "
                                    ));
                                } else {
                                    $template->setVariable(array(
                                        'mensaje_estado' => "Se ha registrado la solicitud de aprobación de protocolo. Recibirá respuesta en el siguiente mes.<br><br>
                                    					<input class='btn btn-primary' type='button' value='Carta para asesores propuestos' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_protocolo_cartasaceptacion.php?numero_tema=$es[numero_tema]', 'contenido')\">"
                                    ));
                                }
                                break;
                            }
                        case 4: {
                                //verificando si está habilitado el procleso de ingreo para proyecto de graduación... ARQUITECTURA
                                if ($inscripcion[carrera] == 1 && !in_array($carnet, $habilitar_para_temas_arq) && !in_array($carnet, $habilitar_para_prot_arq)) {
                                    if (!verificarFechaHabilitada($habilitar_fechas_arq, $fecha_actual)) {
                                        aviso(
                                                "Actualmente no está habilitado el ingreso de temas y protocolos. Las fechas en las que está habilitada la plataforma son:"
                                                . "<br> Verificar el calendario de actividades <br />REF.5", "../menus/contenido.php"
                                        );
                                        exit;
                                    }
                                }
                                // Verificacion de estado de asesores
                                $consulta = "SELECT *
                                                FROM proyecto_graduacion p
                                                INNER JOIN proyecto_graduacion_asesores a
                                                ON a.numero_tema = p.numero_tema
                                                WHERE p.carnet = $carnet AND p.estado = 4 AND a.aprobado = 2";
                                $asesores = & $db->getAll($consulta);
                                if ($db->isError($asesores)) {
                                    $error = true;
                                    $mensaje = "Hubo un problema al consultar el estado de los asesores.";
                                } else {

                                    if (count($asesores) == 0) {
                                        if ($inscripcion['carrera'] == 3) {
                                            $template->setVariable(array(
                                                'mensaje_estado' => "El protocolo esta siento verificado por favor espere a que la Comisión de Proyecto de Graduación dictamine el resultado.<br><br>
											"
                                            ));
                                        } else {
                                            $template->setVariable(array(
                                                'mensaje_estado' => "El protocolo esta siento verificado por favor espere a que la Comisión de Proyecto de Graduación dictamine el resultado.<br><br>
											<input class='btn btn-primary' type='button' value='Carta para asesores propuestos' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_protocolo_cartasaceptacion.php?numero_tema=$es[numero_tema])','contenido')\">
											"
                                            ));
                                        }

                                        break;
                                    } else {
                                        $template->setVariable(array(
                                            'mensaje_estado' => "Existen asesores desaprobados para esta solicitud.",
                                            'btn_estado' => "<input class='btn btn-primary' type='button' value='Cambio de asesores' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_protocolo_asesores.php', 'contenido')\">"
                                        ));
                                        break;
                                    }
                                }
                            }
                        case 5: {
                                if ($es[acuerdo_decanato] == NULL AND $es[estado] <> 5) {
                                    $template->setVariable(array(
                                        'mensaje_estado' => "El protocolo ha sido aprobado por la Comisión de Proyecto de Graduación, debe solicitar la aprobación de los asesores para poder solicitar examen privado."
                                    ));
                                    break;
                                } else {

                                    // Consultar los datos del acta
                                    $consulta = "SELECT *
                                    FROM examen_privado e
                                    WHERE e.carnet = $carnet AND e.carrera = $es[carrera] AND e.aprobado = 1";
                                    $datos_acta = & $db->getRow($consulta);
                                    if ($db->isError($datos_acta)) {
                                        $error = true;
                                        $mensaje = "Hubo un problema al obtener los datos del acta de examen privado.";
                                    } else {

                                        if ($datos_acta <> 0) {

                                            if ($publico == 0) {
                                                $template->setVariable(array(
                                                    'mensaje_estado' => "Examen privado aprobado. *NOTA: Copia del acta de su examen privado puede solicitarse en Secretaría Académica de la Facultad.",
                                                    'examen_publico' => "<input class='btn btn-primary' type='button' value='Examen Público - Solicitud de autorización de impresión' OnClick=\"window.open('../publico/examen_publico_formulario.php', 'contenido')\">"
                                                ));
                                                break;
                                            } else {

                                                switch ($publico[estado]) {
                                                    case 0 : {
                                                            $template->setVariable(array(
                                                                'mensaje_estado' => "Ha solicitado autorización de impresión de su proyecto de graduación, espere a recibir la confirmación. <input class='btn btn-primary' type='button' value='Consultar instructivo' OnClick=\"window.open('../publico/examen_publico_instructivo_impresion.php','contenido')\">
																*NOTA: Copia del acta de su examen privado puede solicitarse en Secretaría Académica de la Facultad.",
                                                                'examen_publico' => "<input class='btn btn-primary' type='button' value='Examen Público - Imprimir Solicitud de autorización de impresión' OnClick=\"window.open('../publico/examen_publico_solicitud_impresion.php', 'contenido')\">"
                                                            ));
                                                            break;
                                                        }
                                                    case 1 : {
                                                            $template->setVariable(array(
                                                                'examen_publico' => "<input class='btn btn-primary' type='button' value='Examen Público - Instructivo' OnClick=\"window.open('../publico/examen_publico_instructivo_publico.php', 'contenido')\">"
                                                            ));
                                                            break;
                                                        }
                                                    case 2 : {

                                                            if ($publico[numero_recibo] <> NULL) {
                                                                $template->setVariable(array(
                                                                    'examen_publico' => "<input class='btn btn-primary' type='button' value='Examen Público - Solicitud de examen público' OnClick=\"window.open('../publico/examen_publico_padrinos_formulario.php', 'contenido')\">
<input class='btn btn-primary' type='button' value='Instructivo Impresión' OnClick=\"window.open('../publico/examen_publico_instructivo_impresion.php', 'contenido')\">
<input class='btn btn-primary' type='button' value='Instructivo Público' OnClick=\"window.open('../publico/examen_publico_instructivo_publico2.php', 'contenido')\">
                                                                    
                                                                    "
                                                                ));
                                                                break;
                                                            } else {
                                                                $template->setVariable(array(
                                                                    'mensaje_estado' => "Debe realizar el pago de examen público, impresión y registro de titulo, previo a solicitar el examen público. <input class='btn btn-primary' type='button' value='Ordenes de pago' OnClick=\"window.open('../financiero/estado_cuenta.php','contenido')\">"
                                                                ));
                                                            }
                                                        }
                                                    case 3 : {
                                                            if ($publico[numero_recibo] <> NULL) {
                                                                $template->setVariable(array(
                                                                    'examen_publico' => "<input class='btn btn-primary' type='button' value='Examen Público - Imprimir solicitud de examen' OnClick=\"window.open('../publico/examen_publico_solicitud_examenpublico.php', 'contenido')\">

<input class='btn btn-primary' type='button' value='Instructivo Impresión' OnClick=\"window.open('../publico/examen_publico_instructivo_impresion.php', 'contenido')\">
<input class='btn btn-primary' type='button' value='Instructivo Público' OnClick=\"window.open('../publico/examen_publico_instructivo_publico2.php', 'contenido')\">
"
                                                                ));
                                                            }
                                                            break;
                                                        }
                                                }
                                            }
                                        } else {

                                            if ($validacion_prorroga <> 0) {

                                                $template->setVariable(array(
                                                    'mensaje_estado' => "Su proyecto de graduación vence el <b>" . fechaEnTextoNumero($es[fecha_vencimiento]) . "</b>, para solicitar prorroga debe completar el formulario y presentar solicitud en la unidad de graduación correspondiente.<br><br>
													<form method='GET' action='../proyecto_graduacion/proyecto_graduacion_carta_prorroga.php'>
													<input type='hidden' value='$validacion_prorroga[numero_tema]' name='numero_solicitud'>
													<input class='form-control' type='text' value='' name='inconveniente' placeholder='Inconveniente por el cual no ha culminado' required><br>
													<select class='form-control-static' name='prorroga_solicitada' required>
													<option selected></option>
													<option value='1 mes'>1 mes</option>
													<option value='2 meses'>2 meses</option>
													<option value='3 meses'>3 meses</option>
													</select><br><br>
													<input class='form-control-static' type='text' value='' name='avance' placeholder='% de avance' pattern='^[0-9]{1,3}' title='Números 0-100' required><br><br>
													<input class='btn btn-primary btn-sm' type='submit' value='Solicitud de prorroga'>
													</form>
													",
                                                    'examen_privado' => "<input class='btn btn-primary' type='button' value='Examen Privado' OnClick=\"window.open('../privado/examen_privado_introduccion.php', 'contenido')\"><input class='btn btn-primary' type='button' value='Carta para asesores propuestos' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_protocolo_cartasaceptacion.php?numero_tema=$es[numero_tema])','contenido')\">"
                                                ));
                                            } else {
                                                if ($inscripcion['carrera'] == 3) {
                                                    $template->setVariable(array(
                                                        'mensaje_estado' => "Su proyecto de graduación vence el <b>" . fechaEnTextoNumero($es[fecha_vencimiento]) . "</b>, debe cumplir el proyecto de graduación antes de su vencimiento.<br /><br /><b>***NOTA:</b> La carta para los asesores propuestos se genera únicamente para los asesores no docentes.",
                                                        'examen_privado' => "<input class='btn btn-primary' type='button' value='Examen Privado' OnClick=\"window.open('../privado/examen_privado_introduccion.php', 'contenido')\">
                                                                        <input class='btn btn-primary ' type='button' value='Carta para asesores propuestos' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_protocolo_cartasaceptacion.php?numero_tema=$es[numero_tema])','contenido')\"><input class='btn btn-primary' type='button' value='Carta para asesores propuestos' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_protocolo_cartasaceptacion.php?numero_tema=$es[numero_tema])','contenido')\">
                                                        "
                                                    ));
                                                } else {
                                                    $template->setVariable(array(
                                                        'mensaje_estado' => "Su proyecto de graduación vence el <b>" . fechaEnTextoNumero($es[fecha_vencimiento]) . "</b>, debe cumplir el proyecto de graduación antes de su vencimiento.",
                                                        'examen_privado' => "<input class='btn btn-primary' type='button' value='Examen Privado' OnClick=\"window.open('../privado/examen_privado_introduccion.php', 'contenido')\"><input class='btn btn-primary' type='button' value='Carta para asesores propuestos' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_protocolo_cartasaceptacion.php?numero_tema=$es[numero_tema])','contenido')\">
                                                        "
                                                    ));
                                                }
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                    }
                    $template->parse('estado_proyecto_graduacion');
                }
            } else {


                if ($privado_aprobado == 0) {
                    if ($inscripcion[carrera] == 1 && !in_array($carnet, $habilitar_para_temas_arq) && !in_array($carnet, $habilitar_para_prot_arq)) {
                        if (!verificarFechaHabilitada($habilitar_fechas_arq, $fecha_actual)) {
                            aviso(
                                    "Actualmente no está habilitado el ingreso de temas y protocolos. Las fechas en las que está habilitada la plataforma son:"
                                    . "<br> Verificar el calendario de actividades <br />REF.6", "../menus/contenido.php"
                            );
                            exit;
                        }
                    }
                    $template->setVariable(array(
                        'mensaje_estado' => "Actualmente no existe ninguna solicitud de aprobación de proyecto de graduación.<br><br>
							<b>Si usted ya aprobo el examen privado, deberá solicitar a Secretaría Académica que sea incorporado al sistema, 
							con esa solicitud se actualizarán los datos y podrá continuar con su proceso de graduación.</b>",
                        'btn_estado' => "<input class='btn btn-primary' type='button' value='Solicitud de Tema' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_solicitud_introduccion.php', 'contenido')\">"
                    ));
                } else {
                    // Verificacion temporal de estudiantes que han aprobado el examen privado de la siguiente manera
                    //  -> Sin haber solicitado tema
                    //  -> Sin acuerdo de decanato almacenado en el sistema
                    if ($publico == 0) {
                        if ($inscripcion[carrera] == 1 && !in_array($carnet, $habilitar_para_temas_arq) && !in_array($carnet, $habilitar_para_prot_arq)) {
                            if (!verificarFechaHabilitada($habilitar_fechas_arq, $fecha_actual)) {
                                aviso(
                                        "Actualmente no está habilitado el ingreso de temas y protocolos. Las fechas en las que está habilitada la plataforma son:"
                                        . "<br> Verificar el calendario de actividades <br />REF.7", "../menus/contenido.php"
                                );
                                exit;
                            }
                        }
                        $template->setVariable(array(
                            'mensaje_estado' => "Actualmente no existe ninguna solicitud de aprobación de proyecto de graduación.<br><br>
															<b>Si usted ya aprobo el examen privado, deberá Si usted ya aprobo el examen privado, deberá solicitar a Secretaría Académica que sea incorporado al sistema, con esa solicitud se actualizarán los datos y podrá continuar con su proceso de graduación.</b>",
                            'btn_estado' => "<input class='btn btn-primary' type='button' value='Solicitud de Tema' OnClick=\"window.open('../proyecto_graduacion/proyecto_graduacion_solicitud_introduccion.php', 'contenido')\">"
                        ));
                    } else {

                        switch ($publico[estado]) {
                            case 0 : {
                                    $template->setVariable(array(
                                        'mensaje_estado' => "Ha solicitado autorización de impresión de su proyecto de graduación, pero aún no tenemos registrado
										el proyecto de graduación de forma digital en nuestro sistema, por favor, dirijase a la Secretaría Académica de la Facultad de 
										Arquitectura con la fotocopia de su examen privado.",
                                    ));
                                    break;
                                }
                            case 1 : {
                                    $template->setVariable(array(
                                        'examen_publico' => "<input class='btn btn-primary' type='button' value='Examen Público - Solicitud de examen público' OnClick=\"window.open('../publico/examen_publico_instructivo_publico.php', 'contenido')\">
<input class='btn btn-primary' type='button' value='Instructivo Impresión' OnClick=\"window.open('../publico/examen_publico_instructivo_impresion.php', 'contenido')\">
<input class='btn btn-primary' type='button' value='Instructivo Público' OnClick=\"window.open('../publico/examen_publico_instructivo_publico2.php', 'contenido')\">

"
                                    ));
                                    break;
                                }
                        }
                    }
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
