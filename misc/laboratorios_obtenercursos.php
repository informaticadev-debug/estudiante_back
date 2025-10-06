<?php

/*
  Document   : laboratorios_obtenercursos.php
  Created on : 03-Jun-2016, 12:53
  Author     : Angel Caal
  Description:
  -> Obtener el listado de cursos de laboratorio
 */

require_once "../misc/funciones.php";
require_once "DB.php";
require_once "XML/Query2XML.php";

session_start();
$user = $_SESSION['user'];
$pass = $_SESSION['pass'];
$host = $_SESSION['host'];

if (isset($_SESSION['usuario'])) {

    // Preparar la conexion a la base de datos
    $dsn = "mysqli://" . $user . ":" . $pass . "@" . $host . "/satu";
    $db = DB::connect($dsn);
    $constructXML = XML_Query2XML::factory($db);

    $db->setfetchmode(DB_FETCHMODE_ASSOC);
    $extension = 0;
    $anio = $_SESSION['anio'];
    $semestre = $_SESSION['semestre'];
    $evaluacion = 1;
    $carnet = $_SESSION['usuario'];

    // Verificacion de la inscripcion del estudiante en el ciclo actual
    $consulta = "SELECT i.carnet, IF (
            EXISTS(
                    SELECT i2.carrera 
                    FROM inscripcion i2
                    WHERE i2.extension = i.`extension` AND i2.anio = i.`anio` AND i2.semestre = i.`semestre` AND i2.carnet = i.`carnet` AND i2.carrera > 3
                    LIMIT 1
            ),
                    (
                            SELECT i3.carrera 
                            FROM inscripcion i3
                            WHERE i3.extension = i.`extension` AND i3.anio = i.`anio` AND i3.semestre = i.`semestre` AND i3.carnet = i.`carnet`
                            AND i3.carrera > 3
                            LIMIT 1
                    )
            ,i.carrera
    ) AS carrera
    FROM inscripcion i
    WHERE i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet
    GROUP BY i.carrera";
    $inscripcion = & $db->getAll($consulta);
    if ($db->isError($inscripcion)) {
        $error = true;
        $mensaje = "Hubo un error al determinar tu inscripcion en el ciclo actual.";
        $url = "../menus/contenido.php";
    } else {

        foreach ($inscripcion AS $in) {

            $consulta = "SELECT codigo, asignatura, total AS perdidas
                FROM (
                    SELECT a2.`anio`, a2.`semestre`, a2.`evaluacion`, a2.`carnet`, a2.`codigo`, TRIM(c.`nombre`) AS asignatura, a2.`nota`, COUNT(*) AS total
                    FROM asignacion a2
                    INNER JOIN curso c
                    ON c.`pensum` = a2.`pensum` AND c.`codigo` = a2.`codigo` AND c.caracter = 'F'
                    INNER JOIN pensum p
                    ON p.pensum = a2.pensum AND p.carrera = $in[carrera]
                    WHERE CONCAT(a2.anio,a2.`semestre`) >= 20052 AND a2.`nota` < 61 AND a2.`status` = 1
                    AND a2.pensum IN (5,20) AND a2.`evaluacion` IN (1,2) AND a2.`carnet` = $carnet 
                    AND (
                            EXISTS(
                                    SELECT *
                                    FROM asignacion a
                                    WHERE a.`extension` = a2.`extension` AND CONCAT(a.`anio`,a.`semestre`) < 20102 AND a.`semestre` = a2.`semestre` AND a.`evaluacion` = a2.`evaluacion` AND a2.`evaluacion` = 1
                                    AND a.`anio` = a2.`anio` AND a2.`semestre` = a.`semestre` AND a.`pensum` = a2.`pensum` AND a.`codigo` = a2.`codigo` 
                                    AND a.`nota` < 61 AND a.`carnet` = a2.`carnet` AND a.`status` = a2.`status`
                            )
                            OR EXISTS(
                                    SELECT *
                                    FROM asignacion a
                                    WHERE a.`extension` = a2.`extension` AND CONCAT(a.`anio`,a.`semestre`) >= 20102 AND a.`semestre` = a2.`semestre` AND a.`evaluacion` = a2.`evaluacion` AND a2.`evaluacion` = 1
                                    AND a.`anio` = a2.`anio` AND a2.`semestre` = a.`semestre` AND a.`pensum` = a2.`pensum` AND a.`codigo` = a2.`codigo` 
                                    AND a.`nota` <> 0 AND a.`nota` < 61 AND a.`carnet` = a2.`carnet` AND a.`status` = a2.`status`
                            )
                            OR EXISTS(
                                    SELECT *
                                    FROM asignacion a
                                    WHERE a.`extension` = a2.`extension` AND CONCAT(a.`anio`,a.`semestre`) >= 20102 AND a.`semestre` = a2.`semestre` AND a.`evaluacion` = a2.`evaluacion` AND a2.`evaluacion` = 2
                                    AND a.`anio` = a2.`anio` AND a2.`semestre` = a.`semestre` AND a.`pensum` = a2.`pensum` AND a.`codigo` = a2.`codigo` 
                                    AND a.`nota` <> 0 AND a.`nota` < 61 AND a.`carnet` = a2.`carnet` AND a.`status` = a2.`status`
                                    AND NOT EXISTS(
                                            SELECT *
                                            FROM asignacion a4
                                            WHERE a4.extension = a.extension AND a4.anio = a.anio AND a4.semestre = a.semestre AND a4.evaluacion = 1
                                            AND a4.pensum = a.pensum AND a4.codigo = a.codigo AND a4.carnet = a.carnet
                                    )
                            )
                            OR EXISTS(
                                    SELECT *
                                    FROM asignacion a
                                    WHERE a.`extension` = a2.`extension` AND CONCAT(a.`anio`,a.`semestre`) < 20102 AND a.`semestre` = a2.`semestre` AND a.`evaluacion` = a2.`evaluacion` AND a2.`evaluacion` = 2
                                    AND a.`anio` = a2.`anio` AND a2.`semestre` = a.`semestre` AND a.`pensum` = a2.`pensum` AND a.`codigo` = a2.`codigo` 
                                    AND a.`nota` <> 0 AND a.`nota` < 61 AND a.`carnet` = a2.`carnet` AND a.`status` = a2.`status`
                                    AND NOT EXISTS(
                                            SELECT *
                                            FROM asignacion a4
                                            WHERE a4.extension = a.extension AND a4.anio = a.anio AND a4.semestre = a.semestre AND a4.evaluacion = 1
                                            AND a4.pensum = a.pensum AND a4.codigo = a.codigo AND a4.carnet = a.carnet
                                    )
                            )
                    )
                    AND NOT EXISTS (
                            SELECT *
                            FROM nota n
                            WHERE n.`carnet` = a2.`carnet` AND n.`pensum` = a2.`pensum` AND n.`codigo` = a2.`codigo`
                    )
                    AND NOT EXISTS (
                            SELECT *
                            FROM repitente r
                            WHERE r.carnet = a2.carnet AND r.pensum = a2.pensum AND r.codigo = a2.codigo
                            AND r.oportunidad = 1
                    )
                    GROUP BY a2.`carnet`, a2.`codigo`
                ) sel 
                WHERE sel.total >= 3";
            $repitencia = & $db->getAll($consulta);
            if ($db->isError($repitencia)) {
                $consulta = "SELECT 0 AS estado, 'Hubo un error al determinar al verificar el estado de repitencia para el estudiante' AS detalle";
            } else {

                if (count($repitencia) <> 0) {
                    $detalle = "Por el momento no puede asignarse cursos en este ciclo, debido a la repitencia en: \n\n";

                    foreach ($repitencia AS $re) {
                        $detalle = $detalle . "$re[codigo] $re[asignatura] ($re[perdidas] veces reprobada) \n";
                    }

                    $consulta = "SELECT 0 AS estado, '$detalle' AS detalle";
                } else {

                    // Asignaturas con pago de laboratorio para mostrar al estudiante y generar orden de pago
                    if ($in[carrera] == 3) {

                        // Consulta de cursos a Asignar en la Licenciatura en Diseño Gráfico
                        $consulta = "SELECT 1 AS estado, cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
                        FROM curso_ciclo cc, pensum p, curso c
                        WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
                                cc.pensum = p.pensum AND p.carrera = $in[carrera] AND cc.codigo = c.codigo AND cc.pensum = c.pensum
                                AND c.codigo IN ('1.07.4', '1.03.4', '1.04.4', 30211, 30311, 30411, 30511, 30611, 30712, 30711, 30811, 30911, 30812)
                                AND NOT EXISTS 
                                (
                                        SELECT *
                                        FROM nota n
                                        WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9)
                                ) AND
                                NOT EXISTS 
                                (
                                        SELECT *
                                        FROM prerrequisito p
                                        WHERE	p.pensum = c.pensum AND p.codigo = c.codigo AND
                                                NOT EXISTS
                                                (
                                                        SELECT *
                                                        FROM curso c2
                                                        WHERE	c2.pensum = p.pensum_prerrequisito AND c2.codigo = p.codigo_prerrequisito AND 
                                                                (
                                                                        EXISTS 
                                                                        (
                                                                                SELECT *
                                                                                FROM 	nota n, ciclo cic
                                                                                WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9) AND
                                                                                        n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                        STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                        (
                                                                                                SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                FROM ciclo
                                                                                                WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                        )
                                                                        ) 
                                                                        OR EXISTS
                                                                        (
                                                                                SELECT * 
                                                                                FROM curso c3 
                                                                                WHERE 	c3.pensum = c2.pensum AND c3.codigo = c2.codigo AND 
                                                                                        EXISTS 
                                                                                        (
                                                                                                SELECT *
                                                                                                FROM equivalencia e
                                                                                                WHERE e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
                                                                                                        e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                                        ) AND NOT EXISTS
                                                                                        (
                                                                                                SELECT * 
                                                                                                FROM equivalencia e
                                                                                                WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
                                                                                                        e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0)))))) AND  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                                                        NOT EXISTS 
                                                                                                        (
                                                                                                                SELECT *
                                                                                                                FROM nota n, ciclo cic
                                                                                                                WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
                                                                                                                        n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                                                        STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                                                        (
                                                                                                                                SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                                                FROM ciclo
                                                                                                                                WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                                                        )
                                                                                                        )
                                                                                        )
                                                                        )
                                                                        OR EXISTS
                                                                        (
                                                                                SELECT *
                                                                                FROM carrera_estudiante c, curso ci
                                                                                WHERE c.carnet = $carnet AND c.carrera = 2 AND c.fecha_cierre IS NOT NULL
                                                                                AND c.ciclo >= 7 AND c.pensum = 20
                                                                        )																				
                                                                )
                                                )
                                )
                                AND 
                                (
                                        NOT EXISTS 
                                        (
                                                SELECT *
                                                FROM nota n
                                                WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9)
                                        )
                                        AND NOT EXISTS 
                                        (
                                                SELECT *
                                                FROM curso c2
                                                WHERE	c2.pensum = c.pensum AND c2.codigo = c.codigo AND 
                                                (

                                                        EXISTS 
                                                        (
                                                                SELECT *
                                                                FROM equivalencia e
                                                                WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
                                                                        e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                        ) AND NOT EXISTS 
                                                        (

                                                                SELECT * 
                                                                FROM equivalencia e
                                                                WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
                                                                        e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0)))))) AND -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                        NOT EXISTS 
                                                                        (
                                                                                SELECT *
                                                                                FROM nota n, ciclo cic
                                                                                WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
                                                                                        n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                        STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                        (
                                                                                                SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                FROM ciclo
                                                                                                WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                        )
                                                                        )
                                                        )	
                                                )
                                        )
                                )
                                AND NOT EXISTS 
                                (
                                    SELECT *
                                    FROM asignacion a
                                    WHERE a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
                                    a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
                                )
                                AND NOT EXISTS(
                                    SELECT *
                                    FROM detalle_orden_pago p
                                    INNER JOIN orden_pago o
                                    ON o.orden_pago = p.orden_pago
                                    WHERE o.carnet = $carnet AND p.anio = $anio AND p.semestre = $semestre AND p.evaluacion = $evaluacion
                                    AND p.pensum = c.pensum AND p.codigo = c.codigo
                                )
                        ORDER BY c.ciclo,c.codigo";
                        $cursos_a_asignar = $consulta;
                    }

                    if ($in[carrera] == 1) {

                        // Verificacion de linea de Herramientas Digitales
                        // -> Para ver que linealinea del pensum ha elegido el estudiante a seguir
                        $consulta = "SELECT n.codigo 
                        FROM nota n
                        WHERE n.codigo IN ('1.03.4','1.04.4','1.07.4','1.09.4') AND n.carnet = $carnet AND n.estado IN (1,2,9)";
                        $linea_herramientas = & $db->getAll($consulta);
                        if ($db->isError($linea_herramientas)) {
                            $consulta = "SELECT 0 AS estado, 'Hubo un error al determinar la linea del pensum que ha seguido el estudiante' AS detalle";
                        } else {

                            // Consulta de cursos a Asignar en la Licenciatura en Diseño Gráfico
                            // 	-> Tomando en cuenta la nueva linea de Herramientas Digitales que el estudiante siga o no
                            if (count($linea_herramientas) == 0) {

                                $consulta = "SELECT 1 AS estado, cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
                                FROM curso_ciclo cc, pensum p, curso c
                                WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
                                        cc.pensum = p.pensum AND p.carrera = $in[carrera] AND cc.codigo = c.codigo AND cc.pensum = c.pensum
                                        AND c.codigo IN ('1.07.4', '1.03.4', '1.04.4', 30211, 30311, 30411, 30511, 30611, 30712, 30711, 30811, 30911, 30812)
                                        AND NOT EXISTS 
                                        (
                                                SELECT *
                                                FROM nota n
                                                WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9)
                                        ) AND
                                        NOT EXISTS 
                                        (
                                                SELECT *
                                                FROM prerrequisito p
                                                WHERE	p.pensum = c.pensum AND p.codigo = c.codigo AND
                                                        NOT EXISTS
                                                        (
                                                                SELECT *
                                                                FROM curso c2
                                                                WHERE	c2.pensum = p.pensum_prerrequisito AND c2.codigo = p.codigo_prerrequisito AND 
                                                                        (
                                                                                EXISTS 
                                                                                (
                                                                                        SELECT *
                                                                                        FROM 	nota n, ciclo cic
                                                                                        WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9) AND
                                                                                                n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                                STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                                (
                                                                                                        SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                        FROM ciclo
                                                                                                        WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                                )
                                                                                ) 
                                                                                OR EXISTS
                                                                                (
                                                                                        SELECT * 
                                                                                        FROM curso c3 
                                                                                        WHERE 	c3.pensum = c2.pensum AND c3.codigo = c2.codigo AND 
                                                                                                EXISTS 
                                                                                                (
                                                                                                        SELECT *
                                                                                                        FROM equivalencia e
                                                                                                        WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
                                                                                                                e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                                                ) AND NOT EXISTS
                                                                                                (
                                                                                                        SELECT * 
                                                                                                        FROM equivalencia e
                                                                                                        WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
                                                                                                                e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0)))))) AND  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                                                                NOT EXISTS 
                                                                                                                (
                                                                                                                        SELECT *
                                                                                                                        FROM nota n, ciclo cic
                                                                                                                        WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
                                                                                                                                n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                                                                STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                                                                (
                                                                                                                                        SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                                                        FROM ciclo
                                                                                                                                        WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                                                                )
                                                                                                                )
                                                                                                )
                                                                                )
                                                                        )
                                                        )
                                        )
                                        AND 
                                        (
                                                NOT EXISTS 
                                                (
                                                        SELECT *
                                                        FROM nota n
                                                        WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9)
                                                )
                                                AND NOT EXISTS 
                                                (
                                                        SELECT *
                                                        FROM curso c2
                                                        WHERE	c2.pensum = c.pensum AND c2.codigo = c.codigo AND 
                                                        (

                                                                EXISTS 
                                                                (
                                                                        SELECT *
                                                                        FROM equivalencia e
                                                                        WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
                                                                                e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                ) AND NOT EXISTS 
                                                                (

                                                                        SELECT * 
                                                                        FROM equivalencia e
                                                                        WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
                                                                                e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0)))))) AND -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                                NOT EXISTS 
                                                                                (
                                                                                        SELECT *
                                                                                        FROM nota n, ciclo cic
                                                                                        WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
                                                                                                n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                                STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                                (
                                                                                                        SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                        FROM ciclo
                                                                                                        WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                                )
                                                                                )
                                                                )	
                                                        )
                                                )
                                        )
                                        AND NOT EXISTS 
                                        (
                                                SELECT *
                                                FROM asignacion	a
                                                WHERE a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
                                                a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
                                        )
                                        AND NOT EXISTS(
                                        SELECT *
                                        FROM detalle_orden_pago p
                                        INNER JOIN orden_pago o
                                        ON o.orden_pago = p.orden_pago
                                        WHERE o.carnet = $carnet AND p.anio = $anio AND p.semestre = $semestre AND p.evaluacion = $evaluacion
                                        AND p.pensum = c.pensum AND p.codigo = c.codigo
                                    )
                                ORDER BY c.ciclo,c.codigo";


                                $cursos_a_asignar = $consulta;
                            } else {

                                $consulta = "SELECT 1 AS estado, cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
                                FROM curso_ciclo cc, pensum p, curso c
                                WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
                                        cc.pensum = p.pensum AND p.carrera = $in[carrera] AND cc.codigo = c.codigo AND cc.pensum = c.pensum
                                        AND c.codigo IN ('1.07.4', '1.03.4', '1.04.4', 30211, 30311, 30411, 30511, 30611, 30712, 30711, 30811, 30911, 30812)
                                        AND NOT EXISTS 
                                        (
                                                SELECT *
                                                FROM nota n
                                                WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9)
                                        ) AND
                                        NOT EXISTS 
                                        (
                                                SELECT *
                                                FROM prerrequisito_tmp p
                                                WHERE	p.pensum = c.pensum AND p.codigo = c.codigo AND
                                                        NOT EXISTS
                                                        (
                                                                SELECT *
                                                                FROM curso c2
                                                                WHERE	c2.pensum = p.pensum_prerrequisito_tmp AND c2.codigo = p.codigo_prerrequisito_tmp AND 
                                                                        (
                                                                                EXISTS 
                                                                                (
                                                                                        SELECT *
                                                                                        FROM 	nota n, ciclo cic
                                                                                        WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9) AND
                                                                                                n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                                STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                                (
                                                                                                        SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                        FROM ciclo
                                                                                                        WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                                )
                                                                                ) 
                                                                                OR EXISTS
                                                                                (
                                                                                        SELECT * 
                                                                                        FROM curso c3 
                                                                                        WHERE 	c3.pensum = c2.pensum AND c3.codigo = c2.codigo AND 
                                                                                                EXISTS 
                                                                                                (
                                                                                                        SELECT *
                                                                                                        FROM equivalencia e
                                                                                                        WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
                                                                                                                e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                                                ) AND NOT EXISTS
                                                                                                (
                                                                                                        SELECT * 
                                                                                                        FROM equivalencia e
                                                                                                        WHERE	e.pensum = c3.pensum AND e.codigo = c3.codigo AND 
                                                                                                                e.pensum_equivalente = IF(c3.pensum = 5,3,IF(c3.pensum = 3,1,IF(c3.pensum = 16,4,IF(c3.pensum = 4,2,IF(c3.pensum = 18,0,IF(c3.pensum = 20,16,0)))))) AND  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                                                                NOT EXISTS 
                                                                                                                (
                                                                                                                        SELECT *
                                                                                                                        FROM nota n, ciclo cic
                                                                                                                        WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
                                                                                                                                n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                                                                STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                                                                (
                                                                                                                                        SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                                                        FROM ciclo
                                                                                                                                        WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                                                                )
                                                                                                                )
                                                                                                )
                                                                                )
                                                                        )
                                                        )
                                        )
                                        AND 
                                        (
                                                NOT EXISTS 
                                                (
                                                        SELECT *
                                                        FROM nota n
                                                        WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9)
                                                )
                                                AND NOT EXISTS 
                                                (
                                                        SELECT *
                                                        FROM curso c2
                                                        WHERE	c2.pensum = c.pensum AND c2.codigo = c.codigo AND 
                                                        (

                                                                EXISTS 
                                                                (
                                                                        SELECT *
                                                                        FROM equivalencia e
                                                                        WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
                                                                                e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0))))))  -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                ) AND NOT EXISTS 
                                                                (

                                                                        SELECT * 
                                                                        FROM equivalencia e
                                                                        WHERE	e.pensum = c2.pensum AND e.codigo = c2.codigo AND 
                                                                                e.pensum_equivalente = IF(c2.pensum = 5,3,IF(c2.pensum = 3,1,IF(c2.pensum = 16,4,IF(c2.pensum = 4,2,IF(c2.pensum = 18,0,IF(c2.pensum = 20,16,0)))))) AND -- AGREGAR EL PENSUM ACUTAL Y EL PENSUM EQUIVALENTE 
                                                                                NOT EXISTS 
                                                                                (
                                                                                        SELECT *
                                                                                        FROM nota n, ciclo cic
                                                                                        WHERE	n.carnet = $carnet AND n.pensum = e.pensum_equivalente AND n.codigo = e.codigo_equivalente AND n.estado IN (1,2,9) AND
                                                                                                n.anio = cic.anio AND n.semestre = cic.semestre AND n.evaluacion = cic.evaluacion AND
                                                                                                STR_TO_DATE(CONCAT(cic.fecha_anio,'-',cic.fecha_mes),'%Y-%d') < 
                                                                                                (
                                                                                                        SELECT STR_TO_DATE(CONCAT(fecha_anio,'-',fecha_mes),'%Y-%d') 
                                                                                                        FROM ciclo
                                                                                                        WHERE 	anio = $anio AND semestre = $semestre AND evaluacion = $evaluacion
                                                                                                )
                                                                                )
                                                                )	
                                                        )
                                                )
                                        )
                                        AND NOT EXISTS 
                                        (
                                                SELECT *
                                                FROM asignacion	a
                                                WHERE a.anio = cc.anio AND a.semestre = cc.semestre AND a.evaluacion = cc.evaluacion AND
                                                a.pensum = c.pensum AND a.codigo = c.codigo AND a.carnet = $carnet
                                        )
                                        AND NOT EXISTS(
                                        SELECT *
                                        FROM detalle_orden_pago p
                                        INNER JOIN orden_pago o
                                        ON o.orden_pago = p.orden_pago
                                        WHERE o.carnet = $carnet AND p.anio = $anio AND p.semestre = $semestre AND p.evaluacion = $evaluacion
                                        AND p.pensum = c.pensum AND p.codigo = c.codigo
                                    )
                                ORDER BY c.ciclo,c.codigo";
                                $cursos_a_asignar = $consulta;
                            }
                        }
                    }

                    $cursos_laboratorio = & $db->getAll($cursos_a_asignar);
                    if ($db->isError($cursos_laboratorio)) {
                        $consulta = "SELECT 0 AS estado, 'Hubo un error al obtener el listado de asignaturas' AS detalle";
                    } else {

                        if (count($cursos_laboratorio) == 0) {
                            $consulta = "SELECT 0 AS estado, 'No cuenta con cursos de laboratorio de computación pendientes de pago.' AS detalle";
                        }
                    }
                }
            }
        }
    }


    $dom = $constructXML->getFlatXML($consulta, 'listado_cursos', 'curso');
    header("Content-Type: application/xml");
    $dom->formatOutput = true;
    print $dom->saveXML();

    $db->disconnect();
} else {
    $mensaje = "Se ha superado el periodo permitido de inactividad, la sesión se ha cerrado automáticamente, para volver a entrar digite sus datos nuevamente.";
    errorLogin($mensaje);
}
?>