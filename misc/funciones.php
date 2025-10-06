<?php

ini_set("date.timezone", "America/Guatemala");

function verificarActualizarDatos() {
    include dirname(__FILE__) . "/../config/local.php";
    $dsn = "mysqli://" . $conf_db_user . ":" . $conf_db_passwd . "@" . $conf_db_host . "/satu";
    $db = DB::connect($dsn);
    if (isset($_SESSION["usuario"])) {
        /*
          verificar si existe una solicitud de actualización de datos forzada...
         */
        //$pendiente = $db->getAll("SELECT * FROM estudiante WHERE carnet = {$_SESSION["usuario"]} AND (actualizar_datos = 1 OR actualizar_passwd = 1)");
        if (!empty($pendiente)) {
            header("location: ../personal/datos_personales.php");
            exit();
        }
    }
}


function verificarPrioridad($db, $carrera, $extension, $anio, $semestre, $carnet){
    if (date("Y-m-d H:i:s") >= '2019-01-17 08:00:00' && (validarNivelado($db, $carnet, $carrera, $semestre) || validarEnLimpio($db, $carnet, $carrera, $semestre) || validarEtrabajadorAutorizado($db, $extension, 2018, 2, 1, $carnet))) {
        return $carnet;
        //return "9999999999";
    } else {
        return "9999999999";
    }
}

function getProyectosAsesorados($db, $registro_personal) {
    //profesores bloqueados...
    if (in_array($registro_personal, [99999999])) {
        return [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20];
    }
    $consulta = "
        SELECT a.`registro_personal`, p.`numero_tema`
        FROM `proyecto_graduacion` p
	        INNER JOIN `proyecto_graduacion_asesores` a ON a.`numero_tema` = p.`numero_tema`
        WHERE NOW() BETWEEN p.`fecha_aprobacion_protocolo` AND p.`fecha_vencimiento` 
	        AND a.`registro_personal` = $registro_personal
	        AND a.`registro_personal` NOT IN (18422,15649,9033,
            -- asesores EPS
            /*15491,*/950642,17633,16867,12104,20030688,10259,11474
            -- asesores EPS
            )
	        AND NOT EXISTS (SELECT 1 FROM `examen_privado` pub WHERE pub.fecha_confirmacion IS NOT NULL AND pub.carnet = p.carnet AND pub.carrera = p.carrera)
        ";
    $data = $db->getAll($consulta);
    if ($db->isError($data)) {
        return false;
    }
    return $data;
}

function verificarEnSPD($db, $extension, $anio, $semestre, $evaluacion, $carnet, $pensum, $codigo) {
    $consulta = "SELECT *
                FROM `asignacion_spd` a
                WHERE a.extension = $extension AND a.`anio` = $anio AND a.`semestre` = $semestre AND a.`evaluacion` = $evaluacion AND a.`carnet` = $carnet AND a.`pensum` = $pensum AND a.`codigo` = '$codigo'";
    $data = ejecutarQuery($db, $consulta);
    return (!empty($data)) ? true : false;
}

function imprimirEstudiantesEnLimpio($db) {
    echo "Iniciando..<br />";
    $consulta = "SELECT i.`carnet`, i.`carrera`, e.nombre
                FROM inscripcion i
                    INNER JOIN estudiante e on e.carnet = i.carnet
                WHERE i.`anio` = 2022 and i.`extension` = 0 and i.`carrera` in (1,3)
                GROUP BY i.`carnet`, i.`carrera`
            ";
    $data = ejecutarQuery($db, $consulta);
    foreach ($data as $row) {
        echo (validarEnLimpio($db, $row['carnet'], $row['carrera'], 1)) ? $row['carnet'] . "," . $row['nombre'] . "," . $row['carnet'] . "@farusac.edu.gt" . "," . $row['carrera'] . '<br />' : '';
    }
}

function imprimirEstudiantesNivelados($db) {
    echo "Iniciando..<br />";
    $consulta = "SELECT i.`carnet`, i.`carrera`, e.nombre
                FROM inscripcion i
                    INNER JOIN estudiante e on e.carnet = i.carnet
                WHERE i.`anio` = 2022 and i.`extension` = 0 and i.`carrera` in (1,3)
                GROUP BY i.`carnet`, i.`carrera`
            ";
    $data = ejecutarQuery($db, $consulta);
    foreach ($data as $row) {
        echo (validarNivelado($db, $row['carnet'], $row['carrera'], 1)) ? $row['carnet'] . "," . $row['nombre'] . "," . $row['carnet'] . "@farusac.edu.gt" . "," . $row['carrera'] . '<br />' : '';
    }
}

