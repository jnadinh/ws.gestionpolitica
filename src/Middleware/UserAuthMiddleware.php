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
final class UserAuthMiddleware
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
        $sql = "SELECT u.usuario, u.id, u.esquema_db, u.tipo, id_rol 
        FROM public.users u
        INNER JOIN public.token t ON t.users_id = u.id AND
        t.token ='$token' AND (t.updated_at + interval '".Variables::$tiempoSESION."minutes') > now() ;";
        // die($sql);
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
                
        if (!$res) {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'SESION INACTIVA' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // actualiza fecha updated_at para tiempo de session
        $sqltok = "UPDATE public.token SET updated_at=NOW() WHERE token = '$token' " ;
        $sqltok=reemplazar_vacios($sqltok);
        $restok=$this->conector->update($sqltok);  
        
        $_SESSION['id_usuario']=$res[0]['id'];
        $_SESSION['usuario']=$res[0]['usuario'];
        $_SESSION['esquema_db']=$res[0]['esquema_db'];
        $_SESSION['tipo_casino']=$res[0]['tipo'];
        $_SESSION['token']=$token;
        $_SESSION['id_rol']=$res[0]['id_rol'];

        return $handler->handle($request);

    }
}
