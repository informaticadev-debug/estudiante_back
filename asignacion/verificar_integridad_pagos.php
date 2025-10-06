<?php

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../lib/nusoap.php';
require_once '../misc/xml2array.php';

function crearXMLPeticionSIIF($estudiante, $unidad, $extension, $carrera, $nombre, $montoTotal, $array_detalle) {
    $orden_pago = "";
    $datos_estudiante = "<CARNET>" . $estudiante . "</CARNET>" .
            "<UNIDAD>" . $unidad . "</UNIDAD>" .
            "<EXTENSION>" . str_pad($extension, 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
            "<CARRERA>" . str_pad($carrera, 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
            "<NOMBRE>" . $nombre . "</NOMBRE>" .
            "<MONTO>" . $montoTotal . "</MONTO>";

    $detalle_orden_pago = "";

    foreach ($array_detalle as $detalle) {
        $detalle_orden_pago .= "<DETALLE_ORDEN_PAGO>" .
                "<ANIO_TEMPORADA>" . $detalle['anio'] . "</ANIO_TEMPORADA>" .
                "<ID_RUBRO>" . $detalle['id_rubro'] . "</ID_RUBRO>" .
                "<ID_VARIANTE_RUBRO>" . $detalle['id_variante_rubro'] . "</ID_VARIANTE_RUBRO>" .
                "<TIPO_CURSO>" . $detalle['tipo_curso'] . "</TIPO_CURSO>" .
                "<CURSO>" . $detalle['curso'] . "</CURSO>" .
                "<SECCION>" . $detalle['seccion'] . "</SECCION>" .
                "<SUBTOTAL>" . $detalle['subtotal'] . "</SUBTOTAL>" .
                "</DETALLE_ORDEN_PAGO>";
    }

    return "<GENERAR_ORDEN>" . $datos_estudiante . $detalle_orden_pago . "</GENERAR_ORDEN>";
}

function crearBoletasCruzadas() {

    session_start();
    require_once '../config/local.php';

    $user = $conf_db_user;
    $pass = $conf_db_passwd;
    $host = $conf_db_host;
    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    if (DB::isError($db)) {
        echo "En sistema se encuentra fuera de linea temporalmente, disculpe las molestias.";
        die;
    }

    $db->setFetchMode(DB_FETCHMODE_ASSOC);

    $consulta_inicial = "
            SELECT a.*
            FROM asignacion a
            WHERE a.`anio` = 2023 AND a.`semestre` = 1 AND a.`evaluacion` = 2 
                AND a.orden_pago IS NULL
            ORDER BY a.`codigo`, a.`seccion`
        ";



    $asignaciones_verificar = & $db->getAll($consulta_inicial);
    if ($db->isError($asignaciones_verificar)) {
        echo "Hubo un error al verificar los cursos que tiene asignados.";
        die;
    }

    foreach ($asignaciones_verificar as $asignacion_verificar) {

        $carnet = $asignacion_verificar['carnet'];
        $extension = $asignacion_verificar['extension'];
        $anio = $asignacion_verificar['anio'];
        $semestre = $asignacion_verificar['semestre'];
        $evaluacion = $asignacion_verificar['evaluacion'];
        $pensum = $asignacion_verificar['pensum'];
        $codigo = $asignacion_verificar['codigo'];
        $seccion = $asignacion_verificar['seccion'];

        $db->autoCommit(false);

        // Consulta de los datos del estudiante 
        $consulta = "SELECT e.carnet, e.extension AS codigo_extesion, p.carrera, e.nombre		
		FROM estudiante e
		INNER JOIN pensum p
		ON p.pensum = $pensum		
		WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al determinar los datos del estudiante actual.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Consulta de los datos de la Asignatura actual.
            $consulta = "SELECT c.nombre
			FROM curso c
			WHERE c.codigo = '$codigo' AND c.pensum = '$pensum'";
            $asignatura = & $db->getRow($consulta);
            if ($db->isError($asignatura)) {
                $error = true;
                $mensaje = "Error al consultar los datos de la Asignatura.";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

        $costo_curso = costo_curso($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion);
        $costo_inscripcion = costo_inscripcion($db, $extension, $anio, $semestre, $evaluacion, $carnet);
        $costo_total = $costo_curso + $costo_inscripcion;

        // Rubro de pago: 2 = Interciclos Junio.  3 = Interciclos Diciembre
        if ($semestre == 1) {
            $id_rubro = 2;
        } else {
            $id_rubro = 3;
        }

        // Generar numero de transaccion para el estudiante
        $errorEnTransaccion = false;


        $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
        $correlativo_trans = & $db->getRow($consulta);
        if ($db->isError($correlativo_trans)) {
            $error = true;
            $mensaje = "Hubo un error al obtener el No. de Transaccion.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            $errorIngresoCursos = ingresarTransaccionCursos($db, $user, 02, $extension, $estudiante[carrera], $carnet, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $costo_curso, 2, $correlativo_trans['no_transaccion'], $id_rubro, 2);

            if (!$errorIngresoCursos) {

                if ($costo_inscripcion <> 0) {

                    $errorIngresoInscripcion = ingresarTransaccionInscripcion($db, $user, 02, $extension, $estudiante[carrera], $carnet, $anio, $semestre, $evaluacion, 1, $correlativo_trans['no_transaccion'], $id_rubro, 1, $costo_inscripcion);

                    if (!$errorIngresoInscripcion) {

                        $db->commit();
                    } else {

                        $error = true;
                        $db->rollback();
                        $mensaje = "Error en la Transaccion del pago de Inscripción de Interciclos.";
                        $url = $_SERVER[HTTP_REFERER];
                    }
                } else {
                    $db->commit();
                }
            } else {
                $error = true;
                $db->rollback();
                $mensaje = "Error en la Transaccion del pago de Asignaturas de Interciclos.";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

        if (!$error && !$aviso) {

            if ($costo_curso <> 0) {

                // --------------------------------------------------	-------------------------------
                // XML COMO CADENA DE TEXTO (STRING)
                // ---------------------------------------------------------------------------------
                $array_detalle = array();

                $id_variante_rubro = 2; // Rubro de Escuela de Vacaciones (Interciclos) -> Pago de curso

                $array_detalle[] = array(
                    'anio' => $anio,
                    'id_rubro' => $id_rubro,
                    'id_variante_rubro' => 2,
                    'tipo_curso' => 'CURSO',
                    'curso' => $codigo,
                    'seccion' => $seccion,
                    'subtotal' => $costo_curso
                );

                //Generando XML de detalle de pago inscripcion (una inscripcion por el INTERCICLOS)
                if ($costo_inscripcion <> 0) {
                    $array_detalle[] = array(
                        'anio' => $anio,
                        'id_rubro' => $id_rubro,
                        'id_variante_rubro' => 1,
                        'tipo_curso' => '',
                        'curso' => '',
                        'seccion' => '',
                        'subtotal' => $costo_inscripcion
                    );
                }

                // Generando el XML final de la orden de pago
                $orden_pago = crearXMLPeticionSIIF($estudiante['carnet'], '02', $estudiante['codigo_extension'], $estudiante['carrera'], $estudiante['nombre'], $costo_total, $array_detalle);

                //var_dump($orden_pago); die;

                $wsdl = "http://arquitectura.farusac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
                $client = new nusoap_client($wsdl, 'wsdl');
                $err = $client->getError();
                if ($err) {

                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 10, "ERROR AL CREAR EL OBJETO CLIENTE DEL WEB SERVICE");
                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }
                    $mensaje = "Hubo un error en la ejecución del Proceso Orden de Pago";
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                }

                // De Procesamiento de Datos
                $param = array(
                    'pxml' => $orden_pago
                );

                $res = $client->call('generarOrdenPago', $param);  // De Procesamiento de Datos
                // ¿ocurrio error al llamar al web service?
                if ($client->fault) { // si
                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 11, "FALLO EN LA LLAMADA AL WEB SERVICE");
                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }

                    $mensaje = "Error en la creacion del servicio cliente en la Orden de Pago";
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                } else { // no
                    $error = $client->getError();
                    if ($error) {

                        // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                        $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 12, "ERROR EN LA LLAMADA AL PROCESO DE ORDEN DE PAGO");
                        if (!$error_bitacora_orden) {

                            $db->commit();
                        } else {

                            $db->rollback();
                        }

                        $mensaje = "Error en la llamada del Proceso Orden de Pago.";
                        $url = $_SERVER[HTTP_REFERER];
                        error($mensaje, $url);
                    }
                }

                $datos_respuesta = xml2array(utf8_encode($res['result'])); // Si envian el XML con: encoding="utf-8" ó sin encoding (DE PROCESAMIENTO DE DATOS)
                //print_r( $datos_respuesta);
                // Analizando la respuesta del XML
                if ($datos_respuesta['RESPUESTA']['CODIGO_RESP'] == 1) {

                    $anio_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 0, 4);
                    $mes_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 4, 2);
                    $dia_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 6, 2);

                    // Respuesta correcta. Actualizar el estado de la transaccion como correcta y actualizar el No. de Orden de Pago y la llave de seguridad
                    //$_SESSION['orden_pago'] = $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'];
                    $error_bitacora_orden = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                    $error_registro_nueva_orden_pago = nuevaOrdenPago($db, $user, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], 2, 02, $extension, $estudiante[carrera], $carnet, $id_rubro, $datos_respuesta['RESPUESTA']['CHECKSUM'], $costo_total, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                    $error_registro_asignacion = /* registrarAsignacion($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO']) */ false;

                    if (!$error_bitacora_orden && !$error_registro_nueva_orden_pago) {

                        if (!$error_registro_asignacion) {

                            $orden_pago_id = $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'];

                            //actualizando orden de pago...
                            $query_actualizacion = "
                                    UPDATE asignacion SET orden_pago = $orden_pago_id WHERE carnet = $carnet and anio = $anio and semestre = $semestre and extension = $extension and evaluacion = $evaluacion and codigo = '$codigo' and seccion = '$seccion'
                                ";

                            $resultado = & $db->query($query_actualizacion);
                            if ($db->isError($resultado)) {
                                echo 'Error al actualizar la orden.';
                                var_dump($query_actualizacion);
                                die;
                            } else {
                                echo 'Actualizada: ' . $orden_pago_id . ';;;';
                            }

                            $db->commit();
                        } else {

                            $db->rollback();
                            $mensaje = "El proceso de Generacion de Orden de Pago fue exitoso pero hubo un error en el registro de la Asignacion.";
                            $url = $_SERVER[HTTP_REFERER];
                            error($mensaje, $url);
                        }
                    } else {

                        $db->rollback();
                        $mensaje = "La Orden de Pago fue Generada por el Sistema Bancario pero se produjo un error en el Registro de Dicha orden <br> Si el error se sigue presentando, acuda a la Direcci&oacute;n de Interciclos de la Facultad de Arquitectura";
                        $url = $_SERVER[HTTP_REFERER];
                        error($mensaje, $url);
                    }
                } else {

                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 13, $datos_respuesta['RESPUESTA']['DESCRIPCION']);

                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }

                    $mensaje = "La Orden de Pago no fue Generada por el Sistema Bancario. Si el error se sigue presentando, acuda a la Direcci&oacute;n de Interciclos de la Facultad de Arquitectura.<br><br>" . "Descripci&oacute;n del error: " . $datos_respuesta['RESPUESTA']['DESCRIPCION'];
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                }

                // Respuesta de error para unidades fuera de Extension Central. SIIF
            }
        }
    }
}

