<?php

use Exception;

/**
 * API Client para el Webservice de DEPPA - Evaluación Docente
 * REQUERIMIENTOS: php-curl curl
 */

class DeppaApiClient
{
    private $url_base;
    private $usuario;
    private $passwd;
    private $unidad;
    private $config;

    const ERROR_REQUEST = 1001;
    const ERROR_BAD_RESPONSE = 1002;
    const ERROR_BAD_REQUEST = 1003;

    /**
     * Configuración inicial del API Client para la evaluación docente
     *
     * @param string  $url_base      La url base sobre la cual se encuentran los servicios Rest
     * @param string  $usuario       Nombre de usuario provisto a la unidad académica para integrarse al sistema de evaluación
     *                             del personal.
     * @param string  $passwd      Contraseña del usuario provisto a la unidad académica para integrarse al sistema de evaluación
     *                             del personal.
     * @param int   $unidad   Código que identifica la unidad académica que está realizando la
     *                             consulta.
     * @param array $config          Configuración general para el API Client y la configuración de las peticiones por CURL (no usado
     *                             actualmente).
     */
    function __construct($url_base, $usuario, $passwd, $unidad, $config)
    {
        $this->url_base = $url_base;
        $this->usuario = $usuario;
        $this->passwd = $passwd;
        $this->config = $config;
        $this->unidad = $unidad;
    }

    /**
     * Función de proposito general para generar peticiones POST a los servicios REST
     *
     * @param   $url_base
     * @param   $apiMethod
     * @param   $data
     * @param   $config
     *
     * @return array Retorna un array con el formato ["error" => true/false, "data" => respuesta_del_API, "exception" => stackTrace_del_error, descripcion => descripcion_del_error]
     */
    private function makeRequest($url_base, $apiMethod, $data, $config)
    {
        $dataSend = array_merge($data, ["usuario" => $this->usuario, "password" => $this->passwd, "codigo_unidad" => $this->unidad]);
        //var_dump("Array enviado como parametros de la petición: ", $dataSend);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_base . $apiMethod);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dataSend));
            $response = curl_exec($ch);
            //convertir el texto a un array o reportar error...
            try {
                $dataArray = json_decode($response, true);
                if (!empty($dataArray)) {
                    return ["error" => false, "data" => $dataArray];
                } else {
                    return ["error" => true, "response" => $response, "descripcion" => "La respuesta NO se encuentra bien formada.", "errorCode" => static::ERROR_BAD_REQUEST, 'responseCode' => curl_getinfo($ch, CURLINFO_HTTP_CODE), "response" => $response];
                }
            } catch (Exception $exc) {
                return ["error" => true, "exception" => $exc->getTraceAsString(), "descripcion" => "Ha sucedido un error al intentar convertir la respuesta a un Array.", "errorCode" => static::ERROR_BAD_RESPONSE];
            }
        } catch (Exception $exc) {
            return ["error" => true, "exception" => $exc->getTraceAsString(), "descripcion" => "Ha sucedido un error al intentar realizar la petición.", "errorCode" => static::ERROR_REQUEST];
        }
    }

    /**
     * Consultar si existe algún período de evaluación activo para una fecha en específico.
     *
     * @param string $fecha Fecha de consulta de período activo. Formato: año-mes-dia.
     *
     * @return array
     */
    public function verificarPeriodoDeEvaluacion($fecha)
    {
        $data = [
            "fecha" => $fecha,
        ];
        return $this->makeRequest($this->url_base, "/PeriodoEvaluacion", $data, $this->config);
    }

    /**
     * Reservar un ticket para contestar la evaluación (encuesta) y enviar la información al componente que lo solicita para mostrarlo al evaluador.
     *
     * @param int  $anio           Año al que corresponde el token
     *                             de evaluación.
     * @param string $codigo_periodo Periodo al que corresponde el token de evaluación.
     * @param string $codigo_curso   Codigo de curso al que se desea evaluar en la nomenclatura de la unidad.
     * @param string $seccion        Sección de curso al que se desea evaluar en la nomenclatura de la
     *                             unidad.
     * @param string $token          Identificador del evaluador al que se genera el token. (Función Hash interna, independiente de la plataforma de
     *                             encuestas)
     * @param string $numero_boleta  Boleta con la que se desea evaluar al responsable del curso..
     *
     * @return array
     */
    public function generarEnlaceEvaluacionEnLinea($anio, $codigo_periodo, $codigo_curso, $seccion, $token, $numero_boleta)
    {
        $data = [
            "anio" => $anio,
            "codigo_periodo" => $codigo_periodo,
            "codigo_curso" => $codigo_curso,
            "seccion" => $seccion,
            "token" => $token,
            "numero_boleta" => $numero_boleta,
        ];
        return $this->makeRequest($this->url_base, "/EnlaceEvaluacionEnLinea", $data, $this->config);
    }

    /**
     * Reservar un ticket para contestar la evaluación (encuesta) y enviar la información al componente que lo solicita para mostrarlo al evaluador.
     *
     * @param int $sid   Identificador de la encuesta de LimeSurvey al que corresponde el token consultado.
     * @param string $token Identificador alfanumérico de la encuesta a consultar.
     *
     * @return array
     */
    public function verificarEstadoEvaluacion($sid, $token)
    {
        $data = [
            "sid" => $sid,
            "token" => $token,
        ];
        return $this->makeRequest($this->url_base, "/EstadoEvaluacion", $data, $this->config);
    }
}
