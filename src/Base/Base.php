<?php

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

use Matrix\Functions;

require_once __DIR__ . '/../componentes/conector/ConectorDB.php';
require_once __DIR__ . '/../conf/configuracion.php';
require_once __DIR__ . '/../token/Token.php';
require_once __DIR__ . '/../componentes/general/general.php';


class Base {

    private $conector;
    
	public function __construct() {        
        $this -> conector = ConectorDB::get_conector(Variables::$HOST_DB, Variables::$USUARIO_DB,
        Variables::$CLAVE_DB, Variables::$NOMBRE_DB, ConectorDB::$TIPO_MYSQLI);    
    }

    public function obtenerFechaResta($visita_id,$dias)
    {
       $fecha = "";
       $sql = "SELECT DATE_ADD(fecha_planeacion,INTERVAL -".$dias." DAY) as fecha FROM visitas WHERE id=".$visita_id;
       $res = $this->conector->select2($sql);
       if(count2($res)>0) {
            $fecha = $res[0]['FECHA'];
        }
        return $fecha;
    }

    public function obtenerRolUsuario($rol_id)
    {
        $rol_usuario="";
        $sql = "SELECT nombre FROM rol WHERE id ='$rol_id' AND estado=1 ;";
        $res = $this->conector->select2($sql);
        if(count2($res)>0) {            
            $rol_usuario = $res[0]['NOMBRE'];
        }
        // retorna 
       return $rol_usuario;                
    }
                
    public function crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id) 
    {
        $sql = "INSERT INTO notificaciones
        (titulo, descripcion, proyecto_id, modulo_id, usuario_notificacion_id, enviado, leido, 
         estado, palabras_clave, fecha_registro, usuario_id, activo)
        VALUES('$titulo', '$descripcion', $proyecto_id, $modulo_id, $usuario_notificacion_id, 1, 0, 1, '$palabras_clave', current_timestamp(), $usuario_id, 1) ";
        $sql = reemplazar_vacios($sql);
        $res = $this->conector->insert($sql);
        // die($sql);
        if (!$res) {
            return null; 
        }else {
            return $_SESSION['id']; 
        }
    }


}

?>