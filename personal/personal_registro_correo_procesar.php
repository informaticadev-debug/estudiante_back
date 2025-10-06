<?php

/*
  Document   : personal_registro_correo_procesar.php
  Created on : 27-Oct-2015, 14:27
  Author     : Angel Caal
  Description:
  -> Enviar correo al destinatario, con las instrucciones para registro de correo institucional
 */

require_once 'DB.php';
require_once 'HTML/Template/Sigma.php';
require_once '../misc/funciones.php';
require_once '../lib/nusoap.php';
session_start();

if (isset($_SESSION['usuario'])) {

    $user = $_SESSION['user'];
    $pass = $_SESSION['pass'];
    $host = $_SESSION['host'];

    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::Connect($dsn);
    if (DB::isError($db)) {
        $mensaje = "La Plataforma esta temporalmente fuera de línea, por favor intente en un momento. Si el problema persiste comuníquese con el Programador (Angel Caal | 3070 1746)";
        echo $mensaje;
    } else {

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $error = false;
        $db->autoCommit(false);

        // Datos para actualizar
        $carnet = $_SESSION['usuario'];
        $departamento = $_POST['departamento'];
        $municipio = $_POST['municipio'];
        $zona = $_POST['zona'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];
        $celular = $_POST['celular'];
        $correo_personal = $_POST['correo_personal'];
        $fecha_nacimiento = $_POST['anio_nacimiento'] . "-" . $_POST['mes_nacimiento'] . "-" . $_POST['dia_nacimiento'];
        $dpi = $_POST['dpi1'] . " " . $_POST['dpi2'] . " " . $_POST['dpi3'];
        $correo_institucional = $_POST['correo_institucional'];
        $contrasena_temporal = substr(MD5($carnet), 9, 8);

        // Actualización de datos para el docente 
        $consulta = "UPDATE estudiante e
        SET e.departamento = '$departamento', e.municipio = '$municipio', e.zona = '$zona', e.direccion = '$direccion', e.telefono = '$telefono', e.celular = '$celular', e.fecha_nacimiento = '$fecha_nacimiento',
        e.email_fda = '$correo_personal', e.fecha_actualizacion = NOW()
        WHERE e.carnet = $carnet";
        $actualizar_datos = & $db->Query($consulta);
        if ($db->isError($actualizar_datos)) {
            $error = true;
            $mensaje = "Hubo un problema al actualizar los datos.";
            $url = $_SERVER[HTTP_REFERER];
            $db->rollback();
        } else {

            $db->commit();

            // Enviar correo a cuenta personal para registro de datos de acceso
            $wsdl = "http://www.jaguatemala.org/ws/ws.php?wsdl";
            $client = new nusoap_client($wsdl, 'wsdl');
            $err = $client->getError();

            $asunto = "Datos de acceso";
            $mensaje = "<b>Correo institucional:</b> <br><br>
                    Ha sido creado el siguiente correo institucional en la Facultad de Arquitectura $correo_institucional, su cuenta sera dada de alta en un 
                    tiempo estimado de 12 horas, por favor conserve este correo para poder hacer uso de los datos de acceso.<br><br>
                    
                    Datos de acceso<br>
                    Url: https://mail.google.com/<br>
                    Usuario: $correo_institucional<br>
                    Contraseña temporal: $contrasena_temporal<br><br>
                        
                    <font color='#B40404'>* Adjunto encontrará las Normas y Políticas de uso del correo institucional</font><br><br>
                    
                    Atentamente ID Y ENSEÑAD A TODOS.
                    ";

            $data = array(
                'nombre' => "$carnet",
                'nombre_remitente' => "Adminstración Correo Institucional",
                'correo' => "$correo_personal",
                'correo_remitente' => "administrador.correo@farusac.edu.gt",
                'asunto' => $asunto,
                'msj' => $mensaje
            );

            $res = $client->call('gestor_correo_institucional_farusac', $data);
        }

        if (!$error) {

            $_SESSION['proceso_finalizado'] = "El ha creado correctamente su correo institucional, por favor ingrese a $correo_personal para leer las instrucciones de registro.";
            echo "<script>
                window.open('../menus/inicio.php','_parent');
            </script>";
        }

        $db->disconnect();
    }
} else {
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    echo $mensaje;
    echo "
	<script>
		
	</script>
	";
}
?>