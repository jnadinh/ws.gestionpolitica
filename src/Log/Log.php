<?php

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../componentes/conector/ConectorDB.php';
require_once __DIR__ . '/../conf/configuracion.php';

class Log {

    private $conector;

	public function __construct() {
        $this -> conector = ConectorDB::get_conector(Variables::$HOST_DB, Variables::$USUARIO_DB,
        Variables::$CLAVE_DB, Variables::$NOMBRE_DB, ConectorDB::$TIPO_MYSQLI);
    }

    public function registrarEvento($evento, $id_metodo, $datos, $mensaje,  $ip, $usuario, $token) {

        // obtiene el id del usuario
        $id_cuenta=null;
        $sql1 = "SELECT id_cuenta FROM log WHERE evento ='Respuesta' AND token='$token' AND id_metodo=1 ;" ;
        $sql1=reemplazar_vacios($sql1);

        $datos1 = $this->conector->select2($sql1);
        $id_cuenta=null;
        if (count2($datos)>0) {
            $id_cuenta = isset($datos['ID_CUENTA'])?$datos1[0]['ID_CUENTA']:null;
        }

        $sql2 = "INSERT INTO log (evento, id_cuenta, id_metodo, datos, mensaje, fecha_registro, ip, usuario, token)
        VALUES('$evento', '$id_cuenta', '$id_metodo','$datos', '$mensaje', now(), '$ip' , '$usuario', '$token' );" ;

        $sql2=reemplazar_vacios($sql2);
        $res=$this->conector->insert($sql2);

        return $res;

    }
}

?>
