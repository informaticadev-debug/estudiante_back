<?php

/*
  PROCESAR ASIGNACION DE AUCAS
  -> Registrar la asignacion en el sistema
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "HTML/Template/Sigma.php";

session_start();

if (isset($_SESSION[usuario])) {

    $errorLogin = false;

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

        $extension = $_SESSION['extension'];
        $anio = $_SESSION['anio'];
        $semestre = $_SESSION['semestre'];
        $carnet = $_SESSION['usuario'];
        $pensum = $_POST['pensum'];
        $codigo = $_POST['codigo'];
        $periodo = $_POST['periodo'];
        $departamento = $_POST['departamento'];
        $municipio = $_POST['municipio'];
        $correlativo = $_POST['correlativo'];

        $hay_cupo = false;

        //consulta para verificar el cupo
        $columnaCupo = ($codigo == '1.05.0') ? "cupo" : "cupo2";
        $consulta = "SELECT r.extension, r.anio, r.semestre, r.departamento, d.nombre AS nombre_departamento, r.municipio, m.nombre AS nombre_municipio, r.correlativo
            FROM practica_tecnica_region r
                INNER JOIN departamento d ON d.departamento = r.departamento
                INNER JOIN municipio m ON m.departamento = r.departamento AND m.municipio = r.municipio
            WHERE r.anio = $anio AND r.semestre = $semestre AND r.departamento = $departamento AND r.municipio = $municipio AND r.periodo = $periodo AND r.correlativo = $correlativo
                AND r.{$columnaCupo} > (
                SELECT COUNT(*)
                FROM practica_tecnica_asignacion a
                WHERE a.anio = r.anio AND a.semestre = r.semestre AND a.departamento = r.departamento AND a.municipio = r.municipio AND a.codigo = '$codigo' AND a.periodo = $periodo
                	AND r.correlativo = a.correlativo
            )";
        $con = mysqli_connect($host, $user, $pass, "satu");
        if (mysqli_connect_errno()) {
            die;
        }
        $run_query = mysqli_query($con, $consulta);
        $row_query = mysqli_fetch_array($run_query) or $row_query = mysqli_fetch_assoc($run_query);
        if ($row_query) {
            $hay_cupo = true;
        }
        if ($hay_cupo == false) {
            $error = true;
            $mensaje = "Ya no existe cupo en la region que ha seleccionado.";
            $url = "/aucas/aucas_gestion.php";
        }
	//verificar que solo se asigne una sede...
	$consulta2 = "SELECT * FROM practica_tecnica_asignacion WHERE anio = $anio AND semestre = $semestre AND carnet = $carnet";
	$restYaAsignada = mysqli_query($con, $consulta2);
	$cantAsignaciones = mysqli_num_rows($restYaAsignada);
	if ($cantAsignaciones > 0){
	    error("Usted ya se encuentra asignado", "/aucas/aucas_gestion.php");
            exit();
	}
	mysqli_close($con);
        
	
        if ($hay_cupo) {
            $db->autoCommit(false);
            if (!empty($codigo) && !empty($carnet) && !empty($periodo) && !empty($departamento) && !empty($municipio)) {
                // Registro de la asignacion en la plataforma
                $consulta = "INSERT INTO practica_tecnica_asignacion
                (extension, anio, semestre, pensum, codigo, carnet, departamento, municipio, fecha_asignacion, periodo, correlativo)
                VALUES (
                    $extension,
                    $anio,
                    $semestre,
                    $pensum,
                    '$codigo',
                    $carnet,
                    $departamento,
                    $municipio,
                    NOW(),
                    $periodo,
                    $correlativo
                )";
                $registrar_practica = & $db->Query($consulta);
                if ($db->isError($registrar_practica)) {
                    $error = true;
                    $mensaje = "Hubo un problema al registrar la asignación de la práctica.";
                    $url = "/aucas/aucas_gestion.php";
                    $db->rollback();
                } else {
                    $db->commit();
                }
            } else {
                $error = true;
                $mensaje = "Hubo un problema al registrar la asignación de la práctica, verifique que exista cupo en la sede que ha elegido.";
                $url = "/aucas/aucas_gestion.php";
                $db->rollback();
            }
        }
        $db->disconnect();

        if (!$error) {

            $_SESSION['proceso_finalizado'] = "Se ha registrado la asignación correctamente.";

            if (isset($_SESSION['proceso_finalizado'])) {

                echo "
                    <script>
                        window.open('../aucas/aucas_gestion.php','contenido');
                    </script>
                ";
            }
        }

        if ($error) {
            error($mensaje, $url);
        }
    }
} else {
    $mensaje = "La sesion ha caducado en el sistema, por favor ingrese nuevamente.";
    mostrarErrorLogin($mensaje);
}
?>
