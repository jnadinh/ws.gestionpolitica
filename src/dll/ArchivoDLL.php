<?php

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../componentes/conector/ConectorDB.php';
require_once __DIR__ . '/../conf/configuracion.php';
require_once __DIR__ . '/../token/Token.php';
require_once __DIR__ . '/../componentes/general/general.php';


class ArchivoDLL {

    private $conector;
    
	public function __construct() {        
        $this -> conector = ConectorDB::get_conector(Variables::$HOST_DB, Variables::$USUARIO_DB,
        Variables::$CLAVE_DB, Variables::$NOMBRE_DB, ConectorDB::$TIPO_MYSQLI);    
    }


    public function crearArchivo($nombre, $nombre_nuevo, $destFile, $tipo_archivo, $id_cuenta, $padre_id, $palabras_clave, $cambios, $version, $fecha_registro, $usuario_actualiza_id ) {

        if($tipo_archivo=="1") {
            $sourceFile   =   Variables::$urlArchivos . $nombre_nuevo;
            // para probar en local
            // $sourceFile = __DIR__ . '/../../archivos/'.$nombre_nuevo;

            // die("Archivos: ".$sourceFile. " Files: " . $destFile);
            //mueve el archivo desde sourceFile a desFile
            $comp = rename ($sourceFile,$destFile);    
        }

        if($fecha_registro==null) {
            // si no envia fecha de regsitro es un archivo original = now()
            $sql1 = "INSERT INTO archivos " .
            "(nombre, nombre_nuevo, `path`, tipo_archivo, usuario_id, activo, padre_id, palabras_clave, cambios, version, fecha_registro ) " .
            " VALUES (" .
            "'$nombre'," .
            "'$nombre_nuevo'," .
            "'$destFile'," .
            "'$tipo_archivo'," .
            "'$id_cuenta',1, " .
            "'$padre_id', " .
            "'$palabras_clave'," .
            "'$cambios'," .
            "'$version',now() );" ;
        }else {
            $sql1 = "INSERT INTO archivos " .
            "(nombre, nombre_nuevo, `path`, tipo_archivo, usuario_id, fecha_registro, activo, padre_id, palabras_clave, cambios, version,  usuario_actualiza_id, fecha_actualiza ) " .
            " VALUES (" .
            "'$nombre'," .
            "'$nombre_nuevo'," .
            "'$destFile'," .
            "'$tipo_archivo'," .
            "'$id_cuenta', " .
            "'$fecha_registro',1, " .
            "'$padre_id', " .
            "'$palabras_clave'," .
            "'$cambios'," .
            "'$version'," .
            "'$usuario_actualiza_id',now() );" ;
        }
        $sql1=reemplazar_vacios($sql1);
        // die($sql1);
        $res1 = $this->conector->insert($sql1);
        return $_SESSION['id'];

    }                  
          
}

?>