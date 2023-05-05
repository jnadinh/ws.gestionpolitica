<?php
namespace App\Api\Esquemas;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Esquemas {

    private $id_usuario;
    private $usuario;
    private $esquema_db;
    private $id_rol;
    private $tipo_casino;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_rol = $_SESSION['id_rol'];
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> usuario    = $_SESSION['usuario'];
        $this -> tipo_casino= $_SESSION['tipo_casino'];
    }

    public function obtenerEsquemas(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql = "SELECT s.oid AS id, s.nspname AS nombre, u.usename
        FROM pg_catalog.pg_namespace s
        JOIN pg_catalog.pg_user u ON u.usesysid = s.nspowner
        WHERE nspname NOT IN ('information_schema', 'pg_catalog', 'public')
        AND nspname NOT LIKE 'pg_toast%' AND nspname NOT LIKE 'pg_temp%'";
        $res = $this->conector->select($sql);
        // die($sql);
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
        $municipios_id          = $json['municipios_id'];
        $telefono_administrador = $json['telefono_administrador'];
        $correo_administrador   = $json['correo_administrador'];
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
        '$telefono_administrador', '$correo_administrador', '$direccion_administrador', '$genero_administrador')";

        $res = $this->conector->select($sql);
        // var_dump($res);die($sql);
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
        if( !isset($json['id'])     || $json['id']==""      ||
            !isset($json['nombre']) || $json['nombre']==""  ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $id     = $json['id'];
        $nombre = $json['nombre'];

        // valida si hay un esquema con el nuevo nombre
        $sql0="select nspname as nombre FROM pg_catalog.pg_namespace where nspname = '$nombre';";
        $nom_esquema = $this->conector->select($sql0);
        $res0 = $this->conector->select($sql0);
        // var_dump($res0);
        if($res0) {
            // si trae datos retorna codigo 2
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR REGISTRO DUPLICADO', 'DATOS' => "NO PUEDEN HABER 2 ESQUEMAS CON EL MISMO NOMBRE");
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // obtiene el nombre del esquema
        $sql1="select nspname as nombre FROM pg_catalog.pg_namespace where oid = $id";
        $nom_esquema = $this->conector->select($sql1);

        $sql = "ALTER SCHEMA ".$nom_esquema[0]["nombre"]." RENAME TO $nombre ;";
        $sql=reemplazar_vacios($sql);
        // var_dump($nom_esquema[0]["nombre"]);
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

    public function obtenerPagosEsquemas(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql = "SELECT id, fecha, valor, esquema, obs
        FROM public.pagos ORDER BY fecha DESC";
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

    public function crearPagoEsquema(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['fecha'])      || $json['fecha']==""   ||
            !isset($json['valor'])      || $json['valor']==""   ||
            !isset($json['esquema'])    || $json['esquema']=="" ||
            !isset($json['obs'])        || $json['obs']==""     ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // crea el registro
        $sql="INSERT INTO public.pagos (fecha, valor, esquema, obs)
        VALUES(
        '".$json['fecha']."',
        '".$json['valor']."',
        '".$json['esquema']."',
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

    public function editarPagoEsquema(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if(  !isset($json['id'])  || $json['id']==""              ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $id = $json['id'];

        //para validar los datos que edita
        $array_editar = array(
            'valor'=>'',
            'fecha'=>'',
            'esquema'=>'',
            'obs'=>'',
        );

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE public.pagos SET $cadena WHERE id='$id'  ;";
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

    public function eliminarPagoEsquema(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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
        $sql = "DELETE FROM public.pagos WHERE id = $id";
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
