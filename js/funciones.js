function ocultar_menu_izquierdo() {

    document.getElementById("menu_izquierdo").className = "collapse navbar-collapse navbar-ex1-collapse";
}

window.onresize = aumentar_margen_iframe();

function aumentar_margen_iframe() {

    posicion_menu = document.getElementById("menu_izquierdo").offsetWidth;

    if (posicion_menu == 0) {
        document.getElementById("contenido").style.top = "70px";
    } else {
        document.getElementById("contenido").style.top = "20px";
    }
}

function get_municipio(departamento) {

    depto = departamento.value;

    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    xmlhttp.open("GET", "../misc/get_municipio.php?departamento=" + depto, false);
    xmlhttp.send();
    xmlDoc = xmlhttp.responseXML;

    municipio = xmlDoc.getElementsByTagName("municipio");
    nombre = xmlDoc.getElementsByTagName("nombre");
    cantidad = xmlDoc.getElementsByTagName("municipios").length;
    contenedor = document.getElementById("municipios");

    if (contenedor.length != 0) {
        for (i = 0; i < document.getElementsByTagName("option").length; i++) {
            mun = document.getElementsByTagName("option");
            contenedor.remove(mun[i]);
        }
    }

    for (i = 0; i < cantidad; i++) {
        mun = document.createElement("option");
        mun.text = municipio[i].firstChild.data + " - " + nombre[i].firstChild.data;
        mun.value = municipio[i].firstChild.data;
        contenedor.add(mun);
    }
}

function notificaciones(usuario) {
    setInterval(function () {
        get_notificaciones(usuario)
    }, 10000);
}

function procesos_asignaciones(proceso) {

    // Generacion de documentos PDF de procesos 
    // 1 = Des-Asignacion
    // 2 = Constancia de Asignaciones
    if (proceso.value == 1) {
        // Envio del formulario en el proceso 1
        form_seleccion = document.getElementById("form_asignaciones");
        form_seleccion.action = "../asignatura/desasignacion.php";
        form_seleccion.submit();
    }

    if (proceso.value == 2) {
        // Envio del formulario en el proceso 2
        form_seleccion = document.getElementById("form_asignaciones");
        form_seleccion.action = "../asignatura/asignaciones.php";
        form_seleccion.submit();
    }
}

function minimizar_panel_izquierdo() {

    estado_panel = document.getElementById("panel_izquierdo").style.display;
    panel = window.frames["contenido"].document;

    //alert(panel);	
    //alert(estado_panel);


    if (estado_panel == "none") {
        document.getElementById("facultad").style.display = "block";
        document.getElementById("panel_izquierdo").style.display = "block";
        document.getElementById("iframe_contenido").style.width = "80%";
        panel.getElementById("bloque").style.width = "48.5%";
        panel.getElementById("noticias").style.width = "48.5%";
    } else {
        document.getElementById("facultad").style.display = "none";
        document.getElementById("panel_izquierdo").style.display = "none";
        document.getElementById("iframe_contenido").style.width = "100%";
        panel.getElementById("bloque").style.width = "97%";
        panel.getElementById("noticias").style.width = "97%";
    }
}


function tamano_menu() {

    estado_panel = document.getElementById("panel_izquierdo").style.display;
    panel = window.frames["contenido"].document;
    tamano_ventana = document.body.offsetWidth;

    if (tamano_ventana > 800) {
        document.getElementById("facultad").style.display = "block";
        document.getElementById("panel_izquierdo").style.display = "block";
        document.getElementById("iframe_contenido").style.width = "80%";
        panel.getElementById("bloque").style.width = "48.5%";
        panel.getElementById("noticias").style.width = "48.5%";
    } else {
        document.getElementById("facultad").style.display = "none";
        document.getElementById("panel_izquierdo").style.display = "none";
        document.getElementById("iframe_contenido").style.width = "100%";
        panel.getElementById("bloque").style.width = "97%";
        panel.getElementById("noticias").style.width = "97%";
    }
}
window.onresize = tamano_menu;


function varias_funciones() {
    notificaciones();
    get_notificaciones();
    //tamano_menu();
}

function seleccionar_asignaciones(campo) {
    asignaturas = document.getElementsByName("codigo[]").length;
    asignatura = document.getElementsByName("codigo[]");

    for (i = 0; i < asignaturas; i++) {
        asignatura[i].checked = campo.checked;
    }

}

