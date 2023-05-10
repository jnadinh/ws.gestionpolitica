<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require_once (__DIR__."/../../../vendor/autoload.php");
require_once __DIR__ . '/../../conf/configuracion.php';

use Systemico\JMail;

$archivo = $_SERVER['argv'][0];
$correo = $_SERVER['argv'][1];
$asunto = $_SERVER['argv'][2];
$mensaje = $_SERVER['argv'][3];

$cuenta = $_SERVER['argv'][4];
$clave = $_SERVER['argv'][5];
$nombreenvia = $_SERVER['argv'][6];
$nombrerecibe = $_SERVER['argv'][7];


$jmail= new JMail();
$jmail->credentials_mailer($cuenta, $clave, $nombreenvia, $nombrerecibe);
$envio = $jmail->send($correo, $asunto, $mensaje, $mensaje);

?>