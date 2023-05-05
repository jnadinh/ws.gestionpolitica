<?php
/**
 * JMail example with Mailgun
 * @author Edwin Ariza <edwin.ariza@systemico.co>
 * @copyright Systemico Software S.A.S
 */

require_once (__DIR__."/../vendor/autoload.php");

use Systemico\JMail;

$jmail= new JMail();

// Mensaje con MailGUN
$jmail->credentials('[PRIVATE_API_KEY]', '[DOMAIN]', '[SENDER]',JMail::$MAILGUN,'[NAME_SENDER]');
$jmail->send('[EMAIL_TO]','Hello World!','My firts Email HTML','My first mail TEXT');

// Mensaje con MailJet
$jmail->credentials(array('[API_KEY]','[SECRET_API_KEY]'), '[DOMAIN]', '[SENDER]',JMail::$MAILJET,'[NAME_SENDER]');
$jmail->send('[EMAIL_TO]','Hello World!','My firts Email HTML','My first mail TEXT');

// Mensaje con PHPMailer
$jmail->credentials_mailer('[EMAIL_FROM]', '[EMAIL_FROM_PASSWORD]', '[NAME]', 'NAME_TO');
$jmail->send('[EMAIL_TO]','Hello World!','My firts Email HTML','My first mail TEXT');
