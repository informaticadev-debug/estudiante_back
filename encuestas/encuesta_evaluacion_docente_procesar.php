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
        
        $extension = $_SESSION['extension'];
        $ano = $_POST['anio'];
        $sem = $_POST['semestre'];
        $eva = $_POST['evaluacion'];
        $car = $_POST['carnet'];
        $cod = $_POST['codigo'];
        $sec = $_POST['seccion'];
        
        $result_ya_grabada = mysqli_query($con, "SELECT * FROM evaluacion_docente WHERE extension = $extension AND anio = $ano AND semestre = $sem AND evaluacion = $eva AND carnet = $car AND codigo = '$cod' AND seccion = '$sec'");
        $conteo = mysqli_num_rows($result_ya_grabada);

        if ($conteo < 1) {
            // Valores a mysqli://
            $resp1 = $_POST['respuesta1'];
            $resp2 = $_POST['respuesta2'];
            $resp3 = $_POST['respuesta3'];
            $resp4 = $_POST['respuesta4'];
            $resp5 = $_POST['respuesta5'];
            $resp6 = $_POST['respuesta6'];
            $resp7 = $_POST['respuesta7'];
            $resp8 = $_POST['respuesta8'];
            $resp9 = $_POST['respuesta9'];
            $resp10 = $_POST['respuesta10'];
            $resp11 = $_POST['respuesta11'];
            $resp12 = $_POST['respuesta12'];
            $resp13 = $_POST['respuesta13'];
            $resp14 = $_POST['respuesta14'];
            $resp15 = $_POST['respuesta15'];
            $resp16 = $_POST['respuesta16'];
            $resp17 = $_POST['respuesta17'];
            $resp18 = $_POST['respuesta18'];
            $resp19 = $_POST['respuesta19'];
            $resp20 = $_POST['respuesta20'];
            $comentario = $_POST['comentario'];

            $consulta8 = "INSERT INTO evaluacion_docente
    (extension,`anio`,`semestre`,`evaluacion`,`carnet`,`codigo`,`seccion`,`respuesta1`,`respuesta2`,`respuesta3`,`respuesta4`,`respuesta5`,`respuesta6`,`respuesta7`,`respuesta8`,`respuesta9`,`respuesta10`,`respuesta11`,`respuesta12`,`respuesta13`,`respuesta14`,`respuesta15`,`respuesta16`,`respuesta17`,`respuesta18`,`respuesta19`,`respuesta20`, comentario, fecha_encuesta)
    VALUES ($extension,'$ano','$sem','$eva','$car','$cod','$sec','$resp1','$resp2','$resp3','$resp4','$resp5','$resp6','$resp7','$resp8','$resp9','$resp10','$resp11','$resp12','$resp13','$resp14','$resp15','$resp16','$resp17','$resp18','$resp19','$resp20', '$comentario', NOW())";
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

