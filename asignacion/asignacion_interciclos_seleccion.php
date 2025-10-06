<?php

/*
  Proceso de Asignacion para Interciclos
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 * 
 * pruebas 9317930
 * 
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../config/local.php';

$permitir_ingreso = '';

$array_liberar_repitencia = [
       // 200610785, //providencia 011-2019,
       // 200321108, //providencia 167-2019
       // 201122701, //providencia pendiente, dibujo constructivo con código que ya no se imparte..
];

function obtenerCursosAAsignar($db, $carnet, $carrera, $anio, $semestre, $extension, $evaluacion) {
    if ($carrera == 3) {

        // Consulta de cursos a Asignar en la Licenciatura en Diseño Gráfico
        $consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
            FROM curso_ciclo cc, pensum p, curso c
            WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
                    cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND
                    NOT EXISTS 
                    (
                            SELECT *
                            FROM nota n
                            WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9,10,12)
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
                                                                    WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9,10)
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
                                    WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9,10)
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
            ORDER BY c.ciclo,c.codigo";
        $cursos_a_asignar = $consulta_cursos_asignar;
    }

    if ($carrera == 1) {

        // Verificacion de linea de Herramientas Digitales
        // 		-> Para ver que linea del pensum ha elegido el estudiante a seguir
        $consulta = "SELECT n.codigo 
                    FROM nota n
                    WHERE n.codigo IN ('1.03.4', '1.04.4', '1.07.4', '1.09.4') AND n.carnet = $carnet";
        $linea_herramientas = & $db->getAll($consulta);
        if ($db->isError($linea_herramientas)) {
            $error = true;
            $mensaje = "Hubo un error al determinar la linea del pensum que ha seguido el estudiante.";
            $url = "../menus/contenido.php";
        }

        // Consulta de cursos a Asignar en la Licenciatura en Diseño Gráfico
        // 	-> Tomando en cuenta la nueva linea de Herramientas Digitales que el estudiante siga o no
        if (count($linea_herramientas) == 0) {

            $consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
                FROM curso_ciclo cc, pensum p, curso c
                WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
                        cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND
                        NOT EXISTS 
                        (
                                SELECT *
                                FROM nota n
                                WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9,10)
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
                                                                        WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9,10) 
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
                                        WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9,10)
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
                ORDER BY c.ciclo,c.codigo";
            $cursos_a_asignar = $consulta_cursos_asignar;
        } else {

            $consulta_cursos_asignar = "SELECT cc.pensum,p.anio AS anio_pensum,cc.codigo,c.nombre
                    FROM curso_ciclo cc, pensum p, curso c
                    WHERE 	cc.extension = $extension AND cc.anio = $anio AND cc.semestre = $semestre AND cc.evaluacion = $evaluacion AND
                            cc.pensum = p.pensum AND p.carrera = $carrera AND cc.codigo = c.codigo AND cc.pensum = c.pensum AND
                            NOT EXISTS 
                            (
                                    SELECT *
                                    FROM nota n
                                    WHERE	n.carnet = $carnet AND n.pensum = cc.pensum AND n.codigo = cc.codigo AND n.estado IN (1,2,9,10)
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
                                                                            WHERE	n.carnet = $carnet AND n.pensum = c2.pensum AND n.codigo = c2.codigo AND n.estado IN (1,2,9,10)
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
                                            WHERE 	n.carnet = $carnet AND n.pensum = c.pensum AND n.codigo = c.codigo AND n.estado IN (1,2,9,10)
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
                    ORDER BY c.ciclo,c.codigo";
            $cursos_a_asignar = $consulta_cursos_asignar;
        }
    }

    $cursos_asignar = & $db->getAll($cursos_a_asignar);
    if ($db->isError($cursos_asignar)) {
        $error = true;
        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
        $url = "../menus/contenido.php";
        return false;
    }

    return $cursos_asignar;
}

function obtenerCargaCurso($db, $anio, $semestre, $extension, $evaluacion, $curso) {
    $codigo = $curso['codigo'];
    $seccion = $curso['seccion'];
    $consulta = "
                SELECT s.cupo, count(*) as asignados
                FROM seccion s
                    INNER JOIN asignacion a on s.anio = a.anio and s.extension = a.extension and s.semestre = a.semestre and s.evaluacion = a.evaluacion 
                    and s.pensum = a.pensum and s.codigo = a.codigo and s.seccion = a.seccion
                WHERE s.anio = $anio and s.semestre = $semestre and s.extension = $extension and s.evaluacion = $evaluacion and s.codigo = '$codigo' and s.seccion = '$seccion' AND s.status = 'A'
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

function obtenerCursosAsignados($db, $anio, $semestre, $extension, $evaluacion, $carnet) {
    $consulta = "
                SELECT a.carnet, a.codigo, a.seccion
                FROM asignacion a
                WHERE a.anio = $anio and a.semestre = $semestre and a.extension = $extension and a.evaluacion = $evaluacion and a.carnet = $carnet
            ";
    $cursos = & $db->getAll($consulta);
    if ($db->isError($cursos)) {
        $error = true;
        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
        $url = "../menus/contenido.php";
        return false;
    }
    $curso_array = array();
    foreach ($cursos as $curso) {
        $curso_array[$curso['codigo']] = $curso['seccion'];
    }
    //var_dump($curso_array); die;
    return $curso_array;
}

session_start();
if (isset($_SESSION[usuario])) {

    $errorLogin = false;
    $error = false;
    $aviso = false;

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

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_GET['evaluacion'];
        $carnet = $_SESSION['usuario'];
//	if($carnet == 201809546) {var_dump($extension, $anio, $semestre, $evaluacion); die;}
	        if (in_array($carnet, [
		        
	        ])) {
          $error = true;
          $mensaje = "No puede asignarse en Interciclos por instrucciones de la Dirección de Escuela de Arquitectura";
          $url = "../menus/contenido.php";
          } 

        if (isset($_SESSION['pensum'])) {

            // Quitando la sesion para la asignatura seleccionada.			
            unset($_SESSION['pensum']);
            unset($_SESSION['codigo']);
            unset($_SESSION['seccion']);
        } else {

            $_SESSION['evaluacion'] = $evaluacion;
        }

        // Verificacion de la inscripcion del estudiante en el ciclo actual
        $consulta = "SELECT i.carnet, IF (
			EXISTS(
				SELECT i2.carrera 
				FROM inscripcion i2
				WHERE i2.extension = i.`extension` AND i2.anio = i.`anio` AND i2.semestre = i.`semestre` AND i2.carnet = i.`carnet` AND i2.carrera > 3
			),
				(
					SELECT i3.carrera 
					FROM inscripcion i3
					WHERE i3.extension = i.`extension` AND i3.anio = i.`anio` AND i3.semestre = i.`semestre` AND i3.carnet = i.`carnet`
					AND i3.carrera > 3
				)
			,i.carrera
		) AS carrera
        FROM inscripcion i
        WHERE i.extension = $extension AND i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet
		-- AND i.carrera = 1
        GROUP BY i.carrera";
        $inscripcion = & $db->getAll($consulta);
        if ($db->isError($inscripcion)) {
            $error = true;
            $mensaje = "Hubo un error al determinar tu inscripcion en el ciclo actual.";
            $url = "../menus/contenido.php";
        } else {
            //variable de control para verificar la cantidad de cursos en repitencia...
            $cursos_repitencia = [];
            //verificar repitencia general...
            $data = array(
                "auth" => array(
                    "user" => "arqws",
                    "passwd" => "a08!¡+¿s821!kdui23#kd$"
                ),
                'id' => $carnet,
            );
            $dataRepitencia = json_decode(postRequest($api_uri . 'Repitencia', $data), true);

            // Verificacion de la existencia de repitencia del estudiante
            foreach ($inscripcion AS $in) {
                foreach ($dataRepitencia as $pensum => $cursos) {
                    //verificar que sea un pensum actual valido
                    if (($pensum == 5 && $in["carrera"] == 1) || ($pensum == 20 && $in["carrera"] == 3)) {
                        foreach ($cursos as $codigo => $cursoInfo) {
                            //verificar que el curso aun no se haya aprobado y que no haya entrado en repitencia en el semestre actual...
                            if ($cursoInfo["resultado"]["aprobado"] == 0 && $cursoInfo["resultado"]["ciclo_repitencia"] != "$anio-$semestre") {
                                $cursos_repitencia[$codigo] = $cursoInfo;
                            }
                        }
                    }
                }
            }
            
            if (!empty($cursos_repitencia) && !in_array($carnet, $array_liberar_repitencia)) {
                $error = true;
                $mensaje = "Lo sentimos pero usted no puede asignarse por tener repitencia en los siguientes cursos: <br /><br />";
                foreach ($cursos_repitencia as $codigo => $cursoInfo) {
                    $mensaje .= "<b>" . $codigo . " - " . $cursoInfo["nombre"] . "</b><br />";
                    $mensaje .= "<span style='margin-left: 20px;'>Ciclo en el que entro en repitencia: <b>" . $cursoInfo["resultado"]["ciclo_repitencia"] . "</b></span><br /><br />";
                }
                $url = "../menus/contenido.php";
                aviso($mensaje, $url);
                exit;
            } else {

                // Verificacion del periodo de Asignacion.
                $consulta = "SELECT c.fecha_inicio_asignacion, c.fecha_fin_asignacion, NOW() AS fecha_actual,
				DATE_FORMAT(c.fecha_inicio_asignacion, CONCAT(
					(SELECT
                      DATE_FORMAT(c.fecha_inicio_asignacion, '%d'))
                        , ' de ', 
                    (SELECT
                      CASE MONTH(c.fecha_inicio_asignacion)
                        WHEN 1  THEN 'enero'
                        WHEN 2  THEN 'febrero'
                        WHEN 3  THEN 'marzo'
                        WHEN 4  THEN 'abril'
                        WHEN 5  THEN 'mayo'
                        WHEN 6  THEN 'junio'
                        WHEN 7  THEN 'julio'
                        WHEN 8  THEN 'agosto'
                        WHEN 9  THEN 'septiembre'
                        WHEN 10 THEN 'octubre'
                        WHEN 11 THEN 'noviembre'
                        WHEN 12 THEN 'diciembre'
                    END),
                    ' del ',
					(SELECT
                      DATE_FORMAT(c.fecha_inicio_asignacion, '%Y'))					
					, ' a las ',
					(SELECT
						DATE_FORMAT(c.fecha_inicio_asignacion, '%H:%i'))
					)) AS inicio_asignacion, e.nombre AS evaluacion, s.nombre AS semestre
				FROM ciclo c
				INNER JOIN evaluacion e
				ON e.evaluacion = c.evaluacion
				INNER JOIN semestre s
				ON s.semestre = c.semestre
				WHERE c.anio = $anio AND c.semestre = $semestre AND c.evaluacion = $evaluacion AND c.asignacion = 1";
                $ciclo = & $db->getRow($consulta);
                if ($db->isError($ciclo)) {
                    $error = true;
                    $mensaje = "Hubo un error al determinar el ciclo actual. E.01";
                    $url = "../menus/contenido.php";
                } else {

                    if ($inscripcion == 0) {
                        $error = true;
                        $mensaje = "Usted no esta inscrito en el ciclo actual. Por favor verifique esta información";
                        $url = "../menus/contenido.php";
                    } else {
                        // Verificacion del periodo de Asignacion.
                        if ($ciclo == 0) {
                            $error = true;
                            $mensaje = "El período de asignación de cursos para Interciclos no ha sido definido.";
                            $url = "../menus/contenido.php";
                        } else {

                            if ($ciclo[fecha_inicio_asignacion] > $ciclo[fecha_actual] && $permitir_ingreso != $carnet) {
                                $error = true;
                                $mensaje = "El período de asignación de cursos para $ciclo[evaluacion] $ciclo[semestre] dará inicio el: $ciclo[inicio_asignacion] horas.";
                                //$mensaje = "El período de asignación de cursos para $ciclo[evaluacion] $ciclo[semestre] dará inicio el:  6 de Junio a las 07:00 horas.";
                                $url = "../menus/contenido.php";
                            } else {

                                if ($ciclo[fecha_fin_asignacion] < $ciclo[fecha_actual] && $permitir_ingreso != $carnet) {
                                    $error = true;
                                    $mensaje = "El período de asignación de cursos para $ciclo[evaluacion] $ciclo[semestre] ha finalizado.";
                                    $url = "../menus/contenido.php";
                                } else {

                                    if (($ciclo[fecha_inicio_asignacion] < $ciclo[fecha_actual] AND $ciclo[fecha_fin_asignacion] > $ciclo[fecha_actual]) || $permitir_ingreso == $carnet) {
					//if($carnet == 201809546) {var_dump($inscripcion); die;}
                                        foreach ($inscripcion AS $in) {
                                            $carrera = $in[carrera];

                                            $cursos_asignar = obtenerCursosAAsignar($db, $carnet, $carrera, $anio, $semestre, $extension, $evaluacion);
                                            if (count($cursos_asignar) <> 0) {
                                                foreach ($cursos_asignar as $ca) {
                                                    $a[] = array("pensum" => $ca['pensum'], "anio_pensum" => $ca['anio_pensum'], "codigo" => $ca['codigo'], "nombre" => $ca['nombre']);
                                                }
					    }
					    //if($carnet == 201809546) {var_dump($a); die;}
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$error && !$aviso) {

            // Cargando la pagina de seleccion de Asignatura para Interciclos
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('asignacion_interciclos_seleccion.html');

            if (!empty($a)) {

                $cursos_asignados = obtenerCursosAsignados($db, $anio, $semestre, $extension, $evaluacion, $carnet);

		foreach ($a AS $cu) {
		    //if($carnet == 201809546) {var_dump($cu); die;}

		    //verificar si esta en la lista de solicitud de tercer curso..
		    $consulta = "SELECT 1 FROM asignacion_solicitud WHERE anio = $anio AND $semestre = semestre AND evaluacion = $evaluacion AND codigo = '$cu[codigo]' ";
		    $resultYaSolicitado = & $db->getAll($consulta);
		    if (!empty($resultYaSolicitado)) continue;

                    // Verificacion de secciones disponibles para asignacion.
                    $consulta = "SELECT s.codigo, s.seccion, s.cupo, count(a.carnet) as asignados
                                    FROM seccion s
                                            LEFT JOIN asignacion a ON s.anio = a.anio AND s.extension = a.extension AND s.semestre = a.semestre AND s.evaluacion = a.evaluacion 
                                            AND s.pensum = a.pensum AND s.codigo = a.codigo AND s.seccion = a.seccion
                                    WHERE s.anio = $anio AND s.semestre = $semestre AND s.extension = $extension AND s.evaluacion = $evaluacion 
                                            AND s.codigo = '$cu[codigo]' AND s.status = 'A'
                                    GROUP BY 1,2,3
						";
                    $seccion = & $db->getAll($consulta); //var_dump($consulta); die;
                    if ($db->isError($seccion)) {
                        $error = true;
                        $mensaje = "Hubo un error al verificar las secciones disponibles.";
                        $url = "../menus/contenido.php";
                    }

                    // Lista de cursos aperturados.
                    $template->setVariable(array(
                        'anio_pensum' => $cu[anio_pensum],
                        'pensum' => $cu[pensum],
                        'codigo' => $cu[codigo],
                        'asignatura' => $cu[nombre]
                    ));

                    if (!empty($seccion)) {

                        foreach ($seccion AS $se) {

                            if ($se[codigo] == $cu[codigo]) {

                                $template->setVariable(array(
                                    'seccion' => $se[seccion] . " (Cupo: $se[asignados]/$se[cupo])",
                                    'value' => ($se[asignados] < $se[cupo]) ? $se[seccion] : '--',
                                    'seleccionado' => (isset($cursos_asignados[$se[codigo]]) && $cursos_asignados[$se[codigo]] == $se[seccion]) ? 'SELECTED' : ''
                                ));
                                $template->parse('secciones_disponibles');
                            }
                        }
                    } else {

                        $template->setVariable(array(
                            'seccion' => "--"
                        ));
                        $template->parse('secciones_disponibles');
                    }

                    $template->parse('seleccion_asignaturas');

                    if ($error) {
                        error($mensaje, $url);
                    }
                }
            } else {

                $template->setVariable(array(
                    'sin_asignaciones_disponibles' => "<div class='alert alert-danger'>No existen asignaciones disponibles.</div>"
                ));
                $template->parse('seleccion_asignaturas');
            }

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_aviso'])) {
                $mensaje_aviso = $_SESSION['mensaje_aviso'];
                $template->setVariable(array(
                    'mensaje_aviso' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #DF7401; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Aviso</h4>
									</div>
									<div class='modal-body'>
										$mensaje_aviso
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-warning' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
								</div>
							</div>
						</div>"
                ));
                unset($_SESSION['mensaje_aviso']);
            }

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_error'])) {
                $mensaje_error = $_SESSION['mensaje_error'];
                $template->setVariable(array(
                    'mensaje_error' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #DF0101; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Error en proceso</h4>
									</div>
									<div class='modal-body'>
										$mensaje_error
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-danger' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
								</div>
							</div>
						</div>"
                ));
                unset($_SESSION['mensaje_error']);
            }

            // Proceso culminado con exito
            if (isset($_SESSION['proceso_finalizado'])) {
                $proceso_finalizado = $_SESSION['proceso_finalizado'];
                $template->setVariable(array(
                    'mensaje_proceso_finalizado' => "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #084B8A; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Proceso finalizado</h4>
									</div>
									<div class='modal-body'>
										$proceso_finalizado
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-primary' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
								</div>
							</div>
						</div>"
                ));
                unset($_SESSION['proceso_finalizado']);
            }

            $template->show();
            exit;
        }

        if ($error) {
            error($mensaje, $url);
        }

        if ($aviso) {
            aviso($mensaje, $url);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
