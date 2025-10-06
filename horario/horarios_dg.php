<style>

    #asignatura_dg_noperiodo {
        font-family: Trebuchet MS;
        font-size: 12px;
        width: 120px;
        padding: 2px;
        margin-bottom: 5px;
        color: #610B0B;
        background-image: -webkit-linear-gradient(top, #F5A9A9, #FA5858);
        background-image: -moz-linear-gradient(top, #F5A9A9, #FA5858);
        background-image: linear-gradient(#F5A9A9, #FA5858);
        border: 2px solid #610B0B;
        border-radius: 10px;
    }

    #asignatura_dg_matutina {
        font-family: Trebuchet MS;
        font-size: 12px;
        width: 120px;
        padding: 2px;
        margin-bottom: 5px;
        color: #B45F04;
        background-image: -webkit-linear-gradient(top, #F3E2A9, #FACC2E);
        background-image: -moz-linear-gradient(top, #F3E2A9, #FACC2E);
        background-image: linear-gradient(#F3E2A9, #FACC2E);
        border: 2px solid #B45F04;
        border-radius: 10px;
    }

    #asignatura_dg_vespertina {
        font-family: Trebuchet MS;
        font-size: 12px;
        width: 120px;
        padding: 2px;
        margin-bottom: 5px;
        color: #0B3B0B;
        background-image: -webkit-linear-gradient(top, #BCF5A9, #3ADF00);
        background-image: -moz-linear-gradient(top, #BCF5A9, #3ADF00);
        background-image: linear-gradient(#BCF5A9, #3ADF00);
        border: 2px solid #0B3B0B;
        border-radius: 10px;
    }

    #asignatura_dg_vespertina .codigo, #asignatura_dg_matutina .codigo {
        font-size: 16px;
    }

    #asignatura_dg_vespertina .horario, #asignatura_dg_matutina .horario {
        font-size: 16px;
    }

    #tabla {
        font-family: Trebuchet MS;
        font-size: 20px;
        padding: 2px;
        background-image: -webkit-linear-gradient(top, #F5ECCE, #F3E2A9, #F5ECCE);
        background-image: -moz-linear-gradient(top, #F5ECCE, #F3E2A9, #F5ECCE);
        background-image: linear-gradient(#F5ECCE, #F3E2A9, #F5ECCE);
        border: 5px solid #B45F04;
        color: #FFFFFF;
        border-radius: 20px;
        margin-top: 10px;
    }

    #ciclos {
        font-family: Trebuchet MS;
        font-size: 18px;
        color: brown;
    }

</style>


<?php
$host = '192.168.10.250';
$user = 'estudiante';
$pass = 'estudiante';
$con = mysql_connect($host, $user, $pass);
$bd = 'satu';
mysql_select_db($bd);

$consulta_p1 = "SELECT s.pensum, c.ciclo, pc.jornada, s.pensum, s.codigo, c.nombre, h.seccion, d.nombre_abreviado, pc.hora_ini, pc.hora_fin, s.cupo, 
IF (dt.registro_personal IS NULL,'<font color=black>Pendiente de docente</font>',
CONCAT(TRIM(dt.nombre),' ',TRIM(dt.apellido))) AS docente_nomostrar,
IF(s.cupo <= (SELECT COUNT(*) FROM asignacion WHERE anio = s.anio AND semestre = s.semestre AND evaluacion = s.evaluacion AND extension = s.extension 
AND pensum = s.pensum AND codigo = s.codigo AND seccion = s.seccion),'0',
s.cupo-(SELECT COUNT(*) FROM asignacion WHERE anio = s.anio AND semestre = s.semestre AND evaluacion = s.evaluacion AND extension = s.extension 
AND pensum = s.pensum AND codigo = s.codigo AND seccion = s.seccion)) AS cupos
FROM seccion s
INNER JOIN pensum p
ON p.pensum = s.pensum
LEFT OUTER JOIN horario h
ON h.anio = s.anio AND h.semestre = s.semestre AND h.evaluacion = s.evaluacion AND h.extension = s.extension
AND h.pensum = s.pensum AND h.codigo = s.codigo AND h.seccion = s.seccion
LEFT OUTER JOIN periodo_ciclo pc
ON h.anio = pc.anio AND h.semestre = pc.semestre AND h.evaluacion = pc.evaluacion AND h.extension = pc.extension
AND h.periodo = pc.periodo AND p.carrera = pc.carrera
INNER JOIN curso c
ON c.codigo = s.codigo
INNER JOIN dia d
ON d.dia = h.dia
LEFT OUTER JOIN staff f
ON f.anio = h.anio AND f.semestre = h.semestre AND f.evaluacion = h.evaluacion AND f.pensum = h.pensum AND f.codigo = h.codigo
AND f.seccion = h.seccion AND f.extension = h.extension
LEFT OUTER JOIN docente dt
ON dt.registro_personal = f.registro_personal AND dt.extension = f.extension
WHERE s.anio = 2013 AND s.semestre = 2 AND s.evaluacion = 2 AND s.extension = 0 AND s.status = 'A'
GROUP BY s.codigo,s.seccion
ORDER BY h.periodo DESC";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 01</td>";
echo "</tr>";
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '1') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 02</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '2') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 03</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '3') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 04</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '4') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 05</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '5') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 06</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '6') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 07</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '7') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 08</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '8') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 09</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '9') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";

echo "<table border=0 cellspacing=2 align=center id=tabla>";
echo "<tr>";
echo "<td colspan=20 id=ciclos>CICLO 10</td>";
echo "</tr>";
$resultado_p1 = mysql_query($consulta_p1, $con);
$p1 = mysql_fetch_assoc($resultado_p1);
do {
    if ($p1[pensum] != 5 AND $p1[ciclo] == '10') {
        if ($p1[jornada] == 'M') {
            echo "<td><div id=asignatura_dg_matutina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        } else {
            echo "<td><div id=asignatura_dg_vespertina><center>
		<b><font class=codigo>$p1[codigo] - $p1[seccion]</b></font><br>
		<font class=asignatura>$p1[nombre]</font><br>
		<font class=horario><b>$p1[hora_ini] - $p1[hora_fin]</b></font><br>
		<b>$p1[docente]</b></center><br>
		Cupo: <b>$p1[cupo]</b><br>
		Disponible: <b>$p1[cupos]</b>
		</div></td>";
        }
    }
} while ($p1 = mysql_fetch_array($resultado_p1));
echo "</table>";
?>