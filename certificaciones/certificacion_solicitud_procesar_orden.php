<?php

require_once "../misc/funciones.php";
require_once '../config/local.php';

session_start();
if (isset($_SESSION[usuario])) {
    $numero_registro = $_POST["numero_registro"];

    header("Content-Disposition:attachment; filename=\"Orden_de_Pago_$numero_registro.pdf\"");
    header("Content-Type:application/pdf");
    $data = array(
        "auth" => array(
            "user" => "arqws",
            "passwd" => "a08!¡+¿s821!kdui23#kd$"
        ),
        'action' => "ordenPagoCertificacion",
        "numero_registro" => $numero_registro,
    );
    //consumiendo el servicio Rest de Inscripciones
    $respuesta = postRequest($api_uri . 'Certificacion', $data);
    echo($respuesta); 
}

?>