function editar_perfil() {
    window.parent.document.getElementById("base_perfil").style.display = "block";
}

function editar_perfil_porActualizacion() {
    document.getElementById("base_perfil").style.display = "block";
}

function item_activo(campo) {
    itemsMenu = document.getElementsByName("item_menu").length;
    items = document.getElementsByName("item_menu");

    for (i = 0; i < itemsMenu; i++) {

        items[i].firstChild.classList.remove('activo');

        if (items[i].href == campo) {
            activo = campo.firstChild;
            activo.classList.add("activo");
        }
    }
}

function asesor_externo(estado) {

    tit = document.getElementById("tit_asesor_externo");
    con = document.getElementById("con_asesor_externo");

    if (estado.checked) {

        asesores_locales_cnt = document.getElementsByName('asesores[]').length;
        asesores_locales = document.getElementsByName('asesores[]');

        for (i = 0; i < asesores_locales_cnt; i++) {
            asesores_locales[2].disabled = true;
        }

        tit.style.display = "block";
        con.style.display = "block";

        cantidad_elementos = con.getElementsByTagName("input").length;
        elementos = con.getElementsByTagName("input");

        for (i = 0; i < cantidad_elementos; i++) {
            elementos[i].required = true;
        }

    } else {

        tit.style.display = "none";
        con.style.display = "none";

        cantidad_elementos = con.getElementsByTagName("input").length;
        elementos = con.getElementsByTagName("input");

        for (i = 0; i < cantidad_elementos; i++) {
            elementos[i].required = false;
            elementos[i].value = "";
        }

        asesores_locales_cnt = document.getElementsByName('asesores[]').length;
        asesores_locales = document.getElementsByName('asesores[]');

        for (i = 0; i < asesores_locales_cnt; i++) {
            asesores_locales[2].disabled = false;
        }
    }
}

function confirmar_correo(correo) {

    var correo_ingresado = document.getElementsByName("email_fda")[0].value;
    var confirmacion = correo.value;

    if (confirmacion !== correo_ingresado) {
        alert("El correo electrónico no coincide, por favor verifique.");
        correo.value = "";
        correo.focus();
    }
}

