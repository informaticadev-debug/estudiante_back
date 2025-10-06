<?php

require_once "../misc/funciones.php";
require_once '../config/local.php';

session_start();
if (isset($_SESSION[usuario])) {
    $numero_recibo = $_POST["numero_recibo"];
    $orden_pago = $_POST["orden_pago"];
    
    $data = array(
        "auth" => array(
            "user" => "arqws",
            "passwd" => "a08!¡+¿s821!kdui23#kd$"
        ),
        'action' => "validarOrdenPagoCertificacion",
        "orden_pago" => $orden_pago,
        "numero_recibo" => $numero_recibo,
        "carnet" => $_SESSION["usuario"],
        "carrera" => $_POST["carrera"],
        "ponderada" => $_POST["ponderada"],
        "cantidad" => $_POST["cantidad"],
    );
    //consumiendo el servicio Rest de Inscripciones
    $respuesta = postRequest($api_uri . 'Certificacion', $data);
    $dataResp = json_decode($respuesta, true); 
    if ($dataResp["error"] == true) {
        error($dataResp["descripcion"], "../certificaciones/certificacion_solicitud_formulario_nf.php");
    } else {
        $proceso_finalizado = "La orden de pago ha sido cargada correctamente, por favor, verificar que aparezca en su sección de historial para corroborar que la información fue cargada con éxito (la fecha de solicitud debe de ser la fecha actual).";
        $_SESSION['proceso_finalizado'] = $proceso_finalizado;
        echo "
            <script>
                window.open('../certificaciones/certificacion_solicitud_formulario_nf.php','contenido');
            </script>
        ";
    }
}

?>
