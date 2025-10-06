<?php
session_start();
header("Location: https://farusac.edu.gt/"); exit();
?>
<!DOCTYPE html>
<html lang="es">

    <head>

        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="shortcut icon" href="images/icono.ico" type="image/x-icon" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="FARUSAC">
        <meta name="author" content="Angel Caal">

        <title>SIA.estudiante</title>

        <!-- Bootstrap Core CSS -->
        <link href="css/bootstrap.css" rel="stylesheet">
        <link href="css/menu.css" rel="stylesheet">

        <!-- Custom CSS -->
        <link href="css/landing-page.css" rel="stylesheet">

        <!-- Custom Fonts -->
        <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
        <link href="http://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

    </head>

    <body>

        <?php
        if (isset($_SESSION['mensaje_error']) || isset($_SESSION['mensaje_aviso'])) {

            $mensaje_aviso = $_SESSION['mensaje_aviso'];
            $mensaje_error = $_SESSION['mensaje_error'];

            echo "<div id='base_proceso_finalizado'>
							<div class='modal-dialog'>
								<div class='modal-content' style='margin-top: 120px'>
									<div class='modal-header' style='background: #DF7401; color: #FFFFFF'>
										<h4 class='modal-title' id='myModalLabel'>Aviso</h4>
									</div>
									<div class='modal-body'>
										$mensaje_aviso $mensaje_error
									</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-warning' OnClick='window.location.reload()' autofocus>Cerrar</button>
									</div>
								</div>
								<!-- /.modal-content -->
							</div>
							<!-- /.modal-dialog -->
						</div>";
            unset($_SESSION['mensaje_aviso']);

            unset($_SESSION['mensaje_error']);
        }
        session_destroy();
        ?>

        <div class="modal fade" id="geografia" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="myModalLabel">Comunicado</h4>
                    </div>
                    <div class="modal-body">
                        ...
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>       

        <!-- Navigation -->
        <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
            <div class="container topnav">
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand topnav" href="#">USAC - Facultad de Arquitectura</a>
                </div>
                <!-- Collect the nav links, forms, and other content for toggling -->
                <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">				
                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">Redes Curriculares<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="docs/redes/LIC_ARQ.jpg" target="_blanck">Licenciatura en Arquitectura</a>
                                </li>
                                <li>
                                    <a href="docs/prerrequisitos/prerrequisitos_pensum_5.pdf" target="_blanck">Prerrequisitos Licenciatura en Arquitectura</a>
                                </li>
                                <li>
                                    <a href="docs/redes/LIC_DG.jpg" target="_blanck">Licenciatura en Diseño Gráfico</a>
                                </li>                                
                                <li>
                                    <a href="docs/prerrequisitos/prerrequisitos_pensum_20.pdf" target="_blanck">Prerrequisitos Licenciatura en Diseño Gráfico</a>
                                </li>
                            </ul>							
                        </li>
                        <!-- li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">HORARIOS<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="docs/horarios/semestre 02-2016 Arquitectura Matutina.pdf" target="_blanck">Semestre 02-2016 Arquitectura Matutina</a>
                                </li>
                                <li>
                                    <a href="docs/horarios/semestre 02-2016 Arquitectura Vespertina.pdf" target="_blanck">Semestre 02-2016 Arquitectura Vespertina</a>
                                </li>
                            </ul>							
                        </li -->
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">Formularios<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <!-- li>
                                    <a href="docs/cuarta_oportunidad/articulo28.pdf" target="_blanck">Cuarta Oportunidad - Art 28</a>
                                </li -->
                                <li>
                                    <a href="docs/cuarta_oportunidad/cuarta_oportunidad.pdf" target="_blanck">Cuarta Oportunidad</a>
                                </li>
                                <li>
                                    <a href="docs/suficiencia/examen_suficiencia_info.pdf" target="_blanck">Examen por suficiencia - Información</a>
                                </li>
                                <li>
                                    <a href="docs/suficiencia/examen_suficiencia_formulario.pdf" target="_blanck">Examen por suficiencia - Solicitud</a>
                                </li>
                                <!--li>
                                    <a href="docs/solicitud_asignacion_manual.pdf" target="_blanck">Asignación Manual - Solicitud</a>
                                </li-->
                                <li>
                                    <a href="docs/suficiencia/Form. UICA-03  Examen Aplicaciones en Computadora.pdf" target="_blanck">Examen de Aplicaciones en Computadora</a>
                                </li>
                            </ul>							
                        </li>
                        <!--li>
                            <a href="#" class="dropdown-toggle" data-toggle="modal" data-target="#geografia"><font class="text-danger">Aviso importante</font></a>
                        </li-->
                    </ul>
                </div>
                <!-- /.navbar-collapse -->
            </div>
            <!-- /.container -->
        </nav>


        <!-- Header -->
        <a name="about"></a>
        <div class="intro-header">
            <div class="container">

                <div class="row">
                    <!--div class="col-md-4">
                            <div class="intro-message">
                                    <div class="alert alert-warning">
                                            <b>Asignaciones:</b><br><br>
                                            Diseño Gráfico 14 de julio de 2016<br>
                                            Arquitectura 15 de julio de 2016<br><br>
                                            De 08:00 a 22:00 horas
                                            
                                    </div>
                            </div>
                    </div -->
                    <div class="col-md-12" style="margin-top: -70px;">
                        <div class="intro-message">
                            <center><img class="img-responsive" src="../image/logo_farusac.png" width="400"></center>
                            <h3>Sistema de información académica - Estudiante</h3>                            
                            <hr class="intro-divider">
                            <form method="POST" action="login.php">
                                <ul class="list-inline">
                                    <li>
                                        <input class="form-control" type="text" style="margin-top: 5px" value="" placeholder="Número de carnet" name="usuario" required>
                                    </li>
                                    <li>
                                        <input class="form-control" type="password" style="margin-top: 5px" value="" placeholder="Contraseña" name="password" required>
                                    </li>
                                    <li>
                                        <button style="margin-top: 5px"  class="form-control btn-primary"><i class="fa fa-fw fa-key"></i> Ingresar</button>
                                    </li>
                                </ul>
                            </form>
                            <?php
                            require_once ('misc/Google/autoload.php');

                            //Insert your cient ID and secret 
                            //You can get it from : https://console.developers.google.com/
                            $client_id = '545781005689-pgqht2bhk4o9rm8egc0hfp6p85q6csof.apps.googleusercontent.com';
                            $client_secret = 'I2hfRzX0X7THnf9ic_TVNcBq';
                            $redirect_uri = 'http://estudiante.arquitectura.usac.edu.gt/login.php';

                            $client_id = '38334352999-umk5odvcefpk6huh9214gno5mdco5cri.apps.googleusercontent.com';
                            $client_secret = 'bq4pLg7Y-lRi4Cz4gG9xsat5';
                            $redirect_uri = 'http://estudiante.arquitectura.usac.edu.gt/login.php';

                            //database
                            $db_username = "administrador"; //Database Username 
                            $db_password = "&OfI=WCV*,W=oEiADG~+"; //Database Password
                            $host_name = "192.168.10.251"; //Mysql Hostname
                            $db_name = 'satu'; //Database Name
                            //incase of logout request, just unset the session var
                            if (isset($_GET['logout'])) {
                                unset($_SESSION['access_token']);
                            }

                            /*                             * **********************************************
                              Make an API request on behalf of a user. In
                              this case we need to have a valid OAuth 2.0
                              token for the user, so we need to send them
                              through a login flow. To do this we need some
                              information from our API console project.
                             * ********************************************** */
                            $client = new Google_Client();
                            $client->setClientId($client_id);
                            $client->setClientSecret($client_secret);
                            $client->setRedirectUri($redirect_uri);
                            $client->addScope("email");
                            $client->addScope("profile");

                            /*                             * **********************************************
                              When we create the service here, we pass the
                              client to it. The client then queries the service
                              for the required scopes, and uses that when
                              generating the authentication URL later.
                             * ********************************************** */
                            $service = new Google_Service_Oauth2($client);

                            /*                             * **********************************************
                              If we have a code back from the OAuth 2.0 flow,
                              we need to exchange that with the authenticate()
                              function. We store the resultant access token
                              bundle in the session, and redirect to ourself.
                             */

                            if (isset($_GET['code'])) {
                                $client->authenticate($_GET['code']);
                                $_SESSION['access_token'] = $client->getAccessToken();
                                header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
                                exit;
                            }

                            /*                             * **********************************************
                              If we have an access token, we can make
                              requests, else we generate an authentication URL.
                             * ********************************************** */
                            if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
                                $client->setAccessToken($_SESSION['access_token']);
                            } else {
                                $authUrl = $client->createAuthUrl();
                            }


                            //Display user info or display login url as per the info we have.
                            if (isset($authUrl)) {
                                //show login url
                                echo "<ul class='list-inline'>";
                                echo "<li>";
                                echo "<button style='margin-top: 5px'  class='form-control btn-danger' Onclick=\"window.open('$authUrl','_parent')\"><i class='fa fa-google'></i> Ingresar con <small>Correo institucional</small></button>";
                                echo "</li>";
                                echo "</ul>";
                            }
                            ?>                            
                        </div>
                    </div>
                </div>
                <!--a href="http://estudiante.arquitectura.usac.edu.gt/images/Pago_de_laboratorio_20170220.png" alt="Comunicado Oficial - Asignaciones." target="_blank"> 
                <img src="http://estudiante.arquitectura.usac.edu.gt/images/Pago_de_laboratorio_20170220.png" title="Comunicado Oficial - Asignaciones." class="" style="
                        max-width: 15%;
                        border-style: solid;
                        border-width: 3px;
                        border-color: #337ab7;
                        float: right;
                        position: absolute;
                        right: 5px;
                        top: 55px;
                "></a -->
            </div>
            <!-- /.container -->

        </div>
        <!-- /.intro-header -->

        <footer>
            <div class="container">
                <div class="row>">
                    <div class="col-lg-12">
                        <?php echo DATE("o") ?> &copy; Unidad de Informática - Control Académico<br>[WS2]
                    </div>
                </div>
            </div>
        </footer>

        <!-- jQuery -->
        <script src="js/jquery.js"></script>

        <!-- Bootstrap Core JavaScript -->
        <script src="js/bootstrap.js"></script>

    </body>

</html>
