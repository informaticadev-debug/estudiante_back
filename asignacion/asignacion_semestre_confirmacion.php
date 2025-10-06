<?php

/*
  Proceso de Asignacion para Semestre
  -> Funciones para la creacion de asignaciones y preasignaciones por pago de laboratorio
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../lib/nusoap.php';
require_once '../misc/xml2array.php';

session_start();
if (isset($_SESSION['usuario'])) {

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
        $db->autoCommit(false);

        // Datos de la session actual
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $extension = $_SESSION['extension'];
        $evaluacion = $_SESSION['evaluacion'];
        $carnet = $_SESSION['usuario'];
        $carrera = inscripcion_estudiante($db, $anio, $semestre, $carnet);
        
        /* if ($carrera == 3 OR $carrera == 1) {
          $anio = 2016;
          $semestre = 1;
          } */

        // Consulta de los datos del estudiante 
        $consulta = "SELECT e.carnet, e.extension AS codigo_extesion, e.nombre, i.carrera
        FROM estudiante e
		INNER JOIN inscripcion i
		ON i.carnet = e.carnet AND i.anio = $anio AND i.semestre = $semestre
        WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al determinar los datos del estudiante actual.";
            $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
        } else {

            // Registrar las Asignaciones de la seleccion anterior 
            for ($i = 0; $i < count($_POST['codigo']); $i++) {

                $pensum = $_POST['pensum'][$i];
                $codigo = $_POST['codigo'][$i];
                $seccion = $_POST['seccion'][$i];
                $preasignacion = $_POST['preasignacion'][$i];
                $observacion = $_POST['observacion'][$i];

                if ($seccion == 'SPD' || strpos($seccion, "CUPO - ") > -1) {
                    $result_jornada = getJornadaSeleccionada($db, $extension, $anio, $semestre, $evaluacion, $carnet);
                    $jornada = $determinar_jornada = (!empty($result_jornada)) ? $result_jornada : 'X';
                    //$jornada = "X"; //comentar 2 lineas de arriba
                    registrarCursoSPD($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $carnet, $jornada, str_replace("CUPO - ", "", $seccion));
                    $db->commit();
                    continue;
                }

                if ($preasignacion == 1 && $observacion <> 'Traslape') {

                    // Verificar el cupo de la sección en este último paso
                    $verificar_cupo = "";
                    $consulta = "SELECT *
                        FROM seccion s
                        WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion AND s.pensum = $pensum
                        AND s.codigo = '$codigo' AND s.seccion = '$seccion' AND s.cupo > (
                            SELECT COUNT(*)
                            FROM asignacion a
                            WHERE a.extension = s.extension AND a.anio = s.anio AND a.semestre = s.semestre AND a.evaluacion = s.evaluacion
                            AND a.pensum = s.pensum AND a.codigo = s.codigo AND a.seccion = s.seccion
                        )";
                    $verificar_cupo = & $db->getRow($consulta);
                    if ($db->isError($verificar_cupo)) {
                        $error = true;
                        $mensaje = "Hubo un problema al verificar el cupo disponible en la sección seleccionada.";
                        $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                    } else {

                        /* if ($verificar_cupo <> 0) {

                          // Modificación temporal
                          // Para las asignaiones del ciclo 01-2016 no se genera automáticamente el pago de laboratorio este se generará mas adelante con el WS
                          $registrar_asignaciones = registrarPreasignacion($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet);
                          if (!$registrar_asignaciones) {
                          $db->commit();
                          } else {
                          $db->rollback();
                          $error = true;
                          $mensaje = "Error al registrar Asignaciones.";
                          $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                          }
                          } else {

                          $sin_cupo[] = array(
                          'pensum' => $pensum,
                          'codigo' => $codigo,
                          'seccion' => $seccion
                          );

                          $wsdl = "http://arquitectura.usac.edu.gt/ws/ws_administrativo.php?wsdl";
                          $client = new nusoap_client($wsdl, 'wsdl');
                          $err = $client->getError();

                          $data = array(
                          'carnet' => $carnet,
                          'pensum' => $pensum,
                          'codigo' => $codigo,
                          'seccion' => $seccion
                          );

                          $res = $client->call('alimentar_bitacora_asignacion', $data);
                          } */
                    }

                    if ($verificar_cupo <> 0) {

                        // Obtener el costo total del curso 
                        $consulta = "SELECT s.costo
						  FROM seccion s
						  WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
						  AND s.pensum = $pensum AND s.codigo = '$codigo' AND s.seccion = '$seccion'";
                        $costos = & $db->getRow($consulta);
                        if ($db->isError($costos)) {
                            $error = true;
                            $mensaje = "Hubo un problema al obtener el costo total de la asignatura. " . mysql_error();
                            $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                        } else {

                            // Modificación a los pagos de laboratorio
                            // Rubro de pago: 6 = Primera recuperacion.  7 = Segunda recuperacion.
                            $costo_total = $costos[costo];
                            $id_rubro = 63;
                            $id_variante_rubro = 1;

                            // Generar numero de transaccion para el estudiante
                            $errorEnTransaccion = false;

                            $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
                            $correlativo_trans = & $db->getRow($consulta);
                            if ($db->isError($correlativo_trans)) {
                                $error = true;
                                $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                                $mensaje = "Hubo un error al obtener el No. de Transaccion.";
                            } else {

                                $errorIngresoCursos = ingresarTransaccionCursos($db, $user, 02, $extension, $estudiante[carrera], $carnet, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $costo_total, 2, $correlativo_trans['no_transaccion'], $id_rubro, $id_variante_rubro);

                                if (!$errorIngresoCursos) {

                                    $db->commit();

                                    // --------------------------------------------------	-------------------------------
                                    // XML COMO CADENA DE TEXTO (STRING)
                                    // ---------------------------------------------------------------------------------

                                    $orden_pago = "";
                                    $datos_estudiante = "<CARNET>" . $carnet . "</CARNET>" .
                                            "<UNIDAD>" . "02" . "</UNIDAD>" .
                                            "<EXTENSION>" . str_pad($extension, 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
                                            "<CARRERA>" . str_pad($estudiante[carrera], 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
                                            "<NOMBRE>" . $estudiante[nombre] . "</NOMBRE>" .
                                            "<MONTO>" . ($costo_total) . "</MONTO>";

                                    $detalle_orden_pago = "";

                                    $datos_orden_pago = "";
                                    $datos_orden_pago = "<DETALLE_ORDEN_PAGO>" .
                                            "<ANIO_TEMPORADA>" . $anio . "</ANIO_TEMPORADA>" .
                                            "<ID_RUBRO>" . $id_rubro . "</ID_RUBRO>" .
                                            "<ID_VARIANTE_RUBRO>" . $id_variante_rubro . "</ID_VARIANTE_RUBRO>" .
                                            "<TIPO_CURSO>" . "CURSO" . "</TIPO_CURSO>" .
                                            "<CURSO>" . $codigo . "</CURSO>" .
                                            "<SECCION>" . $seccion . "</SECCION>" .
                                            "<SUBTOTAL>" . ($costo_total) . "</SUBTOTAL>" .
                                            "</DETALLE_ORDEN_PAGO>";

                                    $detalle_orden_pago = $datos_orden_pago;

                                    // Generando el XML final de la orden de pago
                                    $orden_pago = "<GENERAR_ORDEN>" . $datos_estudiante . $detalle_orden_pago . "</GENERAR_ORDEN>";

                                    $wsdl = "http://arquitectura.usac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
                                    //$wsdl="https://siif.usac.edu.gt/WSGeneracionOrdenPago/WSGeneracionOrdenPagoSoapHttpPort?WSDL";
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
                                        $error = true;
                                        $mensaje = "Hubo un error en la ejecución del Proceso Orden de Pago";
                                        $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
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

                                        $error = true;
                                        $mensaje = "Error en la creacion del servicio cliente en la Orden de Pago";
                                        $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
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
                                            $error = true;
                                            $mensaje = "Error en la llamada del Proceso Orden de Pago.";
                                            $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                                        }
                                    }

                                    $datos_respuesta = xml2array(utf8_encode($res['result'])); // Si envian el XML con: encoding="utf-8" ó sin encoding (DE PROCESAMIENTO DE DATOS)
                                    //print_r($datos_respuesta);
                                    // Analizando la respuesta del XML
                                    if ($datos_respuesta['RESPUESTA']['CODIGO_RESP'] == 1) {

                                        $anio_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 0, 4);
                                        $mes_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 4, 2);
                                        $dia_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 6, 2);

                                        // Respuesta correcta. Actualizar el estado de la transaccion como correcta y actualizar el No. de Orden de Pago y la llave de seguridad
                                        //$_SESSION['orden_pago'] = $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'];
                                        $error_bitacora_orden = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                                        $error_registro_nueva_orden_pago = nuevaOrdenPago($db, $user, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], 2, 02, $extension, $estudiante[carrera], $carnet, $id_rubro, $datos_respuesta['RESPUESTA']['CHECKSUM'], $costo_total, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);


                                        $error_registro_asignacion = registrarAsignacionLaboratorio($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO']);


                                        if (!$error_bitacora_orden && !$error_registro_nueva_orden_pago) {

                                            $db->commit();
                                            if (!$error_registro_asignacion) {
                                                $db->commit();
                                            } else {
                                                $error = true;
                                                $db->rollback();
                                                $mensaje = "El proceso de Generacion de Orden de Pago fue exitoso pero hubo un error en el registro de la Asignacion.";
                                                $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                                            }
                                        } else {
                                            $error = true;
                                            $db->rollback();
                                            $mensaje = "La Orden de Pago fue Generada por el Sistema Bancario pero se produjo un error en el Registro de Dicha orden. Si el error se sigue presentando, notifique a Control Académico para verificar el inconveniente.";
                                            $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                                        }
                                    } else {

                                        // Respuesta INCORRECTA. Actualizar el estado de la transaccion como INCORRECTA.
                                        $error_bitacora_orden = registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, 13, $datos_respuesta['RESPUESTA']['DESCRIPCION']);

                                        if (!$error_bitacora_orden) {
                                            $db->commit();
                                        } else {
                                            $db->rollback();
                                        }

                                        $error = true;
                                        $mensaje = "La Orden de Pago no fue Generada por el Sistema Bancario.<br><br>" . "<code>" . $datos_respuesta['RESPUESTA']['DESCRIPCION'] . "</code>";
                                        $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                                    }
                                } else {
                                    $error = true;
                                    $db->rollback();
                                    $mensaje = "Error en la transacción de pagos de asignaturas que se imparten en laboratorio de computación";
                                    $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                                }
                            }
                        }
                    } else {

                        $sin_cupo[] = array(
                            'pensum' => $pensum,
                            'codigo' => $codigo,
                            'seccion' => $seccion
                        );
                    }
                } else if ($preasignacion == 0 && $observacion <> 'Traslape') {

                    // Verificar el cupo de la sección en este último paso
                    $verificar_cupo = "";
                    $consulta = "SELECT *
					FROM seccion s
					WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion AND s.pensum = $pensum
					AND s.codigo = '$codigo' AND s.seccion = '$seccion' AND s.cupo > (
						SELECT COUNT(*)
						FROM asignacion a
						WHERE a.extension = s.extension AND a.anio = s.anio AND a.semestre = s.semestre AND a.evaluacion = s.evaluacion
						AND a.pensum = s.pensum AND a.codigo = s.codigo AND a.seccion = s.seccion
					)";
                    $verificar_cupo = & $db->getRow($consulta);
                    if ($db->isError($verificar_cupo)) {
                        $error = true;
                        $mensaje = "Hubo un problema al verificar el cupo disponible en la sección seleccionada.";
                        $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                    } else {

                        if ($verificar_cupo <> 0) {

                            $registrar_asignaciones = registrarAsignacion($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, "NULL");
                            if (!$registrar_asignaciones) {
                                $db->commit();
                            } else {
                                $db->rollback();
                                $error = true;
                                $mensaje = "Error al registrar Asignaciones.";
                                $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                            }
                        } else {

                            $sin_cupo[] = array(
                                'pensum' => $pensum,
                                'codigo' => $codigo,
                                'seccion' => $seccion
                            );

                            /* $wsdl = "http://arquitectura.usac.edu.gt/ws/ws_administrativo.php?wsdl";
                              $client = new nusoap_client($wsdl, 'wsdl');
                              $err = $client->getError();

                              $data = array(
                              'carnet' => $carnet,
                              'pensum' => $pensum,
                              'codigo' => $codigo,
                              'seccion' => $seccion
                              );

                              $res = $client->call('alimentar_bitacora_asignacion', $data); */
                        }
                    }
                }
            }
        }

        if (!$error) {

            if (count($sin_cupo) <> 0) {

                // Informar al estudiante de las asignaturas sin cupo
                foreach ($sin_cupo AS $sc) {

                    $consulta = "SELECT c.pensum, TRIM(c.nombre) AS asignatura, c.codigo, '$sc[seccion]' AS seccion
                    FROM curso c
                    WHERE c.pensum = $sc[pensum] AND c.codigo = '$sc[codigo]'";
                    $curso_sin_cupo = & $db->getRow($consulta);
                    if ($db->isError($curso_sin_cupo)) {
                        $mensaje = "Error al registrar Asignaciones.";
                        $url = "../asignacion/asignacion_semestre_seleccion.php?evaluacion=$evaluacion";
                        error($mensaje, $url);
                    } else {
                        $no_asignadas = $no_asignadas . $curso_sin_cupo[pensum] . " - " . $curso_sin_cupo[codigo] . " " . $curso_sin_cupo[asignatura] . " " . "<b>" . $curso_sin_cupo[seccion] . "</b><br>";
                    }
                }

                $_SESSION['proceso_finalizado'] = "<font class='text text-danger'>Durante el proceso de asignación algunas secciones ya tienen el cupo lleno. Las asignaturas no asignadas aparecen
                en el listado de abajo, por favor verifique si existe cupo en otra sección.<br><br>" . $no_asignadas;
            } else {

                $_SESSION['proceso_finalizado'] = "Las asignaciones se han procesado correctamente.<br />
                                                    <br />
							<!-- button class='btn btn-success' onclick=\"$('button:contains(Cerrar)').show(); form_seleccion = $('#form_asignaciones')[0];
        form_seleccion.action = '../asignatura/asignaciones.php';
        form_seleccion.submit();  \">Constancia de Asignación</button --> 
							<!-- script>
								$(document).ready(function() {
									$('button:contains(Cerrar)').hide();
								});
							</script -->
						";
            }

            echo "
          <script>
          window.open('../menus/contenido.php','contenido');
          </script>
          ";
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
