<?php
namespace App\Api\Publicacion;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

// require_once __DIR__ . '/../../../vendor/autoload.php';

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Factory\AppFactory;
use \Slim\Http\Stream;

use ConectorDBPostgres;
use Variables;


final class Publicacion
{

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

    public function crearPublicacion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if( !isset($json['asunto'])     || $json['asunto']==""       ||
            !isset($json['detalle'])    || $json['detalle']=="" ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }        
        
        // hace la consulta  
        $sql = "INSERT INTO public.publicaciones (asunto, detalle, users_id)
        VALUES(
            '".$json['asunto']."', 
            '".$json['detalle']."', 
            '".$this->id_usuario."'  ) RETURNING id;" ;
        $sql=reemplazar_vacios($sql);
        // die($sql);
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

    public function editarPublicacion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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
            'asunto'=>'',
            'detalle'=>'',
            'eliminado'=>'',
        );

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);        
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE public.publicaciones SET $cadena WHERE id='$id'  ;";
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

    public function obtenerPublicaciones(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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

        // hace la consulta  
        $sql = "SELECT p.id, p.asunto, p.detalle, p.users_id, p.fecha,
        u.usuario AS users_nombre
        FROM publicaciones p 
        INNER JOIN users u ON u.id=p.users_id
        WHERE p.eliminado = false AND ";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);

        //Campos que se excluyen
        $KEY_FILTRO_EXCLUYE = array("token");
        //Campos con otros nombres en la db
        $KEY_CAMPOS_DIF = array("publicaciones_id"=>"id");
        foreach ($json as $key => $value) {
            if(!in_array($key, $KEY_FILTRO_EXCLUYE)){
                // 
                if($KEY_CAMPOS_DIF[$key]){
                    // cambia los campos que tienen nombre diferente en la db
                    $sql.='p.'.$KEY_CAMPOS_DIF[$key]."='".$value."' AND ";
                }else{
                    $sql.='p.'.$key."='".$value."' AND ";
                }
            }
        }
        //Se elimina el ultimo AND      
        $sql = substr($sql, 0, -4);
        //completar sql
        $sql.=" ORDER BY fecha DESC LIMIT 50 ";
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
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'PERIODO' => $periodo, 'ID_USUARIO' => $this -> id_usuario, 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);            
    }
    
}

?>
