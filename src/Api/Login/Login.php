<?php
namespace App\Api\Login;

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Login {

    private $id_rol;
    private $tipo_casino;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> tipo_casino= $_SESSION['tipo_casino'];
    }

    public function iniciarSesion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

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

        // si trae el esquema hace el logueo, si no trae el esquema busca en todos.
        // si encuentra el usuario en un esquema, hace el logueo, si esta en mas de uno,
        // devuelve la informacion para permitir elegir esquema

        if(isset($json['esquema']) && $json['esquema'] != "")  {
            // valida el usuario y clave

        }

        $usuario = $json['usuario'];
        $clave   = $json['clave'];
        $tipo    = isset($json['tipo'])?$json['tipo']:1;        // 1=casino 2=tienda 3=admin
        $id_rol  = isset($json['id_rol'])?$json['id_rol']:1;    // 1=usuario 2=admin

	}


    public function cerrarSesion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $token = $_SESSION['token'];

        // elimina el token
        $sql = "DELETE FROM public.token WHERE token='$token' ;";
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

    public function validarToken(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // El token se valida en el UserAuthMiddleware. Si llega aqui es válido

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => "Token Válido" );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }


    public function olvidopassword(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['cedula'])    || $json['cedula']==""     ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // hace la consulta

        // Crear token
        //$validar = $c->crearTokenPassword($usuario, $metodo, $ip, Variables::$urlRestaurar);

        $response->getBody()->write((string)json_encode("validar"));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

	}

    public function resetPassword(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = array_merge($args, (array)$request->getParsedBody());

        $clave = isset($json['clave'])?$json['clave']:null;
        $token = isset($json['token'])?$json['token']:null;     // url

        // Valida reset_password
        // $validar = $c->cambiarClave($clave, $token, $metodo, $ip);

        $response->getBody()->write((string)json_encode("validar"));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

	}

    public function getXml(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        $html = "<html lang=\"en\">\n" .
        "<head>\n" .
        "  <meta charset=\"UTF-8\">\n" .
        "  <title>Restaurar Clave Formulario</title>\n" .
        "</head>\n" .
        "<body>\n" .
        "<form id='form' action=\"".Variables::$urlBase."restaurar_clave/".$args['token']."\" method=\"POST\" enctype='text/plain'>\n" .
        "    <input type=\"password\" id='clave' name='clave'>\n" .
        "    \n" .
        "</form> <input type=\"submit\" onclick='enviar()'>\n  <script> function enviar(){var temp = document.getElementById('clave').value;\n" .
        "  fetch('".Variables::$urlBase."restaurar_clave/".$args['token']."', {\n headers: {\n" .
        "      'Accept': 'application/json',\n" .
        "      'Content-Type': 'application/json'\n" .
        "    }," .
        "    body: JSON.stringify({clave:temp}),\n" .
        "    method: 'POST',\n" .
        "  })\n" .
        "  .then(function (response) {\n" .
        "     response.json().then( data => {"
                                . "document.getElementById('form').innerHTML = data.MENSAJE;\n});" .
        "    return console.log('Success!', response);\n" .
        "  })} </script>" .

        "</body>\n" .
        "</html> ";

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html')
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    public function login2(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write("OK");
        return $response->withHeader('allow','Content-Type', 'application/json')->withStatus(200)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    public function cerrarSesion2(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write("OK");
        return $response->withHeader('allow','Content-Type', 'application/json')->withStatus(200)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    public function olvidopassword2(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write("OK");
        return $response->withHeader('allow','Content-Type', 'application/json')->withStatus(200)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    public function resetPassword2(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write("OK");
        return $response->withHeader('allow','Content-Type', 'application/json')->withStatus(200)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }

    public function cambiarClave(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

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
        $sql = "SELECT id, name, telefono, ciudad, estado, email, obs, usuario
        FROM public.users
        WHERE usuario='$usuario' OR email='$usuario' AND clave= MD5('$clave') ";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
        // print_r($res);die;
        $id_usuario = $res[0]['id'];

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
            // cambia la clave

            $sqlup = "UPDATE public.users SET clave = MD5('$nueva_clave')  WHERE id='$id_usuario'  ;";
            $sqlup=reemplazar_vacios($sqlup);
            //die($sqlup);
            $resup = $this->conector->update($sqlup);
            if(!$resup) {
                // si no trae datos retorna codigo 2
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => "NO SE ACTUALIZO EL REGISTRO");
                $response->getBody()->write(json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }

        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $id_usuario );
        $response->getBody()->write((string)json_encode($respuesta) );
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function validarUsuarioClave (ResponseInterface $response, $usuario, $clave, $esquema) {

        // valida el usuario y clave
        $sql = "SELECT id, name, telefono, ciudad, estado, email, obs, usuario, tipo, id_rol
        FROM $esquema.users
        WHERE (usuario='$usuario' OR email='$usuario') AND clave= MD5('$clave') ";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
        $id_usuario = $res[0]['id'];
        // var_dump($res, $id_usuario);die($sql);

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
            $token= strtoupper($usuario . "CS" . $random);

            // crea el token
            $sqltok = "INSERT INTO public.token (users_id, token, updated_at)
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

}
?>
