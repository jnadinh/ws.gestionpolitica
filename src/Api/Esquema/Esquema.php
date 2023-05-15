<?php
namespace App\Api\Esquema;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Esquema {

    private $id_usuario;
    private $usuario;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> usuario    = $_SESSION['usuario'];
    }

    public function obtenerEsquemas(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql = "SELECT s.oid AS id, s.nspname AS nombre_esquema, u.usename
        FROM pg_catalog.pg_namespace s
        JOIN pg_catalog.pg_user u ON u.usesysid = s.nspowner
        WHERE nspname NOT IN ('information_schema', 'pg_catalog', 'public')
        AND nspname NOT LIKE 'pg_toast%' AND nspname NOT LIKE 'pg_temp%'";
        $res = $this->conector->select($sql);
        //var_dump($res); die($sql);
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

    public function crearEsquema(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['nombre_esquema'])         || $json['nombre_esquema']==""          ||
            !isset($json['nombre_candidato'])       || $json['nombre_candidato']==""        ||
            !isset($json['corporaciones_id'])       || $json['corporaciones_id']==""        ||
            !isset($json['departamentos_id'])       || $json['departamentos_id']==""        ||
            !isset($json['nombre_administrador'])   || $json['nombre_administrador']==""    ||
            !isset($json['apellidos_administrador'])|| $json['apellidos_administrador']=="" ||
            !isset($json['cedula_administrador'])   || $json['cedula_administrador']==""    ||
            !isset($json['celular_administrador'])  || $json['celular_administrador']==""   ||
            !isset($json['email_administrador'])    || $json['email_administrador']==""     ||
            !isset($json['genero_administrador'])   || $json['genero_administrador']==""    ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $nombre_esquema         = $json['nombre_esquema'];
        $nombre_candidato       = $json['nombre_candidato'];
        $corporaciones_id       = $json['corporaciones_id'];
        $departamentos_id       = $json['departamentos_id'];
        $nombre_administrador   = $json['nombre_administrador'];
        $apellidos_administrador= $json['apellidos_administrador'];
        $cedula_administrador   = $json['cedula_administrador'];
        $celular_administrador  = $json['celular_administrador'];
        $genero_administrador   = $json['genero_administrador'];
        $municipios_id          = isset($json['municipios_id']) && $json['municipios_id']!=""? "'".$json["municipios_id"]."'":0 ;
        $telefono_administrador = $json['telefono_administrador'];
        $email_administrador    = $json['email_administrador'];
        $direccion_administrador= $json['direccion_administrador'];

        // valida si hay un esquema con el nuevo nombre
        $sql0="select nspname as nombre FROM pg_catalog.pg_namespace where nspname = '$nombre_esquema';";
        $nom_esquema = $this->conector->select($sql0);
        $res0 = $this->conector->select($sql0);
        // var_dump($res0);
        if($res0) {
            // si trae datos retorna codigo 2
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR REGISTRO DUPLICADO', 'DATOS' => "NO PUEDEN HABER 2 ESQUEMAS CON EL MISMO NOMBRE");
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // crea el esquema con la funcion
        $sql="SELECT public.fun_esquema('$nombre_esquema', '$nombre_candidato', $corporaciones_id, $departamentos_id,
        $municipios_id, '$nombre_administrador', '$apellidos_administrador', '$cedula_administrador', '$celular_administrador',
        '$telefono_administrador', '$email_administrador', '$direccion_administrador', '$genero_administrador')";
        // die($sql);
        $res = $this->conector->select($sql);

        if($res==2) {
            // si no trae datos retorna codigo 2 no creo el registro
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => 'NO SE CREO EL REGISTRO' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else {
            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $json['nombre_esquema'] );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

    public function editarEsquema(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['id'])             || $json['id']==""              ||
            !isset($json['nombre_esquema']) || $json['nombre_esquema']==""  ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $id     = $json['id'];
        $nombre = $json['nombre_esquema'];

        // valida si hay un esquema con el nuevo nombre
        $sql0="select nspname as nombre_esquema FROM pg_catalog.pg_namespace where nspname = '$nombre';";
        $nom_esquema = $this->conector->select($sql0);
        $res0 = $this->conector->select($sql0);
        // var_dump($res0); die($sql0);

        // obtiene el nombre del esquema
        $sql1="select nspname as nombre_esquema FROM pg_catalog.pg_namespace where oid = $id";
        $nom_esquema = $this->conector->select($sql1);

        if($res0) {

            if($nom_esquema[0]['nombre_esquema']==$json['nombre_esquema']) {
                //
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'TIENE EL MISMO NOMBRE', 'DATOS' => "NO HAY DATOS PARA EDITAR");
                $response->getBody()->write(json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }
            // si trae datos retorna codigo 2
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR REGISTRO DUPLICADO', 'DATOS' => "NO PUEDEN HABER 2 ESQUEMAS CON EL MISMO NOMBRE");
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $sql = "ALTER SCHEMA ".$nom_esquema[0]["nombre_esquema"]." RENAME TO $nombre ;";
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