function crearBoletasInscripcion($anio, $semestre, $evaluacion) {

    session_start();
    require '../config/local.php';

    $user = $conf_db_user;
    $pass = $conf_db_passwd;
    $host = $conf_db_host;
    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";

    $db = DB::connect($dsn);
    if (DB::isError($db)) {
        echo "En sistema se encuentra fuera de linea temporalmente, disculpe las molestias EI1.";
        die;
    }

    $db->setFetchMode(DB_FETCHMODE_ASSOC);

    $consulta_inicial = "
            SELECT a.*
			FROM asignacion a
				INNER JOIN seccion s ON a.`extension` = s.`extension` AND a.`anio` = s.`anio` AND s.`semestre` = a.`semestre` AND s.`evaluacion` = a.`evaluacion` AND s.`pensum` = a.`pensum` AND s.`codigo` = a.`codigo` AND s.`seccion` = a.seccion
			WHERE a.`anio` = $anio AND a.`semestre` = $semestre AND a.`evaluacion` = $evaluacion  AND a.extension = 0
				AND a.`carnet` NOT IN
					(
						SELECT carnet
						FROM orden_pago o
						WHERE o.`carnet` = a.`carnet` AND o.`monto_total` = 20 AND o.`anio` = a.`anio` AND o.`semestre` = a.`semestre` AND o.`evaluacion` = a.`evaluacion`
					) AND s.status = 'A'
			GROUP BY a.carnet
        ";
    $asignaciones_verificar = & $db->getAll($consulta_inicial);
    if ($db->isError($asignaciones_verificar)) {
        echo "Hubo un error al verificar los cursos que tiene asignados.";
        die;
    }

    foreach ($asignaciones_verificar as $asignacion_verificar) {

        $carnet = $asignacion_verificar['carnet'];
        $extension = $asignacion_verificar['extension'];
        $anio = $asignacion_verificar['anio'];
        $semestre = $asignacion_verificar['semestre'];
        $evaluacion = $asignacion_verificar['evaluacion'];
        $pensum = $asignacion_verificar['pensum'];

        $db->autoCommit(false);

        // Consulta de los datos del estudiante 
        $consulta = "SELECT e.carnet, e.extension AS codigo_extesion, p.carrera, e.nombre		
		FROM estudiante e
		INNER JOIN pensum p
		ON p.pensum = $pensum		
		WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al determinar los datos del estudiante actual.";
            $url = $_SERVER[HTTP_REFERER];
        }

        $costo_inscripcion = costo_inscripcion($db, $extension, $anio, $semestre, $evaluacion, $carnet);
        $costo_total = 0 + $costo_inscripcion;

        // Rubro de pago: 2 = Interciclos Junio.  3 = Interciclos Diciembre
        if ($semestre == 1) {
            $id_rubro = 2;
        } else {
            $id_rubro = 3;
        }

        // Generar numero de transaccion para el estudiante
        $errorEnTransaccion = false;


        $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
        $correlativo_trans = & $db->getRow($consulta);
        if ($db->isError($correlativo_trans)) {
            $error = true;
            $mensaje = "Hubo un error al obtener el No. de Transaccion.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            $errorIngresoCursos = false;

            if (!$errorIngresoCursos) {

                if ($costo_inscripcion <> 0) {

                    $errorIngresoInscripcion = ingresarTransaccionInscripcion($db, $user, 02, $extension, $estudiante[carrera], $carnet, $anio, $semestre, $evaluacion, 1, $correlativo_trans['no_transaccion'], $id_rubro, 1, $costo_inscripcion);

                    if (!$errorIngresoInscripcion) {

                        $db->commit();
                    } else {

                        $error = true;
                        $db->rollback();
                        $mensaje = "Error en la Transaccion del pago de Inscripción de Interciclos.";
                        $url = $_SERVER[HTTP_REFERER];
                    }
                } else {
                    $db->commit();
                }
            } else {
                $error = true;
                $db->rollback();
                $mensaje = "Error en la Transaccion del pago de Asignaturas de Interciclos.";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

        if (!$error && !$aviso) {
// 9317930
            if ($costo_inscripcion <> 0) {

                // --------------------------------------------------	-------------------------------
                // XML COMO CADENA DE TEXTO (STRING)
                // ---------------------------------------------------------------------------------
                $array_detalle = array();

                $id_variante_rubro = 2; // Rubro de Escuela de Vacaciones (Interciclos) -> Pago de curso
                //Generando XML de detalle de pago inscripcion (una inscripcion por el INTERCICLOS)
                if ($costo_inscripcion <> 0) {
                    $array_detalle[] = array(
                        'anio' => $anio,
                        'id_rubro' => $id_rubro,
                        'id_variante_rubro' => 1,
                        'tipo_curso' => '',
                        'curso' => '',
                        'seccion' => '',
                        'subtotal' => $costo_inscripcion
                    );
                }

                // Generando el XML final de la orden de pago
                $orden_pago = crearXMLPeticionSIIF($estudiante['carnet'], '02', $estudiante['codigo_extension'], $estudiante['carrera'], $estudiante['nombre'], $costo_total, $array_detalle);

                //var_dump($orden_pago); die;

                $wsdl = "http://arquitectura.farusac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
                $client = new nusoap_client($wsdl, 'wsdl');
                $err = $client->getError();
                if ($err) {

                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 10, "ERROR AL CREAR EL OBJETO CLIENTE DEL WEB SERVICE");
                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }
                    $mensaje = "Hubo un error en la ejecución del Proceso Orden de Pago";
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                }

                // De Procesamiento de Datos
                $param = array(
                    'pxml' => $orden_pago
                );

                $res = $client->call('generarOrdenPago', $param);  // De Procesamiento de Datos
                // ¿ocurrio error al llamar al web service?
                if ($client->fault) { // si
                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 11, "FALLO EN LA LLAMADA AL WEB SERVICE");
                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }

                    $mensaje = "Error en la creacion del servicio cliente en la Orden de Pago";
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                } else { // no
                    $error = $client->getError();
                    if ($error) {

                        // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                        $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 12, "ERROR EN LA LLAMADA AL PROCESO DE ORDEN DE PAGO");
                        if (!$error_bitacora_orden) {

                            $db->commit();
                        } else {

                            $db->rollback();
                        }

                        $mensaje = "Error en la llamada del Proceso Orden de Pago.";
                        $url = $_SERVER[HTTP_REFERER];
                        error($mensaje, $url);
                    }
                }

                $datos_respuesta = xml2array(utf8_encode($res['result'])); // Si envian el XML con: encoding="utf-8" ó sin encoding (DE PROCESAMIENTO DE DATOS)
                //print_r( $datos_respuesta);
                // Analizando la respuesta del XML
                if ($datos_respuesta['RESPUESTA']['CODIGO_RESP'] == 1) {

                    $anio_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 0, 4);
                    $mes_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 4, 2);
                    $dia_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 6, 2);

                    // Respuesta correcta. Actualizar el estado de la transaccion como correcta y actualizar el No. de Orden de Pago y la llave de seguridad
                    //$_SESSION['orden_pago'] = $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'];
                    $error_bitacora_orden = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                    $error_registro_nueva_orden_pago = nuevaOrdenPago($db, $user, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], 2, 02, $extension, $estudiante[carrera], $carnet, $id_rubro, $datos_respuesta['RESPUESTA']['CHECKSUM'], $costo_total, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                    $error_registro_asignacion = /* registrarAsignacion($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO']) */ false;

                    if (!$error_bitacora_orden && !$error_registro_nueva_orden_pago) {

                        if (!$error_registro_asignacion) {

                            $orden_pago_id = $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'];

                            echo 'Actualizada: ' . $orden_pago_id . ';;;';

                            $db->commit();
                        } else {

                            $db->rollback();
                            $mensaje = "El proceso de Generacion de Orden de Pago fue exitoso pero hubo un error en el registro de la Asignacion.";
                            $url = $_SERVER[HTTP_REFERER];
                            error($mensaje, $url);
                        }
                    } else {

                        $db->rollback();
                        $mensaje = "La Orden de Pago fue Generada por el Sistema Bancario pero se produjo un error en el Registro de Dicha orden <br> Si el error se sigue presentando, acuda a la Direcci&oacute;n de Interciclos de la Facultad de Arquitectura";
                        $url = $_SERVER[HTTP_REFERER];
                        error($mensaje, $url);
                    }
                } else {

                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 13, $datos_respuesta['RESPUESTA']['DESCRIPCION']);

                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }

                    $mensaje = "La Orden de Pago no fue Generada por el Sistema Bancario. Si el error se sigue presentando, acuda a la Direcci&oacute;n de Interciclos de la Facultad de Arquitectura.<br><br>" . "Descripci&oacute;n del error: " . $datos_respuesta['RESPUESTA']['DESCRIPCION'];
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                }

                // Respuesta de error para unidades fuera de Extension Central. SIIF
            }
        }
    }
}

