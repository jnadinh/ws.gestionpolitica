<?php
namespace App\Api\Login;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use App\Api\Correo\Mail2 as Mail2;
use App\Api\Sms\Sms as Sms;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Login {

    private $id_usuario;
    private $usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> esquema_db = $_SESSION['esquema_db'];
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> usuario    = $_SESSION['usuario'];
    }

    public function iniciarSesion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['usuario'])    || $json['usuario']==""     ||
            !isset($json['clave'])      || $json['clave']==""       ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $usuario    = $json['usuario'];
        $clave      = $json['clave'];
        $esquema_db = $json['esquema_db'];

        // si trae el esquema hace el logueo, si no trae el esquema busca en todos.
        // busca en los esquemas y devuelve la informacion para permitir elegir esquema

        // aqui valida y devuelve los datos de los esquemas y se sale hasta que reciba el esquema

        if(isset($json['esquema_db']) && $json['esquema_db'] != "")  {

            // valida el usuario y clave
            $sql = "SELECT id, nombre, apellidos
            FROM $esquema_db.tab_personas
            WHERE (email='$usuario' OR cedula ='$usuario') AND clave= MD5('$clave') ";
            $sql=reemplazar_vacios($sql);
            $res = $this->conector->select($sql);
            // die($sql);
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
                // genera el token
                mt_srand();
                $random=null;
                for($i=1;$i<=16;$i++) {
                $random .= mt_rand (0, 9);
                }
                $token= strtoupper($usuario."CANDIDATO".$esquema_db."GP".$random);

                // crea el token
                $sqltok = "INSERT INTO $esquema_db.tab_token (personas_id, token, fecha_actualiza)
                VALUES ('$id_usuario', '$token', now() ) ";
                $sqltok=reemplazar_vacios($sqltok);
                $restok=$this->conector->insert($sqltok);
                //die($sqltok);

                //agreaga el token al array
                $res[0]['token'] = $token;

                $sqlmod = "SELECT DISTINCT m.id, m.nombre
                FROM public.tab_modulos m
                inner join public.tab_roles_modulos rm on m.id = rm.modulos_id
                inner join $esquema_db.tab_personas_roles pr on rm.roles_id  = pr.roles_id
                where pr.personas_id = $id_usuario
                order by m.id";
                //die($sqlmod);
                $sqlmod=reemplazar_vacios($sqlmod);
                $resmod=$this->conector->select($sqlmod);

                //agreaga los modulas al array
                $res[0]['modulos'] = $resmod;

                $_SESSION['esquema_db']=$esquema_db;
            }
            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $res[0] );
            $response->getBody()->write((string)json_encode($respuesta) );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        }else {

            // hace la consulta para obtener los esquemas
            $sql = "SELECT s.oid AS id, s.nspname AS nombre_esquema, u.usename
            FROM pg_catalog.pg_namespace s
            JOIN pg_catalog.pg_user u ON u.usesysid = s.nspowner
            WHERE nspname NOT IN ('information_schema', 'pg_catalog', 'public')
            AND nspname NOT LIKE 'pg_toast%' AND nspname NOT LIKE 'pg_temp%';";
            $res = $this->conector->select($sql);
            // var_dump($res); die($sql);
            if(!$res){
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'USUARIO O PASSWORD INVALIDO' );
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }elseif($res==2){
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA');
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }else {
                $esquemas = array();
                foreach ($res as $key => $value) {

                    $esquema_db = $value['nombre_esquema'];

                    $sqlus = "SELECT pe.id, '$esquema_db' AS esquema_db, pa.nombre_candidato
                    FROM $esquema_db.tab_personas pe
                    LEFT JOIN public.tab_parametros pa ON pa.nombre_esquema = '$esquema_db'
                    WHERE (pe.email='$usuario' OR pe.cedula ='$usuario') AND pe.clave= MD5('$clave')";
                    $sqlus=reemplazar_vacios($sqlus);
                    $resus = $this->conector->select($sqlus);
                    //die($sqlus);
                    $id_usuario = $resus[0]['id'];

                    if(count2($resus)>0)  {
                        array_push($esquemas, $resus[0]);
                    }
                }

                if(count2($esquemas)==0)  {
                    // Construye la respuesta
                    $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'USUARIO O PASSWORD INVALIDO' );
                    $response->getBody()->write((string)json_encode($respuesta));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                }

                $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $esquemas);
                $response->getBody()->write((string)json_encode($respuesta) );
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }
        }
	}

    public function cerrarSesion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $token = $_SESSION['token'];

        // elimina el token
        $sql = "DELETE FROM $this->esquema_db.tab_token WHERE token='$token' ;";
        $res = $this->conector->delete($sql);
        // die($sql);

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

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => "TOKEN VALIDO" );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function cambiarClave(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['usuario'])        || $json['usuario']==""     ||
            !isset($json['clave'])          || $json['clave']==""       ||
            !isset($json['nueva_clave'])    || $json['nueva_clave']=="" ){

                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $usuario = $json['usuario'];
        $clave   = $json['clave'];
        $nueva_clave = $json['nueva_clave'];

        // valida el usuario y clave
        $sql = "SELECT id FROM $this->esquema_db.tab_personas
        WHERE (email='$usuario' OR cedula ='$usuario') AND clave= MD5('$clave') ";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
        // die($sql);

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

            $id_usuario = $res[0]['id'];

            // cambia la clave
            $sqlup = "UPDATE $this->esquema_db.tab_personas SET clave = MD5('$nueva_clave')
            WHERE id='$id_usuario' ;";
            $sqlup=reemplazar_vacios($sqlup);
            //die($sqlup);
            $resup = $this->conector->update($sqlup);

            // cambia la clave en las demas esquemas donde este la persona

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

    public function olvidoClave(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['cedula']) || $json['cedula']==""     ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $cedula = $json['cedula'];

        //generar clave aleatoria
        mt_srand();
        $random=null;
        for($i=1;$i<=5;$i++) {
        $random .= mt_rand (0, 9);
        }
        $clave= $cedula.$random;
        // die($clave);
        // cambia la clave en todos los esquemas
        // hace la consulta para obtener los esquemas
        $sql = "SELECT s.oid AS id, s.nspname AS nombre_esquema, u.usename
        FROM pg_catalog.pg_namespace s
        JOIN pg_catalog.pg_user u ON u.usesysid = s.nspowner
        WHERE nspname NOT IN ('information_schema', 'pg_catalog', 'public')
        AND nspname NOT LIKE 'pg_toast%' AND nspname NOT LIKE 'pg_temp%'";
        $res = $this->conector->select($sql);
        //var_dump($res, $sql);
        $email = array();
        $celular = array();
        $nombre = "";
        foreach ($res as $key => $value) {

            $esquema_db = $value['nombre_esquema'];

            $sqlus = "SELECT id, email, celular, nombre, apellidos
            FROM $esquema_db.tab_personas
            WHERE cedula = '$cedula' ";
            $sqlus=reemplazar_vacios($sqlus);
            $resus = $this->conector->select($sqlus);
            // die($sqlus);
            if(count2($resus)>0) {
                $id_usuario = $resus[0]['id'];
                $email[] = $resus[0]['email'];
                $celular[] = $resus[0]['celular'];
                $nombre = $resus[0]['nombre'];
                // cambia la clave
                $sqlup = "UPDATE $esquema_db.tab_personas SET clave = MD5('$clave')
                WHERE id='$id_usuario' ;";
                $sqlup=reemplazar_vacios($sqlup);
                //die($sqlup);
                $resup = $this->conector->update($sqlup);
            }
        }

        // valores unicos en arrays
        $email = array_unique($email);
        $celular = array_unique($celular);

        foreach ($email as $key => $value) {

            $email1 = $value;

            // envia correo
            $asunto = "Olvido de Clave";
            $cuerpo="<section>
            <div class=\"cuadro\" style=\"background-color: white; max-width: 400px; border-radius: 5px 5px 5px 5px; -moz-border-radius: 5px 5px 5px 5px; -webkit-border-radius: 5px 5px 5px 5px; border: 0px solid #000000; margin: 0 auto; margin: 4% auto 0 auto; padding: 0px 0px 20px 0px; -webkit-box-shadow: 0px 3px 3px 2px rgba(0,0,0,0.16); -moz-box-shadow: 0px 3px 3px 2px rgba(0,0,0,0.10); box-shadow: 0px 3px3px 2px rgba(0,0,0,0.16);  overflow: hidden;\">
            <img style=\"width:100%; height: 180px;\" src=\"https://cdn.gestionpolitica.com/images/logo.png\">
                <center><p style=\"text-align: center; font-size: 14px; color: #636A76;\"> Hola ".$nombre."  <br> A continuación verá la Clave Temporal<br> para ingresar a Gestión Política</p></center>
                <center><p style= \"padding: 10px 0px 0px 0px;text-align: center; font-size: 16px; color: #636A76; font-weight: bold;\">Clave Temporal</p></center>
                <div style=\"padding: 0px 20%;\" class=\"password\">
                    <p style=\"text-align: left; font-size: 14px; color: #448AFC;\">".$clave."</p>
                </div>
                <center><p style= \"padding: 10px 0px 20px 0px; text-align: center; font-size: 16px; color: #636A76; font-weight: bold;\">Por favor, ingrese desde aquí</p></center>
                <center><a href=\" ".Variables::$urlIngreso." \" style=\"padding: 10px 44px; border-radius: 20px; background-color: #448AFC; font-size: 14px; color: white; text-decoration: none;\">INGRESAR</a></center>
                <br><br>
            </div>
            </section>";

            // enviar correo
            $mail   = new Mail2();
            $res1[0]['info_correo'] = $mail->enviar_mail($email1, $asunto, $cuerpo);

        }

        foreach ($celular as $key => $value) {

            $celular1 = $value;

            // enviar sms
            $sms    = new Sms();
            $res1[0]['info_sms'] = $sms->enviar_sms($celular1, "Hola Su clave: " .$clave );
            // $res1[0]['info_sms'] = $sms->enviar_sms($celular1, "Hola ".$nombre." Su clave temporal es: " .$clave. " Por favor ingrese aqui ". Variables::$urlIngreso );

        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $cedula );
        $response->getBody()->write((string)json_encode($respuesta) );
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

	}

}

?>
