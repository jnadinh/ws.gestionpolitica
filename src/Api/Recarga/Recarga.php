<?php
namespace App\Api\Recarga;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Recarga {

    private $id_usuario;
    private $usuario;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> usuario    = $_SESSION['usuario'];
    }

    public function obtenerRecargas(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $where = "";
        if (isset($json['nombre_esquema']) && $json['nombre_esquema']!="") {
            $where = " WHERE nombre esquema = ".$json['nombre_esquema'] ;
        }

        // hace la consulta
        $sql = "SELECT r.id, r.nombre_esquema, r.valor, r.fecha_crea, r.fecha_actualiza, r.crea_usuarios_id, r.actualiza_usuarios_id, r.obs,
        uc.nombre as crea_usuaarios_nombre, ua.nombre as actualiza_usuaarios_nombre
        FROM public.tab_recargas r
        LEFT JOIN public.tab_usuarios uc ON r.crea_usuarios_id = uc.id
        LEFT JOIN public.tab_usuarios ua ON r.actualiza_usuarios_id = ua.id
        $where ORDER BY r.fecha_crea DESC";
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

    public function crearRecarga(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['valor'])          || $json['valor']==""           ||
            !isset($json['nombre_esquema']) || $json['nombre_esquema']==""  ||
            !isset($json['obs'])            || $json['obs']==""             ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // crea el registro
        $sql="INSERT INTO public.tab_recargas (nombre_esquema, valor, crea_usuarios_id, obs)
        VALUES(
        '".$json['nombre_esquema']."',
        '".$json['valor']."',
        '".$this->id_usuario."',
        '".$json['obs']."' ) RETURNING id;" ;

        $res = $this->conector->insert($sql);
        $id=$_SESSION['id'];
        if(!$res) {
            // si no trae datos retorna codigo 2 no creo el registro
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => 'NO SE CREO EL REGISTRO' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else {
            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $id );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

    }

    public function editarRecarga(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if(  !isset($json['id']) || $json['id']==""){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $id = $json['id'];

        //para validar los datos que edita
        $array_editar = array(
            'valor'=>'',
            'nombre_esquema'=>'',
            'obs'=>'',
        );

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE public.tab_recargas SET $cadena, actualiza_usuarios_id = $this->id_usuario
        WHERE id='$id' ;";
        $sql=reemplazar_vacios($sql);
        //die($sql);
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

    public function eliminarRecarga(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if(  !isset($json['id'])  || $json['id']=="" ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $id = $json['id'];

        // elimina todos los registros del periodo
        $sql = "DELETE FROM public.tab_recargas WHERE id = $id";
        $res = $this->conector->delete($sql);
        //die($sql);

        if(!$res) {
            // si no trae datos retorna codigo 2
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => "NO SE ELIMINO EL REGISTRO");
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $id );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }


}
