<?php

/*
  Document   : encuesta_nuevaencuesta_procesar.php
  Created on : 16-mar-2015, 23:30
  Author     : Angel Caal
  Description:
  -> Almacenar la encuesta respondida
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
        $db->autoCommit(false);
        $error = false;

        $usuario = $_SESSION['usuario'];
        $encuesta = $_SESSION['encuesta'];
        $respuestas = $_POST['respuestas'];

        foreach ($respuestas AS $re => $keys) {
            foreach ($keys AS $key => $k) {
                if (!empty($k)) {

                    $key = $key + 1;

                    if ($key == '$k') {
                        $detalle_respuesta = "NULL";
                    } else {
                        $detalle_respuesta = $k;
                    }

                    // Registro de la encuesta respondida 
                    $consulta = "INSERT INTO encuestas_respuestas
					(encuesta, pregunta, respuesta, detalle_respuesta, fecha_creacion, usuario_creacion)
					VALUES (
						'$encuesta',
						$re,
						'$k',
						IF($key = 1,NULL,$key),
						NOW(),
						$usuario
					)";
                    $registrar_encuesta = & $db->Query($consulta);
                    if ($db->isError($registrar_encuesta)) {
                        $error = true;

                        if (mysql_errno() == 1062) {
                            $mensaje = "La encuesta ha sido registrada anteriormente, no puede volver a registrarse.";
                        } else {
                            $mensaje = "Hubo un problema al registrar la encuesta. " . mysql_error();
                        }

                        $url = $_SERVER[HTTP_REFERER];
                        $db->rollback();
                    } else {

                        // Actualizar que ha completado la encuesta
                        $consulta = "UPDATE encuestas_asignacion a
						SET a.completada = 1
						WHERE a.carnet = $usuario AND a.encuesta = '$encuesta'";
                        $completada = & $db->Query($consulta);
                        if ($db->isError($completada)) {
                            $error = true;
                            $mensaje = "Hubo un problema al marcar esta encuesta completada.";
                            $url = $_SERVER[HTTP_REFERER];
                        } else {
                            $db->commit();
                        }
                    }
                }
            }
        }

        if (!$error) {

            $_SESSION['proceso_finalizado'] = "Gracias por responder la encuesta, su respuesta ayuda al mejoramiento de tu facultad.";
            echo "
                <script>
                    window.open('../menus/inicio.php','_parent');
                </script>                    
            ";
        }

        if ($error) {
            error($mensaje, $url);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    errorLoginInicio($mensaje);
}
?>