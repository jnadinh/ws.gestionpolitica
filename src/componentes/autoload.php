<?php
require_once dirname(__FILE__).'/../conf/configuracion.php';
require_once dirname(__FILE__).'/general/general.php';
require_once dirname(__FILE__).'/../conf/response.php';
require_once dirname(__FILE__).'/conector/ConectorDB.php';
//require_once dirname(__FILE__).'/PayU/PayU.php';
require_once dirname(__FILE__).'/Mailgun/JMail.php';
require_once dirname(__FILE__).'/Slim/Slim.php';
require_once dirname(__FILE__).'/token/token.php';
//require_once dirname(__FILE__).'/google/calendario.php';
//require_once dirname(__FILE__).'/google/drive.php';
require_once dirname(__FILE__).'/idiomas/idiomas.php';
require_once dirname(__FILE__) . "/mpdf60/vendor/autoload.php";

require_once dirname(__FILE__) .'/phpmailer/PHPMailer.php';
require_once dirname(__FILE__) .'/phpmailer/SMTP.php';
require_once dirname(__FILE__) .'/payu-php-sdk-4.5.6/lib/PayU.php';


?>
