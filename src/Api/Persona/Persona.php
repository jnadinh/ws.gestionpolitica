<?php
namespace App\Api\Persona;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Persona {

    private $id_usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> esquema_db = $_SESSION['esquema_db'];
        $this -> id_usuario = $_SESSION['id_usuario'];
    }

    public function obtenerPersonas(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // hace la consulta
        $sql ="SELECT id, nombre, apellidos, cedula, clave, celular, telefono, email, fecha_nac,
        direccion, genero, rh, obs, lider_personas_id, estados_personas_id, es_usuario, fecha_crea,
        fecha_actualiza, crea_personas_id, actualiza_personas_id
        FROM $this->esquema_db.tab_personas
        WHERE estados_personas_id <> 9  AND ";

        //Campos que se excluyen
        $KEY_FILTRO_EXCLUYE = array("token", "esquema_db");
        //Campos con otros nombres en la db
        $KEY_CAMPOS_DIF = array("personas_id"=>"id");
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
        $sql.=" ORDER BY nombre ";
        $sql=reemplazar_vacios($sql);
        // die($sql);
        $res = $this->conector->select($sql);

        if(!$res){
            $respuesta = array('CODIGO' => 6, 'MENSAJE' => 'Consulta vacía. La consulta no devolvió datos', 'DATOS' => 'LA CONSULTA NO DEVOLVIÓ DATOS');
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

    public function crearPersona(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['nombre'])     || $json['nombre']==""      ||
            !isset($json['apellidos'])  || $json['apellidos']==""   ||
            !isset($json['cedula'])     || $json['cedula']==""      ||
            !isset($json['celular'])    || $json['celular']==""     ||
            !isset($json['genero'])     || $json['genero']==""      ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. Faltan datos', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que la cedula no exista en la bbdd
        $sqlced ="SELECT id, nombre, apellidos, cedula, estados_personas_id
        FROM $this->esquema_db.tab_personas WHERE cedula = '".$json['cedula']."'" ;
        $resced = $this->conector->select($sqlced);
        // die($sqlced);

        if($resced){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Registro duplicado. Ya existe un registro con la cedula '.$json['cedula'], 'DATOS' => 'YA EXISTE UN REGISTRO CON LA CEDULA '.$json['cedula'] );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }elseif($resced==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta', 'DATOS' => 'ERROR EN LA CONSULTA cedula');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el correo no exista en la bbdd
        if(isset($json['email']) && $json['email']!="") {

            $sqlemail ="SELECT id, nombre, apellidos, cedula, estados_personas_id
            FROM $this->esquema_db.tab_personas WHERE email = '".$json['email']."'" ;
            $resemail = $this->conector->select($sqlemail);
            //die($sqlemail);

            if($resemail){
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Registro duplicado. Ya existe un registro con el correo '.$json['email'], 'DATOS' => 'YA EXISTE UN REGISTRO CON EL CORREO '.$json['email'] );
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }elseif($resced==2){
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta', 'DATOS' => 'ERROR EN LA CONSULTA cedula');
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }
        }

        // hace la consulta
        $sql="INSERT INTO $this->esquema_db.tab_personas
        (nombre, apellidos, cedula, celular, telefono, email, fecha_nac, direccion, genero, rh,
        es_usuario, lider_personas_id, obs, estados_personas_id, crea_personas_id, clave)
        VALUES(
        '".$json['nombre']."',
        '".$json['apellidos']."',
        '".$json['cedula']."',
        '".$json['celular']."',
        '".$json['telefono']."',
        '".$json['email']."',
        '".$json['fecha_nac']."',
        '".$json['direccion']."',
        '".$json['genero']."',
        '".$json['rh']."',
        '".$json['es_usuario']."',
        '".$json['lider_personas_id']."',
        '".$json['obs']."',1,
        '".$this->id_usuario."', MD5('".$json['clave']."')  ) RETURNING id;" ;
        $sql=reemplazar_vacios($sql);
        // die($sql);
        $res = $this->conector->insert($sql);
        $id=$_SESSION['id'];
        // var_dump($res, $sql);
        if(!$res) {
            // si no trae datos retorna codigo 2 no creo el registro
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error BBDD', 'DATOS' => 'NO SE CREO EL REGISTRO' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else {

            if(isset($json['roles'])) {

                // elimina los roles
                $sqldel = "DELETE FROM $this->esquema_db.tab_personas_roles WHERE personas_id = '$id' ;";
                $resdel = $this->conector->delete($sqldel);
                // die($sqldel);

                // crea los roles
                $creados=0;
                $no_creados=0;
                // hace la consulta
                foreach ($json['roles'] as $key => $value) {

                    $sql = "INSERT INTO $this->esquema_db.tab_personas_roles
                    (personas_id, roles_id, crea_personas_id)
                    VALUES($id, $value, $this->id_usuario) RETURNING roles_id;" ;
                    $sql=reemplazar_vacios($sql);
                    // die($sql);
                    $res = $this->conector->insert($sql);

                    if(!$res) {
                        // si no trae datos aagrega a respuesta no creados
                        $no_creados ++;
                    }else {
                        // si no trae datos aagrega a respuesta no creados
                        $creados ++;
                    }
                }
            }

            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS2' => "Det cread: ".$creados. ", No cread: ".$no_creados, 'DATOS' => $id );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

    public function editarPersona(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();
        // Valida datos completos
        if(  !isset($json['id'])  || $json['id']==""   ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. Faltan datos', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $id = $json['id'];

        //para validar los datos que edita
        $array_editar = array(
            'nombre'=>'',
            'apellidos'=>'',
            'celular'=>'',
            'telefono'=>'',
            'email'=>'',
            'fecha_nac'=>'',
            'direccion'=>'',
            'genero'=>'',
            'rh'=>'',
            'obs'=>'',
            'es_usuario'=>'',
            'estados_personas_id'=>'',
            'lider_personas_id'=>'',
        );

        $clave="";
        if(isset($json['clave'])) {
            $clave ="clave=MD5('".$json['clave']."'), ";
        }

        $actualiza="actualiza_personas_id = $this->id_usuario, fecha_actualiza=now(), ";

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE $this->esquema_db.tab_personas SET $actualiza $clave $cadena WHERE id='$id'  ;";
        $sql=reemplazar_vacios($sql);
        // die($sql);
        $res = $this->conector->update($sql);
        if(!$res) {
            // si no trae datos retorna codigo 2
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error BBDD', 'DATOS' => "NO SE ACTUALIZO EL REGISTRO");
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida si no era usuario y ahora si para enviar mensaje

        // valida si no era referido y ahora si para enviar mensaje

        if(isset($json['roles'])) {

            // elimina los roles
            $sqldel = "DELETE FROM $this->esquema_db.tab_personas_roles WHERE personas_id = '$id' ;";
            $resdel = $this->conector->delete($sqldel);
            // die($sqldel);

            // crea los roles
            $creados=0;
            $no_creados=0;
            // hace la consulta
            foreach ($json['roles'] as $key => $value) {

                $sql = "INSERT INTO $this->esquema_db.tab_personas_roles
                (personas_id, roles_id, crea_personas_id)
                VALUES($id, $value, $this->id_usuario) RETURNING roles_id;" ;
                $sql=reemplazar_vacios($sql);
                // die($sql);
                $res = $this->conector->insert($sql);

                if(!$res) {
                    // si no trae datos aagrega a respuesta no creados
                    $no_creados ++;
                }else {
                    // si no trae datos aagrega a respuesta no creados
                    $creados ++;
                }
            }
        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS2' => "Det cread: ".$creados. ", No cread: ".$no_creados, 'DATOS' => $id );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

}