function crearBoletasPago($anio, $semestre, $evaluacion) {

    session_start();

    require '../config/local.php';

    $user = $conf_db_user;
    $pass = $conf_db_passwd;
    $host = $conf_db_host;

    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    if (DB::isError($db)) {
        echo "En sistema se encuentra fuera de linea temporalmente, disculpe las molestias EB1.";
        die;
    }

    $db->setFetchMode(DB_FETCHMODE_ASSOC);
    if ($evaluacion == 2) {
        $query_actualizacion = "UPDATE asignacion a SET preasignacion = 1, numero_recibo = NULL
                            WHERE a.`anio` = $anio AND a.`semestre` = $semestre AND a.`evaluacion` = $evaluacion AND a.pensum IN (5,20) AND a.orden_pago IS NULL AND (a.numero_recibo IS NULL OR a.numero_recibo < 0) AND extension = 0";
        $resultado = $db->query($query_actualizacion);
    }


    $consulta_inicial = "
            SELECT a.*
            FROM asignacion a
            	INNER JOIN seccion s ON a.`extension` = s.`extension` AND a.`anio` = s.`anio` AND s.`semestre` = a.`semestre` AND s.`evaluacion` = a.`evaluacion` AND s.`pensum` = a.`pensum` AND s.`codigo` = a.`codigo` AND s.`seccion` = a.seccion
            WHERE a.`anio` = $anio AND a.`semestre` = $semestre AND a.`evaluacion` = $evaluacion AND a.preasignacion = 1 AND a.pensum IN (5,20) AND a.extension = 0
                AND a.orden_pago IS NULL AND s.status = 'A' AND s.costo > 0
            ORDER BY a.`codigo`, a.`seccion`
        ";
    $asignaciones_verificar = & $db->getAll($consulta_inicial);
    //var_dump($asignaciones_verificar); die;
    if ($db->isError($asignaciones_verificar)) {
        echo "Hubo un error al verificar los cursos que tiene asignados.";
        die;
    }

    foreach ($asignaciones_verificar as $asignacion_verificar) {

        $carnet = $asignacion_verificar['carnet'];
        $extension = $asignacion_verificar['extension'];
        $anio = $asignacion_verificar['anio'];
        $semestre = $asignacion_verificar['semestre'];
        $evaluacion = $asignacion_verificar['evaluacion'];
        $pensum = $asignacion_verificar['pensum'];
        $codigo = $asignacion_verificar['codigo'];
        $seccion = $asignacion_verificar['seccion'];

        $db->autoCommit(false);

        // Consulta de los datos del estudiante 
        $consulta = "SELECT e.carnet, e.extension AS codigo_extesion, p.carrera, e.nombre		
		FROM estudiante e
		INNER JOIN pensum p
		ON p.pensum = $pensum		
		WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al determinar los datos del estudiante actual.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Consulta de los datos de la Asignatura actual.
            $consulta = "SELECT c.nombre
			FROM curso c
			WHERE c.codigo = '$codigo' AND c.pensum = '$pensum'";
            $asignatura = & $db->getRow($consulta);
            if ($db->isError($asignatura)) {
                $error = true;
                $mensaje = "Error al consultar los datos de la Asignatura.";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

	$costo_curso = costo_curso($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion);

	if ($costo_curso < 5) continue;

        $costo_inscripcion = 0; //costo_inscripcion($db, $extension, $anio, $semestre, $evaluacion, $carnet);
        $costo_total = $costo_curso + $costo_inscripcion;

        // Rubro de pago: 2 = Interciclos Junio.  3 = Interciclos Diciembre
        // Rubro de pago lab: 63 y variante 1
        if ($evaluacion == 2 && $semestre == 1) {
            $id_rubro = 2;
            $id_variante_rubro = 2;
        } else if ($evaluacion == 2 && $semestre == 2) {
            $id_rubro = 3;
            $id_variante_rubro = 2;
        } else if ($evaluacion == 1 && ($semestre == 1 || $semestre == 2)) {
            $id_rubro = 63;
            $id_variante_rubro = 1;
        } else {
            echo "CICLO INVALIDO";
            die;
        }
        
        // Generar numero de transaccion para el estudiante
        $errorEnTransaccion = false;

        $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
        $correlativo_trans = & $db->getRow($consulta);
        if ($db->isError($correlativo_trans)) {
            $error = true;
            $mensaje = "Hubo un error al obtener el No. de Transaccion.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            $errorIngresoCursos = ingresarTransaccionCursos($db, $user, 02, $extension, $estudiante[carrera], $carnet, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $costo_curso, 2, $correlativo_trans['no_transaccion'], $id_rubro, $id_variante_rubro);

            if (!$errorIngresoCursos) {
                
            } else {
                $error = true;
                $db->rollback();
                $mensaje = "Error en la Transaccion del pago de Asignaturas de Interciclos.";
                $url = $_SERVER[HTTP_REFERER];
            }
        }

        if (!$error && !$aviso) {

            if ($costo_curso <> 0) {

                // --------------------------------------------------	-------------------------------
                // XML COMO CADENA DE TEXTO (STRING)
                // ---------------------------------------------------------------------------------
                $array_detalle = array();

                $array_detalle[] = array(
                    'anio' => $anio,
                    'id_rubro' => $id_rubro,
                    'id_variante_rubro' => $id_variante_rubro,
                    'tipo_curso' => 'CURSO',
                    'curso' => $codigo,
                    'seccion' => $seccion,
                    'subtotal' => $costo_curso
                );

                // Generando el XML final de la orden de pago
                $orden_pago = crearXMLPeticionSIIF($estudiante['carnet'], '02', $estudiante['codigo_extension'], $estudiante['carrera'], $estudiante['nombre'], $costo_total, $array_detalle);

                //var_dump($orden_pago); die;

                $wsdl = "http://arquitectura.farusac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
                $client = new nusoap_client($wsdl, 'wsdl');
                $err = $client->getError();
                if ($err) {

                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 10, "ERROR AL CREAR EL OBJETO CLIENTE DEL WEB SERVICE");
                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }
                    $mensaje = "Hubo un error en la ejecución del Proceso Orden de Pago";
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                }

                // De Procesamiento de Datos
                $param = array(
                    'pxml' => $orden_pago
                );

                $res = $client->call('generarOrdenPago', $param);  // De Procesamiento de Datos
                // ¿ocurrio error al llamar al web service?
                if ($client->fault) { // si
                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 11, "FALLO EN LA LLAMADA AL WEB SERVICE");
                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }

                    $mensaje = "Error en la creacion del servicio cliente en la Orden de Pago";
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                } else { // no
                    $error = $client->getError();
                    if ($error) {

                        // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                        $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 12, "ERROR EN LA LLAMADA AL PROCESO DE ORDEN DE PAGO");
                        if (!$error_bitacora_orden) {

                            $db->commit();
                        } else {

                            $db->rollback();
                        }

                        $mensaje = "Error en la llamada del Proceso Orden de Pago.";
                        $url = $_SERVER[HTTP_REFERER];
                        error($mensaje, $url);
                    }
                }

                $datos_respuesta = xml2array(utf8_encode($res['result'])); // Si envian el XML con: encoding="utf-8" ó sin encoding (DE PROCESAMIENTO DE DATOS)
                //print_r( $datos_respuesta);
                // Analizando la respuesta del XML
                if ($datos_respuesta['RESPUESTA']['CODIGO_RESP'] == 1) {

                    $anio_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 0, 4);
                    $mes_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 4, 2);
                    $dia_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 6, 2);

                    // Respuesta correcta. Actualizar el estado de la transaccion como correcta y actualizar el No. de Orden de Pago y la llave de seguridad
                    //$_SESSION['orden_pago'] = $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'];
                    $error_bitacora_orden = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                    $error_registro_nueva_orden_pago = nuevaOrdenPago($db, $user, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], 2, 02, $extension, $estudiante[carrera], $carnet, $id_rubro, $datos_respuesta['RESPUESTA']['CHECKSUM'], $costo_total, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                    $error_registro_asignacion = /* registrarAsignacion($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO']) */ false;

                    if (!$error_bitacora_orden && !$error_registro_nueva_orden_pago) {

                        if (!$error_registro_asignacion) {

                            $orden_pago_id = $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'];

                            //actualizando orden de pago...
                            $query_actualizacion = "
                                    UPDATE asignacion SET orden_pago = $orden_pago_id WHERE carnet = $carnet and anio = $anio and semestre = $semestre and extension = $extension and evaluacion = $evaluacion and codigo = '$codigo' and seccion = '$seccion'
                                ";

                            $resultado = & $db->query($query_actualizacion);
                            if ($db->isError($resultado)) {
                                echo 'Error al actualizar la orden.<pre>';
                                var_dump($query_actualizacion, $datos_respuesta, $datos_respuesta['RESPUESTA'], $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO']);
                                echo '</pre>';
                                die;
                            } else {
                                echo 'Actualizada: ' . $orden_pago_id . ';;;';
                            }

                            $db->commit();
                        } else {

                            $db->rollback();
                            $mensaje = "El proceso de Generacion de Orden de Pago fue exitoso pero hubo un error en el registro de la Asignacion.";
                            $url = $_SERVER[HTTP_REFERER];
                            error($mensaje, $url);
                        }
                    } else {

                        $db->rollback();
                        $mensaje = "La Orden de Pago fue Generada por el Sistema Bancario pero se produjo un error en el Registro de Dicha orden <br> Si el error se sigue presentando, acuda a la Direcci&oacute;n de Interciclos de la Facultad de Arquitectura";
                        $url = $_SERVER[HTTP_REFERER];
                        error($mensaje, $url);
                    }
                } else {

                    // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                    $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 13, $datos_respuesta['RESPUESTA']['DESCRIPCION']);

                    if (!$error_bitacora_orden) {

                        $db->commit();
                    } else {

                        $db->rollback();
                    }

                    $mensaje = "La Orden de Pago no fue Generada por el Sistema Bancario. Si el error se sigue presentando, acuda a la Direcci&oacute;n de Interciclos de la Facultad de Arquitectura.<br><br>" . "Descripci&oacute;n del error: " . $datos_respuesta['RESPUESTA']['DESCRIPCION'];
                    $url = $_SERVER[HTTP_REFERER];
                    error($mensaje, $url);
                }

                // Respuesta de error para unidades fuera de Extension Central. SIIF
            }
        }
    }
}

//crearBoletasCruzadas();
crearBoletasInscripcion(2023, 1, 2);
//crearBoletasPago(2023, 1, 2);


?>





