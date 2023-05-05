<?php
namespace App\Api\Parametros;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;

class Parametros {

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

    public function obtenerParametros(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();


        // valida el periodo activo
        $sqlpa="SELECT id, nombre, fecha_desde, fecha_hasta FROM $this->esquema_db.tab_periodos WHERE activo=true;";
        $respa = $this->conector->select($sqlpa);      

        if($respa==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }elseif(!$respa){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'NO HAY PERIODO ACTIVO', 'DATOS' => 'DEBE HABILITAR UN PERIODO');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }

        $periodo = $respa[0]['nombre'];
        $periodos_id = $respa[0]['id'];
        $fecha_desde = $respa[0]['fecha_desde'];
        $fecha_hasta = $respa[0]['fecha_hasta'];    


        $fecha = isset($json['fecha'])?$json['fecha']:$fecha_hasta;

                               
        // si es tienda tiene dos campos mas
        if ($this->tipo_casino == 2) {
            $tienda = "nombre_panadero, firma_panadero, ";
        }else {
            $tienda="";
        }


        // hace la consulta  
        $sql="SELECT id, nombre_casino, direccion_casino, ciudad, tel_casino, cel_casino, 
        nombre_unidad, nombre_corto_unidad, banco, cuenta_banco, ganancia, redondeo,
        (SELECT nombre FROM $this->esquema_db.tab_firmas WHERE tipo = 6 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS nombre_presidente,
        (SELECT firma FROM $this->esquema_db.tab_firmas WHERE tipo = 6 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS firma_presidente,
        (SELECT nombre FROM $this->esquema_db.tab_firmas WHERE tipo = 5 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS nombre_cdte_unidad,
        (SELECT firma FROM $this->esquema_db.tab_firmas WHERE tipo = 5 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS firma_cdte_unidad,
        (SELECT nombre FROM $this->esquema_db.tab_firmas WHERE tipo = 4 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS nombre_ejecutivo,
        (SELECT firma FROM $this->esquema_db.tab_firmas WHERE tipo = 4 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS firma_ejecutivo,
        (SELECT nombre FROM $this->esquema_db.tab_firmas WHERE tipo = 3 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS nombre_s4,
        (SELECT firma FROM $this->esquema_db.tab_firmas WHERE tipo = 3 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS firma_s4,
        (SELECT nombre FROM $this->esquema_db.tab_firmas WHERE tipo = 2 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS nombre_administrador,
        (SELECT firma FROM $this->esquema_db.tab_firmas WHERE tipo = 2 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS firma_administrador,
        (SELECT nombre FROM $this->esquema_db.tab_firmas WHERE tipo = 1 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS nombre_panadero,
        (SELECT firma FROM $this->esquema_db.tab_firmas WHERE tipo = 1 AND fecha_desde <= '$fecha' AND 
        (fecha_hasta IS NULL OR '$fecha' BETWEEN fecha_desde and fecha_hasta) ORDER BY fecha_desde limit 1) AS firma_panadero
        FROM $this->esquema_db.tab_parametros WHERE id=1";
        // die($sql);
        $res = $this->conector->select($sql);


        // // hace la consulta  
        // $sql="SELECT id, nombre_casino, direccion_casino, ciudad, tel_casino, cel_casino, 
        // nombre_administrador, firma_administrador, nombre_s4, firma_s4, nombre_ejecutivo, 
        // firma_ejecutivo, nombre_cdte_unidad, firma_cdte_unidad, nombre_unidad, $tienda  
        // nombre_corto_unidad, banco, cuenta_banco 
        // FROM $this->esquema_db.tab_parametros WHERE id=1";
        // // die($sql);
        // $res = $this->conector->select($sql);
      
        if(!$res){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'CONSULTA VACIA', 'DATOS' => 'LA CONSULTA NO DEVOLVIÓ DATOS');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }elseif($res==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'PERIODO' => $periodo, 'DATOS' => $res[0]);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);            
    }

    public function editarParametros(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();
  
        $id = $json['id'];

        //para validar los datos que edita
        $array_editar = array(
            'nombre_casino'=>'',
            'direccion_casino'=>'',
            'ciudad'=>'',
            'tel_casino'=>'',
            'cel_casino'=>'',
            'nombre_administrador'=>'',
            'firma_administrador'=>'',
            'nombre_s4'=>'',
            'firma_s4'=>'',
            'nombre_ejecutivo'=>'',
            'firma_ejecutivo'=>'',
            'nombre_cdte_unidad'=>'',
            'firma_cdte_unidad'=>'',
            'nombre_unidad'=>'',
            'nombre_corto_unidad'=>'',
            'banco'=>'',
            'cuenta_banco'=>'',
            'ganancia'=>'',
            'redondeo'=>'',
        );

        // si es tienda tiene dos campos mas
        if ($this->tipo_casino == 2) {
            $array_editar += [ 
                'nombre_panadero'=>'',
                'firma_panadero'=>''
            ];
        }        

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);        
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE $this->esquema_db.tab_parametros SET $cadena WHERE id='$id'  ;";
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


    public function crearFirma(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if( !isset($json['nombre'])     || $json['nombre']==""      ||
            !isset($json['firma'])      || $json['firma']==""       ||
            !isset($json['fecha_desde'])|| $json['fecha_desde']=="" ||
            !isset($json['tipo'])       || $json['tipo']==""        ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }        

        $json['fecha_hasta'] = isset($json['fecha_hasta'])?$json['fecha_hasta']:null;
        
        // hace la consulta  
        $sql = "INSERT INTO $this->esquema_db.tab_firmas
        (nombre, firma, fecha_desde, fecha_hasta, tipo)
        VALUES('".$json['nombre']."', '".$json['firma']."' , '".$json['fecha_desde']."' , '".$json['fecha_hasta']."' , '".$json['tipo']."'  ) RETURNING id;" ;
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

    public function editarFirma(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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
            'firma'=>'',
            'fecha_desde'=>'',
            'fecha_hasta'=>'',
            'tipo'=>'',
            'eliminado'=>'',
        );

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);        
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE $this->esquema_db.tab_firmas SET $cadena WHERE id='$id'  ;";
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

    public function eliminarFirma(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if(  !isset($json['id'])  || $json['id']==""              ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }
  
        $id = $json['id'];

        $sql = "UPDATE $this->esquema_db.tab_firmas SET eliminado='t' WHERE id='$id'  ;";
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

    public function obtenerFirmas(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta  
        $sql = "SELECT id, nombre, firma, fecha_desde, fecha_hasta, tipo
        FROM $this->esquema_db.tab_firmas WHERE eliminado='f' ORDER BY fecha_hasta DESC, tipo";
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
    
}