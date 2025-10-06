function get_mensajes(carnet) {
    if (window.XMLHttpRequest)
    {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    } else
    {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    xmlhttp.open("GET", "misc/get_mensajes.php?carnet=" + carnet, false);
    xmlhttp.send();
    xmlDoc = xmlhttp.responseXML;
    var tmensajes = xmlDoc.getElementsByTagName("mensajes");

    mens = document.getElementsByTagName("div").length;
    res = tmensajes.length;
    //alert("capas creadas" + mens + " -  " + "Resultados" + res);


    for (i = res; i > mens; i--) {
        var list = i - 1;
        //var idcar = document.getElementById("to");
        var car = xmlDoc.getElementsByTagName("carnet")[list].firstChild.data;
        //idcar.innerHTML = car;

        var newDiv = document.createElement("div");
        var newContent = document.createTextNode(car + " - " + i);
        newDiv.appendChild(newContent);
        my_div = document.getElementById("org_div1");
        document.body.insertBefore(newDiv, my_div);
    }

    setInterval(function () {
        get_mensajes(carnet)
    }, 10000);
}