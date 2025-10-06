<?php

/*
  Document   : encuesta_alimentarencuesta_formulario.php
  Created on : 18-mar-2015, 09:08
  Author     : Angel Caal
  Description:
  -> Alimentación manual de encuesta
 */

require_once 'DB.php';
require_once 'HTML/Template/Sigma.php';
require_once '../misc/funciones.php';
session_start();

if (isset($_SESSION['usuario'])) {

    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];

    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::Connect($dsn);
    if (DB::isError($db)) {
        $mensaje = "La Plataforma esta temporalmente fuera de línea, por favor intente en un momento. Si el problema persiste comuníquese con el Programador (Angel Caal | 3070 1746)";
        errorLoginInicio($mensaje);
    } else {

        $rol = $_SESSION['rol'];

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;
        $aviso = false;

        $encuesta = $_GET['encuesta'];

        // Datos de la encuesta
        $consulta = "SELECT *
		FROM encuestas e
		WHERE MD5(e.encuesta) = '$encuesta'";
        $datos_encuesta = & $db->getRow($consulta);
        if ($db->isError($datos_encuesta)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener los datos de la encuesta.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            $_SESSION['encuesta'] = $datos_encuesta[encuesta];

            // Preguntas de la encuesta
            $consulta = "SELECT *
			FROM encuestas_preguntas p
			WHERE MD5(p.encuesta) = '$encuesta'";
            $preguntas = & $db->getAll($consulta);
            if ($db->isError($preguntas)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener el listado de preguntas de la encuesta actual.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                foreach ($preguntas AS $pr) {

                    $consulta = "SELECT *
					FROM encuestas_preguntas_detalle d
					WHERE MD5(d.encuesta) = '$encuesta' AND d.pregunta = $pr[pregunta]";
                    $respuestas = & $db->getAll($consulta);
                    if ($db->isError($respuestas)) {
                        $error = true;
                        $mensaje = "Hubo un problema al obtener el listado de respuestas para las preguntas.";
                        $url = $_SERVER[HTTP_REFERER];
                    } else {

                        foreach ($respuestas AS $re) {

                            if ($re[pregunta] == $pr[pregunta]) {

                                $data_respuestas[] = array(
                                    'pregunta' => $pr[pregunta],
                                    'respuesta' => $re[respuesta],
                                    'nombre_respuesta' => $re[nombre]
                                );
                            }
                        }
                    }
                }
            }
        }

        if (!$error && !$aviso) {

            $template = new HTML_Template_Sigma("../templates");
            $template->LoadTemplateFile("encuesta_alimentarencuesta_formulario.html");

            $template->setVariable(array(
                'encuesta' => $encuesta,
                'nombre' => $datos_encuesta[nombre],
                'descripcion' => $datos_encuesta[descripcion],
                'instrucciones' => $datos_encuesta[instrucciones]
            ));

            foreach ($preguntas AS $pe) {

                if ($pe[tipo_elemento] == 7 || $pe[tipo_elemento] == 4) {
                    $tamano = "12";
                    $tamano2 = "12";
                } else {
                    $tamano = "4";
                    $tamano2 = "8";
                }

                $template->setVariable(array(
                    'pregunta' => $pe[pregunta],
                    'columna' => $tamano,
                    'columna2' => $tamano2
                ));

                if ($pe[tipo_elemento] == 7) {

                    $template->setVariable(array(
                        'nombre_pregunta' => "<b><font size='3'>$pe[nombre]</font></b>"
                    ));
                } else {

                    $template->setVariable(array(
                        'nombre_pregunta' => $pe[nombre]
                    ));
                }

                if ($pe[tipo_elemento] == 1) {

                    $template->setVariable(array(
                        'apertura_tipo_elemento' => "<select class='form-control' name='respuestas[$pe[pregunta]][]'>
                            <option selected></option>"
                    ));
                }

                if (!empty($data_respuestas)) {

                    foreach ($data_respuestas AS $re) {

                        if ($re[pregunta] == $pe[pregunta]) {

                            if ($pe[tipo_elemento] == 1) {
                                $template->setVariable(array(
                                    'opcion_elemento' => "<option value='$re[respuesta]'>$re[nombre_respuesta]</option>"
                                ));
                            } else if ($pe[tipo_elemento] == 2) {
                                $template->setVariable(array(
                                    'opcion_elemento' => "<div class='col-lg-4'><label class='checkbox-inline'>
                                    <input type='checkbox' value='$re[respuesta]' name='respuestas[$pe[pregunta]][]'>$re[nombre_respuesta]
                                </label></div>"
                                ));
                            } else if ($pe[tipo_elemento] == 3) {
                                $template->setVariable(array(
                                    'opcion_elemento' => "<div class='col-lg-4'><label class='radio-inline'>
                                    <input type='radio' value='$re[respuesta]' name='respuestas[$pe[pregunta]][]'>$re[nombre_respuesta]
                                </label></div>"
                                ));
                            } else if ($pe[tipo_elemento] == 4) {
                                $template->setVariable(array(
                                    'opcion_elemento' => "<div class='col-lg-4'>
                                    <input class='form-control-static' size='1' type='text' value='' OnKeyUp='comprobar_campo_numerico(this), comprobar_peso(this, $pe[peso])' name='respuestas[$pe[pregunta]][]'> $re[nombre_respuesta]
                                </div>"
                                ));
                            }
                            $template->parse("listado_respuestas");
                        }
                    }

                    if ($pe[tipo_elemento] == 5) {
                        $template->setVariable(array(
                            'opcion_elemento' => "<div class='col-lg-12'>
                                    <input class='form-control' type='text' value='' name='respuestas[$pe[pregunta]][]'>
                                </div>"
                        ));
                    } else if ($pe[tipo_elemento] == 6) {
                        $template->setVariable(array(
                            'opcion_elemento' => "<div class='col-lg-12'>
                                    <textarea class='form-control' rows='5' name='respuestas[$pe[pregunta]][]'></textarea>
                                </div>"
                        ));
                    }
                }
                $template->parse("listado_preguntas");
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
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    errorLoginInicio($mensaje);
}
?>