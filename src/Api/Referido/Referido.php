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

    // no se esta usando
    public function obtenerReferidos(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        $where = "";
        if (isset($json['id']) && $json['id']!="") {
            $where = " AND id = ".$json['id'];
        }
        if (isset($json['lideres_personas_id']) && $json['lideres_personas_id']!="") {
            $where = " AND lideres_personas_id = ".$json['lideres_personas_id'];
        }
        $where = "";
        if (isset($json['cedula']) && $json['cedula']!="") {
            $where = " AND cedula = '".$json['cedula']."'";
        }

        // hace la consulta
        $sql ="SELECT id, nombre, apellidos, cedula, clave, celular, telefono, email, fecha_nac,
        direccion, genero, rh, es_usuario, obs, lider_personas_id, estados_personas_id, fecha_crea,
        fecha_actualiza, crea_personas_id, actualiza_personas_id
        FROM $this->esquema_db.tab_personas WHERE estados_personas_id <> 9  $where ORDER BY nombre";
        $res = $this->conector->select($sql);
        // var_dump($_SESSION);
        // die($sql);

        if(!$res){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Consulta vacía. La consulta no devolvió datos', 'DATOS' => 'LA CONSULTA NO DEVOLVIÓ DATOS');
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

    public function crearReferido(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['cedula']) || $json['cedula']==""  ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. Faltan datos', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida si la cedula ya está registrada en la BBDD
        $sqlced ="SELECT id, cedula, lider_personas_id
        FROM $this->esquema_db.tab_personas WHERE cedula = '".$json['cedula']."' ";
        // die($sqlced);
        $resced = $this->conector->select($sqlced);

        if($resced) {
            $personas_id        = $resced[0]['id'];
            $cedula             = $resced[0]['cedula'];
            $lider_personas_id  = $resced[0]['lider_personas_id'];
            // valida que la persona no sea referida de otro lider
            if(isset($lider_personas_id) && $lider_personas_id != $this->id_usuario){
                $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'La Persona con CC. '.$json['cedula']. ' ya es referida de otro Lider.', 'DATOS' => 'LA PERSONA YA ES REFERIDA'.$json['cedula'] );
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }elseif(isset($lider_personas_id) && $lider_personas_id == $this->id_usuario){
                $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'La Persona con CC. '.$json['cedula']. ' ya es su referida.', 'DATOS' => 'LA PERSONA YA ES SU REFERIDA '.$json['cedula'] );
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }else {
                // actualiza el registro
                $sqlup = "UPDATE $this->esquema_db.tab_personas SET lider_personas_id = $this->id_usuario,
                estados_personas_id = 1 WHERE id = $personas_id ;";
                // die($sqlup);
                $resup = $this->conector->update($sqlup);
                if(!$resup) {
                    // si no trae datos retorna codigo 2
                    $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => "NO SE ACTUALIZO EL REGISTRO");
                    $response->getBody()->write(json_encode($respuesta));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                }
            }

        }elseif(!$resced) {

            //crea el registro de personas
            // Valida datos completos
            if( !isset($json['nombre'])     || $json['nombre']==""      ||
                !isset($json['apellidos'])  || $json['apellidos']==""   ||
                !isset($json['celular'])    || $json['celular']==""     ||
                !isset($json['genero'])     || $json['genero']==""      ){

                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. Faltan datos', 'DATOS' => 'FALTAN DATOS' );
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
                    $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta.', 'DATOS' => 'ERROR EN LA CONSULTA cedula');
                    $response->getBody()->write((string)json_encode($respuesta));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
                }
            }

            // hace la consulta
            $sql="INSERT INTO $this->esquema_db.tab_personas
            (nombre, apellidos, cedula, celular, telefono, email, fecha_nac, direccion, genero, rh, obs,
            estados_personas_id, crea_personas_id, lider_personas_id)
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
            '".$json['obs']."', 1,
            '".$this->id_usuario."',
            '".$this->id_usuario."'  ) RETURNING id;" ;
            $sql=reemplazar_vacios($sql);
            // die($sql);
            $res = $this->conector->insert($sql);
            $personas_id=$_SESSION['id'];
            // var_dump($res, $sql);
            if(!$res) {
                // si no trae datos retorna codigo 2 no creo el registro
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta. No se creó el registro', 'DATOS' => 'NO SE CREO EL REGISTRO' );
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }

        }elseif($resced==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta.', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'Registro creado con éxito '.$personas_id, 'DATOS' => $personas_id );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);


    }

    public function editarReferido(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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
            'apellidos'=>'',
            'celular'=>'',
            'telefono'=>'',
            'email'=>'',
            'fecha_nac'=>'',
            'direccion'=>'',
            'genero'=>'',
            'rh'=>'',
            'obs'=>'',
            'estados_personas_id'=>'',
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
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'ERROR DB', 'DATOS' => "NO SE ACTUALIZO EL REGISTRO");
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $id );
        $response->getBody()->write((string)json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

}
