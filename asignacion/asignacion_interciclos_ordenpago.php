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

function verificarCursoAsignado($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo) {
    $consulta = "
                SELECT a.codigo, a.seccion
                FROM asignacion a
                WHERE a.anio = $anio and a.semestre = $semestre and a.extension = $extension and a.evaluacion = $evaluacion and a.carnet = $carnet AND codigo = '$codigo'
            ";
    $cursos = & $db->getAll($consulta);
    if ($db->isError($cursos)) {
        $error = true;
        $mensaje = "Hubo un error al verificar los cursos que tiene asignados.";
        $url = "../menus/contenido.php";
        return false;
    }
    if (count($cursos) > 0) {
        return $cursos[0];
    }
    return false;
}

function obtenerCargaCurso($db, $anio, $semestre, $extension, $evaluacion, $codigo, $seccion) {
    $consulta = "
                SELECT s.cupo, count(a.carnet) as asignados
                FROM seccion s
                    LEFT JOIN asignacion a on s.anio = a.anio and s.extension = a.extension and s.semestre = a.semestre and s.evaluacion = a.evaluacion 
                    and s.pensum = a.pensum and s.codigo = a.codigo and s.seccion = a.seccion
                WHERE s.anio = $anio and s.semestre = $semestre and s.extension = $extension and s.evaluacion = $evaluacion and s.codigo = '$codigo' and s.seccion = '$seccion'
            ";
    //var_dump($consulta); die;
    $cupo = & $db->getAll($consulta);
    if ($db->isError($cupo)) {
        $error = true;
        $mensaje = "Hubo un error al obtener la carga del curso.";
        $url = "../menus/contenido.php";
        return false;
    }
    return $cupo[0];
}

function cambiarSeccion($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo, $seccion, $seccionAnterior) {
    $consulta = "
                UPDATE asignacion SET seccion = '$seccion'
                WHERE anio = $anio and semestre = $semestre and carnet = $carnet and extension = $extension and evaluacion = $evaluacion and codigo = '$codigo'
            ";
    //var_dump($consulta); die;
    $result = & $db->query($consulta);
    if ($db->isError($result)) {
        return false;
    }
    guardarEnBitacoraAsignacionCambioSeccion($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo, $seccion, $seccionAnterior);
    return true;
}

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

function desasignar($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo) {
    $consulta = "
                DELETE FROM asignacion
                WHERE anio = $anio and carnet = $carnet and semestre = $semestre and extension = $extension and evaluacion = $evaluacion and codigo = '$codigo'
            ";
    $result = & $db->query($consulta);
    if ($db->isError($result)) {
        return false;
    }
    return true;
}

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
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_SESSION['evaluacion'];
        $carnet = $_SESSION['usuario'];

        // Datos de la asignatura seleccionada convertida en session.
        $pensum = $_SESSION['pensum'];
        $codigo = $_SESSION['codigo'];
        $seccion = $_SESSION['seccion'];

        $db->setFetchMode(DB_FETCHMODE_ASSOC);

        $curso_ya_asignado = verificarCursoAsignado($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo);

        $cargaCurso = obtenerCargaCurso($db, $anio, $semestre, $extension, $evaluacion, $codigo, $seccion);

        if ($cargaCurso['cupo'] <= $cargaCurso['asignados']) {
            //var_dump($cargaCurso['cupo']);            var_dump('-----'); var_dump($cargaCurso['asignados']); die;
            $aviso = true;
            $mensaje = "El cupo para la sección seleccionada se encuentra lleno, no puede asignarse en dicha sección.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            if ($curso_ya_asignado) {
                if (desasignar($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo)) {
                    
                } else {
                    $error = true;
                    $mensaje = "Hubo un error al realizar el cambio de sección.";
                    $url = $_SERVER[HTTP_REFERER];
                }
            }

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
            $costo_inscripcion = 0; //costo_inscripcion($db, $extension, $anio, $semestre, $evaluacion, $carnet);
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
            //$correlativo_trans = & $db->getRow($consulta);
            //saltando generacion de nuevos correlativos..
            if (false && $db->isError($correlativo_trans)) {
                $error = true;
                $mensaje = "Hubo un error al obtener el No. de Transaccion.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                //$errorIngresoCursos = ingresarTransaccionCursos($db, $user, 02, $extension, $estudiante[carrera], $carnet, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $costo_curso, 2, $correlativo_trans['no_transaccion'], $id_rubro, 2);
                //saltando ingreso de correlativos de transaccion..
                $errorIngresoCursos = false; //ingresarTransaccionCursos($db, $user, 02, $extension, $estudiante[carrera], $carnet, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $costo_curso, 2, $correlativo_trans['no_transaccion'], $id_rubro, 2);

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
                    //$orden_pago = crearXMLPeticionSIIF($estudiante['carnet'], '02', $estudiante['codigo_extension'], $estudiante['carrera'], $estudiante['nombre'], $costo_total, $array_detalle);
                    //var_dump($orden_pago); die;

                    $wsdl = "https://arquitectura.farusac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
                    //$wsdl = "http://arquitectura.usac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
                    //$client = new nusoap_client($wsdl, 'wsdl');
                    //$err = $client->getError();
                    //saltar operaciones de boletas...;
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

                    //QUITANDO PETICION $res = $client->call('generarOrdenPago', $param);  // De Procesamiento de Datos
                    // ¿ocurrio error al llamar al web service?
                    if (false && $client->fault) { // si
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
                        /* /$error = $client->getError();

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
                          } */
                    }

                    //$datos_respuesta = xml2array(utf8_encode($res['result'])); // Si envian el XML con: encoding="utf-8" ó sin encoding (DE PROCESAMIENTO DE DATOS)
                    //print_r( $datos_respuesta);
                    //SALTANDO VALIDACIONES... Analizando la respuesta del XML
                    if (true || $datos_respuesta['RESPUESTA']['CODIGO_RESP'] == 1) {

                        /* $anio_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 0, 4);
                          $mes_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 4, 2);
                          $dia_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 6, 2);

                          // Respuesta correcta. Actualizar el estado de la transaccion como correcta y actualizar el No. de Orden de Pago y la llave de seguridad
                          //$_SESSION['orden_pago'] = $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'];
                          $error_bitacora_orden = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                          $error_registro_nueva_orden_pago = nuevaOrdenPago($db, $user, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], 2, 02, $extension, $estudiante[carrera], $carnet, $id_rubro, $datos_respuesta['RESPUESTA']['CHECKSUM'], $costo_total, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                         */
                        //$error_registro_asignacion = registrarAsignacion($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO']);
                        // Cambiando validacion de orden pago
                        $error_registro_asignacion = registrarAsignacion($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, 0);

                        if (true || (!$error_bitacora_orden && !$error_registro_nueva_orden_pago)) {

                            if (true || !$error_registro_asignacion) {

                                $db->commit();

                                guardarEnBitacoraAsignacion($db, $carnet, $anio, $semestre, $extension, $evaluacion, $pensum, $codigo, "Asignación creada con éxito.");

                                $db->commit();

                                $_SESSION['proceso_finalizado'] = "La <b>preasignación</b> del curso <b>$asignatura[nombre] $seccion</b> se ha realizado con éxito.";

                                echo "
							<script>
								window.open('../menus/contenido.php','contenido');
							</script>
							";
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
