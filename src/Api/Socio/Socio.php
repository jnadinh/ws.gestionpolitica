<?php
namespace App\Api\Socio;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Socio {

    private $id_usuario;
    private $usuario;
    private $esquema_db;
    private $id_rol;
    private $tipo_casino;
    
    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> esquema_db = $_SESSION['esquema_db'];
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> usuario    = $_SESSION['usuario'];        
        $this -> tipo_casino= $_SESSION['tipo_casino'];        
    }

    public function obtenerSocios(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // valida el periodo activo
        $sqlpa="SELECT id, nombre, fecha_desde, fecha_hasta FROM $this->esquema_db.tab_periodos WHERE activo='true';";
        $respa = $this->conector->select($sqlpa);      
        if(!$respa){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'NO HAY PERIODO ACTIVO', 'DATOS' => 'DEBE HABILITAR UN PERIODO');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }elseif($respa==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }

        $periodo = $respa[0]['nombre'];
        $periodos_id = $respa[0]['id'];
        $fecha_desde = $respa[0]['fecha_desde'];
        $fecha_hasta = $respa[0]['fecha_hasta'];        
        
        // hace la consulta  
        $sql = "SELECT s.id, s.orden, s.cedula, s.gdo, s.nombre, s.unidades_id, s.obs, u.nombre AS unidades_nombre, f.valor AS vienen_fondo
        FROM $this->esquema_db.tab_socios s
        LEFT JOIN $this->esquema_db.tab_unidades u  ON u.id = s.unidades_id
        LEFT JOIN $this->esquema_db.tab_vienen_fondo f ON s.id = f.socios_id AND f.periodos_id = $periodos_id
        WHERE s.eliminado = false
        ORDER BY u.orden, s.orden, s.id ";
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
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'FECHA_DESDE' => $fecha_desde, 'FECHA_HASTA' => $fecha_hasta, 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);            
    }
 
    public function crearSocio(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if( !isset($json['nombre']) || $json['nombre']==""  ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }        
        
        // hace la consulta  
        $sql = "INSERT INTO $this->esquema_db.tab_socios (nombre, obs, orden, cedula, gdo, unidades_id)
        VALUES('".$json['nombre']."', '".$json['obs']."', '".$json['orden']."', 
        '".$json['cedula']."', '".$json['gdo']."', '".$json['unidades_id']."'  ) RETURNING id;" ;
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
            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $id );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }        
    }

    public function editarSocio(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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
            'nombre'=>'',
            'orden'=>'',
            'cedula'=>'',
            'gdo'=>'',
            'unidades_id'=>'',
            'obs'=>'',
        );

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);        
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE $this->esquema_db.tab_socios SET $cadena WHERE id='$id'  ;";
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

    public function eliminarSocio(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if(  !isset($json['id'])  || $json['id']==""              ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }
  
        $id = $json['id'];

        $sql = "UPDATE $this->esquema_db.tab_socios SET eliminado='t' WHERE id='$id'  ;";
        $res = $this->conector->update($sql);
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

    public function obtenerSociosEliminados(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // valida el periodo activo
        $sqlpa="SELECT id, nombre, fecha_desde, fecha_hasta FROM $this->esquema_db.tab_periodos WHERE activo='true';";
        $respa = $this->conector->select($sqlpa);      
        if(!$respa){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'NO HAY PERIODO ACTIVO', 'DATOS' => 'DEBE HABILITAR UN PERIODO');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }elseif($respa==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }

        $periodo = $respa[0]['nombre'];
        $periodos_id = $respa[0]['id'];
        $fecha_desde = $respa[0]['fecha_desde'];
        $fecha_hasta = $respa[0]['fecha_hasta'];        
        
        // hace la consulta  
        $sql = "SELECT s.id, s.orden, s.cedula, s.gdo, s.nombre, s.unidades_id, s.obs, u.nombre AS unidades_nombre
        FROM $this->esquema_db.tab_socios s
        LEFT JOIN $this->esquema_db.tab_unidades u  ON u.id = s.unidades_id
        WHERE s.eliminado = 'true'
        ORDER BY u.orden, s.orden, s.id ";
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
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'FECHA_DESDE' => $fecha_desde, 'FECHA_HASTA' => $fecha_hasta, 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);            
    }
 
    public function habilitarSocio(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if(  !isset($json['id'])  || $json['id']=="" ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }
  
        $id = $json['id'];

        $sql = "UPDATE $this->esquema_db.tab_socios SET eliminado='f' WHERE id='$id'  ;";
        $res = $this->conector->update($sql);
        if(!$res) {
            // si no trae datos retorna codigo 2 
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => "NO SE HABILITO EL REGISTRO");
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $id );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);            
    }

    
}