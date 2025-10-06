<?php
session_start();

$user = $_SESSION['user'];
$pass = $_SESSION['pass'];
$host = $_SESSION['host'];
?>
<!-- UNIVERSIDAD SAN CARLOS DE GUATEMALA - FARUSAC -->
<!-- Programador: Angel Caal -->
<!-- Año 2012 -->

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <!-- meta http-equiv="Refresh" content="0;url=../menus/inicio.php" -->
        <title>USAC - Facultad de Arquitectura</title>
        <link rel="stylesheet" href="config/estilo_intro.txt" type="text/css">
    </head>
    <body>

        <center><h3>Almacenando encuesta</h3></center>

        <!-- # Comenzar con la estructura -->

        <?php
        $con = mysqli_connect($host, $user, $pass);
        mysqli_select_db($con, "satu");

// Valores a mysqli://
        $extension = $_SESSION['extension'];
        $ano = $_POST['anio'];
        $sem = $_POST['semestre'];
        $eva = $_POST['evaluacion'];
        $car = $_POST['carnet'];
        $cod = $_POST['codigo'];
        $sec = $_POST['seccion'];
        $respuestas = $_POST['resp'];
        $comentarios = $_POST['comentario'];

        $result_ya_grabada = mysqli_query($con, "SELECT * FROM evaluacion_docente WHERE extension = $extension AND anio = $ano AND semestre = $sem AND evaluacion = $eva AND carnet = $car AND codigo = '$cod' AND seccion = '$sec'");
        $conteo = mysqli_num_rows($result_ya_grabada);

        if ($conteo < 1) {
            var_dump("pre-grabado");

            $consulta8 = "INSERT INTO evaluacion_docente
                (extension,`anio`,`semestre`,`evaluacion`,`carnet`,`codigo`,`seccion`";
            for ($i = 0; $i < count($respuestas); $i++) {
                $indice = $i + 1;
                $consulta8 .= ",`respuesta{$indice}`";
            }
            for ($i = 0; $i < count($comentarios); $i++) {
                $indice = $i + 1;
                $consulta8 .= ",`respuestat{$indice}`";
            }
            $consulta8 .=  ",fecha_encuesta) VALUES ($extension,'$ano','$sem','$eva','$car','$cod','$sec'"; 
            for ($i = 0; $i < count($respuestas); $i++) {
                $consulta8 .= ",'{$respuestas[$i]}'";
            }
            for ($i = 0; $i < count($comentarios); $i++) {
                $texto = str_replace(["'"], ['"'], $comentarios[$i]);
                $consulta8 .= ",'$texto'";
            }
            $consulta8 .= ",NOW())";
            $resultado8 = mysqli_query($con, $consulta8) or die(mysqli_error());
        }

        header('Location: ../menus/inicio.php')
        ?>

        <!--?php
        $con = mysql_connect("192.168.10.250","administrador","admin");
        mysql_select_db("satu",$con);
        
        //variables POST
        $reg=$_POST['carnet'];
        
        $sql="UPDATE estudiante SET fecha_actualizacion=now() WHERE carnet=$car";
        mysql_query($sql,$con);         
        ?-->

