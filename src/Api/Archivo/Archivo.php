<?php
namespace App\Api\Archivo;

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


final class Archivo
{

    private $id_usuario;
    private $usuario;
    private $esquema_db;
    private $tipo_casino;
    
    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> esquema_db = $_SESSION['esquema_db'];
        $this -> id_usuario = $_SESSION['id_usuario'];
        $this -> usuario    = $_SESSION['usuario'];        
        $this -> tipo_casino= $_SESSION['tipo_casino'];        
    }

    public function uploadFile(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();
        $files = $request->getUploadedFiles();

        // Valida datos completos
        if( !isset($files['fichero']) ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $fichero = $files['fichero'];

        // hace la consulta
        $uploadedFiles = $request->getUploadedFiles();

         $directory=Variables::$urlArchivos;

        // handle single input with single file upload
        $uploadedFile = $uploadedFiles['fichero'];
        // die("fichero: ".$uploadedFile. " Url Archvivos: ".$directory);
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
            // Construye la respuesta HTTP
            $response->getBody()->write($filename.",".$fichero->getClientFilename() );
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR AL CARGAR ARCHIVO', 'DATOS' => $uploadedFile->getError() );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

    function moveUploadedFile(string $directory, UploadedFileInterface $uploadedFile) 
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

        // see http://php.net/manual/en/function.random-bytes.php
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . $filename);

        return $filename;
    }


    public function descargaArchivo(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = array_merge($args, (array)$request->getParsedBody());

        // Valida datos completos
        if( !isset($json['token'])      || $json['token']==""   ||
            !isset($json['nombre'])     || $json['nombre']==""  ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $token = $json['token'];

        // valida el usuario y token
        $sql = "SELECT u.usuario, u.id, u.esquema_db, u.tipo 
        FROM public.users u
        INNER JOIN public.token t ON t.users_id = u.id AND
        t.token ='$token' AND (t.updated_at + interval '".Variables::$tiempoSESION."minutes') > now() ;";
        // die($sql);
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
                
        if (!$res) {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'SESION INACTIVA' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // actualiza fecha updated_at para tiempo de session
        $sqltok = "UPDATE public.token SET updated_at=NOW() WHERE token = '$token' " ;
        $sqltok=reemplazar_vacios($sqltok);
        $restok=$this->conector->update($sqltok);  

        $esquema_db=$res[0]['esquema_db'];

        $archivo = isset($json['nombre'])?$json['nombre']:null;
        $arch =  Variables::$urlArchivos.$archivo;

        $file = file_exists($arch);
        //Valida si existe el archivo
        if(!$file){
            $response->getBody()->write("ERROR, NO EXISTE EL ARCHIVO");
            return $response;
        } else {

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($arch));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($arch));
            ob_clean();
            flush();
            readfile($arch);
        }
    }

    public function downloadArchivo(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {

        // Recopilar datos de la solicitud HTTP
        $json = array_merge($args, (array)$request->getParsedBody());

        // Valida datos completos
        if( !isset($json['token'])      || $json['token']==""       ||
            !isset($json['tabla'])      || $json['tabla']==""       ||
            !isset($json['id_archivo']) || $json['id_archivo']==""  ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }


        //Valida token 

        $token = $json['token'];

        // valida el usuario y token
        $sql = "SELECT u.usuario, u.id, u.esquema_db, u.tipo 
        FROM public.users u
        INNER JOIN public.token t ON t.users_id = u.id AND
        t.token ='$token' AND (t.updated_at + interval '".Variables::$tiempoSESION."minutes') > now() ;";
        // die($sql);
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
                
        if (!$res) {
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'SESION INACTIVA' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // actualiza fecha updated_at para tiempo de session
        $sqltok = "UPDATE public.token SET updated_at=NOW() WHERE token = '$token' " ;
        $sqltok=reemplazar_vacios($sqltok);
        $restok=$this->conector->update($sqltok);  

        $esquema_db=$res[0]['esquema_db'];

        $id_archivo = isset($json['id_archivo'])?$json['id_archivo']:null;
        //$tabla      = $json['tabla']==1?$esquema_db.'.tab_archivos ': 'public.archivos ';   // si tabla=1 esquemas sino(2) generales

        $tabla="";
        if ($json['tabla']==1) {
            $tabla=$esquema_db.'.tab_archivos ';
        }elseif (($json['tabla']==2)) {
            $tabla='public.archivos ';
        }elseif (($json['tabla']==3)) {
            $tabla=$esquema_db.'.tab_pedidos ';
        }elseif (($json['tabla']==4)) {
            $tabla=$esquema_db.'.tab_saldo_certificado ';
        }

        // hace la consulta

        // busca el archivo
        $sql = "SELECT nombre_db FROM $tabla WHERE id ='$id_archivo' ;";
        $res = $this->conector->select($sql);
        // var_dump($sql); die;
        if((!$res)) {
            $response->getBody()->write("ERROR, NO EXISTE EL ARCHIVO EN BD");
            return $response;
        } else {
    
            $archivo = $res[0]['nombre_db']!=null?$res[0]['nombre_db']:null;
            $arch =  Variables::$urlArchivos.$archivo;
            $file = file_exists($arch);
            //Valida si existe el archivo
            if(!$file){
                $response->getBody()->write("ERROR, NO EXISTE EL ARCHIVO EN DISCO");
                return $response;
            } else {

                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.basename($arch));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($arch));
                ob_clean();
                flush();
                readfile($arch);
            }
        }

    }

    public function crearArchivo(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if( !isset($json['nombre'])     || $json['nombre']==""      ||
            !isset($json['nombre_db'])  || $json['nombre_db']==""   ||
            !isset($json['tipo'])       || $json['tipo']==""         ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }        
        
        $ruta=Variables::$urlArchivos.$json['nombre_db'];

        // hace la consulta  
        $sql = "INSERT INTO $this->esquema_db.tab_archivos
        (planes_id, nombre, nombre_db, ruta, obs, tipo)
        VALUES(
            '".$json['planes_id']."', 
            '".$json['nombre']."', 
            '".$json['nombre_db']."', 
            '".$ruta."', 
            '".$json['obs']."', 
            '".$json['tipo']."'  ) RETURNING id;" ;
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

    public function editarArchivo(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if(  !isset($json['id'])  || $json['id']==""              ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }
  
        $id = $json['id'];

        // desde la aplicacion de excel no edita nombre_db, no permite cargar un nuevo archivo, solo edita obs y nombre

        //para validar los datos que edita
        $array_editar = array(
            'planes_id'=>'',
            'nombre'=>'',
            'nombre_db'=>'',
            'ruta'=>'',
            'obs'=>'',
            'tipo'=>'',
        );

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);        
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE $this->esquema_db.tab_archivos SET $cadena WHERE id='$id'  ;";
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

    public function eliminarArchivo(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos                           
        if(  !isset($json['id'])  || $json['id']==""              ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);        
        }
  
        $id = $json['id'];

        $sql = "DELETE FROM $this->esquema_db.tab_archivos WHERE id='$id'  ;";
        $res = $this->conector->delete($sql);
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

    public function obtenerArchivos(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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

        $token =  $json['token'];
        $urld=Variables::$urlBase."archivo/descargar_archivo/";
        // hace la consulta  
        $sql = "SELECT id, planes_id, nombre, nombre_db, ruta, obs, fecha, tipo,
        '$urld' || '1/' || id || '/' || '$token'   AS descarga
        FROM $this->esquema_db.tab_archivos WHERE id > 0 AND ";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);

        //Campos que se excluyen
        $KEY_FILTRO_EXCLUYE = array("token");
        //Campos con otros nombres en la db
        $KEY_CAMPOS_DIF = array("archivos_id"=>"id");
        foreach ($json as $key => $value) {
            if(!in_array($key, $KEY_FILTRO_EXCLUYE)){
                // 
                if($KEY_CAMPOS_DIF[$key]){
                    // cambia los campos que tienen nombre diferente en la db
                    $sql.= $KEY_CAMPOS_DIF[$key]."='".$value."' AND ";
                }else{
                    $sql.= $key."='".$value."' AND ";
                }
            }
        }
        //Se elimina el ultimo AND      
        $sql = substr($sql, 0, -4);
        //completar sql
        $sql.=" ORDER BY fecha ";
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
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'PERIODO' => $periodo, 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);            
    }

    public function obtenerArchivosGenerales(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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

        $token =  $json['token'];
        $urld=Variables::$urlBase."archivo/descargar_archivo/";
        // hace la consulta  
        $sql = "SELECT id, nombre, nombre_db, ruta, obs, fecha, tipo,
        '$urld' || '2/' || id || '/' || '$token'   AS descarga
        FROM archivos WHERE id > 0 AND ";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);

        //Campos que se excluyen
        $KEY_FILTRO_EXCLUYE = array("token");
        //Campos con otros nombres en la db
        $KEY_CAMPOS_DIF = array("archivos_id"=>"id");
        foreach ($json as $key => $value) {
            if(!in_array($key, $KEY_FILTRO_EXCLUYE)){
                // 
                if($KEY_CAMPOS_DIF[$key]){
                    // cambia los campos que tienen nombre diferente en la db
                    $sql.= $KEY_CAMPOS_DIF[$key]."='".$value."' AND ";
                }else{
                    $sql.= $key."='".$value."' AND ";
                }
            }
        }
        //Se elimina el ultimo AND      
        $sql = substr($sql, 0, -4);
        //completar sql
        $sql.=" ORDER BY tipo, fecha DESC ";
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
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'PERIODO' => $periodo, 'DATOS' => $res);
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);            
    }
    
}

?>
