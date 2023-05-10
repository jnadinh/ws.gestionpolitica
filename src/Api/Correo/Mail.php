<?php
namespace App\Api\Correo;

 //error_reporting(E_ALL);
 //ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use ConectorDBPostgres;
use Variables;


class Mail {

    private $id_usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_usuario = $_SESSION['id_usuario'];
    }

    function enviar_mail($para, $asunto, $mensaje){


        // envia correo phpmailer desde consola
        $archivo = __DIR__."/enviomail.php";

        $cuentauser = Variables::$Usernamephpmailer ;
        $cuentapass = Variables::$Passwordphpmailer ;
        $cuentauser = Variables::$Usernamephpmailer ;
        $nomremite  = Variables::$nombreRemite;
        $nomdestino = Variables::$nombreDestino;
        $smtpserver = Variables::$puertohpmailer;

        $data = passthru("php '$archivo' ' $para' ' $asunto' ' $mensaje' ' $cuentauser' ' $cuentapass' ' $nomremite' ' $nomdestino' ' $smtpserver' ");

        // EJEMPLO ENVIO MAIL
        // $jmail->credentials_mailer('[EMAIL_FROM]', '[EMAIL_FROM_PASSWORD]', '[NAME]', 'NAME_TO', 'SMTP SERVER', true);

        // envia los datos del mensaje a la BBDD

        return $data;
    }
}

?>
