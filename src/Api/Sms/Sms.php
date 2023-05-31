<?php
namespace App\Api\Sms;

 //error_reporting(E_ALL);
 //ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use ConectorDBPostgres;
use Variables;


class Sms {

    private $id_usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_usuario = $_SESSION['id_usuario'];
    }

    public function enviar_sms_mercadeo($celular, $mensaje) {

        // Toma los datos de la tabla configuracion

        $url    = "https://api103.hablame.co/api/sms/v3/send/marketing";
        $account= "10025171";
        $apikey = "acoLPkyjYoONo9y8cEQBEpRKTSrm05";
        $token  = "c15467b8e4b88aba4e81b6012510f619";

        $curl = curl_init();
        $fields = array(
            'toNumber' => $celular,
            'sms' => $mensaje,
            'flash' => '0',
            'sc' => '890202',
            'request_dlvr_rcpt' => '0',
            'sendDate' => 'string'
        );
        $header = array(
            'account:'.$account,
            'apiKey:'.$apikey,
            'token:'.$token,
            'Content-Type: application/json'
        );
        $json_string = json_encode($fields);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
        $data = curl_exec($curl);
        curl_close($curl);

        // envia los datos del mensaje a la BBDD

        return $data;

    }
    public function enviar_sms_prioritario($celular, $mensaje) {

        // Toma los datos de la tabla configuracion

        $url    = "https://api103.hablame.co/api/sms/v3/send/priority";
        $account= "10025171";
        $apikey = "acoLPkyjYoONo9y8cEQBEpRKTSrm05";
        $token  = "c15467b8e4b88aba4e81b6012510f619";

        $curl = curl_init();
        $fields = array(
            'toNumber' => $celular,
            'sms' => $mensaje,
            'flash' => '0',
            'sc' => '890202',
            'request_dlvr_rcpt' => '0',
            'sendDate' => 'string'
        );
        $header = array(
            'account:'.$account,
            'apiKey:'.$apikey,
            'token:'.$token,
            'Content-Type: application/json'
        );
        $json_string = json_encode($fields);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
        $data = curl_exec($curl);
        curl_close($curl);

        // envia los datos del mensaje a la BBDD

        return $data;

    }


}
