<?php
namespace App\Api\Reunion;

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../../componentes/general/general.php';
require_once __DIR__ . '/../../conf/configuracion.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ConectorDBPostgres;
use Variables;


class Reunion {

    private $id_usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> esquema_db = $_SESSION['esquema_db'];
        $this -> id_usuario = $_SESSION['id_usuario'];
    }

    public function obtenerReuniones(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $where = "";
        if (isset($json['id']) && $json['id']!="") {
            $where = " AND id = ".$json['id'];
        }

        // hace la consulta
        $sql ="SELECT r.id, r.nombre, r.salones_id, r.obs, r.estados_reuniones_id, r.fecha_hora, duracion_horas,
        r.fecha_crea, r.fecha_actualiza, r.crea_personas_id, r.actualiza_personas_id,
        s.nombre AS salones_nombre
        FROM $this->esquema_db.tab_reuniones r
        INNER JOIN $this->esquema_db.tab_salones s ON s.id=r.salones_id
        WHERE estados_reuniones_id <> 9 $where ORDER BY r.nombre";
        $res = $this->conector->select($sql);
        // var_dump($_SESSION); die($sql);
        // WHERE lider_personas_id= $this->id_usuario $where
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

    public function crearReunion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['nombre'])     || $json['nombre']==""      ||
            !isset($json['salones_id']) || $json['salones_id']==""  ||
            !isset($json['obs'])        || $json['obs']==""         ||
            !isset($json['fecha_hora']) || $json['fecha_hora']==""  ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida que el salon esté libre  ajustar la consulta que tome todo el tiempo de la nueva reunion
        $sqlced ="SELECT id, nombre, salones_id, fecha_hora
        FROM $this->esquema_db.tab_reuniones WHERE salones_id = '".$json['salones_id']."'
        AND fecha_hora BETWEEN '".$json['fecha_hora']."'::TIMESTAMP
        AND '".$json['fecha_hora']."'::TIMESTAMP + (duracion_horas || ' hr')::INTERVAL ";
        $resced = $this->conector->select($sqlced);
        // die($sqlced);

        if($resced){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'REGISTRO DUPLICADO', 'DATOS' => 'YA HAY UNA REUNION PROGRAMADA EN ESTE SALON Y FECHA' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }elseif($resced==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR EN LA CONSULTA', 'DATOS' => 'ERROR EN LA CONSULTA cedula');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // hace la consulta
        $sql="INSERT INTO $this->esquema_db.tab_reuniones
        (nombre, salones_id, obs, estados_reuniones_id, fecha_hora, crea_personas_id)
        VALUES(
        '".$json['nombre']."',
        '".$json['salones_id']."',
        '".$json['obs']."', 1,
        '".$json['fecha_hora']."',
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

    public function editarReunion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();
        // Valida datos completos
        if(  !isset($json['id'])  || $json['id']==""   ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $id = $json['id'];

        //para validar los datos que edita
        $array_editar = array(
            'nombre'=>'',
            'salones_id'=>'',
            'obs'=>'',
            'estados_reuniones_id'=>'',
            'fecha_hora'=>'',
            'duracion_horas'=>'',
        );

        $actualiza="actualiza_personas_id = $this->id_usuario, fecha_actualiza=now(), ";

        $json_editar = array_intersect_key($json, $array_editar);
        $cadena=cadena_editar($json_editar);
        if($cadena=="" || $cadena=="'") {
            $cadena = "id='$id' ";
        }
        $sql = "UPDATE $this->esquema_db.tab_reuniones SET $actualiza $cadena WHERE id='$id'  ;";
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
