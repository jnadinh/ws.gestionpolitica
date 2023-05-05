<?php
namespace App\Api\LoginSuperAdmin;

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class LoginSuperAdmin {

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
    }

    public function iniciarSesionSuperAdmin(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['usuario'])    || $json['usuario']==""      ||
            !isset($json['clave'])      || $json['clave']==""
        ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $usuario = $json['usuario'];
        $clave   = $json['clave'];

        // valida el usuario y clave
        $sql = "SELECT id, nombre, telefono, celular, ciudad, estado, email, obs, usuario
        FROM public.tab_usuarios
        WHERE (usuario='$usuario' OR email='$usuario' OR celular='$usuario') AND clave = MD5('$clave') ";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
        $id_usuario = $res[0]['id'];
        // var_dump($res);
        if($res==2)  {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR BBDD', 'DATOS' => 'ERROR EN LA CONSULTA' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else if(count2($res)==0)  {
            // Construye la respuesta
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'USUARIO O PASSWORD INVALIDO' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else{
            // genera el token
            mt_srand();
            $random=null;
            for($i=1;$i<=16;$i++) {
              $random .= mt_rand (0, 9);
            }
            $token= strtoupper($usuario . "SA" . $random);

            // crea el token
            $sqltok = "INSERT INTO public.tab_token (usuarios_id, token, fecha_actualiza)
            VALUES ('$id_usuario', '$token', now() ) ";
            $sqltok=reemplazar_vacios($sqltok);
            $restok=$this->conector->update($sqltok);

            //agreaga el token al array
            $res[0]['token'] = $token;

        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $res[0] );
        $response->getBody()->write((string)json_encode($respuesta) );
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
	}


    public function cerrarSesionSuperAdmin(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $id_usuario = $_SESSION['id_usuario'];
        $usuario    = $_SESSION['usuario'];
        $token      = $_SESSION['token'];

        // elimina el token
        $sql = "DELETE FROM public.tab_token WHERE token='$token' ;";
        $res = $this->conector->delete($sql);

        if(!$res) {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'NO SE CERRO LA SESION', 'DATOS' => $token );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } else {

            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $token );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
	}

    public function validarTokenSuperAdmin(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // El token se valida en el UserAuthMiddleware. Si llega aqui es válido

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => "Token Válido" );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function cambiarClaveSuperAdmin(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['usuario'])        || $json['usuario']==""     ||
            !isset($json['clave'])          || $json['clave']==""       ||
            !isset($json['nueva_clave'])    || $json['nueva_clave']==""
        ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $usuario = $json['usuario'];
        $clave   = $json['clave'];
        $nueva_clave = $json['nueva_clave'];

        // valida el usuario y clave
        $sql = "SELECT id, nombre, telefono, celular, ciudad, estado, email, obs, usuario
        FROM public.tab_usuarios
        WHERE (usuario='$usuario' OR email='$usuario' OR celular='$usuario') AND clave= MD5('$clave') ";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
        // print_r($res);die;
        $id_usuario = $res[0]['id'];

        if($res==2)  {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR BBDD', 'DATOS' => 'ERROR EN LA CONSULTA' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else if(count($res)==0)  {
            // Construye la respuesta
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'USUARIO O PASSWORD INVALIDO' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else{
            // cambia la clave

            $sqlup = "UPDATE public.tab_usuarios SET clave = MD5('$nueva_clave')  WHERE id='$id_usuario'  ;";
            $sqlup=reemplazar_vacios($sqlup);
            //die($sqlup);
            $resup = $this->conector->update($sqlup);
            if(!$resup) {
                // si no trae datos retorna codigo 2
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => "NO SE ACTUALIZÓ EL REGISTRO");
                $response->getBody()->write(json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }

        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $id_usuario );
        $response->getBody()->write((string)json_encode($respuesta) );
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

}
?>
