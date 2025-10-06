<?php

/*
  Proceso de Asignacion para Interciclos
  -> Seleccion de asignaturas y secciones a preasignar.
  -> Verificacion de cupo disponible en el sistema.
 */

require_once "DB.php";
require_once "../misc/funciones.php";
require_once "../config/local.php";
require_once "HTML/Template/Sigma.php";
require_once "../misc/pdf/html2pdf.class.php";

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

        $db->setFetchMode(DB_FETCHMODE_ASSOC);
        $error = false;

        // Datos de la session actual
        $extension = $_SESSION['extension'];
        $anio = $_GET['anio'];
        $semestre = $_GET['semestre'];
        $evaluacion = $_GET['evaluacion'];
        $carnet = $_SESSION['usuario'];
        $orden_pago = $_GET['orden_pago'];
        // REVISAR SI ES NECESARIO RENOVAR LAS ORDENES DE PAGO...
        $data = [
            "auth" => [
                "user" => "arqws",
                "passwd" => "a08!¡+¿s821!kdui23#kd$"
            ],
            "action" => "renovar",
            "orden_pago" => $orden_pago
        ];
        //consumiendo el servicio Rest de Inscripciones
        $respuesta = postRequest($api_uri . 'SIIF', $data);
        $respuestaObj = json_decode($respuesta, true);
        if (!empty($respuestaObj) && isset($respuestaObj["error"]) && $respuestaObj["error"] == false) {
            $orden_pago = $respuestaObj["orden_pago"];
        } else {
            var_dump("Error al renovar la orden de pago: " . $respuesta);
            die;
        }

        $nombresutf8 = "SET NAMES utf8";
        $db->Query($nombresutf8);

        // Datos de la Orden de Pago seleccionada.
        $consulta = "SELECT d.orden_pago, d.carnet, TRIM(e.nombre) AS estudiante, ex.nombre AS extension, ca.nombre_abreviado AS carrera,
		d.monto_total, d.unidad, d.extension AS cod_extension, d.carrera AS cod_carrera, d.llave, d.rubro, DATE_FORMAT(d.fecha_orden_pago, '%Y%m%d') AS fecha_emision, DATE_FORMAT(DATE_ADD(d.fecha_orden_pago, INTERVAL 7 DAY), '%d/%m/%Y') AS fecha_fin_vigencia
		FROM orden_pago d
		    INNER JOIN estudiante e ON d.carnet = e.carnet
		    INNER JOIN extension ex ON ex.extension = d.extension
		    INNER JOIN carrera ca ON ca.carrera = d.carrera
		WHERE d.orden_pago = $orden_pago";
        $orden = &$db->getRow($consulta);
        if ($db->isError($orden)) {
            $error = true;
            $mensaje = "Hubo un error al determinar los datos de la Orden de Pago.";
        } else {

            // Detalle Orden
            $consulta = "SELECT d.monto, d.tipo_pago, d.codigo, 
			IF(
				d.rubro = 9 AND d.variante_rubro = 1,
				CONCAT('Ex&aacute;menes Generales ',d.anio, ' Ex&aacute;men Privado'),
				IF(
					d.rubro = 9 AND d.variante_rubro = 2,
					CONCAT('Ex&aacute;menes Generales ',d.anio, ' Ex&aacute;men P&uacute;blico'), 
					IF(
						d.rubro = 41 AND d.variante_rubro = 1,
						'Impresión de Titulo y registro de titulo (Licenciaturas)',
						IF(
							d.rubro = 41 AND d.variante_rubro = 3,
							'Registro de Titulo (Licenciatura)',
							IF(
								d.rubro = 40 AND d.variante_rubro = 1,
								'Alquiler de togas (Estudiantes)',
								IF (
									d.rubro = 40 AND d.variante_rubro = 2,
									'Alquiler de togas (Arquitectos - USAC, que no son docentes)',
									c.nombre_abreviado
								)
							)                                    
						)
					)
				)
			) AS asignatura, d.seccion			
			FROM bitacora_orden_pago d
			LEFT OUTER JOIN curso c
			ON c.codigo = d.codigo AND c.pensum = d.pensum
			WHERE d.anio = $anio AND d.semestre = $semestre AND d.evaluacion = $evaluacion AND d.carnet = $carnet
			AND d.orden_pago = $orden_pago
			ORDER BY d.tipo_pago ASC";
            //if($orden_pago == 7966031) {var_dump($consulta); die;}
            $detalle_orden = &$db->getAll($consulta);
            if ($db->isError($orden)) {
                $error = true;
                $mensaje = "Hubo un error al obtener los datos de la Orden de pago seleccionada.";
            }
        }

        if (!$error) {

            if (count($orden) <> 0) {

                ob_start();

                // Construyendo la orden de Pago actual.				
                echo "<table style='border: 1px solid #000000; padding: 5px; border-radius: 5px;' align=center>";
                echo "<tr>";
                echo "<td colspan='4' align='left'><img src='../images/logo_farusac_azul.png' width='200'></td>";
                echo "<td align='right'>&nbsp;</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan='5' align='center'>Orden de Pago</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>No.</td>";
                echo "<td width='400' colspan='2'>$orden[orden_pago]</td>";
                echo "<td>&nbsp;</td>";
                echo "<td align='center' rowspan='15' style='border-left: 1px solid #000000'>";
                echo "<table >";
                echo "<tr>";
                echo "<td align='center' colspan='2'>Para uso exclusivo del Banco</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>Orden de pago.</td>";
                echo "<td align='left'>$orden[orden_pago]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>Carn&eacute;:</td>";
                echo "<td align='left'>$orden[carnet]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>Total a pagar:</td>";
                echo "<td align='left'>$orden[monto_total]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>C&oacute;digo unidad:</td>";
                echo "<td align='left'>0$orden[unidad]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>C&oacute;digo de ext.:</td>";
                echo "<td align='left'>0$orden[cod_extension]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>C&oacute;digo de carrera:</td>";
                echo "<td align='left'>0$orden[cod_carrera]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>Fecha de Emisión:</td>";
                echo "<td align='left'>$orden[fecha_emision]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>Rubro de pago:</td>";
                echo "<td align='left'>102</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td align='right'>Llave:</td>";
                echo "<td align='left'>$orden[llave]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan='2' align='center' style='font-size: 10px;'>Puede efectuar su pago en cualquier agencia o</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan='2' align='center' style='font-size: 10px;'>banca virtual BANRURAL (ATX-253),</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan='2' align='center' style='font-size: 10px;'>GyT Continental o BANTRAB.</td>";
                echo "</tr>";
                echo "</table>";
                echo "</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Carn&eacute;:</td>";
                echo "<td>$orden[carnet]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Nombre:</td>";
                echo "<td>$orden[estudiante]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Facultad:</td>";
                echo "<td>Facultad de Arquitectura</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Extension:</td>";
                echo "<td>$orden[extension]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Carrera:</td>";
                echo "<td>$orden[carrera]</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td colspan='3' align='center'>Detalle de Pago</td>";
                echo "</tr>";

                foreach ($detalle_orden as $de) {

                    echo "<tr>";

                    if ($de[tipo_pago] == 1) {
                        echo "<td align='right' colspan='2'>Inscripcion</td>";
                    }

                    if ($de[tipo_pago] == 2 || $de[tipo_pago] == 0) {
                        echo "<td align='right' colspan='2'>$de[codigo] $de[asignatura] <b>$de[seccion]</b></td>";
                    }

                    echo "<td align='right'>$de[monto]</td>";
                    echo "</tr>";
                }

                echo "<tr>";
                echo "<td align='right' colspan='2'>Total a Pagar:</td>";
                echo "<td align='right'>$orden[monto_total]</td>";
                echo "</tr>";
                echo "</table>
                <p style='margin: 20px;'>** El documento es válido para su pago únicamente hasta el día $orden[fecha_fin_vigencia].**<br />
		** El pago es válido únicamente si se realiza dentro de las fechas establecidas por la Facultad de Arquitecta, de lo contrario no se tomaran en cuenta para los procesos.**
<br />
***La cuota aplica complemento únicamente si se encuentra dentro de lo establecido por la Coordinación del PAI, consulto la información oficial.
</p>
                ";

                /* if ($orden[rubro] == 63){
                  echo "<div style='margin-top: 5px; margin-left: 150px; text-align: center; width: 60%; color: red;'>
                  El plazo m&aacute;ximo para realizar el pago de esta orden es de 72 horas,
                  si usted no confirma el pago en este plazo su preasignaci&oacute;n se dar&aacute; de baja
                  y no podr&aacute; confirmar el pago en el Banco. Por favor realizar el pago en BANRURAL o G&T Continental

                  </div>";
                  } */

                try {

                    $contenido_pdf = ob_get_clean();
                    $pdf = new HTML2PDF('P', 'Letter', 'es', array(mL, mT, mR, mB));
                    $pdf->pdf->SetDisplayMode('fullpage');
                    $pdf->WriteHTML($contenido_pdf);
                    $pdf->Output($orden_pago . '.pdf', 'D');
                } catch (HTML2PDF_exception $e) {
                    echo $e;
                    exit;
                }
            }
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