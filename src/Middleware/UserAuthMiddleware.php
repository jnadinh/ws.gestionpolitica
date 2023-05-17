<?php

namespace App\Middleware;

require_once __DIR__ . '/../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../conf/configuracion.php';
require_once __DIR__ . '/../componentes/general/general.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();

        // valida token
        $validar = self::validarToken($json);

        if( $validar!="Token válido"){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validar, 'DATOS' => $validar );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function validarToken($json) {

        // Valida datos completos
        if( !isset($json['token'])      || $json['token']==""       ||
            !isset($json['esquema_db']) || $json['esquema_db']==""  ){

            return "Faltan datos";
        }

        $token = $json['token'];
        $esquema_db = $json['esquema_db'];

        // valida el usuario y token
        $sql = "SELECT p.nombre, p.id
        FROM $esquema_db.tab_personas p
        INNER JOIN $esquema_db.tab_token t ON t.personas_id = p.id AND
        t.token ='$token' AND (t.fecha_actualiza + interval '".Variables::$tiempoSESION." minutes') > now() ;";
        $sql=reemplazar_vacios($sql);
        // die($sql);
        $res = $this->conector->select($sql);

        if (!$res) {
            return "Sesión inactiva";
        }

        // actualiza fecha actualiza para tiempo de session
        $sqltok = "UPDATE $esquema_db.tab_token SET fecha_actualiza=NOW() WHERE token = '$token' " ;
        $sqltok=reemplazar_vacios($sqltok);
        $restok=$this->conector->update($sqltok);
        // var_dump($restok); die($sqltok);

        $_SESSION['id_usuario']=$res[0]['id'];
        $_SESSION['token']=$token;
        $_SESSION['esquema_db']=$esquema_db;

        return "Token válido";
    }

    public function validarModulo($modulos_id) {

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
            return "No tiene acceso al módulo";
        }

        return "Módulo válido";
    }

    public function validarTokenSuperUsuario($json) {

        // Valida datos completos
        if( !isset($json['token'])      || $json['token']=="") {

            return "Faltan datos";
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
            return "Sesión inactiva";
        }

        // actualiza fecha actualiza para tiempo de session
        $sqltok = "UPDATE public.tab_token SET fecha_actualiza=NOW() WHERE token = '$token' " ;
        $sqltok=reemplazar_vacios($sqltok);
        $restok=$this->conector->update($sqltok);

        $_SESSION['id_usuario']=$res[0]['id'];
        $_SESSION['usuario']=$res[0]['usuario'];
        $_SESSION['token']=$token;

        return "Token válido";
    }

    public function dobleValidacion(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();

        // valida token
        $validarToken1 = self::validarToken($json);
        // valida token
        $validarToken2 = self::validarTokenSuperUsuario($json);
        // die($validarToken);
        if( $validarToken1!="Token válido" && $validarToken2!="Token válido"){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. Sesión inactiva o Faltan datos', 'DATOS' => $validarToken1. ' '. $validarToken2);
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function superAdmin(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();

        // valida token
        $validarToken2 = self::validarTokenSuperUsuario($json);
        // die($validarToken);
        if( $validarToken2!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken2, 'DATOS' => $validarToken2 );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }


    public function misReferidos(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 1;
        $modulos_nombre = "Mis Referidos";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validarToken);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function misActividades(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 2;
        $modulos_nombre = "Mis Actividades";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function misReuniones(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 3;
        $modulos_nombre = "Mis Reuniones";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function misGestiones(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 4;
        $modulos_nombre = "Mis Gestiones";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function misVisitas(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 5;
        $modulos_nombre = "Mis Visitas";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function lideres(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 6;
        $modulos_nombre = "Lideres";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function referidos(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 7;
        $modulos_nombre = "Referidos";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function actividades(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 8;
        $modulos_nombre = "Actividades";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function reuniones(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 9;
        $modulos_nombre = "Reuniones";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validarToken);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        // die($validarModulo);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function gestiones(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 10;
        $modulos_nombre = "Gestiones";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function visitas(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 11;
        $modulos_nombre = "Visitas";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function reportes(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 12;
        $modulos_nombre = "Reportes";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function mensajes(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 13;
        $modulos_nombre = "Mensajes";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

    public function registroAsistentesReuniones(Request $request, RequestHandler $handler): Response  {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $response = new Response();
        $modulos_id     = 14;
        $modulos_nombre = "Registro Asistentes Reuniones";

        // valida token
        $validarToken = self::validarToken($json);
        // die($validar);
        if( $validarToken!="Token válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarToken, 'DATOS' => $validarToken );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el rol tenga acceso al modulo
        $validarModulo = self::validarModulo($modulos_id);
        if( $validarModulo!="Módulo válido"  ){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. '.$validarModulo .' '.$modulos_nombre, 'DATOS' => $validarModulo ." ".$modulos_nombre );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        return $handler->handle($request);
    }

}
