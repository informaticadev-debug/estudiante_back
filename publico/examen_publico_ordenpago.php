<?php

/*
  EXAMEN Publico
  -> Proceso para obtener examen Publico
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

    function ingresarTransaccion($db, $unidad, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo, $tipoPago, $noTrans, $rubro, $varianteRubro)
    {

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
        $resultado_ingreso = &$db->query($consulta_ingreso);
        if ($db->isError($resultado_ingreso)) {
            //echo $consulta_ingreso . "<br>";
            $error = true;
        }

        return $error;
    }

    function ingresarTransaccionTitulo($db, $unidad, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo_tit, $costo_reg_tit, $tipoPago, $noTrans, $rubro, $varianteRubro_tit, $varianteRubro_reg_tit)
    {

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
            ,$varianteRubro_tit
            ,NULL
            ,$costo_tit
            ,1
            ,NOW()
            ,'estudiante'
            )
	";
        $resultado_ingreso = &$db->query($consulta_ingreso);
        if ($db->isError($resultado_ingreso)) {
            //echo $consulta_ingreso . "<br>";
            $error = true;
        } else {

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
			,$varianteRubro_reg_tit
			,NULL
			,$costo_reg_tit
			,1
			,NOW()
			,'estudiante'
			)
                    ";
            $resultado_ingreso = &$db->Query($consulta_ingreso);
            if ($db->isError($resultado_ingreso)) {
                $error = true;
            }
        }

        return $error;
    }

    function ingresarTransaccionToga($db, $unidad, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo_toga, $tipoPago, $noTrans, $rubro, $varianteRubro_toga)
    {

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
			,$varianteRubro_toga
			,NULL
			,$costo_toga
			,1
			,NOW()
			,'estudiante'
			)
		";
        $resultado_ingreso = &$db->query($consulta_ingreso);
        if ($db->isError($resultado_ingreso)) {
            echo $consulta_ingreso . "<br>";
            $error = true;
        }

        return $error;
    }

    function ingresarTransaccionTogaProfesional($db, $unidad, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo_toga, $tipoPago, $noTrans, $rubro, $varianteRubro_toga)
    {

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
			,$varianteRubro_toga
			,NULL
			,$costo_toga
			,1
			,NOW()
			,'estudiante'
			)
		";
        $resultado_ingreso = &$db->query($consulta_ingreso);
        if ($db->isError($resultado_ingreso)) {
            echo $consulta_ingreso . "<br>";
            $error = true;
        }

        return $error;
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

        $carnet = $_SESSION['usuario'];

        if ($carnet < 200700000) {
            $extension = "0";
        } else {
            $extension = $_SESSION['extension'];
        }

        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = 1;
        $costo = "250.00";
        $costo_tit = "100.00";
        $costo_reg_tit = "15.00";
        $costo_total_tit = "115.00";
        $costo_toga = "50.00";
        $costo_toga_profesional = "70.00";

        $carrera = $_SESSION['carrera'];
        $id_rubro = 9;
        $id_rubro_tit = 41;
        $id_rubro_toga = 40;
        $id_variante_rubro = 2;
        $id_variante_rubro_tit = 1;
        $id_variante_rubro_reg_tit = 3;
        $id_variante_rubro_toga = 1;
        $id_variante_rubro_toga_profesional = 2;

        // Sesion con los datos a ingresar 
        $nombre = $_SESSION['nombre_estudiante'];
        $dpi = $_SESSION['dpi1'] . " " . $_SESSION['dpi2'] . " " . $_SESSION['dpi3'];
        $email_fda = $_SESSION['email_fda'];
        $telefono = $_SESSION['telefono'];
        $direccion = $_SESSION['direccion'];

        // Datos de los padrinos
        $profesion = $_POST['profesion'];
        $padrino = $_POST['padrino'];
        $colegiado = $_POST['colegiado'];
        $data_padrinos = $profesion . $padrino . $colegiado;

        /* $data = array_keys($profesion);
          foreach ($data AS $da) {
          $padrinos[] = array("profesion" => $profesion[$da], "padrino" => $padrino[$da], "colegiado" => $colegiado[$da]);
          } */

        /* // Datos Estudiante 
          $consulta = "SELECT e.carnet, TRIM(e.nombre) AS nombre
          FROM estudiante e
          WHERE e.carnet = $carnet";
          $estudiante = & $db->getRow($consulta);

          // Creacion de la solicitud de exmane publico
          $consulta = "INSERT INTO examen_publico
          (extension, anio, semestre, carrera, carnet, fecha_solicitud)
          VALUES(
          $extension,
          $anio,
          $semestre,
          $carrera,
          $carnet,
          NOW()
          )";
          $registro_solicitud = & $db->Query($consulta);
          if ($db->isError($registro_solicitud)) {
          $error = true;
          $mensaje = "Hubo un error al registrar la solicitud para Examen Público.";
          $url = $_SERVER[HTTP_REFERER];
          $db->rollback();
          } else {

          // Seleccionar la solicitud creada para almacenar los padrinos
          $consulta = "SELECT e.numero_publico
          FROM examen_publico e
          WHERE e.anio = $anio AND e.carnet = $carnet AND e.carrera = $carrera";
          $solicitud = & $db->getRow($consulta);
          if ($db->isError($solicitud)) {
          $error = true;
          $mensaje = "Hubo un error al consultar la solicitud realizada";
          $url = $_SERVER[HTTP_REFERER];
          $db->rollback();
          } else {

          // Registro de Padrinos
          foreach ($padrinos AS $p) {

          $consulta = "INSERT INTO examen_publico_padrinos
          (numero_publico, profesion, nombre, colegiado)
          VALUES(
          $solicitud[numero_publico],
          '$p[profesion]',
          '$p[padrino]',
          '$p[colegiado]'
          )";
          $registro_padrino = & $db->Query($consulta);
          if ($db->isError($registro_padrino)) {
          $error = true;
          $mensaje = "Hubo un error al registrar a los padrinos." . mysql_error();
          $url = $_SERVER[HTTP_REFERER];
          $db->rollback();
          }
          }

          // Actualizacion de datos de estudiante
          $consulta = "UPDATE estudiante e
          SET e.dpi = '$dpi', e.email_fda = '$email_fda', e.telefono = $telefono, e.direccion = '$direccion',
          e.fecha_actualizacion = NOW()
          WHERE e.carnet = $carnet";
          $actualizar_datos = & $db->Query($consulta);
          if ($db->isError($actualizar_datos)) {
          $error = true;
          $mensaje = "Hubo un error al actualizar los datos personales." . mysql_error();
          $url = $_SERVER[HTTP_REFERER];
          $db->rollback();
          } else { */

        // Datos Estudiante 
        $consulta = "SELECT e.carnet, TRIM(e.nombre) AS nombre
        FROM estudiante e
        WHERE e.carnet = $carnet";
        $estudiante = &$db->getRow($consulta);

        // Correlativo para asignar a examen publico
        $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
        $correlativo_trans = &$db->getRow($consulta);
        if ($db->isError($correlativo_trans)) {
            $error = true;
            $mensaje = "Hubo un error al obtener el No. de Transaccion.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Transaccion Examen Publico
            $error_RegistrarTransaccion = ingresarTransaccion($db, 02, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo, 2, $correlativo_trans['no_transaccion'], $id_rubro, $id_variante_rubro);
            if ($error_RegistrarTransaccion) {
                $error = true;
                $mensaje = "Hubo un error al registrar la transacción de pago de Examen Publico.";
                $url = $_SERVER[HTTP_REFERER];
                $db->rollback();
            } else {

                // Correlativo para asignar a Pago de Titulo
                /*$consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
                $correlativo_trans_tit = & $db->getRow($consulta);
                if ($db->isError($correlativo_trans_tit)) {
                    $error = true;
                    $mensaje = "Hubo un error al verificar el numero de transacción para generar pago de Titulo.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    // Transaccion Titulo
                    $error_RegistrarTransaccion_tit = ingresarTransaccionTitulo($db, 02, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo_tit, $costo_reg_tit, 2, $correlativo_trans_tit['no_transaccion'], $id_rubro_tit, $id_variante_rubro_tit, $id_variante_rubro_reg_tit);
                    if ($error_RegistrarTransaccion_tit) {
                        $error = true;
                        $mensaje = "Hubo un error al registrar la transacción de pago de Titulo.";
                        $url = $_SERVER[HTTP_REFERER];
                        $db->rollback();
                    } else {*/

                // Correlativo para asignar a Pago de Toga
                $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
                $correlativo_trans_toga = &$db->getRow($consulta);
                if ($db->isError($correlativo_trans_toga)) {
                    $error = true;
                    $mensaje = "Hubo un error al verificar el numero de transacción para generar pago de Toga.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    // Transaccion Pago de toga
                    $error_RegistrarTransaccion_toga = ingresarTransaccionToga($db, 02, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo_toga, 2, $correlativo_trans_toga['no_transaccion'], $id_rubro_toga, $id_variante_rubro_toga);
                    if ($error_RegistrarTransaccion_toga) {
                        $error = true;
                        $mensaje = "Hubo un error al registrar la transacción de pago de Toga.";
                        $url = $_SERVER[HTTP_REFERER];
                        $db->rollback();
                    } else {

                        // Correlativo para asignar a Pago de Toga
                        $consulta = "SELECT getCorrelativoTransaccion($anio,$semestre,$evaluacion,$carnet) as no_transaccion";
                        $correlativo_trans_toga_profesional = &$db->getRow($consulta);
                        if ($db->isError($correlativo_trans_toga_profesional)) {
                            $error = true;
                            $mensaje = "Hubo un error al verificar el numero de transacción para generar pago de Toga.";
                            $url = $_SERVER[HTTP_REFERER];
                        } else {

                            // Transaccion Pago de toga profesionañ 
                            $error_RegistrarTransaccion_toga_profesional = ingresarTransaccionTogaProfesional($db, 02, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $costo_toga_profesional, 2, $correlativo_trans_toga_profesional['no_transaccion'], $id_rubro_toga, $id_variante_rubro_toga_profesional);
                            if ($error_RegistrarTransaccion_toga_profesional) {
                                $error = true;
                                $mensaje = "Hubo un error al registrar la transacción de pago de Toga para el profesional no docente de la Facultad.";
                                $url = $_SERVER[HTTP_REFERER];
                                $db->rollback();
                            } else {
                                $db->commit();
                            }
                        }
                    }
                }
                //}
                //}
            }
        }
        /* }
          }
          } */

        if (!$error) {

            // ---------------------------------------------------------------------------------/
            // XML COMO CADENA DE TEXTO (STRING)
            // ---------------------------------------------------------------------------------/

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

            $orden_pago_tit = "";
            $datos_estudiante_tit = "<CARNET>" . $estudiante['carnet'] . "</CARNET>" .
                "<UNIDAD>" . "02" . "</UNIDAD>" .
                "<EXTENSION>" . str_pad($extension, 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
                "<CARRERA>" . str_pad($carrera, 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
                "<NOMBRE>" . $estudiante['nombre'] . "</NOMBRE>" .
                "<MONTO>" . ($costo_total_tit) . "</MONTO>";
            $detalle_orden_pago_tit = "";
            $datos_orden_pago_tit = "";

            $datos_orden_pago_tit = "<DETALLE_ORDEN_PAGO>" .
                "<ANIO_TEMPORADA>" . $anio . "</ANIO_TEMPORADA>" .
                "<ID_RUBRO>" . $id_rubro_tit . "</ID_RUBRO>" .
                "<ID_VARIANTE_RUBRO>" . $id_variante_rubro_tit . "</ID_VARIANTE_RUBRO>" .
                "<TIPO_CURSO>" . "" . "</TIPO_CURSO>" .
                "<CURSO>" . "" . "</CURSO>" .
                "<SECCION>" . "" . "</SECCION>" .
                "<SUBTOTAL>" . ($costo_tit) . "</SUBTOTAL>" .
                "</DETALLE_ORDEN_PAGO>" .
                "<DETALLE_ORDEN_PAGO>" .
                "<ANIO_TEMPORADA>" . $anio . "</ANIO_TEMPORADA>" .
                "<ID_RUBRO>" . $id_rubro_tit . "</ID_RUBRO>" .
                "<ID_VARIANTE_RUBRO>" . $id_variante_rubro_reg_tit . "</ID_VARIANTE_RUBRO>" .
                "<TIPO_CURSO>" . "" . "</TIPO_CURSO>" .
                "<CURSO>" . "" . "</CURSO>" .
                "<SECCION>" . "" . "</SECCION>" .
                "<SUBTOTAL>" . ($costo_reg_tit) . "</SUBTOTAL>" .
                "</DETALLE_ORDEN_PAGO>";

            $orden_pago_toga = "";
            $datos_estudiante_toga = "<CARNET>" . $estudiante['carnet'] . "</CARNET>" .
                "<UNIDAD>" . "02" . "</UNIDAD>" .
                "<EXTENSION>" . str_pad($extension, 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
                "<CARRERA>" . str_pad($carrera, 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
                "<NOMBRE>" . $estudiante['nombre'] . "</NOMBRE>" .
                "<MONTO>" . ($costo_toga) . "</MONTO>";
            $detalle_orden_pago_toga = "";
            $datos_orden_pago_toga = "";

            $datos_orden_pago_toga = "<DETALLE_ORDEN_PAGO>" .
                "<ANIO_TEMPORADA>" . $anio . "</ANIO_TEMPORADA>" .
                "<ID_RUBRO>" . $id_rubro_toga . "</ID_RUBRO>" .
                "<ID_VARIANTE_RUBRO>" . $id_variante_rubro_toga . "</ID_VARIANTE_RUBRO>" .
                "<TIPO_CURSO>" . "" . "</TIPO_CURSO>" .
                "<CURSO>" . "" . "</CURSO>" .
                "<SECCION>" . "" . "</SECCION>" .
                "<SUBTOTAL>" . ($costo_toga) . "</SUBTOTAL>" .
                "</DETALLE_ORDEN_PAGO>";

            $orden_pago_toga_profesional = "";
            $datos_estudiante_toga_profesional = "<CARNET>" . $estudiante['carnet'] . "</CARNET>" .
                "<UNIDAD>" . "02" . "</UNIDAD>" .
                "<EXTENSION>" . str_pad($extension, 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
                "<CARRERA>" . str_pad($carrera, 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
                "<NOMBRE>" . $estudiante['nombre'] . "</NOMBRE>" .
                "<MONTO>" . ($costo_toga_profesional) . "</MONTO>";
            $detalle_orden_pago_toga_profesional = "";
            $datos_orden_pago_toga_profesional = "";

            $datos_orden_pago_toga_profesional = "<DETALLE_ORDEN_PAGO>" .
                "<ANIO_TEMPORADA>" . $anio . "</ANIO_TEMPORADA>" .
                "<ID_RUBRO>" . $id_rubro_toga . "</ID_RUBRO>" .
                "<ID_VARIANTE_RUBRO>" . $id_variante_rubro_toga_profesional . "</ID_VARIANTE_RUBRO>" .
                "<TIPO_CURSO>" . "" . "</TIPO_CURSO>" .
                "<CURSO>" . "" . "</CURSO>" .
                "<SECCION>" . "" . "</SECCION>" .
                "<SUBTOTAL>" . ($costo_toga_profesional) . "</SUBTOTAL>" .
                "</DETALLE_ORDEN_PAGO>";

            $detalle_orden_pago = $detalle_orden_pago . $datos_orden_pago;
            //$detalle_orden_pago_tit = $detalle_orden_pago_tit . $datos_orden_pago_tit;
            $detalle_orden_pago_toga = $detalle_orden_pago_toga . $datos_orden_pago_toga;
            $detalle_orden_pago_toga_profesional = $detalle_orden_pago_toga_profesional . $datos_orden_pago_toga_profesional;

            // Generando el XML final de la orden de pago
            $orden_pago = "<GENERAR_ORDEN>" . $datos_estudiante . $detalle_orden_pago . "</GENERAR_ORDEN>";
            $orden_pago_tit = "<GENERAR_ORDEN>" . $datos_estudiante_tit . $detalle_orden_pago_tit . "</GENERAR_ORDEN>";
            $orden_pago_toga = "<GENERAR_ORDEN>" . $datos_estudiante_toga . $detalle_orden_pago_toga . "</GENERAR_ORDEN>";
            $orden_pago_toga_profesional = "<GENERAR_ORDEN>" . $datos_estudiante_toga_profesional . $detalle_orden_pago_toga_profesional . "</GENERAR_ORDEN>";

            //$wsdl = "http://arquitectura.farusac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
            $wsdl = "http://arquitectura.farusac.edu.gt/ws/WSGeneracionOrdenPagoSoapHttpPort.xml";
            $client = new nusoap_client($wsdl, 'wsdl');
            $err = $client->getError();
            if ($err) {
                $error = true;
                $mensaje = "Hubo un error en la ejecuci?n del Proceso Orden de Pago";
                $url = $_SERVER[HTTP_REFERER];
                error($mensaje, $url);
            }

            // De Procesamiento de Datos
            $param = array(
                'pxml' => $orden_pago
            );

            $param_tit = array(
                'pxml' => $orden_pago_tit
            );

            $param_toga = array(
                'pxml' => $orden_pago_toga
            );

            $param_toga_profesional = array(
                'pxml' => $orden_pago_toga_profesional
            );

            $res = $client->call('generarOrdenPago', $param);
            //$res_tit = $client->call('generarOrdenPago', $param_tit);
            $res_toga = $client->call('generarOrdenPago', $param_toga);
            $res_toga_profesional = $client->call('generarOrdenPago', $param_toga_profesional);
            $datos_respuesta = xml2array(utf8_encode($res['result'])); // Si envian el XML con: encoding="utf-8" ? sin encoding (DE PROCESAMIENTO DE DATOS)            
            //$datos_respuesta_tit = xml2array(utf8_encode($res_tit['result'])); // Si envian el XML con: encoding="utf-8" ? sin encoding (DE PROCESAMIENTO DE DATOS)
            $datos_respuesta_toga = xml2array(utf8_encode($res_toga['result'])); // Si envian el XML con: encoding="utf-8" ? sin encoding (DE PROCESAMIENTO DE DATOS)
            $datos_respuesta_toga_profesional = xml2array(utf8_encode($res_toga_profesional['result'])); // Si envian el XML con: encoding="utf-8" ? sin encoding (DE PROCESAMIENTO DE DATOS)
            //print_r($datos_respuesta);
            //print_r($datos_respuesta_tit);

            if ($datos_respuesta[RESPUESTA][CODIGO_RESP] == 1 /*AND $datos_respuesta_tit[RESPUESTA][CODIGO_RESP] == 1*/and $datos_respuesta_toga[RESPUESTA][CODIGO_RESP] == 1 and $datos_respuesta_toga_profesional[RESPUESTA][CODIGO_RESP] == 1) {

                $anio_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 0, 4);
                $mes_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 4, 2);
                $dia_orden_pago = substr($datos_respuesta['RESPUESTA']['FECHA'], 6, 2);

                $error_BitacoraOrdenPago = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], $carnet, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                //$error_BitacoraOrdenPago_tit = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans_tit['no_transaccion'], $carnet, $datos_respuesta_tit['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta_tit['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                $error_BitacoraOrdenPago_toga = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans_toga['no_transaccion'], $carnet, $datos_respuesta_toga['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta_toga['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                $error_BitacoraOrdenPago_toga_profesional = registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $correlativo_trans_toga_profesional['no_transaccion'], $carnet, $datos_respuesta_toga_profesional['RESPUESTA']['ID_ORDEN_PAGO'], $datos_respuesta_toga_profesional['RESPUESTA']['CHECKSUM'], $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);

                if (!$error_BitacoraOrdenPago and /*! $error_BitacoraOrdenPago_tit AND*/!$error_BitacoraOrdenPago_toga and !$error_BitacoraOrdenPago_toga_profesional) {

                    $error_registro_nueva_orden_pago = nuevaOrdenPago($db, $user, $datos_respuesta['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans['no_transaccion'], 2, 02, $extension, $carrera, $carnet, $id_rubro, $datos_respuesta['RESPUESTA']['CHECKSUM'], $costo, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                    //$error_registro_nueva_orden_pago_tit = nuevaOrdenPago($db, $user, $datos_respuesta_tit['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans_tit['no_transaccion'], 2, 02, $extension, $carrera, $carnet, $id_rubro_tit, $datos_respuesta_tit['RESPUESTA']['CHECKSUM'], $costo_total_tit, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                    $error_registro_nueva_orden_pago_toga = nuevaOrdenPago($db, $user, $datos_respuesta_toga['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans_toga['no_transaccion'], 2, 02, $extension, $carrera, $carnet, $id_rubro_toga, $datos_respuesta_toga['RESPUESTA']['CHECKSUM'], $costo_toga, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                    $error_registro_nueva_orden_pago_toga_profesional = nuevaOrdenPago($db, $user, $datos_respuesta_toga_profesional['RESPUESTA']['ID_ORDEN_PAGO'], $anio, $semestre, $evaluacion, $correlativo_trans_toga_profesional['no_transaccion'], 2, 02, $extension, $carrera, $carnet, $id_rubro_toga, $datos_respuesta_toga_profesional['RESPUESTA']['CHECKSUM'], $costo_toga_profesional, $anio_orden_pago . "-" . $mes_orden_pago . "-" . $dia_orden_pago);
                    $db->commit();


                    if (!$error_registro_nueva_orden_pago /*AND ! $error_registro_nueva_orden_pago_tit*/and !$error_registro_nueva_orden_pago_toga and !$error_registro_nueva_orden_pago_toga_profesional) {

                        $db->commit();

                        $consulta_ordenesPago_otros = "SELECT o.evaluacion, o.rubro AS codigo, 
                        IF(
                            o.rubro = 9 AND d.variante_rubro = 1,
                            CONCAT('Examenes Generales ',o.anio, ' Examen Privado'),
                                IF(
                                    o.rubro = 9 AND d.variante_rubro = 2,
                                    CONCAT('Examenes Generales ',o.anio, ' Examen Público'),
                                    IF(
                                        o.rubro = 41 AND d.variante_rubro = 1,
                                        'Impresión de Titulo y registro de titulo (Licenciaturas)',
                                    IF(
                                        o.rubro = 41 AND d.variante_rubro = 3,
                                        'Registro de Titulo',
                                        IF (
                                            o.rubro = 40 AND d.variante_rubro = 1,
                                            'Alquiler de togas (Estudiantes)',
                                            IF (
                                                o.rubro = 40 AND d.variante_rubro = 2,
                                                'Alquiler de togas (Arquitectos - USAC, que no son docentes)', ''
                                            )
                                        )
                                    )
                                )
                            )
                        ) AS asignatura,
                        NULL AS seccion, o.orden_pago
                        FROM orden_pago o
                        INNER JOIN detalle_orden_pago d
                        ON d.orden_pago = o.orden_pago
                        WHERE o.extension = $extension AND o.anio = $anio AND o.semestre = $semestre AND o.carnet = $carnet
                        AND o.no_boleta_deposito IS NULL AND o.fecha_certificacion_banco IS NULL AND o.usuario_certificacion_banco IS NULL
                        AND o.rubro IN(9,40,41,19)
                        GROUP BY o.orden_pago";
                        $ordenes_pago = &$db->getAll($consulta_ordenesPago_otros);
                        if ($db->isError($ordenes_pago)) {
                            $error = true;
                            $mensaje = "Hubo un error al determinar el estado de las ordenes de pago pendientes.";
                            $url = $_SERVER[HTTP_REFERER];
                            error($mensaje, $url);
                        } else {

                            // Actualizar estado para solicitar examen publico
                            $consulta = "UPDATE examen_publico p
							SET p.estado = 2
							WHERE p.carnet = $carnet AND p.carrera = $carrera";
                            $cambiar_estado = &$db->Query($consulta);
                            if ($db->isError($cambiar_estado)) {
                                $error = true;
                                $mensaje = "Hubo un problema al cambiar el estado de solicitud de examen público";
                                $url = $_SERVER[HTTP_REFERER];
                                error($mensaje, $url);
                            } else {
                                $db->commit();
                            }
                        }

                        // Cargando la pagina del proceso finalizado con Exito.
                        $template = new HTML_Template_Sigma('../templates');
                        $template->loadTemplateFile('examen_publico_ordenpago.html');

                        // Datos Ordenes de Pago
                        if (count($ordenes_pago) <> 0) {

                            foreach ($ordenes_pago as $op) {

                                if ($op[evaluacion] == 1) {
                                    $template->setVariable(
                                        array(
                                            'evaluacion' => "S",
                                            'id_evaluacion' => $op[evaluacion]
                                        )
                                    );
                                }

                                $template->setVariable(
                                    array(
                                        'codigo' => $op[codigo],
                                        'asignatura' => $op[asignatura],
                                        'seccion' => $op[seccion],
                                        'orden_pago' => $op[orden_pago]
                                    )
                                );
                                $template->parse('ordenes_pago');
                            }
                        } else {

                            $template->setVariable(
                                array(
                                    'sin_ordenes_pendientes' => "<div id='msj_naranja'>No hay Ordenes de Pago pendientes de de cancelar.</div>"
                                )
                            );
                            $template->parse('ordenes_pago');
                        }

                        if (isset($_SESSION['carrera'])) {
                            unset($_SESSION['carrera']);
                            unset($_SESSION['dpi1']);
                            unset($_SESSION['dpi2']);
                            unset($_SESSION['dpi3']);
                            unset($_SESSION['email_fda']);
                            unset($_SESSION['telefono']);
                            unset($_SESSION['celular']);
                            unset($_SESSION['nit']);
                            unset($_SESSION['direccion']);
                            unset($_SESSION['padrinos']);
                        }

                        $template->show();
                        exit();
                    } else {
                        $error = true;
                        $mensaje = "Hubo un error al registrar la Orden de Pago.";
                        $url = $_SERVER[HTTP_REFERER];
                        $db->rollback();
                    }
                } else {
                    $error = true;
                    $mensaje = "Hubo un error al actualizar Orden de Pago.";
                    $url = $_SERVER[HTTP_REFERER];
                    $db->rollback();
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