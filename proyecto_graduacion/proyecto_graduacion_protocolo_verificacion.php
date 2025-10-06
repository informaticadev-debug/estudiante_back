<?php

/*
  Documento  : proyecto_graduacion_solicitudformulario.php
  Creado el  : 03 de junio de 2014, 17:02
  Author     : Angel Caal
  Description:
  Formulario para solicitud de aprobacion de proyecto de graduación
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

        $carnet = $_SESSION['usuario'];
        $numero_tema = $_SESSION['numero_tema'];
        $carrera = $_SESSION['carrera'];
        $modalidad = $_SESSION['modalidad'];
        $tema_enmarcado = $_SESSION['tema_enmarcado'];
        
        if (!empty($_POST['antecedentes'])) {
            $antecedentes = $_POST['antecedentes'];
            $justificacion = $_POST['justificacion'];
            $objetivos = $_POST['objetivos'];
            $planteamiento_problema = $_POST['planteamiento_problema'];
            $delimitacion = $_POST['delimitacion'];
            $asesores = $_POST['asesores'];
            $grupo_objetivo = $_POST['grupo_objetivo'];
            $metodos = $_POST['metodos'];
            $bibliografia = $_POST['bibliografia'];
           $modalidad = $_POST['modalidad'];
            
            if (count($asesores) == 3) {
                unset($_SESSION['asesor_externo']);
            }
        } else {
            $antecedentes = $_SESSION['antecedentes'];
            $justificacion = $_SESSION['justificacion'];
            $objetivos = $_SESSION['objetivos'];
            $planteamiento_problema = $_SESSION['planteamiento_problema'];
            $delimitacion = $_SESSION['delimitacion'];
            $asesores = $_SESSION['asesores'];
            $grupo_objetivo = $_SESSION['grupo_objetivo'];
            $metodos = $_SESSION['metodos'];
            $bibliografia = $_SESSION['bibliografia'];
            
        }
        
        $_SESSION['antecedentes'] = $antecedentes;
        $_SESSION['justificacion'] = $justificacion;
        $_SESSION['objetivos'] = $objetivos;
        $_SESSION['planteamiento_problema'] = $planteamiento_problema;
        $_SESSION['delimitacion'] = $delimitacion;
        $_SESSION['grupo_objetivo'] = $grupo_objetivo;
        $_SESSION['metodos'] = $metodos;
        $_SESSION['bibliografia'] = $bibliografia;
        $_SESSION['asesores'] = $asesores;
        $_SESSION['modalidad'] = $modalidad;
        
        
        // Datos de los asesores elegidos por el estudainte
        foreach ($asesores AS $as) {

            $consulta = "SELECT (CONCAT(TRIM(d.nombre), ' ', TRIM(apellido))) AS nombre
            FROM docente d
            WHERE d.registro_personal = '$as'";
            $datos_asesores = & $db->getRow($consulta);
            if ($db->isError($datos_asesores)) {
                $error = true;
                $mensaje = "Hubo un problema durante la consulta de los datos de los asesores elegidos.";
                $url = $_SERVER[HTTP_REFERER];
            } else {

                $res_datos_asesores[] = array(
                    'nombre' => $datos_asesores[nombre]
                );
            }
        }
      // error_reporting(E_ALL);
        // ini_set('display_errors', 'OFF');
        
        // Asesor externo
        if (!empty($_POST['ae_nombres'])) {

            $datos_ae[] = array(
                'ae_nombres' => $_POST['ae_nombres'],
                'ae_apellidos' => $_POST['ae_apellidos'],
                'ae_profesion' => $_POST['ae_profesion'],
                'ae_colegiado' => $_POST['ae_colegiado'],
                'ae_telefono' => $_POST['ae_telefono'],
                'ae_email' => $_POST['ae_email']
            );
            
            $_SESSION['asesor_externo'] = $datos_ae;
        } else {

            if (isset($_SESSION['asesor_externo'])) {

                $datos_ae = $_SESSION['asesor_externo'];
            }
        }
   
        if (!$error) {

            $template = new HTML_Template_Sigma('../templates');
           
            if ($carrera == 1) {
                $template->loadTemplateFile('proyecto_graduacion_protocolo_verificacion_arq.html');
            } else if ($carrera == 3 && $tema_enmarcado == 8) {
                $template->loadTemplateFile('proyecto_graduacion_protocolo_verificacion_dg.html');
            } else if ($carrera == 3 && $tema_enmarcado == 9) {
                $template->loadTemplateFile('proyecto_graduacion_protocolo_verificacion_dg_produccion_de_conocimientos.html');
            }

            $template->setVariable(array(
                'numero_tema' => $numero_tema,
                'antecedentes' => $antecedentes,
                'justificacion' => $justificacion,
                'objetivos' => $objetivos,
                'planteamiento_problema' => $planteamiento_problema,
                'delimitacion' => $delimitacion,
                'grupo_objetivo' => $grupo_objetivo,
                'metodos' => $metodos,
                'bibliografia' => $bibliografia,
                'modalidad' => spg_nombre_modalidad($db, $carrera, $modalidad)
            ));
            
            foreach ($res_datos_asesores AS $da) {
                $template->setVariable(array(
                    'nombre' => $da["nombre"]
                ));

                if (!empty($datos_ae)) {
                    foreach ($datos_ae AS $da) {
                        $template->setVariable(array(
                            'ae_nombres' => $da[ae_nombres] . " " . $da[ae_apellidos]
                        ));
                    }
                }

                $template->parse('datos_asesores');
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
