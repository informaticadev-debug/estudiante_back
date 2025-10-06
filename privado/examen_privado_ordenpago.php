<?php

/*
  EXAMEN PRIVADO
  -> Proceso para obtener examen privado
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../lib/nusoap.php';
require_once '../misc/xml2array.php';
require_once '../config/local.php';

session_start();
if (isset($_SESSION[usuario])) {

    function ingresarTransaccion($db, $user, $unidad, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo, $tipoPago, $noTrans, $rubro, $varianteRubro) {

        $error = false;

        // ingresando los cursos de la transaccion		
        $consulta_ingreso = "
			INSERT INTO bitacora_orden_pago 
			(unidad,extension,carrera,anio,semestre,evaluacion,carnet,
			tipo_pago,orden_pago,no_correlativo,no_transaccion,rubro,variante_rubro,llave,monto,estado,fecha_transaccion,usuario_orden_pago
			)
			VALUES (
			$unidad
			,$extension
			,$carrera
			,$anio
			,$semestre
			,$evaluacion			
			,$carnet
			,$tipoPago
			,NULL
			,getCorrelativoCiclo($anio,$semestre,$evaluacion)
			,$noTrans
			,$rubro
			,$varianteRubro
			,NULL
			,$costo
			,1
			,NOW()
			,'estudiante'
			)
		";
        $resultado_ingreso = & $db->query($consulta_ingreso);
        if ($db->isError($resultado_ingreso)) {
            //echo $consulta_ingreso . "<br>";
            $error = true;
        }

        return $error;
    }

    function actualizarOrdenPagoPrivado($db, $anio, $semestre, $numero_privado, $carnet, $orden_pago) {

        // Actualizacion de orden de pago en la solicitud de examen privado
        $consulta = "UPDATE examen_privado p
        SET p.orden_pago = $orden_pago
        WHERE p.anio = $anio AND p.semestre = $semestre AND p.carnet = $carnet AND p.numero_privado = $numero_privado";
        $actualizar_orden = & $db->Query($consulta);
    }

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
        $db->autoCommit(false);
        $error = false;
        $extension = $_SESSION['extension'];
        $carnet = $_SESSION['usuario'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = 1;
        $costo = "250.00";
        $numero_privado = numero_privado($db);

        $carrera = $_POST['carrera'];
        $proyecto_graduacion = $_POST['proyecto_graduacion'];
        $id_rubro = 9;
        $id_variante_rubro = 1;

        $db->Query("SET NAMES utf8");

        // Datos Estudiante 
        $consulta = "SELECT e.carnet, TRIM(e.nombre) AS nombre
        FROM estudiante e
        WHERE e.carnet = $carnet";
        $estudiante = & $db->getRow($consulta);
        if ($db->isError($estudiante)) {
            $error = true;
            $mensaje = "Hubo un error al obtener los datos del estudiante";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Correlativo actual
            $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
            $correlativo_trans = & $db->getRow($consulta);
            if ($db->isError($correlativo_trans)) {
                $error = true;
                $mensaje = "Hubo un error al obtener el No. de Transaccion. " . mysql_error();
                $url = $_SERVER[HTTP_REFERER];
                $db->rollback();
            } else {

                // Transaccion 
                $error_RegistrarTransaccion = ingresarTransaccion($db, $user, 02, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo, 2, $correlativo_trans['no_transaccion'], $id_rubro, $id_variante_rubro);
                if (!$error_RegistrarTransaccion) {

                    // Error al almacenar la transacción de orden de pago
                } else {
                    $error = true;
                    $mensaje = "Hubo un error al registrar la transaccion de pago de Exámen Privado.";
                    $url = $_SERVER[HTTP_REFERER];
                    $db->rollback();
                }
            }
        }


        if (!$error) {

            // --------------------------------------------------	-------------------------------
            // XML COMO CADENA DE TEXTO (STRING)
            // ---------------------------------------------------------------------------------

            $orden_pago = "";
            $datos_estudiante = "<CARNET>" . $estudiante['carnet'] . "</CARNET>" .
                    "<UNIDAD>" . "02" . "</UNIDAD>" .
                    "<EXTENSION>" . str_pad($extension, 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
                    "<CARRERA>" . str_pad($carrera, 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
                    "<NOMBRE>" . $estudiante['nombre'] . "</NOMBRE>" .
                    "<MONTO>" . ($costo) . "</MONTO>";
            $detalle_orden_pago = "";
            $datos_orden_pago = "";

            $datos_orden_pago = "<DETALLE_ORDEN_PAGO>" .
                    "<ANIO_TEMPORADA>" . $anio . "</ANIO_TEMPORADA>" .
                    "<ID_RUBRO>" . $id_rubro . "</ID_RUBRO>" .
                    "<ID_VARIANTE_RUBRO>" . $id_variante_rubro . "</ID_VARIANTE_RUBRO>" .
                    "<TIPO_CURSO>" . "" . "</TIPO_CURSO>" .
                    "<CURSO>" . "" . "</CURSO>" .
                    "<SECCION>" . "" . "</SECCION>" .
                    "<SUBTOTAL>" . ($costo) . "</SUBTOTAL>" .
                    "</DETALLE_ORDEN_PAGO>";

            //echo $datos_orden_pago;

            $detalle_orden_pago = $detalle_orden_pago . $datos_orden_pago;

            // Generando el XML final de la orden de pago
            $orden_pago = "<GENERAR_ORDEN>" . $datos_estudiante . $detalle_orden_pago . "</GENERAR_ORDEN>";

            $wsdl = "http://arquitectura.usac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
            $client = new nusoap_client($wsdl, 'wsdl');
            $err = $client->getError();
            if ($err) {
                $mensaje = "Hubo un error en la ejecución del Proceso Orden de Pago";
                $url = $_SERVER[HTTP_REFERER];
                error($mensaje, $url);
            } else {

                // De Procesamiento de Datos
                $param = array(
                    'pxml' => $orden_pago
                );

                $res = $client->call('generarOrdenPago', $param);
                $datos_respuesta = xml2array(utf8_encode($res['result'])); // Si envian el XML con: encoding="utf-8" ó sin encoding (DE PROCESAMIENTO DE DATOS)

                if ($datos_respuesta[RESPUESTA][CODIGO_RESP] == 1) {

                    $anio_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 0, 4);
                    $mes_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 4, 2);
                    $dia_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 6, 2);

                    $error_BitacoraOrdenPago = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                    if (!$error_BitacoraOrdenPago) {

                        $error_registro_nueva_orden_pago = nuevaOrdenPago($db, $user, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], 2, 02, $extension, $carrera, $carnet, $id_rubro, $datos_respuesta['RESPUESTA']['CHECKSUM'], $costo, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                        $db->commit();

                        if (!$error_registro_nueva_orden_pago) {

                            // Registro de solicitud de Examen Privado
                            $registro = "INSERT INTO examen_privado
                            (anio, semestre, numero_privado, carnet, carrera, proyecto_graduacion, fecha_solicitud)
                            VALUES(
                                    $anio,
                                    $semestre,
                                    $numero_privado,
                                    $carnet,
                                    $carrera,
                                    '$proyecto_graduacion',
                                    NOW()
                            )";
                            //$solicitud_privado = & $db->Query($registro);
                            $correcto = true;
                            $conn = new mysqli($conf_db_host, $conf_db_user, $conf_db_passwd, $conf_db_database);
                            if ($conn->connect_errno) {
                                $correcto = false;
                            } else {
                                $correcto = $conn->query($registro);
                            }
                            if (/* $db->isError($solicitud_privado) */!$correcto) {
                                $error = true;
                                $mensaje = "No se ha podido registrar la solicitud de Examen Privado.";
                                $url = $_SERVER[HTTP_REFERER];
                                $db->rollback();
                            } else {
                                $db->commit();
                            }

                            // Actualizar orden de solicitud de examen privado
                            $orden_solicitud_privado = actualizarOrdenPagoPrivado($db, $anio, $semestre, $numero_privado, $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO']);
                            $db->commit();

                            $_SESSION['proceso_finalizado'] = "Se ha registrado su solicitud, por favor imprimala y presentala en secretaría con los documentos correspondientes.";

                            if (isset($_SESSION['proceso_finalizado'])) {

                                echo "
                                    <script>
                                        window.open('../privado/examen_privado_formulario.php','contenido');
                                    </script>
                                ";
                            }
                        } else {
                            $mensaje = "Hubo un error al registrar la Orden de Pago.";
                            $url = $_SERVER[HTTP_REFERER];
                            error($mensaje, $url);
                            $db->rollback();
                        }
                    } else {
                        $mensaje = "Hubo un error al actualizar Orden de Pago.";
                        $url = $_SERVER[HTTP_REFERER];
                        error($mensaje, $url);
                        $db->rollback();
                    }
                }
            }
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
