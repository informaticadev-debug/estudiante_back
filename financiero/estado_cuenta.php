<?php

/*
  Proceso de Asignacion para Interciclos
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();

verificarActualizarDatos();

function verificarBoletaDiferente($db, $orden_pago) {
    $consulta = "SELECT d.`seccion`, a.*
                FROM asignacion a
                        INNER JOIN orden_pago o ON o.`orden_pago` = a.`orden_pago`
                        INNER JOIN `detalle_orden_pago` d ON d.`orden_pago` = o.`orden_pago`
                WHERE a.`anio` = 2016 AND a.`semestre` = 2 AND a.`evaluacion` = 2 AND d.`codigo` IS NOT NULL
                        AND a.`codigo` = d.`codigo` AND a.`seccion` <> d.`seccion` and o.orden_pago = $orden_pago
                ORDER BY a.`codigo`, a.`seccion`
                ";
    $result = & $db->getAll($consulta);
    if ($db->isError($result)) {
        $error = true;
        $mensaje = "Hubo un error al verificar los cursos que puede asignarse en este ciclo.";
        $url = "../menus/contenido.php";
        return false;
    }
    if (count($result) > 0) {
        return true;
    }
    false;
}

if (isset($_SESSION[usuario])) {

    $errorLogin = false;
    $error = false;

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

        // Datos de la session actual
        //$extension = $_SESSION['extension'];
        $extension = 0;
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $evaluacion = $_GET['evaluacion'];
        $carnet = $_SESSION['usuario'];
        
        if ($semestre == 1){
            $mesPAI = 'JUNIO';
        }else{
            $mesPAI = 'DICIEMBRE';
        }
        // Consulta de Ordenes Pendientes de Pago
        $consulta_ordenesPago_cursos = "SELECT o.anio, o.semestre, a.evaluacion, a.codigo, o.fecha_orden_pago, TRIM(c.nombre_abreviado) AS asignatura, a.seccion, a.orden_pago
		FROM orden_pago o
		INNER JOIN asignacion a
		ON a.extension = o.extension AND a.anio = o.anio AND a.semestre = o.semestre AND a.evaluacion = o.evaluacion
		AND a.carnet = o.carnet AND a.orden_pago = o.orden_pago
		INNER JOIN curso c
		ON c.codigo = a.codigo AND c.pensum = a.pensum
		WHERE o.extension = $extension AND o.carnet = $carnet
		AND o.no_boleta_deposito IS NULL AND o.fecha_certificacion_banco IS NULL AND o.usuario_certificacion_banco IS NULL
		AND EXISTS(
				SELECT a.carnet
				FROM asignacion a
				WHERE a.extension = o.extension AND a.anio = o.anio AND a.semestre = o.semestre AND a.carnet = o.carnet 
				AND a.orden_pago = o.orden_pago AND a.preasignacion = 1
		) -- AND NOW() < '2019-02-07 23:59:00' -- fecha limite 31/01/2019, modificada de forma verbal por el Arq. Publio, jueves 07 de febrero 2019
		-- AND NOT (o.`anio` = 2017 AND o.`semestre` = 1 AND o.`evaluacion` = 2)
		-- AND NOT (a.codigo = '1.02.3' AND a.`seccion` = 'A')
		-- AND NOT (a.codigo = '3.10.7' AND a.`seccion` = 'A')
		-- AND NOT (a.codigo = '3.08.9' AND a.`seccion` = 'A')
		-- AND NOT (a.codigo = '30642' AND a.`seccion` = 'C')
		-- AND NOT (a.codigo = '3.05.6' AND a.`seccion` = 'B')
		-- AND NOT (a.codigo = '30111' AND a.`seccion` = 'A')
		GROUP BY o.orden_pago ";

        $consulta_ordenesPago_otros = "SELECT o.anio, o.semestre, o.evaluacion, o.rubro AS codigo, o.fecha_orden_pago,
		IF(
			o.rubro = 9 AND d.variante_rubro = 1,
			CONCAT('Examenes Generales ',o.anio, ' Examen Privado'),
		IF(
					o.rubro = 9 AND d.variante_rubro = 2,
					CONCAT('Examenes Generales ',o.anio, ' Examen Público'),
					IF(
						o.rubro = 49999999 AND d.variante_rubro = 1,
						'Impresión de Titulo y registro de titulo (Licenciaturas)',
					IF(
						o.rubro = 49999999 AND d.variante_rubro = 3,
						'Registro de Titulo',
						IF (
							o.rubro = 40 AND d.variante_rubro = 1,
							'Alquiler de togas (Estudiantes)',
							IF (
								o.rubro = 40 AND d.variante_rubro = 2,
								'Alquiler de togas (Arquitectos - USAC, que no son docentes)',
									IF (
										o.rubro = 63 AND d.variante_rubro = 1,
										'Uso de laboratorio de Computaci&oacute;n', ''
									)
							)
						)
					)
				)
			)
		) AS asignatura,
		NULL AS seccion, o.orden_pago
		FROM orden_pago o
		INNER JOIN detalle_orden_pago d
		ON d.orden_pago = o.orden_pago
		WHERE o.carnet = $carnet
		AND o.no_boleta_deposito IS NULL AND o.fecha_certificacion_banco IS NULL AND o.usuario_certificacion_banco IS NULL
		AND o.rubro IN(9,40)
		GROUP BY o.orden_pago";
		
        $consulta_inscripcion_vacaciones = "SELECT o.anio, o.semestre, o.`evaluacion`, '*' AS codigo, o.fecha_orden_pago, 'Inscripcion Interciclos - $mesPAI $anio - PAGO OBLIGATORIO - De no pagar su asignación no será oficial y se dará de baja' AS asignatura, '' AS seccion, o.orden_pago
									FROM orden_pago o
										inner join detalle_orden_pago d on d.orden_pago = o.orden_pago
									WHERE o.carnet = $carnet and d.variante_rubro = 1
									AND o.`anio` = $anio AND o.`semestre` = $semestre AND o.`evaluacion` = 2 AND o.`monto_total` = 20.00
									AND o.no_boleta_deposito IS NULL AND o.fecha_certificacion_banco IS NULL AND o.usuario_certificacion_banco IS NULL AND o.estado <> 4

                                    AND NOT EXISTS(SELECT * FROM orden_pago o2 WHERE o2.carnet = o.carnet and d.variante_rubro = 1
									AND o2.`anio` = o.anio AND o2.`semestre` = o.semestre AND o2.`evaluacion` = 2 AND o2.`monto_total` = 20.00
									AND o2.no_boleta_deposito IS NOT NULL AND o2.fecha_certificacion_banco IS NOT NULL AND o2.usuario_certificacion_banco IS NOT NULL AND o2.estado = 4)
                                   GROUP BY o.orden_pago 
                                   ";

        $consultas_ordenes = $consulta_ordenesPago_otros . " UNION ALL " . $consulta_ordenesPago_cursos . " UNION ALL " . $consulta_inscripcion_vacaciones;
        // var_dump($consultas_ordenes); die;
        $ordenes_pago = & $db->getAll($consultas_ordenes);
        if ($db->isError($ordenes_pago)) {
            $error = true;
            $mensaje = "Hubo un error al determinar el estado de las ordenes de pago pendientes." . mysql_error();
        }

        if (!$error) {

            // Cargando la pagina para mostrar las ordenes de Pago.
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('estado_cuenta.html');

            // Datos Ordenes de Pago
            if (count($ordenes_pago) <> 0) {

                foreach ($ordenes_pago AS $op) {

                    if (verificarBoletaDiferente($db, $op['orden_pago'])) {
                        continue;
                    }

                    if ($op[evaluacion] == 1) {
                        $template->setVariable(array(
                            'evaluacion' => "<div class='btn btn-info btn-xs'>S</div>",
                            'id_evaluacion' => $op[evaluacion]
                        ));
                    }

                    if ($op[evaluacion] == 2) {
                        $template->setVariable(array(
                            'evaluacion' => "<div class='btn btn-warning btn-xs'>I</div>",
                            'id_evaluacion' => $op[evaluacion]
                        ));
                    }

                    if ($op[evaluacion] == 3 || $op[evaluacion] == 4) {
                        $template->setVariable(array(
                            'evaluacion' => "<div class='btn btn-danger btn-xs'>R</div>",
                            'id_evaluacion' => $op[evaluacion]
                        ));
                    }

                    $template->setVariable(array(
                        'anio' => $op[anio],
                        'semestre' => $op[semestre],
                        'codigo' => $op[codigo],
                        'asignatura' => $op[asignatura],
                        'seccion' => $op[seccion],
                        'orden_pago' => $op[orden_pago]
                    ));
                    $template->parse('ordenes_pago');
                }
            } else {

                $template->setVariable(array(
                    'sin_ordenes_pendientes' => "<div class='alert alert-success'>No existen pagos pendientes.</div>"
                ));
                $template->parse('ordenes_pago');
            }

            $template->show();
            exit();
        }

        if ($error) {
            mostrarError($mensaje);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
