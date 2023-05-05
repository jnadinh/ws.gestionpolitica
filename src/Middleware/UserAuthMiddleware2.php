<?php

namespace App\Middleware;

require_once __DIR__ . '/../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../conf/configuracion.php';
require_once __DIR__ . '/../componentes/general/general.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

use Variables;
use ConectorDBPostgres;

/**
 * Middleware.
 */
final class UserAuthMiddleware2
{

    /**
     * Example middleware invokable class
     *
     * @param  ServerRequest  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     */

     private $conector;

	public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
    }

    public function __invoke(Request $request, RequestHandler $handler): Response  {

        $response = new Response();

         //obtiene la ip
        $ip = $_SERVER['REMOTE_ADDR'];

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();


        // Valida datos completos
        if( !isset($json['token'])      || $json['token']=="") {

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $token = $json['token'];

        // valida el usuario y token
        $sql = "SELECT u.usuario, u.id FROM public.tab_usuarios u
        INNER JOIN public.tab_token t ON t.usuarios_id = u.id AND
        t.token ='$token' AND (t.fecha_actualiza + interval '".Variables::$tiempoSESION."minutes') > now() ;";
        // die($sql);
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);

        if (!$res) {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'SESION INACTIVA' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // actualiza fecha actualiza para tiempo de session
        $sqltok = "UPDATE public.tab_token SET fecha_actualiza=NOW() WHERE token = '$token' " ;
        $sqltok=reemplazar_vacios($sqltok);
        $restok=$this->conector->update($sqltok);

        $_SESSION['id_usuario']=$res[0]['id'];
        $_SESSION['usuario']=$res[0]['usuario'];
        $_SESSION['token']=$token;

        return $handler->handle($request);
    }
}
