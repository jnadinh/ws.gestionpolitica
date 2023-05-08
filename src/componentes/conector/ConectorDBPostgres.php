<?php
/**
 * Clase para conexiones a PostgreSQL
 */
class ConectorDBPostgres {
    /**
     * @var string url del servidor de base de datos postgresql
     */
    private static $servidor;
    /**
     * @var Usuario que se usara para para la conexion
     */
    private static $usuario;
    /**
     * @var Clave del usuario con el que se va a conectar a la base de datos
     */
    private static $clave;
    /**
     * @var Base de datos de origen
     */
    private static $base_datos;
    /**
     * @var Variable para almacenar un objeto de la misma conexión y evitar que la conexión se repita
     */
    public static $conector;
    /**
     * @var Variable para almacenar un objeto de la misma conexión y evitar que la conexión se repita
     */

    private function __construct($servidor, $usuario, $clave, $base_datos)
    {
        ConectorDBPostgres::$servidor = $servidor;
        ConectorDBPostgres::$usuario = $usuario;
        ConectorDBPostgres::$clave = $clave;
        ConectorDBPostgres::$base_datos = $base_datos;
        try{
            ConectorDBPostgres::$conector = pg_connect("host=".ConectorDBPostgres::$servidor." port=5432 dbname=".ConectorDBPostgres::$base_datos." user=".ConectorDBPostgres::$usuario." password=".ConectorDBPostgres::$clave."");
            if(!ConectorDBPostgres::$conector){
                throw new Exception("Database connection Error");
            }else{
                $this->con = ConectorDBPostgres::$conector;
            }
        }catch(Exception $e){
        }

    }

    public static function get_conectorPostgres($servidor, $usuario, $clave, $base_datos){
        if(ConectorDBPostgres::$servidor!=$servidor || ConectorDBPostgres::$usuario!=$usuario || ConectorDBPostgres::$clave!=$clave ||ConectorDBPostgres::$base_datos!=$base_datos)
        {
	    ConectorDBPostgres::$conector = new ConectorDBPostgres($servidor,$usuario,$clave,$base_datos);
        }
        return ConectorDBPostgres::$conector;
    }

    public function select($sql){
       $result = pg_query($this->con,$sql);
       if (!$result) {
            return 2;   // ERROR EN LA CONSULTA
       }
       $datos = pg_fetch_all($result);
       return $datos;
    }

    public function update($sql){
        // $sql=strtoupper($sql);
        $result = pg_query($this->con,$sql);
        if (!$result) {
            return false;
        }
        return true;
    }

    public function delete($sql){
        $result = pg_query($this->con,$sql);
        if (!$result) {
            return false;
        }
      return true;
    }

    public function insert($sql){
        /* if(stristr($sql,'""')){
            $sql=strtoupper($sql);
        } */
        $result = pg_query($this->con,$sql);
        if (!$result) {
            return false;
        }
        $id = pg_fetch_all($result);
        $sentencia = pg_escape_string($sql);
        $_SESSION['id'] = $id[0]['id'];
        return true;
    }


}
?>
