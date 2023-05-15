<?php
namespace App\Api\Home;


error_reporting(E_ALL);
ini_set('display_errors', 1);


// require_once (__DIR__."/../vendor/autoload.php");
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Systemico\JMail;
use Variables;

/**
 * Action
 */
final class PruebaMail
{
    /**
     * Invoke.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface
    {
        $json = array_merge($args, (array)$request->getParsedBody());

        $jmail= new JMail();
        // $jmail->credentials_mailer('jnadinh@gmail.com', 'tpiwymtgegamahao', 'Gestion Politica', 'REFERIDOS');
        $jmail->credentials_mailer('gestionpolitica2022@gmail.com', 'vymqkgyzgwqcuhie', 'Gestion Politica', 'REFERIDOS');
        $envio = $jmail->send($json['mail'],'Registro de Referido','Bienvenido al equipo. Ud ha sido registrado en xxx','My first mail TEXT');

        exit;
    }

    public function mail(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface
    {

        $archivo = __DIR__."/testmail.php";

        $para = 'jnadinh@hotmail.com';
        $asunto = 'este es el asunto';
        $mensaje = 'este es el mensaje';

        $cuentauser = Variables::$Usernamephpmailer ;
        $cuentapass = Variables::$Passwordphpmailer ;
        $cuentauser = Variables::$Usernamephpmailer ;
        $nomremite  = Variables::$nombreRemite;
        $nomdestino = Variables::$nombreDestino;

        passthru("php '$archivo' ' $para' ' $asunto' ' $mensaje' ' $cuentauser' ' $cuentapass' ' $nomremite' ' $nomdestino'  ");

        //exec("dir", $output, $return);
        //echo "Dir returned $return, and output:\n";
        //var_dump($output);

        exit;
    }

}
