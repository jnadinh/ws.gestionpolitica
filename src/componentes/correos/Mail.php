<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once __DIR__ . '/../../conf/configuracion.php';

use Variables;


function enviar_mail($para, $asunto, $mensaje){


    // envia correo phpmailer desde consola
    $archivo = __DIR__."/enviomail.php";

    $cuentauser = Variables::$Usernamephpmailer ;
    $cuentapass = Variables::$Passwordphpmailer ;
    $cuentauser = Variables::$Usernamephpmailer ;
    $nomremite  = Variables::$nombreRemite;
    $nomdestino = Variables::$nombreDestino;
    $smtpserver = Variables::$puertohpmailer;

    passthru("php '$archivo' ' $para' ' $asunto' ' $mensaje' ' $cuentauser' ' $cuentapass' ' $nomremite' ' $nomdestino' ' $smtpserver' ");

    // EJEMPLO ENVIO MAIL
    // $jmail->credentials_mailer('[EMAIL_FROM]', '[EMAIL_FROM_PASSWORD]', '[NAME]', 'NAME_TO', 'SMTP SERVER', true);

}


?>
