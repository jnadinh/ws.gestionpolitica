<?php
namespace App\Api\Listado;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Listado {

    private $id_usuario;
    private $usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> usuario    = $_SESSION['usuario'];
        $this -> esquema_db = $_SESSION['esquema_db'];
    }

    public function obtenerDepartamentos(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql = "SELECT id, nombre, codigo_dane FROM public.tab_departamentos ORDER BY nombre";
        $sql=reemplazar_vacios($sql);
        // die($sql);
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

    public function obtenerMunicipios(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $where = "";
        if (isset($json['departamentos_id']) && $json['departamentos_id']!="") {
            $where = " WHERE departamentos_id = ".$json['departamentos_id'] ;
        }

        // hace la consulta
        $sql = "SELECT m.id, m.nombre, m.departamentos_id, m.codigo_dane,
        d.nombre AS departamentos_nombre, d.codigo_dane AS codigo_dane_departamento
        FROM public.tab_municipios m
        INNER JOIN public.tab_departamentos d ON d.id = m.departamentos_id
        $where ORDER BY m.nombre";
        $sql=reemplazar_vacios($sql);
        // die($sql);
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

    public function obtenerCorporaciones(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql = "SELECT id, nombre, nivel FROM public.tab_corporaciones ORDER BY id";
        $sql=reemplazar_vacios($sql);
        // die($sql);
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

    public function obtenerPuestos(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $where = "";
        if (isset($json['departamentos_id'])&& $json['departamentos_id']!="") {
            $where = " WHERE p.departamentos_id = ".$json['departamentos_id'];
        }elseif(isset($json['municipios_id'])&& $json['municipios_id']!="") {
            $where = " WHERE p.municipios_id = ".$json['municipios_id'];
        }

        // hace la consulta
        $sql = "SELECT p.id, p.nombre, p.departamentos_id, p.municipios_id, p.direccion,
        d.nombre AS departamentos_nombre, m.nombre AS municipios_nombre
        FROM public.tab_puestos p
        INNER JOIN public.tab_departamentos d ON d.id = p.departamentos_id
        INNER JOIN public.tab_municipios m ON m.id = p.municipios_id
        $where ORDER BY p.nombre";
        $sql=reemplazar_vacios($sql);
        // die($sql);
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

    public function obtenerRoles(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql="";
        if (isset($json['personas_id']) && $json['personas_id']!="" ) {
            // si recibe id de la persona
            $sql = "SELECT r.id, r.nombre FROM  tab_roles r
            INNER JOIN $this->esquema_db.tab_personas_roles pr on pr.roles_id  = r.id
            INNER JOIN $this->esquema_db.tab_personas p on  p.id = pr.personas_id
            WHERE p.id = ".$json['personas_id'] ."";
        }else {
            $sql = "SELECT id, nombre FROM public.tab_roles ORDER BY id";
        }
        $sql=reemplazar_vacios($sql);
        // die($sql);
        $res = $this->conector->select($sql);

        if(!$res){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'Consulta vacía', 'DATOS' => 'LA CONSULTA NO DEVOLVIÓ DATOS');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }elseif($res==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function obtenerEstadosPersonas(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql = "SELECT id, nombre FROM public.tab_estados_personas ORDER BY id";
        $sql=reemplazar_vacios($sql);
        // die($sql);
        $res = $this->conector->select($sql);

        if(!$res){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'Consulta vacía', 'DATOS' => 'LA CONSULTA NO DEVOLVIÓ DATOS');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }elseif($res==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function obtenerModulos(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql="";
        if (isset($json['roles'])) {
            // si recibe un arrelo de roles
            // $roles = $json['roles'];
            $roles = implode(",", $json['roles']);
            $sql = "SELECT DISTINCT m.id, m.nombre FROM  tab_modulos m
            INNER JOIN tab_roles_modulos rm ON rm.modulos_id = m.id
            INNER JOIN tab_roles r ON  r.id = rm.roles_id
            WHERE r.id IN($roles) ORDER BY m.id";
        }else {
            $sql = "SELECT id, nombre FROM public.tab_modulos ORDER BY id";
        }
        $sql=reemplazar_vacios($sql);
        // var_dump($roles); die($sql);
        $res = $this->conector->select($sql);

        if(!$res){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'Consulta vacía', 'DATOS' => 'LA CONSULTA NO DEVOLVIÓ DATOS');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }elseif($res==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }


}
