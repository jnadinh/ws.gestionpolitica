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

     private $id_usuario;
     private $esquema_db;

    private $conector;

	public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> esquema_db = $_SESSION['esquema_db'];
    }

    public function __invoke(Request $request, RequestHandler $handler): Response  {

        $response = new Response();

         //obtiene la ip
        $ip = $_SERVER['REMOTE_ADDR'];

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['token'])      || $json['token']==""       ||
            !isset($json['esquema_db']) || $json['esquema_db']==""  ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $token = $json['token'];
        $esquema_db = $json['esquema_db'];

        // valida el usuario y token
        $sql = "SELECT p.nombre, p.id
        FROM $esquema_db.tab_personas p
        INNER JOIN $esquema_db.tab_token t ON t.personas_id = p.id AND
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
        $sqltok = "UPDATE $esquema_db.token SET fecha_actualiza=NOW() WHERE token = '$token' " ;
        $sqltok=reemplazar_vacios($sqltok);
        $restok=$this->conector->update($sqltok);

        $_SESSION['id_usuario']=$res[0]['id'];
        $_SESSION['usuario']=$res[0]['usuario'];
        $_SESSION['token']=$token;
        $_SESSION['esquema_db']=$esquema_db;

        return $handler->handle($request);
    }

    public function socio(Request $request, RequestHandler $handler): Response  {

        // valida token en __invoke
        self::__invoke($request, $handler);

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        var_dump($json, "esta en socio");

        $response = new Response();
        $modulos_id = 1;

        // valida que el rol tenga acceso al modulo
        $sql = "SELECT DISTINCT m.id, m.nombre
        FROM public.tab_modulos m
        INNER JOIN public.tab_roles_modulos rm on m.id = rm.modulos_id
        INNER JOIN $this->esquema_db.tab_personas_roles pr on rm.roles_id  = pr.roles_id
        WHERE pr.personas_id = 1 AND rm.modulos_id = $modulos_id
        ORDER BY m.id ";
        // die($sql);
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);

        if (!$res) {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'SESION INACTIVA' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);

    }

}
