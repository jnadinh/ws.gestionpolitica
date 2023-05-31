<?php
namespace App\Api\Correo;

 //error_reporting(E_ALL);
 //ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';
require_once (__DIR__."/../../../vendor/autoload.php");

use Systemico\JMail;

use ConectorDBPostgres;
use Variables;


class Mail2 {

    private $id_usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_usuario = $_SESSION['id_usuario'];
    }

    function enviar_mail($para, $asunto, $mensaje){

        $cuentauser = Variables::$Usernamephpmailer ;
        $cuentapass = Variables::$Passwordphpmailer ;
        $nomremite  = Variables::$nombreRemite;
        $nomdestino = Variables::$nombreDestino;
        $smtpserver = Variables::$hostphpmailer;

        $jmail= new JMail();
        $jmail->credentials_mailer($cuentauser, $cuentapass, $smtpserver, $nomremite, $nomdestino);
        $envio = $jmail->send($para, $asunto, $mensaje, $mensaje);

        // EJEMPLO ENVIO MAIL
        // $jmail->credentials_mailer('[EMAIL_FROM]', '[EMAIL_FROM_PASSWORD]', '[NAME]', 'NAME_TO', 'SMTP SERVER', true);

        // envia los datos del mensaje a la BBDD

        return $envio;
    }
}

?>
