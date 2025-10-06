<?php

// Mostrar error durante el ingreso a la aplicacion
function mostrarErrorLoginIndex($mensaje) {
    session_start();
    $_SESSION['error'] = $mensaje;
    echo "<script>";
    echo "window.open('index.php?error=true','_parent')";
    echo "</script>";
}

// Mostrar los errores durante la verificacion del estudiante
function mostrarErrorLogin($mensaje) {
    session_start();
    $_SESSION['error'] = $mensaje;
    echo "<script>";
    echo "window.open('../index.php?error=true','_parent')";
    echo "</script>";
}

// Mostrar errores en la aplicacion
function mostrarError($mensaje) {
    $template = new HTML_Template_Sigma('../templates');
    $template->loadTemplateFile('error.html');
    $template->setVariable(array(
        'mensaje_error' => $mensaje
    ));
    $template->parse('error');
    $template->show();
    exit;
}

// Errores en tiempo de ejecusion   
function error($mensaje, $url) {
    session_start();
    $_SESSION['mensaje_error'] = $mensaje;
    echo "
        <script>
            window.open('$url','contenido');
        </script>
    ";
}

// Proceso completado con exito
function procesoCompletado($mensaje) {
    $template = new HTML_Template_Sigma('../templates');
    $template->loadTemplateFile('proceso_completado.html');
    $template->setVariable(array(
        'mensaje_proceso_completado' => $mensaje
    ));
    $template->parse('proceso_completado');
    $template->show();
    exit;
}

// Verificacion de inscripcion en el ciclo actual
function verificarInscripcion($db, $anio, $semestre, $carnet) {

    $inscripcion = 0;
    $consulta = "SELECT COUNT(*) AS inscripciones
	FROM inscripcion i
	INNER JOIN carrera c
	ON c.carrera = i.carrera
	WHERE i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet";
    $verif_inscripcion = & $db->getRow($consulta);
    if ($db->isError($inscripcion)) {
        $error = true;
        $mensaje = "Hubo un error al determinar la inscripcion en el ciclo actual.";
    } else {
        $inscripcion = $verif_inscripcion[inscripciones];
    }

    return $inscripcion;
}

function numero_privado($db) {

    $numero_privado = "";
    $consulta = "SELECT MAX(p.numero_privado) + 1 AS numero
    FROM examen_privado p ";
    $privado = & $db->getRow($consulta);
    if ($db->isError($privado)) {
        $error = true;
        $mensaje = "Hubo un error durante la consulta del numero de privado.";
        $url = $_SERVER[HTTP_REFERER];
    } else {
        $numero_privado = $privado[numero];
    }

    return $numero_privado;
}

// Funcion para convertir consulta de cadenas de texto en texto formateado
function texto_formateado($texto) {

    $texto = str_replace("\n", "<br>", $texto);
    $texto = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $texto);

    return $texto;
}

function numero_tema($db) {

    $numero_tema = 0;
    $consulta = "SELECT COUNT(*) + 1 AS cantidad
    FROM proyecto_graduacion";
    $cantidad_temas = & $db->getRow($consulta);

    $numero_tema = $cantidad_temas[cantidad];

    return $numero_tema;
}

function fechaEnTexto($fecha) {
    $anio = substr($fecha, 0, 4);
    $mes = substr($fecha, 5, 2);
    $dia = substr($fecha, 8, 2);

    switch ($dia) {
        case 1: {
                $dia = "Primero";
                break;
            }
        case 2: {
                $dia = "Dos";
                break;
            }
        case 3: {
                $dia = "Tres";
                break;
            }
        case 4: {
                $dia = "Cuadro";
                break;
            }
        case 5: {
                $dia = "Cinco";
                break;
            }
        case 6: {
                $dia = "Seis";
                break;
            }
        case 7: {
                $dia = "Siete";
                break;
            }
        case 8: {
                $dia = "Ocho";
                break;
            }
        case 9: {
                $dia = "Nueve";
                break;
            }
        case 10: {
                $dia = "Diez";
                break;
            }
        case 11: {
                $dia = "Once";
                break;
            }
        case 12: {
                $dia = "Doce";
                break;
            }
        case 13: {
                $dia = "Trece";
                break;
            }
        case 14: {
                $dia = "Catorce";
                break;
            }
        case 15: {
                $dia = "Quince";
                break;
            }
    }

    switch ($mes) {
        case 1: {
                $mes = "enero";
                break;
            }
        case 2: {
                $mes = "febrero";
                break;
            }
        case 3: {
                $mes = "marzo";
                break;
            }
        case 4: {
                $mes = "abril";
                break;
            }
        case 5: {
                $mes = "mayo";
                break;
            }
        case 6: {
                $mes = "junio";
                break;
            }
        case 7: {
                $mes = "julio";
                break;
            }
        case 8: {
                $mes = "agosto";
                break;
            }
        case 9: {
                $mes = "septiembre";
                break;
            }
        case 10: {
                $mes = "octubre";
                break;
            }
        case 11: {
                $mes = "noviembre";
                break;
            }
        case 12: {
                $mes = "diciembre";
                break;
            }
    }
    switch ($anio) {
        case 2014: {
                $anio = "dos mil catorce";
                break;
            }
        case 2015: {
                $anio = "dos mil quince";
                break;
            }
        case 2016: {
                $anio = "dos mil dieciseis";
                break;
            }
        case 2017: {
                $anio = "dos mil diecisiete";
                break;
            }
    }

    $fechaTexto = $dia . " de " . $mes . " de " . $anio;

    return $fechaTexto;
}

?>