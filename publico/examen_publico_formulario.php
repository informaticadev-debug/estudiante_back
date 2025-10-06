<?php

/*
  EXAMEN PUBLICO
  -> Proceso para obtener examen publico
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
        $error = false;

        $carnet = $_SESSION['usuario'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carrera = $_SESSION['carrera'];

        $inscripcion = verificarInscripcion($db, $anio, $semestre, $carnet);

        if ($inscripcion == 0) {
            $error = true;
            $mensaje = "Debe estar inscrito en el ciclo actual para poder solicitar Examen Público.";
            $url = $_SERVER[HTTP_REFERER];
        } else {

            // Verificar examen privado aprobado y con cierre 
            $consulta = "SELECT *
            FROM carrera_estudiante c
            WHERE c.carnet = $carnet AND c.carrera = $carrera";
            $autorizacion_publico = & $db->getRow($consulta);
            if ($db->isError($autorizacion_publico)) {
                $error = true;
                $mensaje = "Hubo un problema durante la revisión de los requisitos para optar a examen público.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                // Consulta de EPS aprobado
                // Para casos en los que se dio el cierre antes de la aprobación de EPS
                $consulta = "SELECT *
                FROM estudiante e
                WHERE e.carnet = $carnet
                AND (
                    EXISTS(
                        SELECT *
                        FROM nota n
                        WHERE n.carnet = e.carnet AND n.codigo IN ('1.11.1') AND n.aprobado = 1
                    )
                    OR EXISTS(
                        SELECT *
                        FROM carrera_estudiante c
                        WHERE c.carnet = e.carnet AND c.fecha_eps IS NOT NULL                        
                    )
                )";
                $eps = & $db->getAll($consulta);
                if ($db->isError($eps)) {
                    $error = true;
                    $mensaje = "Hubo un problema durante la verificación de aprobación de EPS.";
                    $url = $_SERVER[HTTP_REFERER];
                } else {

                    if (count($eps) == 0 && $carrera <> 2) {
                        $error = true;
                        $mensaje = "No ha aprobado EPS.";
                        $url = $_SERVER[HTTP_REFERER];
                    } else {

                        if ($autorizacion_publico[fecha_cierre] == NULL) {
                            $error = true;
                            $mensaje = "No ha concluido la carrera (Sin cierre de pensum registrado).";
                            $url = $_SERVER[HTTP_REFERER];
                        } else {

                            // Verificacion de aprobacion de examen privado
                            // En el nuevo sistema de gestión de examenes privados
                            $consulta = "SELECT *
                            FROM examen_privado e
                            WHERE e.carnet = $carnet AND e.carrera = $carrera AND e.aprobado = 1";
                            $aprobacion_privado = & $db->getRow($consulta);
                            if ($db->isError($aprobacion_privado)) {
                                $error = true;
                                $mensaje = "Hubo un problema durante la verificación de aprobación de examen privado.";
                                $url = $_SERVER[HTTP_REFERER];
                            } else {

                                if ($aprobacion_privado == 0) {
                                    $error = true;
                                    $mensaje = "No se ha aprobado el examen privado";
                                    $url = $_SERVER[HTTP_REFERER];
                                } else {


                                    if ($autorizacion_publico[fecha_privado] == NULL AND $autorizacion_publico[acta_privado] == NULL) {
                                        $error = true;
                                        $mensaje = "No ha aprobado el examen privado.";
                                        $url = $_SERVER[HTTP_REFERER];
                                    } else {

                                        // Datos del estudiante
                                        $consulta = "SELECT p.carnet, p.primer_nombre, p.segundo_nombre, p.tercer_nombre, p.primer_apellido,
                                        p.segundo_apellido
                                        FROM proyecto_graduacion p
                                        WHERE p.carnet = $aprobacion_privado[carnet] AND p.carrera = $aprobacion_privado[carrera]";
                                        $estudiante = & $db->getRow($consulta);
                                        if ($db->isError($estudiante)) {
                                            $error = true;
                                            $mensaje = "Hubo un error al obtener los datos del estudiante.";
                                            $url = $_SERVER[HTTP_REFERER];
                                        } else {

                                            if (!empty($_estudiante['tercer_nombre'])) {
                                                $nombre_estudiante = $estudiante['primer_nombre'] . " " . $estudiante['segundo_nombre'] . " " . $estudiante['tercer_nombre'] . " " . $estudiante['primer_apellido'] . " " . $estudiante['segundo_apellido'];
                                            } else {
                                                $nombre_estudiante = $estudiante['primer_nombre'] . " " . $estudiante['segundo_nombre'] . " " . $estudiante['primer_apellido'] . " " . $estudiante['segundo_apellido'];
                                            }

                                            // Inscripciones en las que se puede solicitar examen publico
                                            $consulta = "SELECT i.carrera AS cod_carrera, IF(i.carrera <= 3,TRIM(c.nombre), TRIM(c.nombre_abreviado)) AS carrera
                                            FROM inscripcion i
                                            INNER JOIN carrera c
                                            ON c.carrera = i.carrera
                                            WHERE i.anio = $anio AND i.semestre = $semestre AND i.carnet = $carnet
                                            AND NOT EXISTS(
                                            SELECT *
                                            FROM examen_publico p
                                            WHERE p.carnet = i.carnet AND p.carrera = i.carrera)";
                                            $inscripciones = & $db->getAll($consulta);
                                            if ($db->isError($inscripciones)) {
                                                $error = true;
                                                $mensaje = "Hubo un error al verificar las carreras activas en este ciclo.";
                                                $url = $_SERVER[HTTP_REFERER];
                                            } else {

                                                // Consulta de las solicitudes realizadas de examen publico
                                                $consulta = "SELECT e.carrera, IF(e.carrera <= 3,TRIM(c.nombre), TRIM(c.nombre_abreviado)) AS nombre_carrera,
                                                IF(
                                                    p.proyecto_graduacion IS NULL, 'No se ha registrado el Proyecto de Graduación', p.proyecto_graduacion
                                                ) AS proyecto_graduacion, e.fecha_publico, o.no_boleta_deposito, e.numero_publico
                                                FROM examen_publico e
                                                LEFT OUTER JOIN examen_privado p
                                                ON p.carnet = e.carnet AND p.carrera = e.carrera
                                                INNER JOIN carrera c
                                                ON c.carrera = e.carrera
                                                LEFT OUTER JOIN bitacora_orden_pago b
                                                ON b.carnet = e.carnet AND b.rubro = 9 AND b.variante_rubro = 2
                                                LEFT OUTER JOIN orden_pago o
                                                ON o.carnet = e.carnet AND o.carrera = e.carrera AND o.orden_pago = b.orden_pago
                                                WHERE e.carnet = $carnet AND e.fecha_publico IS NULL AND e.acta_publico IS NULL";
                                                $solicitudes = & $db->getAll($consulta);
                                                if ($db->isError($solicitudes)) {
                                                    $error = true;
                                                    $mensaje = "Hubo un error al consultar las solicitudes realizadas.";
                                                    $url = $_SERVER[HTTP_REFERER];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$error) {

            // Cargar template de formulario para actualizacion
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('examen_publico_formulario.html');

            // Datos estudiante
            $template->setVariable(array(
                'carnet' => $estudiante[carnet],
                'nombre_estudiante' => $nombre_estudiante
            ));

            if (count($solicitudes) == 0) {

                $template->setVariable(array(
                    'carreras_activas' => "<br><br><font id='texto-15'><b>Carreras activas:</b></font><br>"
                ));

                foreach ($inscripciones AS $in) {
                    $template->setVariable(array(
                        'cod_carrera' => $in[cod_carrera],
                        'carrera' => $in[carrera]
                    ));
                    $template->parse('inscripcion_estudiante');
                }
            }
            $template->parse('datos_estudiante');

            if (count($solicitudes) == 0) {

                // Si existe la sesion de datos ingresados
                if (isset($_SESSION[carrera])) {
                    $template->setVariable(array(
                        'dpi1' => $_SESSION['dpi1'],
                        'dpi2' => $_SESSION['dpi2'],
                        'dpi3' => $_SESSION['dpi3'],
                        'email_fda' => $_SESSION['email_fda'],
                        'telefono' => $_SESSION['telefono'],
                        'direccion' => $_SESSION['direccion']
                    ));
                }

                $template->setVariable(array(
                    'datos_requeridos' => "Formulario de actualización de datos:"
                ));
                $template->parse('formulario_solicitud');
            }

            if (count($solicitudes) <> 0) {

                if (count($inscripciones) <> 0) {
                    $template->setVariable(array(
                        'color_tabla' => "#F2F2F2"
                    ));
                } else {
                    $template->setVariable(array(
                        'color_tabla' => "#FFFFFF"
                    ));
                }

                // Solicitudes Realizadas.
                foreach ($solicitudes AS $sol) {

                    // Consulta de datos de los padrinos del examen publico
                    $consulta = "SELECT p.profesion, p.nombre, p.colegiado
                    FROM examen_publico_padrinos p
                    WHERE p.numero_publico = $sol[numero_publico]";
                    $padrinos = & $db->getAll($consulta);

                    $template->setVariable(array(
                        'carrera' => $sol[carrera],
                        'nombre_carrera' => $sol[nombre_carrera],
                        'proyecto_graduacion' => $sol[proyecto_graduacion],
                    ));

                    if ($sol[fecha_privado] == NULL) {
                        $template->setVariable(array(
                            'fecha_publico' => "<div id='sin_fechaDefinida'>No se ha definido fecha de Examen</div>"
                        ));
                    } else {
                        $template->setVariable(array(
                            'fecha_publico' => "<div id='fechaDefinida'>Fecha de Ex?men " . $sol[fecha_publico] . "</div>"
                        ));
                    }

                    if ($sol[no_boleta_deposito] == NULL) {
                        $template->setVariable(array(
                            'pago_publico' => "<div id='sin_pagoConfirmado'>Pendiente de pago</div>"
                        ));
                    } else {
                        $template->setVariable(array(
                            'pago_publico' => "<div id='fechaDefinida'>Boleta de Pago " . $sol[no_boleta_deposito] . "</div>"
                        ));
                    }

                    // Datos Padrinos
                    foreach ($padrinos AS $pa) {

                        $template->setVariable(array(
                            'profesion' => $pa[profesion],
                            'nombre' => $pa[nombre],
                            'colegiado' => $pa[colegiado]
                        ));
                        $template->parse('padrinos');
                    }

                    $template->parse('solicitudes_publico');
                }
                $template->parse('solicitudes_disponibles');
            }

            // Errores en tiempo de Ejecucion
            if (isset($_SESSION['mensaje_error'])) {
                $mensaje_error = $_SESSION['mensaje_error'];
                $template->setVariable(array(
                    'mensaje_error' => "<div id='base_error_proceso'>
                                    <div id='error'>
                                        $mensaje_error<br><br>
                                        <div id='acciones'>
                                            <input id='btn_rojo' type='button' value='Aceptar' OnClick='window.location.reload()' autofocus>
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
                    'mensaje_proceso_finalizado' => "
                                    <div id='base_proceso_finalizado'>
                                        <div id='finalizado'>
                                        $proceso_finalizado<br><br>
                                        <div id='acciones'>
                                            <input id='btn_azul' type='button' value='Aceptar' OnClick='window.location.reload()' autofocus>
                                        </div>
                                    </div>
                                </div>"
                ));
                unset($_SESSION['proceso_finalizado']);
            }

            $template->show();
            exit();
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
