<?php

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();


if (isset($_SESSION[usuario])) {

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
        $error = false;
        $aviso = false;

        $db->Query("SET lc_time_names = 'es_ES'");

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_GET['evaluacion'];
        $carnet = $_SESSION['usuario'];

        $jornada = $_POST['jornada'];

        guardarSeleccionDisenio($db, $extension, $anio, $semestre, $evaluacion, 'NULL', 'NULL', $carnet, $jornada);

        $db->disconnect();
    }
}


