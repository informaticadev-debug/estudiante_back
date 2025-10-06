<?php

/*
  Documento  : proyecto_graduacion_solicitud_procesar.php
  Creado el  : 12 de junio de 2014, 10:06
  Author     : Angel Caal
  Description:
  Confirmación de la solicitud para entrar a revisión para su futura aprobación o desaprobación
  por parte del comite de proyecto de graduación.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

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

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $db->autoCommit(false);

        $carnet = $_SESSION['usuario'];
        $numero_solicitud = $_SESSION['numero_solicitud'];
        $asesores = $_POST['asesores'];
        $semestre = $_SESSION['semestre'];

        // Eliminar a los asesores propuestos anteriormente 
        $consulta = "DELETE FROM proyecto_graduacion_asesores
        WHERE numero_tema = $numero_solicitud AND aprobado = 2";
        $eliminar_asesores = & $db->Query($consulta);
        if ($db->isError($eliminar_asesores)) {
            $error = true;
            $mensaje = "Hubo un problema al eliminar a los asesores desaprobados." . mysql_error();
            $url = $_SERVER[HTTP_REFERER];
            $db->rollback();
        } else {

            // Registro de asesores propuestos por el estudiante; los asesores locales que son docentes de la facultad
            foreach ($asesores AS $as) {

                // Consulta para determinar el tipo de asesor
                // Asesor externo = 1
                // Asesor interno = 0
                $consulta = "SELECT a.asesor_externo
                FROM proyecto_graduacion_asesores a
                WHERE a.registro_personal = $as AND a.asesor_externo = 1
                LIMIT 1";
                $t_asesor = & $db->getRow($consulta);
                if ($t_asesor <> 0) {
                    $tipo_asesor = 1;
                } else {
                    $tipo_asesor = 0;
                }

                $consulta = "INSERT INTO proyecto_graduacion_asesores
                (numero_tema, registro_personal, asesor_externo)
                VALUES (
                    $numero_solicitud,
                    $as,
                    $tipo_asesor
                )";
                $asesores_locales = & $db->Query($consulta);
                if ($db->isError($asesores_locales)) {
                    $error = true;
                    $mensaje = "Hubo un problema durante el registro de los asesores locales.";
                    $url = $_SERVER[HTTP_REFERER];
                    $db->rollback();
                } else {

                    /* $consulta = "UPDATE proyecto_graduacion p
                      SET p.estado = 5
                      WHERE p.numero_tema = $numero_solicitud";
                      $regresar_estado = & $db->Query($consulta); */
                }
            }

            // Registro de asesor externo si existiera asesor externo
            if (isset($_SESSION['asesor_externo'])) {

                $anio_dos = DATE("y");

                // Registro de personal para asesor externo
                $consulta = "SELECT COUNT(*) + 1 AS total
				FROM docente d
				WHERE LEFT(d.registro_personal,2) = $anio_dos";
                $registro_ae = & $db->getRow($consulta);
                if ($db->isError($registro_ae)) {
                    $error = true;
                    $mensaje = "Hubo un error durante la generación del registro de personal para el asesor externo.";
                    $url = $_SERVER[HTTP_REFERER];
                    $db->rollback();
                } else {

                    $registro_personal_ae = $anio_dos . $semestre . "0" . $registro_ae[total];

                    foreach ($asesor_externo AS $as) {

                        // Registro de docente en la tabla de docentes como asesor
                        $consulta = "INSERT INTO docente
                        (registro_personal, nombre, apellido, titulo, email, status, pin, extension, usuario, telefono_celular, fecha_actualizacion)
                        VALUES (
                            $registro_personal_ae,
                            UCASE('$as[ae_nombres]'),
                            UCASE('$as[ae_apellidos]'),
                            '$as[ae_profesion]',
                            '$as[ae_email]',
                            'ALTA',
                            UCASE(LEFT(MD5('$as[ae_nombres]]'),8)),
                            0,
                            'docente',
                            '$as[ae_telefono]',
                            NOW()
                        )";
                        $registro_docente_ae = & $db->Query($consulta);
                        if ($db->isError($registro_docente_ae)) {
                            $error = true;
                            $mensaje = "Hubo un error al registrar los datos del asesor externo.";
                            $url = $_SERVER[HTTP_REFERER];
                            $db->rollback();
                        } else {

                            // Claves siempre a mayusculas
                            $db->Query("UPDATE docente d
                            SET d.pin = UCASE(d.pin)
                            WHERE d.pin != 'password'");

                            $consulta = "INSERT INTO proyecto_graduacion_asesores
                            (numero_tema, registro_personal, asesor_externo)
                            VALUES (
                                $numero_solicitud,
                                $registro_personal_ae,
                                1
                             )";
                            $registro_ae = & $db->Query($consulta);
                            if ($db->isError($registro_ae)) {
                                $error = true;
                                $mensaje = "Hubo un problema en el registro del asesor externo.";
                                $url = $_SERVER[HTTP_REFERER];
                                $db->rollback();
                            }
                        }
                    }
                }
            }
        }

        if ($eliminar_asesores == 1) {

            $db->commit();
        }

        if (!$error) {

            $proceso_finalizado = "Se han registrado a los nuevos asesores, estos serán verificados por el comité, se le notificará el estado.";
            $_SESSION['proceso_finalizado'] = $proceso_finalizado;
            echo "
                <script>
                    window.open('../proyecto_graduacion/proyecto_graduacion_gestion.php','contenido');
                </script>
            ";

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_error'])) {
                $mensaje_error = $_SESSION['mensaje_error'];
                $template->setVariable(array(
                    'mensaje_error' => "<div id='base_error_proceso'>
                        <div id='error'>
                            $mensaje_error<br><br>
                            <div id='acciones'>
                                <input id='btn_rojo' type='button' value='Aceptar' OnClick='window.location.reload()' autofocus>
                            </div>
                        </div>
                    </div>"
                ));
                unset($_SESSION['mensaje_error']);
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