function get_notificaciones() {

    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    xmlhttp.open("GET", "../misc/get_notificacion.php", false);
    xmlhttp.send();
    xmlDoc = xmlhttp.responseXML;

    var sinleer = xmlDoc.getElementsByTagName("sinleer")[0].firstChild.data;
    var total = xmlDoc.getElementsByTagName("total")[0].firstChild.data;
    var contenedor = (window.frames["contenido"].document.getElementsByName("contenedor_not").length) - 4;

    if (contenedor > 0) {
        if (parseInt(total) != parseInt(contenedor)) {
            document.getElementById('contenido').contentWindow.location.reload();
            //window.location.reload();
        }
    }

    if (sinleer != 0) {
        document.title = "(" + sinleer + ")" + " Sistema de información académica - Estudiante";
        c_not = document.getElementById("cantidad_notificaciones");
        c_not.innerHTML = sinleer;
        var audio = new Audio('../sounds/alert.wav');
        audio.play();
    } else {
        document.getElementById("cantidad_notificaciones").innerHTML = "";
        document.title = "Sistema de información académica - Estudiante";
    }
}

function notificaciones() {
    setInterval(function () {
        get_notificaciones()
    }, 70000);
}

function varias_funciones() {
    notificaciones();
    get_notificaciones();
}