function ventana_voto(opcion) {

    window.open("http://www.arquitectura.usac.edu.gt/estudiante/votacion_login.php?opcion=" + opcion, 'targetWindow', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=600,height=352,left=100,top=100')
}

function contar_palabras_antecedentes() {

    can_palabras = document.getElementsByName("antecedentes")[0];
    con_palabras = document.getElementById("antecedentes");
    con_palabras.innerHTML = can_palabras.value.length;
}

function contar_palabras_justificacion() {

    can_palabras = document.getElementsByName("justificacion")[0];
    con_palabras = document.getElementById("justificacion");
    con_palabras.innerHTML = can_palabras.value.length;
}

function contar_palabras_objetivos() {

    can_palabras = document.getElementsByName("objetivos")[0];
    con_palabras = document.getElementById("objetivos");
    con_palabras.innerHTML = can_palabras.value.length;
}

function contar_palabras_planteamiento_problema() {

    can_palabras = document.getElementsByName("planteamiento_problema")[0];
    con_palabras = document.getElementById("planteamiento_problema");
    con_palabras.innerHTML = can_palabras.value.length;
}

function contar_palabras_delimitacion() {

    can_palabras = document.getElementsByName("delimitacion")[0];
    con_palabras = document.getElementById("delimitacion");
    con_palabras.innerHTML = can_palabras.value.length;
}

function contar_palabras_metodos() {

    can_palabras = document.getElementsByName("metodos")[0];
    con_palabras = document.getElementById("metodos");
    con_palabras.innerHTML = can_palabras.value.length;
}

function contar_palabras_bibliografia() {

    can_palabras = document.getElementsByName("bibliografia")[0];
    con_palabras = document.getElementById("bibliografia");
    con_palabras.innerHTML = can_palabras.value.length;
}

function cursos_laboratorio() {

    contenedor = document.getElementById("contenedor_cursos_lab");

    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    xmlhttp.open("GET", "../misc/laboratorios_obtenercursos.php", false);
    xmlhttp.send();
    xmlDoc = xmlhttp.responseXML;
    estado = xmlDoc.getElementsByTagName("estado")[0].firstChild.data;

    if (estado == 0) {
        alert(xmlDoc.getElementsByTagName("detalle")[0].firstChild.data);
    } else {

        contenedor.innerHTML = "";

        $('#listado_cursos_lab').modal({
            show: true
        });

        pensum = xmlDoc.getElementsByTagName("pensum");
        codigo = xmlDoc.getElementsByTagName("codigo");
        nombre = xmlDoc.getElementsByTagName("nombre");

        for (i = 0; i < pensum.length; i++) {

            fila = document.createElement("tr");
            columna = document.createElement("td");
            columna.innerHTML = "<input type='checkbox' value='" + pensum[i].firstChild.data + codigo[i].firstChild.data + "' name='cursos[]'>";
            fila.appendChild(columna);

            columna = document.createElement("td");
            columna.innerHTML = codigo[i].firstChild.data + " " + nombre[i].firstChild.data;
            fila.appendChild(columna);

            contenedor.appendChild(fila);
        }
    }
}

function asigext_motivos_obtenerrequisitos(motivo) {

    contenedor = document.getElementById("contenedor_requisitos");
    contenedor.innerHTML = "<span class='badge badge-primary'>Requisitos indispensables </span> <br><br>";

    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    xmlhttp.open("GET", "../misc/aisngext_motivos_obtenerrequisitos.php?motivo=" + motivo.value, false);
    xmlhttp.send();
    xmlDoc = xmlhttp.responseXML;
    estado = xmlDoc.getElementsByTagName("estado")[0].firstChild.data;

    if (estado == 0) {
        alert(xmlDoc.getElementsByTagName("detalle")[0].firstChild.data);
    } else {

        requisito = xmlDoc.getElementsByTagName("requisito");

        for (i = 0; i < requisito.length; i++) {

            req = document.createElement("span");
            req.innerHTML = requisito[i].firstChild.data;
            req.className = "label label-danger";
            req.style.margin = "5px";

            contenedor.appendChild(req);

            salto = document.createElement("br");
            contenedor.appendChild(salto);

            salto = document.createElement("br");
            contenedor.appendChild(salto);
        }
    }
}

function asigext_obtener_listadoasignaturas() {

    carrera = document.getElementsByName("carrera")[0];
    contenedor = document.getElementById("contenedor_asignaturas");
    asignaciones_previas = document.getElementById("asignaciones_previas");

    if (carrera.value == "") {
        alert("Debe seleccionar antes la carrera");
        carrera.focus();
    } else {

        if (document.getElementsByName("asignaturas[]").length >= (7 - asignaciones_previas.value)) {
            alert("Solo puede solicitar " + (7 - asignaciones_previas.value) + " asignaturas");
        } else {

            if (window.XMLHttpRequest) {
                xmlhttp = new XMLHttpRequest();
            } else {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }

            xmlhttp.open("GET", "../misc/asigext_obtener_listadoasignaturas.php?carrera=" + carrera.value, false);
            xmlhttp.send();
            xmlDoc = xmlhttp.responseXML;
            estado = xmlDoc.getElementsByTagName("estado")[0].firstChild.data;

            if (estado == 0) {
                alert(xmlDoc.getElementsByTagName("detalle")[0].firstChild.data);
            } else {

                pensum = xmlDoc.getElementsByTagName("pensum");
                codigo = xmlDoc.getElementsByTagName("codigo");
                asignatura = xmlDoc.getElementsByTagName("asignatura");

                menu = document.createElement("select");
                menu.className = "select form-control";
                menu.required = true;
                menu.style.marginTop = "10px";
                menu.name = "asignaturas[]";

                opcion = document.createElement("option");
                opcion.selected = true;
                menu.appendChild(opcion);

                for (i = 0; i < codigo.length; i++) {

                    opcion = document.createElement("option");
                    opcion.value = pensum[i].firstChild.data + codigo[i].firstChild.data;
                    opcion.text = codigo[i].firstChild.data + " - " + asignatura[i].firstChild.data;
                    menu.appendChild(opcion);
                }

                contenedor.appendChild(menu);
            }
        }
    }
}