<?php

/*
  Document   : financiero_ordenespago_laboratorio_procesar.php
  Created on : 06-Jun-2016, 15:43
  Author     : Angel Caal
  Description:
  -> Generador de ordenes de pago de laboratorios de computación previo a la asignación
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../lib/nusoap.php';
require_once '../misc/xml2array.php';

session_start();
if (isset($_SESSION[usuario])) {

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

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = 2;
        $evaluacion = 1;
        $carnet = $_SESSION['usuario'];
        $cursos = $_POST['cursos'];
        $user = "estudiante";

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $db->autoCommit(false);

        // Consulta de los datos del estudiante 
        $consulta = "SELECT *
        FROM estudiante e
        WHERE e.carnet = $carnet";
        $datos_estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al determinar los datos del estudiante actual.";
            $url = $_SERVER['HTTP_REFERER'];
        } else {

            $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
            $correlativo_trans = & $db->getRow($consulta);
            if ($db->isError($correlativo_trans)) {
                $error = true;
                $mensaje = "Hubo un error al obtener el No. de Transaccion.";
                $url = $_SERVER['HTTP_REFERER'];
            } else {

                if (empty($cursos)) {
                    $aviso = true;
                    $mensaje = "Por favor, seleccione al menos un curso para procesar la orden de pago.";
                    $url = $_SERVER['HTTP_REFERER'];
                } else {

                    $costo_total_cursos = "0.00";

                    foreach ($cursos AS $cu) {

                        // Obtener la suma de cupo de las secciones aperturadas y el costo del curso
                        $consulta = "SELECT s.costo, TRIM(c.nombre) AS asignatura, s.pensum,  s.codigo, p.carrera, s.seccion, SUM(s.cupo) AS cupo
                        FROM seccion s
                        INNER JOIN curso c
                        ON c.pensum = s.pensum AND c.codigo = s.codigo
                        INNER JOIN pensum p
                        ON p.pensum = s.pensum
                        WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion 
                        AND CONCAT(s.pensum,s.codigo) = '$cu'";
                        $detalle_curso = & $db->getRow($consulta);
                        if ($db->isError($detalle_curso)) {
                            $error = true;
                            $mensaje = "Hubo un problema al obtener los detalles del cursos seleccionado";
                            $url = $_SERVER['HTTP_REFERER'];
                        } else {

                            // Obtener el cupo ocupado 
                            $consulta = "SELECT COUNT(*) AS total
                            FROM detalle_orden_pago d
                            WHERE d.anio = $anio AND d.semestre = $semestre AND d.evaluacion = $evaluacion AND d.pensum = $detalle_curso[pensum]
                            AND d.codigo = '$detalle_curso[codigo]'";
                            $ocupados = & $db->getRow($consulta);
                            if ($db->isError($ocupados)) {
                                $error = true;
                                $mensaje = "Hubo un problema al obtener la cantidad de ordenes generadas.";
                                $url = $_SERVER['HTTP_REFERER'];
                            } else {

                                $cupo = $detalle_curso['cupo'] - $ocupados['total'];

                                if ($cupo <> 0) {

                                    $data_cursos[] = array(
                                        'pensum' => $detalle_curso['pensum'],
                                        'codigo' => $detalle_curso['codigo'],
                                        'asignatura' => $detalle_curso['asignatura'],
                                        'costo' => $detalle_curso['costo'],
                                        'seccion' => $detalle_curso['seccion']
                                    );

                                    $costo_total_cursos += $detalle_curso['costo'];
                                    $carrera = $detalle_curso['carrera'];
                                } else {
                                    $aviso = true;
                                    $mensaje = "El cupo en esta asignatura esta lleno.";
                                    $url = $_SERVER['HTTP_REFERER'];
                                }
                            }
                        }
                    }

                    if (!$error && !$aviso) {

                        // ---------------------------------------------------------------------------------
                        // XML COMO CADENA DE TEXTO (STRING)
                        // ---------------------------------------------------------------------------------

                        $id_rubro = 63;
                        $id_variante_rubro = 1;

                        $orden_pago = "";
                        $datos_estudiante = "<CARNET>" . $carnet . "</CARNET>" .
                                "<UNIDAD>" . "02" . "</UNIDAD>" .
                                "<EXTENSION>" . str_pad($datos_estudiante['extension'], 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
                                "<CARRERA>" . str_pad($carrera, 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
                                "<NOMBRE>" . $datos_estudiante['nombre'] . "</NOMBRE>" .
                                "<MONTO>" . number_format($costo_total_cursos, 2) . "</MONTO>";

                        $detalle_orden_pago = "";
                        $datos_orden_pago = "";

                        foreach ($data_cursos AS $da) {

                            $errorIngresoCursos = ingresarTransaccionCursos($db, $user, 02, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $da['pensum'], $da['codigo'], $da['seccion'], $da['costo'], 2, $correlativo_trans['no_transaccion'], $id_rubro, $id_variante_rubro);
                            if (!$errorIngresoCursos) {

                                $datos_orden_pago = "<DETALLE_ORDEN_PAGO>" .
                                        "<ANIO_TEMPORADA>" . $anio . "</ANIO_TEMPORADA>" .
                                        "<ID_RUBRO>" . $id_rubro . "</ID_RUBRO>" .
                                        "<ID_VARIANTE_RUBRO>" . $id_variante_rubro . "</ID_VARIANTE_RUBRO>" .
                                        "<TIPO_CURSO>" . "CURSO" . "</TIPO_CURSO>" .
                                        "<CURSO>" . $da['codigo'] . "</CURSO>" .
                                        "<SECCION>" . $da['seccion'] . "</SECCION>" .
                                        "<SUBTOTAL>" . $da['costo'] . "</SUBTOTAL>" .
                                        "</DETALLE_ORDEN_PAGO>";

                                $detalle_orden_pago = $detalle_orden_pago . $datos_orden_pago;
                                $db->commit();
                            } else {
                                $error = true;
                                $mensaje = "Hubo un problema al registrar la transacción de orden de pago.";
                                $url = $_SERVER['HTTP_REFERER'];
                                $db->rollback();
                            }
                        }

                        // Generando el XML final de la orden de pago
                        $orden_pago = "<GENERAR_ORDEN>" . $datos_estudiante . $detalle_orden_pago . "</GENERAR_ORDEN>";

                        $wsdl = "http://arquitectura.usac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
                        $client = new nusoap_client($wsdl, 'wsdl');
                        $err = $client->getError();
                        if ($err) {

                            $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 10, "ERROR AL CREAR EL OBJETO CLIENTE DEL WEB SERVICE");
                            if (!$error_bitacora_orden) {
                                $db->commit();
                            } else {
                                $error = true;
                                $mensaje = "Hubo Hubo un problema al conectarse al WebService.";
                                $url = $_SERVER['HTTP_REFERER'];
                                $db->rollback();
                            }
                        }

                        // De Procesamiento de Datos
                        $param = array(
                            'pxml' => $orden_pago
                        );

                        $res = $client->call('generarOrdenPago', $param);
                        if ($client->fault) {

                            // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                            $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 11, "FALLO EN LA LLAMADA AL WEB SERVICE");
                            if (!$error_bitacora_orden) {
                                $db->commit();
                            } else {
                                $error = true;
                                $mensaje = "Hubo un problema al insertar la orden de pago en Procesamiento de Datos.";
                                $url = $_SERVER['HTTP_REFERER'];
                                $db->rollback();
                            }
                        } else {

                            $error_c = $client->getError();

                            if ($error_c) {

                                // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                                $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 12, "ERROR EN LA LLAMADA AL PROCESO DE ORDEN DE PAGO");
                                if (!$error_bitacora_orden) {
                                    $db->commit();
                                } else {
                                    $error = true;
                                    $mensaje = "Error en la llamada del Proceso Orden de Pago.";
                                    $url = $_SERVER['HTTP_REFERER'];
                                    $db->rollback();
                                }
                            }
                        }

                        $datos_respuesta = xml2array(utf8_encode($res['result']));

                        if ($datos_respuesta['RESPUESTA']['CODIGO_RESP'] == 1) {

                            $anio_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 0, 4);
                            $mes_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 4, 2);
                            $dia_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 6, 2);

                            $error_bitacora_orden = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                            $error_registro_nueva_orden_pago = nuevaOrdenPago($db, $user, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], 2, 02, $extension, $carrera, $carnet, $id_rubro, $datos_respuesta['RESPUESTA']['CHECKSUM'], $costo_total_cursos, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                            if (!$error_bitacora_orden && !$error_registro_nueva_orden_pago) {

                                $db->commit();

                                // Completado con éxito
                                $_SESSION['proceso_finalizado'] = "La orden de pago fue generada exitosamente, procesa a realizar el pago para poder asignarse el curso.";

                                if (isset($_SESSION['proceso_finalizado'])) {
                                    header("location: " . $_SERVER['HTTP_REFERER']);
                                }
                            } else {
                                $error = true;
                                $mensaje = "La Orden de Pago fue Generada por el Sistema Bancario pero se produjo un error en el Registro de Dicha orden.";
                                $url = $_SERVER['HTTP_REFERER'];
                                $db->rollback();
                            }
                        } else {

                            // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                            $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 13, $datos_respuesta['RESPUESTA']['DESCRIPCION']);

                            if (!$error_bitacora_orden) {

                                $db->commit();
                            } else {

                                $db->rollback();
                            }

                            $mensaje = "La Orden de Pago no fue Generada por el Sistema Bancario. Si el error se sigue presentando.<br><br>" . "Descripci&oacute;n del error: " . $datos_respuesta['RESPUESTA']['DESCRIPCION'];
                            $url = $_SERVER[HTTP_REFERER];
                            error($mensaje, $url);
                        }
                    }
                }
            }
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
