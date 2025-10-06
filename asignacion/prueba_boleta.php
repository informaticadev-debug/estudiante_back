<?php

function crearXMLPeticionSIIF($estudiante, $unidad, $extension, $carrera, $nombre, $montoTotal, $array_detalle) {
    $orden_pago = "";
    $datos_estudiante = "<CARNET>" . $estudiante . "</CARNET>" .
            "<UNIDAD>" . $unidad . "</UNIDAD>" .
            "<EXTENSION>" . str_pad($extension, 2, "0", STR_PAD_LEFT) . "</EXTENSION>" .
            "<CARRERA>" . str_pad($carrera, 2, "0", STR_PAD_LEFT) . "</CARRERA>" .
            "<NOMBRE>" . $nombre . "</NOMBRE>" .
            "<MONTO>" . $montoTotal . "</MONTO>";

    $detalle_orden_pago = "";

    foreach ($array_detalle as $detalle) {
        $detalle_orden_pago .= "<DETALLE_ORDEN_PAGO>" .
                "<ANIO_TEMPORADA>" . $detalle['anio'] . "</ANIO_TEMPORADA>" .
                "<ID_RUBRO>" . $detalle['id_rubro'] . "</ID_RUBRO>" .
                "<ID_VARIANTE_RUBRO>" . $detalle['id_variante_rubro'] . "</ID_VARIANTE_RUBRO>" .
                "<TIPO_CURSO>" . $detalle['tipo_curso'] . "</TIPO_CURSO>" .
                "<CURSO>" . $detalle['curso'] . "</CURSO>" .
                "<SECCION>" . $detalle['seccion'] . "</SECCION>" .
                "<SUBTOTAL>" . $detalle['subtotal'] . "</SUBTOTAL>" .
                "</DETALLE_ORDEN_PAGO>";
    }

    return "<GENERAR_ORDEN>" . $datos_estudiante . $detalle_orden_pago . "</GENERAR_ORDEN>";
}

function crearBoletaPago($estudiante, $unidad, $extension, $carrera, $nombre, $montoTotal, $array_detalle) {
    $xmlStr = crearXMLPeticionSIIF($estudiante, $unidad, $extension, $carrera, $nombre, $montoTotal, $array_detalle);
}
