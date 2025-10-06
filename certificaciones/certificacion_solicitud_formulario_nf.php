<?php

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";
require_once '../config/local.php';

session_start();

verificarActualizarDatos();

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
        $error = false;

        $carnet = $_SESSION['usuario'];
        $anio = DATE("o");

        $fecha_actual = DATE("o-m-d H:i");
        $fecha_desabilita = "2025-11-15 23:59";
        $fecha_habilitada = "2025-01-09 12:00";

        if ($fecha_actual >= $fecha_desabilita && $fecha_actual <= $fecha_habilitada /*&& $carnet != 202099999*/) {
            $aviso = true;
            $mensaje = "Las solicitud en línea de certificación será habilitada a partir de enero del 2025 de nuevo.";
            $url = "../menus/contenido.php";
        } else {

            // Carreras en las que se ha inscrito el estudiante
            $consulta = "SELECT c.carrera AS codigo_carrera, TRIM(ce.nombre) AS nombre_carrera
			FROM carrera_estudiante c
			INNER JOIN carrera ce
			ON ce.carrera = c.carrera
			WHERE c.carnet = $carnet";
            $carreras = & $db->getAll($consulta);
            if ($db->isError($carreras)) {
                $error = true;
                $mensaje = "Hubo un problema al obtener el detalle de carrera del estudiante.";
                $url = "../menus/contenido.php";
            } else {
		//previo a consultar el historial... validar todos los pagos pendientes de validar...
		$data = array(
			"auth" => array(
			    "user" => "arqws",
			    "passwd" => "a08!¡+¿s821!kdui23#kd$"
			),
			'action' => "ordenPagoCertificacionValidarAnteriores",
			"carnet" => $carnet,
		    );
		    //consumiendo el servicio Rest de Inscripciones
		    $respuesta = postRequest($api_uri . 'Certificacion', $data);
			echo "<!-- "   . $respuesta . " -->";
		
                // Consultar todas las certificaciones anteriores...
                $consulta = "SELECT r.*, o.estado as op_estado, o.fecha_certificacion_banco as op_fecha_certificacion_banco, o.no_boleta_deposito as op_no_boleta_deposito, c.nombre as carrera_nombre
				FROM reporte r
                    INNER JOIN carrera c ON c.carrera = r.carrera
                    LEFT JOIN orden_pago o on o.orden_pago = r.orden_pago
				WHERE r.carnet = $carnet AND r.listado = 1
				ORDER BY r.fecha_solicitud DESC";
                $historial_certificaciones = & $db->getAll($consulta);
                if ($db->isError($certificacion_impresa)) {
                    $error = true;
                    $mensaje = "Hubo un problema al verificar el estado de solicitudes de certificacion.";
                    $url = "../menus/contenido.php";
                }
            }
        }
        
        if (!$error && !$aviso) {

            // Cargando la pagina para mostrar las ordenes de Pago.
            $template = new HTML_Template_Sigma('../templates');
            $template->loadTemplateFile('certificacion_solicitud_formulario_nf.html');

            foreach ($carreras AS $ca) {

                $template->setVariable(array(
                    'codigo_carrera' => $ca[codigo_carrera],
                    'nombre_carrera' => $ca[nombre_carrera]
                ));
                $template->parse("listado_carreras");
                
                $template->setVariable(array(
                    'codigo_carrera2' => $ca[codigo_carrera],
                    'nombre_carrera2' => $ca[nombre_carrera]
                ));
                $template->parse("listado_carreras2");
            }
            
            
            foreach ($historial_certificaciones AS $c) {
                $tipo = "indefinido";
                if ($c["ponderada"] == 1) {
                    $tipo = "Ponderada";
                } else if ($c["ponderada"] == 0) {
                    $tipo = "Normal";
                }
                $estado = "Pendiente de pago";
                $estadoInt = 0;
                $accion = "---";
                if (!empty($c["numero_recibo"]) || !empty($c["op_no_boleta_deposito"])){
                    $estado = "Pagada, pendiente de impresión";
                    $estadoInt = 1;
                }
                if (!empty($c["fecha_impresion"])){
                    $estado = "Impresa";
                    $estadoInt = 2;
                }
                if ($estadoInt == 0 && $c["ponderada"] != NULL && in_array($c["ponderada"], [0,1]) && !empty($c["cantidad"])) {
                    $accion = '<form method="POST" action="../certificaciones/certificacion_solicitud_procesar_orden.php" OnSubmit="">
                    <input type="hidden" name="numero_registro" value="' . $c["numero_registro"] . '" />
                    <input type="submit" class="btn btn-success btn-sm" value="Orden de pago" />
                    </form>';
                }
                $template->setVariable(array(
                    'fecha_solicitud' => $c["fecha_solicitud"],
                    'carrera_nombre' => $c["carrera_nombre"],
                    'cantidad' => $c["cantidad"],
                    'tipo' => $tipo,
                    'estado' => $estado,
                    'orden_pago' => (empty($c["orden_pago"])) ? "---" : $c["orden_pago"],
                    'acciones' => $accion,
                ));
                $template->parse("listado_solicitudes");
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
