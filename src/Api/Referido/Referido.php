<?php
namespace App\Api\Referido;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Referido {

    private $id_usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> esquema_db = $_SESSION['esquema_db'];
        $this -> id_usuario = $_SESSION['id_usuario'];
    }

    public function obtenerMisReferidos(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $where = "";
        if (isset($json['id']) && $json['id']!="") {
            $where = " AND id = ".$json['id'];
        }

        // hace la consulta
        $sql ="SELECT id, nombre, apellidos, cedula, clave, celular, telefono, email, fecha_nac,
        direccion, genero, rh, es_lider, obs, lider_personas_id, estados_personas_id, fecha_crea,
        fecha_actualiza, crea_personas_id, actualiza_personas_id
        FROM $this->esquema_db.tab_personas WHERE lider_personas_id= $this->id_usuario $where";
        $res = $this->conector->select($sql);
        //var_dump($_SESSION); die($sql);

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

    public function crearMiReferido(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['nombre'])     || $json['nombre']==""      ||
            !isset($json['apellidos'])  || $json['apellidos']==""   ||
            !isset($json['cedula'])     || $json['cedula']==""      ||
            !isset($json['celular'])    || $json['celular']==""     ||
            !isset($json['genero'])     || $json['genero']==""      ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que la cedula no exista en la bbdd
        $sqlced ="SELECT id, nombre, apellidos, cedula, estados_personas_id
        FROM $this->esquema_db.tab_personas WHERE cedula = '".$json['cedula']."'" ;
        $resced = $this->conector->select($sqlced);
        // die($sqlced);

        if($resced){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'REGISTRO DUPLICADO', 'DATOS' => 'YA EXISTE UN REGISTRO CON LA CEDULA '.$json['cedula'] );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }elseif($resced==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA cedula');
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
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'REGISTRO DUPLICADO', 'DATOS' => 'YA EXISTE UN REGISTRO CON EL CORREO '.$json['email'] );
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }elseif($resced==2){
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA cedula');
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }
        }

        // hace la consulta
        $sql="INSERT INTO $this->esquema_db.tab_personas
        (nombre, apellidos, cedula, celular, telefono, email, fecha_nac, direccion, genero, rh, obs,
        lider_personas_id, estados_personas_id, crea_personas_id)
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
        '".$json['obs']."',
        '".$this->id_usuario."',2,
        '".$this->id_usuario."'  ) RETURNING id;" ;
        $sql=reemplazar_vacios($sql);
        //die($sql);
        $res = $this->conector->insert($sql);
        $id=$_SESSION['id'];
        // var_dump($res, $sql);
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

}
