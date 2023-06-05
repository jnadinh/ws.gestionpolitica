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


class Asistente {

    private $id_usuario;
    private $esquema_db;

    private $conector;
    public function __construct() {
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
        $this -> esquema_db = $_SESSION['esquema_db'];
        $this -> id_usuario = $_SESSION['id_usuario'];
    }

    public function obtenerReunionesDigitador(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();
        // devuelve solo las reuniones en estado 2 = Activa y que el usuario logueado tenga permiso de acceder

        // hace la consulta
        $sql ="SELECT r.id, r.nombre, r.salones_id, r.obs, r.estados_reuniones_id, r.fecha_hora, duracion_horas,
        r.fecha_crea, r.fecha_actualiza, r.crea_personas_id, r.actualiza_personas_id,
        s.nombre AS salones_nombre
        FROM $this->esquema_db.tab_reuniones r
        INNER JOIN $this->esquema_db.tab_reuniones_digitadores rd ON rd.reuniones_id=r.id
        INNER JOIN $this->esquema_db.tab_salones s ON s.id=r.salones_id
        WHERE estados_reuniones_id = 2 AND rd.personas_id = $this->id_usuario ORDER BY r.nombre";
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


    public function obtenerAsistentesReunion(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
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

    public function crearAsistente(ServerRequestInterface $request, ResponseInterface $response, array $args = [] ): ResponseInterface {
        // Recopilar datos de la solicitud HTTP
        $json = (array)$request->getParsedBody();

        // Valida datos completos
        if( !isset($json['cedula'])         || $json['cedula']==""          ||
            !isset($json['reuniones_id'])   || $json['reuniones_id']==""    ||
            !isset($json['obs'])            || $json['obs']==""             ){

            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso denegado. Faltan datos', 'DATOS' => 'FALTAN DATOS' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida si tiene permiso de crear asitentes a la reunion
        $sqlreu ="SELECT r.id, r.nombre
        FROM $this->esquema_db.tab_reuniones r
        INNER JOIN $this->esquema_db.tab_reuniones_digitadores rd ON rd.reuniones_id=r.id
        WHERE estados_reuniones_id = 2 AND rd.personas_id = $this->id_usuario
        AND r.id = ".$json['reuniones_id']." ORDER BY r.nombre";
        // die($sqlreu);
        $resreu = $this->conector->select($sqlreu);

        if(!$resreu){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Acceso Denegado. No tiene acceso a la reunión', 'DATOS' => $json['reuniones_id'] );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }elseif($resreu==2){
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta', 'DATOS' => 'ERROR EN LA CONSULTA');
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        // valida si la cedula ya está registrada en la BBDD
        $sqlced ="SELECT id, cedula
        FROM $this->esquema_db.tab_personas WHERE cedula = '".$json['cedula']."' ";
        // die($sqlced);
        $resced = $this->conector->select($sqlced);

        if($resced) {
            $personas_id = $resced[0]['id'];
            // valida que la persona no este registrado en la reunion
            $sqlreun ="SELECT personas_id, reuniones_id
            FROM $this->esquema_db.tab_reuniones_personas
            WHERE reuniones_id = '".$json['reuniones_id']."' AND personas_id = $personas_id " ;
            $resreun = $this->conector->select($sqlreun);
            //die($sqlreun);

            if($resreun){
                $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'La Persona con CC. '.$json['cedula']. ' ya está registrada en la reunión.', 'DATOS' => 'LA PERSONA YA ESTA REGISTRADA EN LA REUNION '.$json['cedula'] );
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }elseif($resced==2){
                $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta', 'DATOS' => 'ERROR EN LA CONSULTA cedula');
                $response->getBody()->write((string)json_encode($respuesta));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
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
            estados_personas_id, crea_personas_id)
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

        // agrega la persona a la reunion
        $sqlinsreun="INSERT INTO $this->esquema_db.tab_reuniones_personas
        (reuniones_id, personas_id, obs, fecha_crea, crea_personas_id)
        VALUES(
        '".$json['reuniones_id']."',
        '".$personas_id."',
        '".$json['obs']."', now(),
        '".$this->id_usuario."' ) ";
        $sqlinsreun=reemplazar_vacios($sqlinsreun);
        //die($sql);
        $resinsreun = $this->conector->insert($sqlinsreun);
        // $id=$_SESSION['id'];
        // var_dump($resinsreun, $sqlinsreun);
        if(!$resinsreun) {
            // si no trae datos retorna codigo 2 no creo el registro
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'Error en la consulta. No se creó el registro', 'DATOS' => 'NO SE CREO EL REGISTRO' );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }else {

            $sqlcount = "SELECT COUNT (*) AS total FROM $this->esquema_db.tab_reuniones_personas
            WHERE reuniones_id =". $json['reuniones_id'] ;
            $rescount = $this->conector->select($sqlcount);
            //die($sqlcount);

            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'Registro creado con éxito '.$rescount[0]['total'], 'DATOS' => $personas_id );
            $response->getBody()->write((string)json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

}