function contarAsignacionesPerdidas($db, $carnet, $carrera){
    $db->setFetchMode(DB_FETCHMODE_ASSOC);
    $consulta = "
            SELECT a.anio, a.semestre, a.evaluacion, a.`pensum`, a.codigo, a.`nota_oficial`, a.`aprobado`
            FROM asignacion a
               INNER JOIN pensum p ON p.`pensum` = a.`pensum`
               INNER JOIN carrera c ON c.`carrera` = p.`carrera`
            WHERE a.`status` = 1 AND a.`aprobado` = 0 AND a.`nota_oficial` = 1 AND a.evaluacion in (1,2) AND a.carnet = $carnet AND c.carrera = $carrera
            ";
    $asignaciones_perdidas = ejecutarQuery($db, $consulta);
    if (!empty($asignaciones_perdidas)) {
        return count($asignaciones_perdidas);
    }
    return 0;
}

function validarEnLimpio($db, $carnet, $carrera, $semestre) {
    //verificando el anio en que se inscribio por primera vez
    $data_ins = ejecutarQuery($db, "SELECT *
                        FROM inscripcion i
                        WHERE i.`carnet` = $carnet AND i.`carrera` = $carrera
                        ORDER BY i.`anio` ASC
                        LIMIT 1");
    $anio_ins = ($data_ins && count($data_ins) > 0) ? $data_ins[0]['anio'] : -1;
    $anio_actual = date("Y");
    $ciclos_deberia_tener = ($semestre == 2) ? (($anio_actual - $anio_ins) * 2) + 1 : ($anio_actual - $anio_ins) * 2;
    if ($ciclos_deberia_tener < 10 && $ciclos_deberia_tener > 0) {
        $asignacionesPerdidas = contarAsignacionesPerdidas($db, $carnet, $carrera);
        $data_cursos_deberia_tener = ejecutarQuery($db, "SELECT count(*) as conteo
                                                        FROM curso c
                                                                INNER JOIN pensum p ON p.`pensum` = c.`pensum` AND p.`fecha_fin` IS NULL
                                                        WHERE p.`carrera` = $carrera AND c.`ciclo` <= $ciclos_deberia_tener AND c.`caracter` = 'F' AND c.fecha_fin IS NULL");
        $data_cursos_que_tiene = ejecutarQuery($db, "SELECT count(*) as conteo
                                                    FROM nota n
                                                            INNER JOIN curso c ON n.`pensum` = c.`pensum` AND n.`codigo` = c.`codigo`
                                                            INNER JOIN pensum p ON p.`pensum` = c.`pensum` AND p.`fecha_fin` IS NULL
                                                    WHERE p.`carrera` = $carrera AND c.`ciclo` <= $ciclos_deberia_tener AND n.`carnet` = $carnet and c.`caracter` = 'F' AND n.estado in (1,2,9)
                                                    AND n.anio < 2022
                                                    ");
        return (!empty($data_cursos_deberia_tener) && $asignacionesPerdidas < 1 && !empty($data_cursos_que_tiene) && intval($data_cursos_deberia_tener[0]['conteo']) <= intval($data_cursos_que_tiene[0]['conteo']));
    }
    return false;
}

function validarNivelado($db, $carnet, $carrera, $semestre) {
    //verificando el anio en que se inscribio por primera vez
    $data_ins = ejecutarQuery($db, "SELECT *
                        FROM inscripcion i
                        WHERE i.`carnet` = $carnet AND i.`carrera` = $carrera
                        ORDER BY i.`anio` ASC
                        LIMIT 1");
    $anio_ins = ($data_ins && count($data_ins) > 0) ? $data_ins[0]['anio'] : -1;
    $anio_actual = date("Y");
    $ciclos_deberia_tener = ($semestre == 2) ? (($anio_actual - $anio_ins) * 2) + 1 : ($anio_actual - $anio_ins) * 2;
    if ($ciclos_deberia_tener < 10 && $ciclos_deberia_tener > 0) {
        $asignacionesPerdidas = contarAsignacionesPerdidas($db, $carnet, $carrera);
        $data_cursos_deberia_tener = ejecutarQuery($db, "SELECT count(*) as conteo
                                                        FROM curso c
                                                                INNER JOIN pensum p ON p.`pensum` = c.`pensum` AND p.`fecha_fin` IS NULL
                                                        WHERE p.`carrera` = $carrera AND c.`ciclo` <= $ciclos_deberia_tener AND c.`caracter` = 'F' AND c.fecha_fin IS NULL");
        $data_cursos_que_tiene = ejecutarQuery($db, "SELECT count(*) as conteo
                                                    FROM nota n
                                                            INNER JOIN curso c ON n.`pensum` = c.`pensum` AND n.`codigo` = c.`codigo`
                                                            INNER JOIN pensum p ON p.`pensum` = c.`pensum` AND p.`fecha_fin` IS NULL
                                                    WHERE p.`carrera` = $carrera AND c.`ciclo` <= $ciclos_deberia_tener AND n.`carnet` = $carnet and c.`caracter` = 'F' AND n.estado in (1,2,9)
                                                    AND n.anio < 2022
                                                    ");
        return (!empty($data_cursos_deberia_tener) && $asignacionesPerdidas > 0 && !empty($data_cursos_que_tiene) && intval($data_cursos_deberia_tener[0]['conteo']) <= intval($data_cursos_que_tiene[0]['conteo']));
    }
    return false;
}

function validarEtrabajadorAutorizado($db, $extension, $anio, $semestre, $evaluacion, $carnet) {
    $data = getAsignacionETrabajadorSolicitud($db, $extension, $anio, $semestre, $evaluacion, $carnet);
    return ($data && $data['resolucion'] == 1);
}

function getAsignacionETrabajadorSolicitud($db, $extension, $anio, $semestre, $evaluacion, $carnet) {
    $consulta = "
                SELECT *
                FROM asignacion_etrabajador e
                WHERE e.extension = $extension AND e.anio = $anio AND e.semestre = $semestre AND e.evaluacion = $evaluacion AND e.carnet = $carnet
            ";
    $result = & $db->getRow($consulta);
    if ($db->isError($result)) {
        $error = true;
        $mensaje = "Hubo un error al verificar la existencia de solicitudes pendientes de carga de documentos laborales.";
        $url = "../menus/contenido.php";
        return false;
    }
    return $result;
}

function postRequest($url, $data) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        return $response;
    } catch (Exception $exc) {
        echo $exc->getTraceAsString();
    }
}

function verificarFechaHabilitada($fechas_habilitadas, $fecha) {
    foreach ($fechas_habilitadas as $fecha_valida) {
        if ($fecha >= $fecha_valida[0] && $fecha <= $fecha_valida[1]) {
            return true;
        }
    }
    return false;
}

function ejecutarDML($db, $sql) {
    $result = & $db->Query($sql);
    if ($db->isError($result)) {
        $error = true;
        $mensaje = "Hubo un error al registrar su jornada y/o Diseño Arquitectónico.";
        error($mensaje, $url);
        return false;
    }
    return true;
}

function ejecutarQuery($db, $sql) {
    $result = & $db->getAll($sql);
    if ($db->isError($result)) {
        $error = true;
        $mensaje = "Hubo un error al registrar su jornada y/o Diseño Arquitectónico.";
        error($mensaje, $url);
        return false;
    }
    return $result;
}

function obtenerCargaCursoAsignacion($db, $anio, $semestre, $extension, $evaluacion, $codigo, $seccion) {
    $consulta = "
                SELECT s.cupo, count(a.carnet) as asignados
                FROM seccion s
                    LEFT JOIN asignacion a on s.anio = a.anio and s.extension = a.extension and s.semestre = a.semestre and s.evaluacion = a.evaluacion 
                    and s.pensum = a.pensum and s.codigo = a.codigo and s.seccion = a.seccion
                WHERE s.anio = $anio and s.semestre = $semestre and s.extension = $extension and s.evaluacion = $evaluacion and s.codigo = '$codigo' and s.seccion = '$seccion'
            ";
    $cupo = & $db->getAll($consulta);
    if ($db->isError($cupo)) {
        $error = true;
        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
        $url = "../menus/contenido.php";
        return false;
    }
    return $cupo[0];
}

function getJornadaSeleccionada($db, $extension, $anio, $semestre, $evaluacion, $carnet) {
    $db->setFetchMode(DB_FETCHMODE_ASSOC);
    $consulta = "
            SELECT jornada
            FROM asignacion_seleccion_disenio
            WHERE extension = $extension AND anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion AND carnet = $carnet AND activo = 1
            ";
    $jornada = ejecutarQuery($db, $consulta);
    if (!empty($jornada) && count($jornada) > 0) {
        return $jornada[0]['jornada'];
    }
    return false;
}

function guardarSeleccionDisenio($db, $extension, $anio, $semestre, $evaluacion, $codigo, $seccion, $carnet, $jornada) {
    $db->setFetchMode(DB_FETCHMODE_ASSOC);

    $sqlDesactivarAnteriores = "
        UPDATE asignacion_seleccion_disenio 
            SET activo = '0' 
            WHERE extension = $extension AND anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion AND carnet = $carnet 
            ";

    $sqlInsert = "
        INSERT asignacion_seleccion_disenio (extension, anio, semestre, evaluacion, codigo, seccion, carnet, fecha, jornada, activo)
            VALUES ($extension, $anio, $semestre, $evaluacion, $codigo, $seccion, $carnet, NOW(), '$jornada', '1')
            ";

    if (ejecutarDML($db, $sqlDesactivarAnteriores)) {
        echo "desactivacion correcta ::: ";
        if (ejecutarDML($db, $sqlInsert)) {
            echo "ingreso correcto ::: ";
            $db->commit();
            header("Location: ./asignacion_semestre_seleccion.php?evaluacion=$evaluacion");
            return true;
        } else {
            echo "Error al ingresar la tupla de seleccion de diseño...";
            var_dump($sqlInsert);
            die;
            $db->rollback();
        }
    } else {
        echo "Error al desactivar las anteriores";
        $db->rollback();
    }
    return false;
}

function registrarJornadaSegunDisenio($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet) {
    $sql = "
        SELECT pc.jornada
        FROM seccion s 
        INNER JOIN horario h on h.extension = s.extension AND h.anio = s.anio AND h.semestre = s.semestre
            AND h.evaluacion = s.evaluacion AND h.pensum = s.pensum AND h.codigo = s.codigo AND h.seccion = s.seccion
        INNER JOIN periodo_ciclo pc ON pc.extension = s.extension AND pc.anio = s.anio AND pc.semestre = h.semestre AND pc.evaluacion = h.evaluacion AND pc.periodo = h.periodo
        WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion AND pc.carrera = 1 
            AND s.codigo = '$codigo' AND s.seccion = '$seccion' AND s.pensum = $pensum
        LIMIT 1
        ";
    //var_dump($sql); die;
    $cursos_array = ejecutarQuery($db, $sql);
    if (!empty($cursos_array)) {
        $jornada = $cursos_array[0]['jornada'];
        guardarSeleccionDisenio($db, $extension, $anio, $semestre, $evaluacion, "'" . $codigo . "'", "'" . $seccion . "'", $carnet, $jornada);
        return true;
    }
    return false;
}

function registrarCursoSPD($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $carnet, $jornada, $seccion) {
    $sql = "
        INSERT INTO `satu`.`asignacion_spd`
            (`extension`,
             `anio`,
             `semestre`,
             `evaluacion`,
             `pensum`,
             `codigo`,
             `carnet`,
             `jornada`,
             `status`,
             `fecha_asignacion`,
             `fecha_revision`,
             `observacion`,
             `seccion`)
    VALUES ($extension,
            $anio,
            $semestre,
            $evaluacion,
            $pensum,
            '$codigo',
            $carnet,
            '$jornada',
            0,
            NOW(),
            NULL,
            NULL,
            '$seccion');
            ";
    return ejecutarDML($db, $sql);
}

function guardarEnBitacoraAsignacionCambioSeccion($db, $carnet, $anio, $semestre, $extension, $evaluacion, $codigo, $seccion, $seccionAnterior) {
	try {
		$dataIP = json_encode(["REMOTE_ADDR" => $_SERVER['REMOTE_ADDR'], "HTTP_X_FORWARDED_FOR" => $_SERVER['HTTP_X_FORWARDED_FOR'], "HTTP_CLIENT_IP" => $_SERVER['HTTP_CLIENT_IP']]);
        $observacion = "'Cambio de sección: Sección $seccionAnterior hacía la sección $seccion'";
        $consulta = "INSERT INTO `bitacora_asignaciones`
            (`fecha_bitacora`,`extension`,`anio`,`semestre`,`evaluacion`,`pensum`,`codigo`,`seccion`,
             `carnet`,`id_asignacion`,`preasignacion`,`status`,`fecha_asignacion`,`observacion`,`usuario_asignacion`,`orden_pago`, data_request)
            (
                SELECT NOW(), a.extension, a.anio, a.semestre, a.evaluacion, a.pensum, a.codigo, a.seccion,
                        a.carnet, a.id_asignacion, a.preasignacion, a.status, a.fecha_asignacion, $observacion,a.usuario_asignacion,a.orden_pago, '$dataIP' as data_request
                FROM asignacion a
                WHERE
                    anio = $anio and carnet = $carnet and semestre = $semestre and extension = $extension and evaluacion = $evaluacion and codigo = \"$codigo\"
            )";
        //var_dump($consulta); die;
        $result = & $db->query($consulta);
        if ($db->isError($result)) {
            return false;
        }
    } finally {
        
    }
    return true;
}

function guardarEnBitacoraAsignacion($db, $carnet, $anio, $semestre, $extension, $evaluacion, $pensum, $codigo, $observacion) {
    try {
	    $observacion = str_replace("'", "\"", $observacion);
	    $dataIP = json_encode(["REMOTE_ADDR" => $_SERVER['REMOTE_ADDR'], "HTTP_X_FORWARDED_FOR" => $_SERVER['HTTP_X_FORWARDED_FOR'], "HTTP_CLIENT_IP" => $_SERVER['HTTP_CLIENT_IP']]);

        $consulta = "INSERT INTO `bitacora_asignaciones`
            (`fecha_bitacora`,`extension`,`anio`,`semestre`,`evaluacion`,`pensum`,`codigo`,`seccion`,
             `carnet`,`id_asignacion`,`preasignacion`,`status`,`fecha_asignacion`,`observacion`,`usuario_asignacion`,`orden_pago`, data_request)
            (
                SELECT NOW(), a.extension, a.anio, a.semestre, a.evaluacion, a.pensum, a.codigo, a.seccion,
                        a.carnet, a.id_asignacion, a.preasignacion, a.status, a.fecha_asignacion, '$observacion',a.usuario_asignacion,a.orden_pago, '$dataIP' as data_request
                FROM asignacion a
                WHERE
                    anio = $anio AND carnet = $carnet AND semestre = $semestre AND extension = $extension AND evaluacion = $evaluacion AND codigo = '$codigo' AND pensum = $pensum
            )";
        //var_dump($consulta); die;
        $result = & $db->query($consulta);
        if ($db->isError($result)) {
            return false;
        }
    } catch (Exception $exc) {
        echo $exc->getTraceAsString();
        return false;
    }
    return true;
}

// Mostrar error durante el ingreso a la aplicacion
function mostrarErrorLogin($mensaje) {
    session_start();
    //$_SESSION['mensaje_error'] = $mensaje;
    //header('location: https://farusac.edu.gt/error-sigaa/');
    header('location: https://estudiante.farusac.edu.gt/');
}

// Mostrar error durante el ingreso a la aplicacion
function errorLogin($mensaje) {
    session_start();
    $_SESSION['mensaje_error'] = $mensaje;
    //header('location: https://farusac.edu.gt/error-sigaa/');
    header('location: https://estudiante.farusac.edu.gt/');
}

function aviso($mensaje, $url) {
    session_start();
    $_SESSION['mensaje_aviso'] = $mensaje;
    header("location: $url");
}

function mostrarError($mensaje) {
    session_start();
    $_SESSION['mensaje_error'] = $mensaje;
}

function error($mensaje, $url) {
    session_start();
    $_SESSION['mensaje_error'] = $mensaje;
    header("location: $url");
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
    $consulta = "SELECT MAX(numero_privado) + 1 AS numero
    FROM examen_privado";
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

function numero_tema($db, $carnet, $carrera) {

    $numero_tema = 0;
    // Consultar la bitacora de temas 
    $consulta = "SELECT *
	FROM proyecto_graduacion_bitacora b
	WHERE b.carnet = $carnet AND b.carrera = $carrera";
    $bitacora = & $db->getAll($consulta);

    if (count($bitacora) <> 0 && FALSE) {
        $numero_tema = $bitacora[0][numero_tema];
    } else {

        $consulta = "SELECT MAX(p.numero_tema) + 1 AS cantidad
		FROM proyecto_graduacion p";
        $cantidad_temas = & $db->getRow($consulta);

        $numero_tema = $cantidad_temas[cantidad];
    }

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

function fechaEnTextoNumero($fecha) {
    $anio = substr($fecha, 0, 4);
    $mes = substr($fecha, 5, 2);
    $dia = substr($fecha, 8, 2);

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

    $fechaTexto = $dia . " de " . $mes . " de " . $anio;

    return $fechaTexto;
}

function unidad($numero) {

    switch ($numero) {

        case 0 : {
                $numero = "cero";
                break;
            }
        case 1 : {
                $numero = "uno";
                break;
            }
        case 2 : {
                $numero = "dos";
                break;
            }
        case 3 : {
                $numero = "tres";
                break;
            }
        case 4 : {
                $numero = "cuatro";
                break;
            }
        case 5 : {
                $numero = "cinco";
                break;
            }
        case 6 : {
                $numero = "seis";
                break;
            }
        case 7 : {
                $numero = "siete";
                break;
            }
        case 8 : {
                $numero = "ocho";
                break;
            }
        case 9 : {
                $numero = "nueve";
                break;
            }
    }
    return $numero;
}

function decena($numero) {
    if ($numero == 100) {
        $numd = "cien";
    } else
    if ($numero >= 90 && $numero <= 99) {
        $numd = "noventa ";
        if ($numero > 90) {
            $numd = $numd . "y " . (unidad($numero - 90));
        }
    } else
    if ($numero >= 80 && $numero <= 89) {
        $numd = "ochenta ";
        if ($numero > 80) {
            $numd = $numd . "y " . (unidad($numero - 80));
        }
    } else
    if ($numero >= 70 && $numero <= 79) {
        $numd = "setenta ";
        if ($numero > 70) {
            $numd = $numd . "y " . (unidad($numero - 70));
        }
    } else
    if ($numero >= 60 && $numero <= 69) {
        $numd = "sesenta ";
        if ($numero > 60) {
            $numd = $numd . "y " . (unidad($numero - 60));
        }
    } else
    if ($numero >= 50 && $numero <= 59) {
        $numd = "cincuenta ";
        if ($numero > 50) {
            $numd = $numd . "y " . (unidad($numero - 50));
        }
    } else
    if ($numero >= 40 && $numero <= 49) {
        $numd = "cuarenta ";
        if ($numero > 40) {
            $numd = $numd . "y " . (unidad($numero - 40));
        }
    } else
    if ($numero >= 30 && $numero <= 39) {
        $numd = "treinta ";
        if ($numero > 30) {
            $numd = $numd . "y " . (unidad($numero - 30));
        }
    } else
    if ($numero >= 20 && $numero <= 29) {
        $numd = "veinte ";
        if ($numero > 20) {
            $numd = $numd . "y " . (unidad($numero - 20));
        }
    } else
    if ($numero >= 10 && $numero <= 19) {
        switch ($numero) {
            case 19 : {
                    $numd = "diecinueve";
                    break;
                }
            case 18 : {
                    $numd = "dieciocho";
                    break;
                }
            case 17 : {
                    $numd = "diecisiete";
                    break;
                }
            case 16 : {
                    $numd = "dieciseis";
                    break;
                }
            case 15 : {
                    $numd = "quince";
                    break;
                }
            case 14 : {
                    $numd = "catorce";
                    break;
                }
            case 13 : {
                    $numd = "trece";
                    break;
                }
            case 12 : {
                    $numd = "doce";
                    break;
                }
            case 11 : {
                    $numd = "once";
                    break;
                }
            case 10 : {
                    $numd = "diez";
                    break;
                }
        }
    } else
        $numd = unidad($numero);
    return $numd;
}

function inscripcion_estudiante($db, $anio, $semestre, $carnet) {
    $carrera = "";
    $consulta = "SELECT i.carrera
	FROM inscripcion i
	WHERE i.carnet = $carnet AND i.anio = $anio AND i.semestre = $semestre /* AND i.carrera IN (1,3)
	AND NOT EXISTS(
		SELECT *
		FROM inscripcion i2
		WHERE i2.anio = i.anio AND i2.semestre = i.semestre AND i2.carrera > 3 AND i2.carnet = i.carnet
	)*/
	ORDER BY i.carrera DESC
	";
    $inscripcion = & $db->getRow($consulta);
    if ($db->isError($inscripcion)) {
        $error = true;
        $mensaje = "Hubo un problema al obtener la inscripcion del estudiante.";
    } else {
        $carrera = $inscripcion[carrera];
    }

    return $carrera;
}

function nombre_mes($mes) {

    $nombre_mes = "";
    SWITCH ($mes) {
        CASE 1: {
                $nombre_mes = "Enero";
                break;
            }
        CASE 2: {
                $nombre_mes = "Febrero";
                break;
            }
        CASE 3: {
                $nombre_mes = "Marzo";
                break;
            }
        CASE 4: {
                $nombre_mes = "Abril";
                break;
            }
        CASE 5: {
                $nombre_mes = "Mayo";
                break;
            }
        CASE 6: {
                $nombre_mes = "Junio";
                break;
            }
        CASE 7: {
                $nombre_mes = "Julio";
                break;
            }
        CASE 8: {
                $nombre_mes = "Agosto";
                break;
            }
        CASE 9: {
                $nombre_mes = "Septiembre";
                break;
            }
        CASE 10: {
                $nombre_mes = "Octubre";
                break;
            }
        CASE 11: {
                $nombre_mes = "Noviembre";
                break;
            }
        CASE 12: {
                $nombre_mes = "Diciembre";
                break;
            }
    }

    return $nombre_mes;
}

function registrarAsignacionLaboratorio($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, $orden_pago) {

    $error = false;

    // Registrar la asignacion con los datos de la transaccion realizada.
    $registrar_asignacion = "INSERT INTO asignacion
			(extension, anio, semestre, evaluacion, pensum, codigo, seccion, carnet, id_asignacion, preasignacion, status, aprobado, 
			fecha_asignacion, observacion, usuario_asignacion, nsp, nota_oficial, orden_pago)
			VALUES(
				$extension,
				$anio,
				$semestre,
				$evaluacion,
				$pensum,
				'$codigo',
				'$seccion',
				$carnet,
				md5(concat(carnet,now(),carnet)),
				1,
				1,
				0,
				NOW(),
				'Asignatura no oficial / Pendiente de pago en Banrural',
				'estudiante',
				0,
				0,
				$orden_pago
			)";
    $asignacion = & $db->Query($registrar_asignacion);
    guardarEnBitacoraAsignacion($db, $carnet, $anio, $semestre, $extension, $evaluacion, $pensum, $codigo, "Asignación creada con éxito. Asignación en Línea");
    if ($db->isError($asignacion)) {
        $error = true;
        $mensaje = "Hubo un error al confirmar las Asginaciones.";
    }

    return $error;
}

function registrarAsignacion($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $carnet, $orden_pago) {

    $error = false;

    if ($evaluacion == 2 || $evaluacion == 3 || $evaluacion == 4 || $pensum <> 5 && $pensum <> 20) {
        $observacion = "Asignación No Oficial / Pendiente de pago en Banco";
        $preasignacion = 1;
    } else {
        $observacion = "Asignatura sin problemas";
        $preasignacion = 0;
    }

    if (empty($orden_pago)) {
        $orden_pago = "NULL";
    }

    // Registrar la asignacion con los datos de la transaccion realizada.
    $registrar_asignacion = "INSERT INTO asignacion
    (extension, anio, semestre, evaluacion, pensum, codigo, seccion, carnet, id_asignacion, preasignacion, status, aprobado, 
    fecha_asignacion, observacion, usuario_asignacion, nsp, nota_oficial, orden_pago)
    VALUES(
            $extension,
            $anio,
            $semestre,
            $evaluacion,
            $pensum,
            '$codigo',
            '$seccion',
            $carnet,
            md5(concat(carnet,now(),carnet)),
            $preasignacion,
            1,
            0,
            NOW(),
            '$observacion',
            'estudiante',
            0,
            0,
            $orden_pago
    )";
    $asignacion = & $db->Query($registrar_asignacion);
    guardarEnBitacoraAsignacion($db, $carnet, $anio, $semestre, $extension, $evaluacion, $pensum, $codigo, "Asignación creada con éxito. Asignación en Línea");
    if ($db->isError($asignacion)) {
        $error = true;
        $mensaje = "Hubo un error al confirmar las Asginaciones.";
    }

    return $error;
}

function ingresarTransaccionCursos($db, $user, $unidad, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion, $costo_curso, $tipoPago, $noTrans, $rubro, $varianteRubro) {

    $error = false;

    // ingresando los cursos de la transaccion		
    $consulta_ingreso = "
			INSERT INTO bitacora_orden_pago 
			(unidad,extension,carrera,anio,semestre,evaluacion,pensum,codigo,seccion,carnet,
			tipo_pago,orden_pago,no_correlativo,no_transaccion,rubro,variante_rubro,llave,monto,estado,fecha_transaccion,usuario_orden_pago
			)
			VALUES (
			$unidad
			,$extension
			,$carrera
			,$anio
			,$semestre
			,$evaluacion
			,$pensum
			,'$codigo'
			,'$seccion'
			,$carnet
			,$tipoPago
			,NULL
			,getCorrelativoCiclo($anio,$semestre,$evaluacion)
			,$noTrans
			,$rubro
			,$varianteRubro
			,NULL
			,$costo_curso
			,1
			,NOW()
			,'$user'
			)
		";
    //var_dump($consulta_ingreso); die;
    $resultado_ingreso = & $db->query($consulta_ingreso);
    if ($db->isError($resultado_ingreso)) {
        echo $consulta_ingreso . "<br>";
        $error = true;
    }

    return $error;
}

function registrarErrorBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $no_transaccion, $carnet, $estado, $descripcion_error) {

    $error = false;
    $consulta = "UPDATE bitacora_orden_pago o
    SET o.estado = $estado, o.descripcion_error = '$descripcion_error'
    WHERE o.no_transaccion = $no_transaccion AND o.anio = $anio AND o.semestre = $semestre AND o.evaluacion = $evaluacion AND o.carnet = $carnet";
    $resultado = & $db->query($consulta);
    if ($db->isError($resultado)) {
        $error = true;
    }
}

function registrarBitacoraOrdenPago($db, $anio, $semestre, $evaluacion, $no_transaccion, $carnet, $orden_pago, $llave, $fecha_orden) {

    $error = false;
    $consulta = "UPDATE bitacora_orden_pago o
    SET o.orden_pago = $orden_pago, o.llave = '$llave', o.estado = 2, o.fecha_orden_pago = '$fecha_orden', o.fecha_registro_orden_pago = NOW()
    WHERE o.no_transaccion = $no_transaccion AND o.anio = $anio AND o.semestre = $semestre AND o.evaluacion = $evaluacion AND o.carnet = $carnet";
    //echo $consulta . "<br>";
    $resultado = & $db->query($consulta);
    if ($db->isError($resultado)) {
        $error = true;
    }
}

function nuevaOrdenPago($db, $user, $orden_pago, $anio, $semestre, $evaluacion, $no_transaccion, $estado_orden, $unidad, $extension, $carrera, $carnet, $rubro, $llave, $monto_total, $fecha_orden) {

    $error = false;
    $consulta_registro_orden = "
			INSERT INTO orden_pago
			(orden_pago,anio,semestre,evaluacion,no_transaccion,
			 unidad,extension,carrera,carnet,
			 estado,rubro,llave,monto_total,fecha_orden_pago,usuario_orden_pago
			) VALUES
			(
			    $orden_pago
			  , $anio
			  , $semestre
			  , $evaluacion
			  , $no_transaccion
			  , $unidad
			  , $extension
			  ,$carrera
			  , $carnet
			  , $estado_orden
			  , $rubro
			  , '$llave'
			  , $monto_total
			  , '$fecha_orden'
			  , '$user'
			)
		";
    //echo $consulta_registro_orden . "<br>"; die;
    $resultado_registro_orden = & $db->query($consulta_registro_orden);
    if ($db->isError($resultado_registro_orden)) {
	    var_dump($resultado_registro_orden); die;
        $error = true;
    } else {
        $consulta_registro_detalle = "
				INSERT INTO detalle_orden_pago
				(
				  orden_pago
				, anio
				, semestre
				, evaluacion
				, no_transaccion
				, no_correlativo
				, pensum
				, codigo
				, seccion
				, tipo_pago
				, variante_rubro
				, monto
				, fecha_transaccion
				)
				SELECT orden_pago,anio,semestre,evaluacion,no_transaccion,no_correlativo,pensum,codigo,seccion,tipo_pago,variante_rubro,monto,fecha_registro_orden_pago
				FROM bitacora_orden_pago 
				WHERE anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion AND no_transaccion = $no_transaccion AND carnet = $carnet
			";
        //echo $consulta_registro_detalle;
        $resultado_registro_detalle = & $db->query($consulta_registro_detalle);
        if ($db->isError($resultado_registro_detalle)) {
            $error = true;
        }
    }
    return $error;
}

function costo_curso($db, $extension, $anio, $semestre, $evaluacion, $pensum, $codigo, $seccion) {

    $error = false;
    $costo_curso = 0;

    // Consulta para verificar el costo del curso
    $consulta = "SELECT s.costo
		FROM seccion s
		WHERE s.extension = $extension AND s.anio = $anio AND s.semestre = $semestre AND s.evaluacion = $evaluacion
		AND s.pensum = $pensum AND s.codigo = '$codigo' AND s.seccion = '$seccion'";
    $pago_curso = & $db->getRow($consulta);
    if ($db->isError($pago_curso)) {
        $mensaje = "Hubo un error al determinar el costo del curso." . mysql_error();
        mostrarError($mensaje);
    } else {

        $costo_curso = $pago_curso[costo];
    }

    return $costo_curso;
}

function costo_inscripcion($db, $extension, $anio, $semestre, $evaluacion, $carnet) {

    $error = false;
    $costo_inscripcion = 0;

    // Consulta si se han hecho asignaciones anteriormente
    // Tambien se verifica si hay asignaciones hechas que la orden de pago tenga la inscripcion correspondiente
    $consulta = "SELECT a.carnet
    FROM asignacion a
    WHERE a.extension = $extension AND a.anio = $anio AND a.semestre = $semestre AND a.evaluacion = $evaluacion
    AND a.carnet = $carnet
    AND EXISTS(
            SELECT *
            FROM detalle_orden_pago d
            WHERE d.anio = a.anio AND d.semestre = a.semestre AND d.evaluacion = a.evaluacion AND d.orden_pago = a.orden_pago
            AND d.monto = '20.00'
    )";
    $asignaciones_anteriores = & $db->getRow($consulta);
    if ($db->isError($asignaciones_anteriores)) {
        $mensaje = "Hubo un error al verificar las asignaciones realizadas.";
        mostrarError($mensaje);
    }

    if (count($asignaciones_anteriores) <> 0) {

        $costo_inscripcion = "0.00";
    } else {

        // Consulta del costo de inscripcion en este ciclo
        $consulta = "SELECT c.inscripcion
        FROM ciclo c
        WHERE c.anio = $anio AND c.semestre = $semestre AND c.evaluacion = $evaluacion";
        $inscripcion_ciclo = & $db->getRow($consulta);
        if ($db->isError($inscripcion_ciclo)) {
            $mensaje = "Hubo un error al determinar el valor de la inscripcion";
        } else {

            $costo_inscripcion = $inscripcion_ciclo[inscripcion];
        }
    }

    return $costo_inscripcion;
}

function ingresarTransaccionInscripcion($db, $user, $unidad, $extension, $carrera, $carnet, $anio, $semestre, $evaluacion, $tipoPago, $noTrans, $rubro, $varianteRubro, $costoInscripcion) {

    $error = false;

    // ingresando el registro de la inscripcion
    $consulta_ingreso = "
			INSERT INTO bitacora_orden_pago 
			(unidad,extension,carrera,anio,semestre,evaluacion,pensum,codigo,seccion,carnet,
			tipo_pago,orden_pago,no_correlativo,no_transaccion,rubro,variante_rubro,llave,monto,estado,fecha_transaccion,usuario_orden_pago
			)
			VALUES (
			$unidad
			,$extension
			,$carrera
			,$anio
			,$semestre
			,$evaluacion
			,NULL
			,NULL
			,NULL
			,$carnet
			,$tipoPago
			,NULL
			,getCorrelativoCiclo($anio,$semestre,$evaluacion)
			,$noTrans
			,$rubro
			,$varianteRubro
			,NULL
			,$costoInscripcion
			,1
			,NOW()
			,'estudiante'
			)		
		";
    $resultado_ingreso = & $db->Query($consulta_ingreso);
    if ($db->isError($resultado_ingreso)) {
        //echo $resultado_ingreso;
        $error = true;
    }
    return $error;
}

function spg_nombre_modalidad($db, $carrera, $modalidad) {

    $nombre_modalidad = "";
    $consulta = "SELECT *
	FROM proyecto_graduacion_modalidad m
	WHERE m.modalidad = $modalidad";
    $datos_modalidad = & $db->getRow($consulta);
    if ($db->isError($datos_modalidad)) {
        return null;
        //echo mysql_error();
    } else {

        $nombre_modalidad = $datos_modalidad['nombre'];
    }

    return $nombre_modalidad;
}

?>
