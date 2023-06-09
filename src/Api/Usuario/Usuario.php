<?php
namespace App\Api\Usuario;

 error_reporting(E_ALL);
 ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use App\Api\Correo\Mail2 as Mail2;
use App\Api\Sms\Sms as Sms;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;

class Usuario {

    private $id_usuario;
    private $usuario;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> usuario    = $_SESSION['usuario'];
    }

    public function obtenerUsuarios(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql = "SELECT id, nombre, telefono, celular, ciudad, estado, email, obs, usuario, clave
        FROM public.tab_usuarios WHERE eliminado = FALSE ORDER BY id";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);

        if(!$res){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'CONSULTA VACIA', 'DATOS' => 'LA CONSULTA NO DEVOLVIÓ DATOS');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }elseif($res==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function crearUsuario(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();


        // Valida datos completos
        if( !isset($json['nombre'])     || $json['nombre']==""    ||
            !isset($json['estado'])     || $json['estado']==""    ||
            !isset($json['email'])      || $json['email']==""     ||
            !isset($json['usuario'])    || $json['usuario']==""   ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        if (!isset($json['clave']) || $json['usuario']==""){
            $json['clave']=$json['clave'];
        }

        // hace la consulta
        $sql= "INSERT INTO public.tab_usuarios
        (nombre, telefono, celular, ciudad, estado, email, obs, usuario, clave)
        VALUES('".$json['nombre']."', '".$json['telefono']."', '".$json['celular']."', '".$json['ciudad']."',
        '".$json['estado']."', '".$json['email']."', '".$json['obs']."', '".$json['usuario']."',
        MD5('".$json['clave']."') ) RETURNING id";
        $sql=reemplazar_vacios($sql);
        //die($sql);
        $res = $this->conector->insert($sql);
        $id=$_SESSION['id'];
        if(!$res) {
            // si no trae datos retorna codigo 2 no creo el registro
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => 'NO SE CREO EL REGISTRO' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else {

            // como prueba porque va al crear lider
            if (isset($json['email']) || $json['email']!=""){
                // envia correo
                $asunto = "Creación de Usuario";
                $cuerpo="<section>
                <div class=\"cuadro\" style=\"background-color: white; max-width: 400px; border-radius: 5px 5px 5px 5px; -moz-border-radius: 5px 5px 5px 5px; -webkit-border-radius: 5px 5px 5px 5px; border: 0px solid #000000; margin: 0 auto; margin: 4% auto 0 auto; padding: 0px 0px 20px 0px; -webkit-box-shadow: 0px 3px 3px 2px rgba(0,0,0,0.16); -moz-box-shadow: 0px 3px 3px 2px rgba(0,0,0,0.10); box-shadow: 0px 3px3px 2px rgba(0,0,0,0.16);  overflow: hidden;\">
                <img style=\"width:100%; height: 180px;\" src=\"https://cdn.gestionpolitica.com/images/logo.png\">
                    <center><p style=\"text-align: center; font-size: 14px; color: #636A76;\">".$json['nombre'].", bienvenid@. <br> A continuación verá el Usuario y la Clave<br> para ingresar a Gestión Política</p></center>
                    <center><p style= \"padding: 10px 0px 0px 0px;text-align: center; font-size: 16px; color: #636A76; font-weight: bold;\">Datos de acceso</p></center>
                    <div style=\"padding: 0px 20%;\" class=\"user\">
                        <p style=\"text-align: left; font-size: 12px; color: #A0B0CB; height: 12px;\">Usuario</p>
                        <p style=\"text-align: left; font-size: 14px; color: #448AFC;\">".$json['usuario']."</p>
                    </div>
                    <div style=\"padding: 0px 20%;\" class=\"password\">
                        <p style=\"text-align: left; font-size: 12px; color: #A0B0CB; height: 12px;\">Clave</p>
                        <p style=\"text-align: left; font-size: 14px; color: #448AFC;\">".$json['clave']."</p>
                    </div>
                    <center><p style= \"padding: 10px 0px 20px 0px; text-align: center; font-size: 16px; color: #636A76; font-weight: bold;\">Por favor, ingrese desde aquí</p></center>
                    <center><a href=\" ".Variables::$urlIngreso." \" style=\"padding: 10px 44px; border-radius: 20px; background-color: #448AFC; font-size: 14px; color: white; text-decoration: none;\">INGRESAR</a></center>
                    <br><br>
                </div>
                </section>";

                // enviar correo
                $mail   = new Mail2();
                $res1[0]['info_correo'] = $mail->enviar_mail($json['email'], $asunto, $cuerpo);
            }

            // como prueba porque va al momento de crear lider o referido validando en parametro
            if (isset($json['celular']) || $json['celular']!=""){

                // enviar sms
                $sms    = new Sms();
                $res1[0]['info_sms'] = $sms->enviar_sms_mercadeo($json['celular'], "mensaje de prueba desde la aplicacion");
            }

            $res1[0]['id'] = $id;

            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $res1 );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

    public function editarUsuario(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();
        // Valida datos completos
        if(  !isset($json['id'])  || $json['id']==""   ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $id = $json['id'];

        //para validar los datos que edita
        $array_editar = array(
            'nombre'=>'',
            'telefono'=>'',
            'celular'=>'',
            'ciudad'=>'',
            'estado'=>'',
            'email'=>'',
            'obs'=>'',
            'usuario'=>'',
            'eliminado'=>'',
        );

        $clave="";
        if(isset($json['clave'])) {
            $clave ="clave=MD5('".$json['clave']."'), ";
        }

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE public.tab_usuarios SET $clave $cadena WHERE id='$id'  ;";
        $sql=reemplazar_vacios($sql);
        // die($sql);
        $res = $this->conector->update($sql);
        if(!$res) {
            // si no trae datos retorna codigo 2
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => "NO SE ACTUALIZO EL REGISTRO");
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $id );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

}
