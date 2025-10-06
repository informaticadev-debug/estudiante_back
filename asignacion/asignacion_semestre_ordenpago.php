<?php

/*
  Proceso de Asignacion para Interciclos
  -> Funciones para la creacion de las transacciones
  -> Bitacora orden de pago
  -> Detalle orden de pago
  -> Orden de Pago
  -> Pre-Asignacion
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../lib/nusoap.php';
require_once '../misc/xml2array.php';

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

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_SESSION['evaluacion'];
        $carnet = $_SESSION['usuario'];

        // Datos de la asignatura seleccionada convertida en session.
        $seleccion = $_POST['seleccion'];

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $db->autoCommit(false);

        // Consulta de los datos del estudiante 
        $consulta = "SELECT e.carnet, TRIM(e.nombre) AS nombre, e.extension AS codigo_extesion, i.carrera
		FROM estudiante e
		INNER JOIN inscripcion i
		ON i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre 
		AND i.carrera NOT IN (1,3)
		AND i.carnet = e.carnet
		WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al determinar los datos del estudiante actual.";
            $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
        } else {

            // Consulta de los datos de las asignaturas seleccionadas
            if (count($seleccion) <> 0) {
                foreach ($seleccion AS $s) {

                    // Modificación al costo de asignaturas para los estudiante de primer ingreso en Posgrado
                    // A partir del año 2016 según Oficio REF. DEPA/064/2016
                    $consulta = "SELECT s.pensum, s.codigo, s.seccion, 
					IF (
						EXISTS(
							SELECT *
							FROM inscripcion i
							WHERE i.carnet = $carnet AND i.`anio` = $anio AND (
								SELECT MIN(i2.anio)
								FROM inscripcion i2
								WHERE i2.`carnet` = i.`carnet` AND i2.`carrera` = i.`carrera`
							) >= 2016
						),IF(s.pensum <> 27 AND s.pensum <> 26 AND s.pensum <> 24001,'800.00',s.`costo`), 
						IF (c.pensum = 10 AND c.ciclo = 1, '800.00', s.costo)
					) AS costo
					FROM seccion s
					INNER JOIN curso c
					ON c.codigo = s.codigo AND c.pensum = s.pensum
					WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
					AND CONCAT(s.pensum, s.codigo, s.seccion) = '$s'";
                    $asignatura = & $db->getRow($consulta);
                    if ($db->isError($asignatura)) {
                        $error = true;
                        $mensaje = "Error al consultar los datos de la Asignatura.";
                        $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                    } else {

                        if (count($asignatura) <> 0) {

                            $data[] = array('pensum' => $asignatura[pensum], 'codigo' => $asignatura[codigo], 'seccion' => $asignatura[seccion], 'costo' => $asignatura[costo]);
                        } else {
                            $error = true;
                            $mensaje = "Por favor seleccione al menos una asignatura para generar la Orden de Pago.";
                            $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                        }
                    }
                }

                // Calcular costo total de asignaturas
                $consulta_costo_cursos = "
						select IF (
						EXISTS(
							SELECT *
							FROM inscripcion i
							WHERE i.carnet = $carnet AND i.`anio` = $anio AND (
								SELECT MIN(i2.anio)
								FROM inscripcion i2
								WHERE i2.`carnet` = i.`carnet` AND i2.`carrera` = i.`carrera`
							) >= 2016
						),IF(s.pensum <> 27 AND s.pensum <> 26 AND s.pensum <> 24001,'800.00',s.`costo`), 
						IF (c.pensum = 10 AND c.ciclo = 1, '800.00', s.costo)
						) AS costo_cursos
						from seccion s 
						INNER JOIN curso c
						ON c.pensum = s.pensum AND c.codigo = s.codigo
						where s.extension = $extension and s.anio = $anio and s.semestre = $semestre and s.evaluacion = $evaluacion and s.condicionado = 0 and
							  concat(rtrim(s.pensum),rtrim(s.codigo),rtrim(s.seccion)) in 
								 (
					";

                foreach ($seleccion as $cur) {
                    $consulta_costo_cursos = $consulta_costo_cursos . "\"" . $cur . "\"";
                    $consulta_costo_cursos = $consulta_costo_cursos . ",";
                }
                $consulta_costo_cursos = $consulta_costo_cursos . "\"\")";
                $costo_cursos = & $db->getRow($consulta_costo_cursos);

                $costo_total = $costo_cursos[costo_cursos];
            } else {
                $error = true;
                $mensaje = "Por favor seleccione al menos una Asignatura para realizar el proceso de Generacion de Orden de Pago.";
            }
        }

        // Variantes para doctorado y posgrado
        if ($data[0][pensum] == 27) {
            $id_rubro = 47;
            $id_variante_rubro = 33;
        } else {
            $id_rubro = 19;
            $id_variante_rubro = 15;
        }

        // Generar numero de transaccion para el estudiante
        $errorEnTransaccion = false;

        $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
        $correlativo_trans = & $db->getRow($consulta);
        if ($db->isError($correlativo_trans)) {
            $error = true;
            "Hubo un error al obtener el No. de Transaccion.";
        } else {

            if (!empty($data)) {
                foreach ($data AS $d_trans) {
                    $errorIngresoCursos = ingresarTransaccionCursos($db, $user, 02, $extension, $estudiante[carrera], $carnet, $anio, $semestre, $evaluacion, $d_trans[pensum], $d_trans[codigo], $d_trans[seccion], $d_trans[costo], 2, $correlativo_trans['no_transaccion'], $id_rubro, $id_variante_rubro);
                }
            }

            if (!$errorIngresoCursos) {

                $db->commit();
            } else {

                $error = true;
                $db->rollback();
                $mensaje = "Error en la Transaccion del pago de Asignaturas.";
                $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                error($mensaje, $url);
            }
        }

        if (!$error) {

            // --------------------------------------------------	-------------------------------
            // XML COMO CADENA DE TEXTO (STRING)
            // ---------------------------------------------------------------------------------

            $orden_pago = "";
            $datos_estudiante = "<CARNET>" . $estudiante['carnet'] . "</CARNET>" .
                    "<UNIDAD>" . "02" . "</UNIDAD>" .
                    "<EXTENSION>" . str_pad($estudiante['codigo_extension'], 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
                    "<CARRERA>" . str_pad($estudiante['carrera'], 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
                    "<NOMBRE>" . $estudiante['nombre'] . "</NOMBRE>" .
                    "<MONTO>" . ($costo_total) . "</MONTO>";

            $detalle_orden_pago = "";

            foreach ($data AS $d_orden) {
                $datos_orden_pago = "";
                $datos_orden_pago = "<DETALLE_ORDEN_PAGO>" .
                        "<ANIO_TEMPORADA>" . $anio . "</ANIO_TEMPORADA>" .
                        "<ID_RUBRO>" . $id_rubro . "</ID_RUBRO>" .
                        "<ID_VARIANTE_RUBRO>" . $id_variante_rubro . "</ID_VARIANTE_RUBRO>" .
                        "<TIPO_CURSO>" . "CURSO" . "</TIPO_CURSO>" .
                        "<CURSO>" . $d_orden[codigo] . "</CURSO>" .
                        "<SECCION>" . $d_orden[seccion] . "</SECCION>" .
                        "<SUBTOTAL>" . ($d_orden[costo]) . "</SUBTOTAL>" .
                        "</DETALLE_ORDEN_PAGO>";

                $detalle_orden_pago = $detalle_orden_pago . $datos_orden_pago;
            }

            // Generando el XML final de la orden de pago
            $orden_pago = "<GENERAR_ORDEN>" . $datos_estudiante . $detalle_orden_pago . "</GENERAR_ORDEN>";

            $wsdl = "http://arquitectura.usac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
            //$wsdl="http://siif.usac.edu.gt/WSGeneracionOrdenPago/WSGeneracionOrdenPagoSoapHttpPort?WSDL";
            $client = new nusoap_client($wsdl, 'wsdl');
            $err = $client->getError();
            if ($err) {

                // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 10, "ERROR AL CREAR EL OBJETO CLIENTE DEL WEB ERVICE");
                if (!$error_bitacora_orden) {
                    $db->commit();
                } else {
                    $db->rollback();
                }

                $mensaje = "Hubo un error en la ejecución del Proceso Orden de Pago";
                $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
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
                $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
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
                    $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
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

                foreach ($data AS $as) {
                    $error_registro_asignacion = registrarAsignacion($db, $extension, $anio, $semestre, $evaluacion, $as[pensum], $as[codigo], $as[seccion], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO']);
                }

                if (!$error_bitacora_orden && !$error_registro_nueva_orden_pago) {

                    if (!$error_registro_asignacion) {

                        $db->commit();

                        $_SESSION['proceso_finalizado'] = "La <b>preasignación</b> del curso <b>$asignatura[nombre] $seccion</b> se ha completado exitosamente,
							puede imprimir la orden de pago (confirmar en BANRURAL o Banco G&T Continental) desde la opción Estado de cuenta, por favor confirme el pago de la asignatura para estar oficialmente asignado, de lo contrario no tendrá derecho a nota final.";

                        echo "
							<script>
								window.open('../menus/contenido.php','contenido');
							</script>
							";
                    } else {

                        $db->rollback();
                        $mensaje = "El proceso de Generacion de Orden de Pago fue exitoso pero hubo un error en el registro de la Asignacion.";
                        $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                        error($mensaje, $url);
                    }
                } else {

                    $db->rollback();
                    $mensaje = "La Orden de Pago fue Generada por el Sistema Bancario pero se produjo un error en el Registro de Dicha orden";
                    $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
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

                $mensaje = "La Orden de Pago no fue Generada por el Sistema Bancario.<br><br>" . $datos_respuesta['RESPUESTA']['DESCRIPCION'];
                $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                error($mensaje, $url);
            }

            // Respuesta de error para unidades fuera de Extension Central. SIIF
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
