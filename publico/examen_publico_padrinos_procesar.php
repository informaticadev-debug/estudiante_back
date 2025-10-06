<?php

/*
  EXAMEN PRIVADO
  -> Proceso para obtener examen publico
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();
if (isset($_SESSION[usuario])) {

    $errorLogin = false;

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

        // Datos de los padrinos
        $carnet = $_SESSION['usuario'];
        $profesion = $_POST['profesion'];
        $padrino = $_POST['padrino'];
        $colegiado = $_POST['colegiado'];
        $data_padrinos = $profesion . $padrino . $colegiado;
        $tipo_examen = $_POST['tipo_examen'];

        $data = array_keys($profesion);
        foreach ($data AS $da) {
            $padrinos[] = array("profesion" => $profesion[$da], "padrino" => $padrino[$da], "colegiado" => $colegiado[$da]);
        }

        // Numero de publico 
        $consulta = "SELECT *
		FROM examen_publico p
		WHERE p.carnet = $carnet AND p.estado = 2";
        $solicitud = & $db->getRow($consulta);
        if ($db->isError($solicitud)) {
            $error = true;
            $mensaje = "Hubo un problema al obtener los datos de la solicitud de examen publico.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Actualizar el estado de la solicitud
            $consulta = "UPDATE examen_publico p
			SET p.estado = 3, p.tipo_examen = $tipo_examen
			WHERE p.carnet = $carnet AND p.estado = 2";
            $actualizar_estado = & $db->Query($consulta);
            if ($db->isError($actualizar_estado)) {
                $error = true;
                $mensaje = "Hubo un problema al actualizar el estado de la solicitud de examen publico.";
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
                        $mensaje = "Hubo un error al registrar a los padrinos.";
                        $url = $_SERVER[HTTP_REFERER];
                        $db->rollback();
                    } else {
                        $db->commit();
                    }
                }
            }
        }

        if (!$error) {

            $_SESSION['proceso_finalizado'] = "Se ha registrado su solicitud, por favor imprimala y presentala en secretaría con los documentos correspondientes.";

            if (isset($_SESSION['proceso_finalizado'])) {

                echo "
                    <script>
                        window.open('../proyecto_graduacion/proyecto_graduacion_gestion.php','contenido');
                    </script>
                ";
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