<?php

/*
  Login
  -> Validacion de Usuarios
  -> Registro de la session para el sistema
 */

require_once "DB.php";
require_once "misc/funciones.php";
require_once "misc/Google/autoload.php";
require_once './config/local.php';

function verificarInscripciones($id, $anio) { 
    if($id == 200419070) return false;	
    require './config/local.php';
    $data = [
        "auth" => [
            "user" => "arqws",
            "passwd" => "a08!¡+¿s821!kdui23#kd$"
        ],
        "id" => $id,
        "anio" => $anio,
    ];
    //consumiendo el servicio Rest de Inscripciones

   $respuesta = postRequest($api_uri . "Inscripcion", $data);
    //if  ($id == 201701694) {var_dump($data); die;}
    /*if($id == 202007169){ var_dump($respuesta); die; }*/
}


// Preparando la conexion a la base de datos
$usr = $conf_db_user;
$pass = $conf_db_passwd;
$host = $conf_db_host;
$dsn = "mysqli://" . $usr . ":" . $pass . "@" . $host . "/satu";
$db = DB::connect($dsn);
if (DB::IsError($db)) {
    $error = true;
    $mensaje = "En sistema se encuentra fuera de linea temporalmente, disculpe las molestias." . $db->user_info;
    errorLogin($mensaje);
} else {

    $db->setFetchMode(DB_FETCHMODE_ASSOC);
    $error = false;

 //año de ciclo actual
    $anio = "2025";

    if (isset($_POST['usuario'])) {
        $usuario = $_POST['usuario'];
        $password = md5($_POST['password']);
        verificarInscripciones($usuario, $anio); //cometar en asignacion
    }


    $client_id = '545781005689-pgqht2bhk4o9rm8egc0hfp6p85q6csof.apps.googleusercontent.com';
    $client_secret = 'I2hfRzX0X7THnf9ic_TVNcBq';
    $redirect_uri = 'http://estudiante.farusac.edu.gt/login.php';

    $client = new Google_Client();
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("email");
    $client->addScope("profile");
    $service = new Google_Service_Oauth2($client);

    if (isset($_GET['code'])) {
        $client->authenticate($_GET['code']);
        $_SESSION['access_token'] = $client->getAccessToken();
    }

    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        $client->setAccessToken($_SESSION['access_token']);
    }

    if (!isset($authUrl) && isset($_GET['code'])) {

        $user = $service->userinfo->get();
        //$statement->bind_param('issss', $user->id, $user->name, $user->email, $user->link, $user->picture);
        // Registrar el acceso del estudiante al sistema
        $consulta = "INSERT INTO usuarios_google
		(google_id, google_name, google_email, google_link, google_picture_link, fecha_ingreso)
		VALUES(
			$user->id,
			'$user->name',
			'$user->email',
			'$user->link',
			'$user->picture',
			NOW()
		) ON DUPLICATE KEY UPDATE fecha_ingreso = VALUES(fecha_ingreso)";
        $registrar_ingreso = & $db->Query($consulta);

        $separar_correo = explode("@", $user->email);
        $usuario = $separar_correo[0];
    }

    // Verificacion de la existencia del estudiante
    $consulta = "SELECT e.carnet, e.password, (
                                                SELECT extension
                                                FROM inscripcion
                                                WHERE carnet = e.`carnet`
                                                ORDER BY anio DESC
                                                LIMIT 1
                                            ) AS extension
	FROM estudiante e
	WHERE e.carnet = $usuario";
    $estudiante = & $db->getRow($consulta);
    if ($db->isError($estudiante)) {
        if (isset($_GET['code'])) {
            $mensaje = "Est� intentando ingresar con su correo personal, para utilizar esta funci�n debe utilizar su correo ...@farusac.edu.gt";
        } else {
            $mensaje = "Error en la determinacion de los datos del estudiante, debe utilizar como usuario su n�mero de carnet.";
        }
        errorLogin($mensaje);
    } else {
         
        if ($estudiante <> 0) {
            if (in_array($usuario, [202007169])){
			echo "Usuario bloqueado"; die;
	    }

            // Comprobacion de la contrase�a registrada en el sistema.
           //if ((empty($password) /* && !isset($_GET['code']) */ || ($usuario == ''))) {
          if (($password != $estudiante[password] /* && !isset($_GET['code']) */ || ($usuario == ''))) {
                $mensaje = "La contraseña ingresada es invalida, por favor verifique sus datos.<br>Si el inconveniente persiste, escriba al correo informatica@farusac.edu.gt."; //var_dump("e2"); die;
                errorLogin($mensaje);
            } else {
		
                // Consulta del ciclo actual
                $consulta = "SELECT *
				FROM ciclo c
				WHERE c.anio = $anio AND c.modificar = 1 AND c.evaluacion = 1
				 ORDER BY c.anio DESC, c.semestre DESC
				";
                //var_dump($consulta);
                $ciclo = & $db->getRow($consulta);
                if ($db->isError($ciclo)) {
                    $mensaje = "Hubo un error en la determinacion del ciclo actual"; //var_dump("e3");die;
                    errorLogin($mensaje);
                } else {

                    // Carrera actual
                    
                    $consulta = "SELECT IF(EXISTS(
						SELECT i2.carrera
						FROM inscripcion i2
						WHERE i2.`carnet` = i.`carnet` AND i.`carrera` > 3
						LIMIT 1
					),(SELECT i3.carrera FROM inscripcion i3 WHERE i3.anio = $anio AND  i3.carnet = i.`carnet` AND i3.carrera NOT IN (1,3) LIMIT 1), i.carrera) AS carrera
					FROM inscripcion i
					WHERE i.`anio` = $anio AND i.carnet = $usuario
					ORDER BY carrera DESC";

                    $inscripcion = & $db->getRow($consulta);
                    if ($db->isError($inscripcion)) {
                        $mensaje = "Hubo un error en la determinacion del ciclo actual."; //var_dump("e4");die;
                        errorLogin($mensaje);
                    }
                }

                if ($ciclo == 0) {
                    $mensaje = "Error al determinar el ciclo actual."; //var_dump("e5");die;
                    errorLogin($mensaje);
                }

                if (!$error) {
                    // Registro de la session para el usuario
                    session_start();
                    // -> Datos de la conexion
                    $_SESSION['user'] = $usr;
                    $_SESSION['pass'] = $pass;
                    $_SESSION['host'] = $host;
                    // --> Datos para la session
                    if ($ciclo <> 0) {

                        $_SESSION['anio'] = $ciclo[anio];

                        if ($inscripcion[carrera] > 3) {
                            $_SESSION['semestre'] = 2;
                        } else {
                            $_SESSION['semestre'] = isset($ciclo[semestre]) ? $ciclo[semestre] : '';
                        }

                        $_SESSION['nombre_semestre'] = isset($ciclo[nombre_semestre]) ? $ciclo[nombre_semestre] : '';
                    }
			        $_SESSION['semestre'] = 2;
                    $_SESSION['usuario'] = $estudiante['carnet'];
                    $_SESSION['extension'] = $estudiante['extension'];
                    $_SESSION['nombre_decano'] = "Sergio Francisco Castillo Bonini";
                    $_SESSION['apellido_decano'] = "Castillo";
                    $_SESSION['nombre_secretario'] = "Juan Fernando Arriola Alegría";
                    $_SESSION['apellido_secretario'] = "Arriola";
                   

		            //verificar evaluacion docente, sino entrar directo al inicio...
                    $fecha_actual = date("o-m-d");
                    if ($fecha_actual >= $calendario_evaluacion_docente[0] && $fecha_actual <= $calendario_evaluacion_docente[1]) {
                        header("location: evaluaciondocente/evaluaciondocente.php"); //semestre
                    } else if ($fecha_actual >= $calendario_evaluacion_general[0] && $fecha_actual <= $calendario_evaluacion_general[1]) {
                        header("location: evaluaciongeneral/evaluaciongeneral.php");
                    } else {
                        // Datos correctos entramos al sistema.
                        /*
                        $consulta = "SELECT *
                        FROM asignacion
                        WHERE anio = 2025 and semestre = 1 and evaluacion = 1 and extension = 0 and pensum = 5 and codigo in ('1.01.1','1.02.1','1.03.1','1.04.1','1.05.1','1.06.1,1.07.1','1.08.1','1.09.1','1.10.1') and carnet = $usuario
                        ";
                        $area_diseño = & $db->getRow($consulta);
                        if ($db->isError($area_diseño)) {
                            $mensaje = "Hubo un error en la determinacion la consulta"; 
                            errorLogin($mensaje);
                        } else {
                            
                        if($area_diseño <> 0){
                            $aviso = true;
                            $mensaje = "Para la Dirección de Planificación, su opinión es de suma importancia para el análisis y propuesta de un rediseño de la actual red curricular, así como para la planificación de estrategias de mejora continua en la calidad académica de los estudios de esta carrera. <br><br>Por favor, permita la apertura de la ventana emergente y llene la encuesta concientemente.";
                            $url = "../menus/contenido.php";
                            aviso($mensaje, $url);
                        }
                        }*/
                    
                        header("location: menus/inicio.php");

                    }

                }
            }
        } else {
            $mensaje = "El estudiante ingresado no existe actualmente en la base de datos.";
            errorLogin($mensaje);
        }
    }

    $db->disconnect();
}
?>
