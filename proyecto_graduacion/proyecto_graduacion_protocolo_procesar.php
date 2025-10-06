<?php

/*
  Documento  : proyecto_graduacion_solicitud_procesar.php
  Creado el  : 12 de junio de 2014, 10:06
  Author     : Angel Caal
  Description:
  Confirmación de la solicitud para entrar a revisión para su futura aprobación o desaprobación
  por parte del comite de proyecto de graduación.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();
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

        $db->setfetchmode(DB_FETCHMODE_ASSOC);
        $db->autoCommit(false);

        $extension = $_SESSION['extension'];
        $anio = DATE("o");
        $semestre = $_SESSION['semestre'];
        $carnet = $_SESSION['usuario'];
        $carrera = inscripcion_estudiante($db, $anio, $semestre, $carnet);
        $numero_tema = $_SESSION['numero_tema'];
        $antecedentes = $_SESSION['antecedentes'];
        $justificacion = $_SESSION['justificacion'];
        $objetivos = $_SESSION['objetivos'];
        $planteamiento_problema = $_SESSION['planteamiento_problema'];
        $delimitacion = $_SESSION['delimitacion'];
        $asesores = $_SESSION['asesores'];
        $asesor_externo = $_SESSION['asesor_externo'];
        $grupo_objetivo = $_SESSION['grupo_objetivo'];
        $metodos = $_SESSION['metodos'];
        $bibliografia = $_SESSION['bibliografia'];
        $modalidad = $_SESSION['modalidad'];

        $signos = array("'", "°", '"');
        $reemplazo = array("\'", "\°", '\"');

        $antecedentes = str_replace($signos, $reemplazo, $antecedentes);
        $justificacion = str_replace($signos, $reemplazo, $justificacion);
        $objetivos = str_replace($signos, $reemplazo, $objetivos);
        $planteamiento_problema = str_replace($signos, $reemplazo, $planteamiento_problema);
        $delimitacion = str_replace($signos, $reemplazo, $delimitacion);
        $grupo_objetivo = str_replace($signos, $reemplazo, $grupo_objetivo);
        $metodos = str_replace($signos, $reemplazo, $metodos);
        $bibliografia = str_replace($signos, $reemplazo, $bibliografia);

        // Almacenar la bitacora de protocolo
        $consulta = "INSERT INTO proyecto_graduacion_protocolo_bitacora
		(numero_tema, extension, carnet, carrera, fecha_creacion)
		VALUES (
			$numero_tema, 
			$extension, 
			$carnet,
			$carrera,
			NOW()
		)";
        $registrar_bitacora_protocolo = & $db->Query($consulta);
        if ($db->isError($registrar_bitacora_protocolo)) {
            $error = true;
            $mensaje = "Hubo un problema al registrar la bitacora del protocolo, por favor intente nuevamente.<!-- $consulta  $carrera $anio $semestre $carnet -->";
            $url = $_SERVER[HTTP_REFERER];
            $db->rollback();
        } else {

            // Eliminar si existiera una solicitud previa
            $consulta = "DELETE FROM proyecto_graduacion_protocolo
			WHERE numero_tema = $numero_tema";
            $eliminar = & $db->Query($consulta);
            if ($db->isError($eliminar)) {
                $error = true;
                $mensaje = "Hubo un problema al eliminar el protocolo anterior.";
                $url = $_SERVER[HTTP_REFERER];
                $db->rollback();
            } else {

                // Registro de protocolo para proceder a verificación por el comité
                $consulta = "INSERT INTO proyecto_graduacion_protocolo
				(numero_tema, antecedentes, justificacion, objetivos, planteamiento_problema, delimitacion, grupo_objetivo, metodos, bibliografia, fecha_creacion)
				VALUES (
					$numero_tema,
					'$antecedentes',
					'$justificacion',
					'$objetivos',
					'$planteamiento_problema',
					'$delimitacion',
					'$grupo_objetivo',
					'$metodos',
					'$bibliografia',
					NOW()
				)";
                //var_dump($consulta);die;
                $registrar_protocolo = & $db->Query($consulta);
                if ($db->isError($registrar_protocolo)) {
                    $error = true;
                    $mensaje = "Hubo un problema durante el registro del protocolo, por favor intente nuevamente." . mysql_error();
                    $url = $_SERVER[HTTP_REFERER];
                    $db->rollback();
                } else {

                    // Actualización de estado en solicitud de tema aprobado para inhabilitar otra solicitud de protocolo

                    if (!empty($modalidad)) {

                        $consulta = "UPDATE proyecto_graduacion p
						SET p.estado = 3, p.modalidad = $modalidad
						WHERE p.carnet = $carnet AND p.numero_tema = $numero_tema";
                    } else {

                        $consulta = "UPDATE proyecto_graduacion p
						SET p.estado = 3
						WHERE p.carnet = $carnet AND p.numero_tema = $numero_tema";
                    }
                    $estado_protocolo = & $db->Query($consulta);
                    if ($db->isError($estado_protocolo)) {
                        $error = true;
                        $mensaje = "Hubo un problema al momento de actualizar el estado para solicitud de protocolo.";
                        $url = $_SERVER[HTTP_REFERER];
                        $db->rollback();
                    } else {

                        // Registro de asesores propuestos por el estudiante; los asesores locales que son docentes de la facultad
                        //eliminando asesores si ya existian algunos...
                        $consulta = "DELETE FROM proyecto_graduacion_asesores WHERE numero_tema = $numero_tema";
                        $result_eliminar_asesores = & $db->Query($consulta);
                        //contando asesores a insertar...
                        $insertados = 0;
                        foreach ($asesores AS $as) {

                            $consulta = "INSERT INTO proyecto_graduacion_asesores
							(numero_tema, registro_personal)
							VALUES (
								$numero_tema,
								$as
							)";
                            $asesores_locales = & $db->Query($consulta);
                            if ($db->isError($asesores_locales)) {
                                $error = true;
                                $mensaje = "Hubo un problema durante el registro de los asesores locales.";
                                $url = $_SERVER[HTTP_REFERER];
                                $db->rollback();
                            } else {
                                $insertados++;
                                if ($insertados >= 3 || ($insertados >= 2 AND isset($_SESSION['asesor_externo']))) {
                                    break;
                                }
                            }
                        }

                        // Registro de asesor externo si existiera asesor externo
                        if (isset($_SESSION['asesor_externo'])) {

                            $anio_dos = DATE("y");

                            // Registro de personal para asesor externo
                            $consulta = "SELECT COUNT(*) + 1 AS total
							FROM docente d
							WHERE LEFT(d.registro_personal,2) = $anio_dos";
                            $registro_ae = & $db->getRow($consulta);
                            if ($db->isError($registro_ae)) {
                                $error = true;
                                $mensaje = "Hubo un error durante la generación del registro de personal para el asesor externo.";
                                $url = $_SERVER[HTTP_REFERER];
                                $db->rollback();
                            } else {

                                $registro_personal_ae = $anio_dos . "20" . ($registro_ae[total] + 24);

                                foreach ($asesor_externo AS $ase) {

                                    // Registro de docente en la tabla de docentes como asesor
                                    $consulta = "INSERT INTO docente
									(registro_personal, nombre, apellido, titulo, correo_personal, status, pin, extension, usuario, telefono_celular, fecha_actualizacion, colegiado)
									VALUES (
										$registro_personal_ae,
										UCASE('$ase[ae_nombres]'),
										UCASE('$ase[ae_apellidos]'),
										'$ase[ae_profesion]',
										'$ase[ae_email]',
										'ALTA',
										UCASE(LEFT(MD5('$ase[ae_nombres]]'),8)),
										0,
										'docente',
										'$ase[ae_telefono]',
										NOW(),
                                                                                '$ase[ae_colegiado]'
									)";
                                    //var_dump($consulta);  die;
                                    $registro_docente_ae = & $db->Query($consulta);
                                    if ($db->isError($registro_docente_ae)) {
                                        var_dump($consulta);  die;
                                        if (mysql_errno() == 1062) {

                                            $nombre_ae = $ase['ae_nombres'];
                                            $apellido_ae = $ase['ae_apellidos'];

                                            // Verificar si el asesor externo ha sido registrado anteriormente
                                            $consulta = "SELECT *
											FROM docente d
											WHERE d.nombre LIKE '%$nombre_ae%' AND d.apellido LIKE '%$apellido_ae%'";
                                            $existencia_asesor_externo = & $db->getRow($consulta);
                                            if ($db->isError($existencia_asesor_externo)) {
                                                $error = true;
                                                $mensaje = "Hubo un error al registrar los datos del asesor externo.";
                                                $url = $_SERVER[HTTP_REFERER];
                                                $db->rollback();
                                            } else {

                                                if ($existencia_asesor_externo <> 0) {

                                                    $consulta = "INSERT INTO proyecto_graduacion_asesores
													(numero_tema, registro_personal, asesor_externo)
													VALUES (
														$numero_tema,
														$existencia_asesor_externo[registro_personal],
														1
													)";
                                                    $registrar_ae = & $db->Query($consulta);
                                                    if ($db->isError($registrar_ae)) {
                                                        $error = true;
                                                        $mensaje = "Hubo un problema al almacenar al asesor externo en el proyecto de graduación.";
                                                        $url = $_SERVER[HTTP_REFERER];
                                                        $db->rollback();
                                                    } else {
                                                        $db->commit();
                                                    }
                                                } else {

                                                    $consulta = "SELECT *
													FROM docente d
													WHERE d.registro_personal = $registro_personal_ae";
                                                    $comprobar_registro = & $db->getRow($consulta);

                                                    WHILE ($comprobar_registro <> 0) {

                                                        $registro_personal_ae++;

                                                        //Verificar insersion de datos para agregar al asesor externo que coincide con otros registros almacenados
                                                        $consulta = "SELECT *
														FROM docente d
														WHERE d.registro_personal = $registro_personal_ae";
                                                        $comprobar_registro = & $db->getRow($consulta);
                                                    }

                                                    // Registro de docente en la tabla de docentes como asesor
                                                    $consulta = "INSERT INTO docente
													(registro_personal, nombre, apellido, titulo, correo_personal, status, pin, extension, usuario, telefono_celular, fecha_actualizacion, colegiado)
													VALUES (
														$registro_personal_ae,
														UCASE('$ase[ae_nombres]'),
														UCASE('$ase[ae_apellidos]'),
														'$ase[ae_profesion]',
														'$ase[ae_email]',
														'ALTA',
														UCASE(LEFT(MD5('$ase[ae_nombres]]'),8)),
														0,
														'docente',
														'$ase[ae_telefono]',
														NOW(),
                                                                                                                $ase[ae_colegiado]
													)";
                                                    $registro_docente_ae = & $db->Query($consulta);
                                                    if ($db->isError($registro_docente_ae)) {
                                                        $error = true;
                                                        $mensaje = "Hubo un error al registrar los datos del asesor externo despues de la verificación de registros, por favor informe al desarrollador 
														enviando un correo a: informatica@farusac.edu.gt";
                                                        $url = $_SERVER[HTTP_REFERER];
                                                        $db->rollback();
                                                    } else {

                                                        // Claves siempre a mayusculas
                                                        $db->Query("UPDATE docente d
														SET d.pin = UCASE(d.pin)
														WHERE d.registro_personal = $registro_personal_ae");

                                                        $consulta = "INSERT INTO proyecto_graduacion_asesores
														(numero_tema, registro_personal, asesor_externo)
														VALUES (
															$numero_tema,
															$registro_personal_ae,
															1
														)";
                                                        $registrar_ae = & $db->Query($consulta);
                                                        if ($db->isError($registrar_ae)) {
                                                            $error = true;
                                                            $mensaje = "Hubo un problema en el registro del asesor externo.";
                                                            $url = $_SERVER[HTTP_REFERER];
                                                            $db->rollback();
                                                        } else {
                                                            $db->commit();
                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            $error = true;
                                            $mensaje = "Hubo un error al registrar los datos del asesor externo.";
                                            $url = $_SERVER[HTTP_REFERER];
                                            $db->rollback();
                                        }
                                    } else {

                                        // Claves siempre a mayusculas
                                        $db->Query("UPDATE docente d
										SET d.pin = UCASE(d.pin)
										WHERE d.registro_personal = $registro_personal_ae");

                                        $consulta = "INSERT INTO proyecto_graduacion_asesores
										(numero_tema, registro_personal, asesor_externo)
										VALUES (
											$numero_tema,
											$registro_personal_ae,
											1
										)";
                                        $registrar_ae = & $db->Query($consulta);
                                        if ($db->isError($registrar_ae)) {
                                            $error = true;
                                            $mensaje = "Hubo un problema en el registro del asesor externo.";
                                            $url = $_SERVER[HTTP_REFERER];
                                            $db->rollback();
                                        }
                                    }
                                }
                            }
                        }

                        if ($registrar_ae == 1 || $asesores_locales == 1) {
                            $db->commit();
                        }
                    }
                }
            }
        }

        if (!$error) {

            $proceso_finalizado = "Se ha creado una solicitud con el tema propuesto, por favor espere a que el comite califique el tema propuesto,
                mas adelante se le notificará el estado.";
            $_SESSION['proceso_finalizado'] = $proceso_finalizado;
            echo "
                <script>
                    window.open('../proyecto_graduacion/proyecto_graduacion_gestion.php','contenido');
                </script>
            ";
        }

        if ($error) {
            error($mensaje, $url);
        }

        $db->disconnect();
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
