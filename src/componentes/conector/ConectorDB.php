<?php
/**
 * Clase SINGLETON con la cual se realiza la conexión a la base de datos Mysql
 * @author Edwin Alonso Ariza Cáceres
 * @version 1.0
 * package basedatos
 */
require_once(dirname(__FILE__)."/adodb5/adodb-exceptions.inc.php");
require_once(dirname(__FILE__)."/adodb5/adodb.inc.php");
/**if(isset(Variables::$ACTIVAR_DEBUG_BD) && Variables::$ACTIVAR_DEBUG_BD){
	require_once(Variables::$PATH_BASE."modulos/pqp/index.php");
}**/
class ConectorDB
{
    /**
     * @var string url del servidor de base de datos Mysql
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
     * @var Varaible para almacenar el descritor de la clase
     */
    private static $descriptor;
    /**
     * @var Varaible para almacenar el descritor de la clase
     */
    private static $descriptori;
    /**
     * @var Varible de control para manejar los valores retornados por cada una de los consultas
     */
    private static $resultado;
    private static $db;
    private static $obj_debug;
    private static $profiler=null;
    public static $id_reciente;
    public static $TIPO_POSTGRES='postgres';
    public static $TIPO_MYSQL='mysql';
    public static $TIPO_MYSQLI='mysqli';
    public static $TIPO_ORACLE='oracle';
    public static $tipo="mysqli";
    /**
     * Contructor de la clase, es privado para permitir el funcionamiento del patron Singleton
     * @return
     * @param $servidor Object
     * @param $usuario Object
     * @param $clave Object
     * @param $base_datos Object
     */
    private function __construct($servidor, $usuario, $clave, $base_datos, $tipo="mysqli")
    {
        ConectorDB::$servidor = $servidor;
        ConectorDB::$usuario = $usuario;
        ConectorDB::$clave = $clave;
        ConectorDB::$base_datos = $base_datos;
        ConectorDB::$tipo = $tipo;
		if($tipo==ConectorDB::$TIPO_MYSQL){
			ConectorDB::$descriptor= ADONewConnection($tipo);
	    	ConectorDB::$descriptor->Connect(ConectorDB::$servidor,ConectorDB::$usuario, ConectorDB::$clave, ConectorDB::$base_datos);

		}
		if($tipo==ConectorDB::$TIPO_MYSQLI){
			ConectorDB::$descriptor=  new mysqli (ConectorDB::$servidor,ConectorDB::$usuario, ConectorDB::$clave, ConectorDB::$base_datos);
      mysqli_set_charset(ConectorDB::$descriptor,"utf8");
      if (mysqli_connect_errno()) {
			    printf("Error de conexión: %s\n", mysqli_connect_error());
			    exit();
			}
		}
	    //if(isset(Variables::$ACTIVAR_DEBUG_BD) && Variables::$ACTIVAR_DEBUG_BD && ConectorDB::$profiler==null){
		//ConectorDB::$profiler = new PhpQuickProfiler(PhpQuickProfiler::getMicroTime());
		//ConectorDB::$obj_debug = new MySqlDatabase();
		//$enlace =  mysql_connect($servidor, $usuario, $clave);
		//ConectorDB::$obj_debug->connect(false,$enlace);
		//ConectorDB::$obj_debug->changeDatabase(ConectorDB::$base_datos);
	    //}
    }//FIN DEL CONTRUCTOR
    private static function seleccionar_base_datos()
    {

    }
    /**
     * Metodo que retorna una referencia de conexión a la base de datos, si la conexión no exite la crea
     * @return $conector Objeto de conexión a la base de datos
     * @param $servidor Object
     * @param $usuario Object
     * @param $clave Object
     * @param $base_datos Object
     * @constructor
     */
    public static function get_conector($servidor, $usuario, $clave, $base_datos, $tipo='mysql')
    {
        //if(empty(ConectorMySQL::$conector))
        if(ConectorDB::$servidor!=$servidor || ConectorDB::$usuario!=$usuario || ConectorDB::$clave!=$clave || ConectorDB::$base_datos!=$base_datos || ConectorDB::$tipo!=$tipo )
        {
	    ConectorDB::$conector = new ConectorDB($servidor, $usuario, $clave, $base_datos,$tipo);
        }
        return ConectorDB::$conector;
    }//FIN DE LA FUNCION

    /**
     * Metodo que se encarga de hacer las peticiones a la base de datos
     * @return
     * @param string $consulta Sql de solicitud a la base de datos
     */
    private static function consulta($consulta)
    {
    	//if(isset(Variables::$ACTIVAR_DEBUG_BD) && Variables::$ACTIVAR_DEBUG_BD){
    	    	//ConectorDB::$obj_debug->query($consulta);
    	//}
        try
        {

			   if(ConectorDB::$tipo==ConectorDB::$TIPO_MYSQL){
			   		if (ConectorDB::$resultado = ConectorDB::$descriptor->execute($consulta))
		        	{
		        		//$id_new=ConectorDB::$descriptor->Insert_ID();
		        	      $id_new = sprintf( "%u", mysql_insert_id() );
		        	      $_SESSION['id']=$id_new;
		               	  return ConectorDB::$resultado;
		        	 }
		        	 else
		        	 {
		        	   	  return null;
		        	 }
			   }
			   if(ConectorDB::$tipo==ConectorDB::$TIPO_MYSQLI){
			   		if (ConectorDB::$resultado = ConectorDB::$descriptor->query($consulta))
		        	{
		        		 //echo "h1";
		        	     $_SESSION['id']=ConectorDB::$descriptor->insert_id;
		                 //echo "h2";
		        	 return ConectorDB::$resultado;
		        	 }
		        	 else
		        	 {
		        	   return null;
		        	 }
			   }


        }
        catch (Exception $e){
        	//echo "error";
            if(isset(Variables::$LOG_SQL) && Variables::$LOG_SQL){
                require_once dirname(__FILE__).'/../../../control/log/Log.php';
                $ip = $_SERVER['REMOTE_ADDR'];
                $log = new Log();
                $log->registrar_error("ERROR DB >> host: ".$ip. " user_id: ".$_SESSION['usuarioActivo']. " sql: $consulta exception:".$e->getMessage().".");
				echo "$consulta exception:".$e->getMessage();
            }

        }
    }

    /**
     * Metodo que se encarga de ejecutar los insert en la base de datos
     * @return
     * @param $insert Sql de insert para realizar un registro en la base de datos
     */
    public function insert($insert)
    {
        if (!ConectorDB::consulta($insert))
		{
			return false;
		}
        else{
            return true;
        }
    }
    //FIN DE LA FUNCION
    /**
     * Metodo que se encarga de registrar datos y realizar un registro de log con la respectiva operacion
     * @return
     * @param $insert Object
     */
    public function insert_sin_log($insert)
    {

    }//FIN DE LA FUNCION
    /**
     * Metodo para realizar un select de la base de datos
     * @return
     * @param $select Sentencia select que se desea ejecutar en la base de datos para obtener valores
     */
    public function select($select)
    {
        //echo "<br/> datos del conectorDB ".ConectorDB::$servidor."-".ConectorDB::$usuario."-".ConectorDB::$clave."-".ConectorDB::$base_datos."<br/>";
        $tabla = array ();
        if (ConectorDB::consulta($select))
        {
            $fil = 0;
            $col = 0;
	    	$sec=0;
	    	$resultado = ConectorDB::$resultado;
			 if(ConectorDB::$tipo==ConectorDB::$TIPO_MYSQL){
			 while(!$resultado->EOF){
				for ($i=0, $max=$resultado->FieldCount(); $i < $max; $i++){
					$tabla[$fil][$col]= $resultado->fields[$i];
					$col++;
				}
				$resultado->MoveNext();
				$fil++;
				$col=0;
			}
			 }
			if(ConectorDB::$tipo==ConectorDB::$TIPO_MYSQLI){
				$hayResultados = true; //Forzamos la entrada al bucle
					while ($hayResultados==true){
					$fila = mysqli_fetch_array($resultado,MYSQLI_NUM);


					if ($fila) { //operaciones a realizar
						$tabla[$fil]=$fila;
						$fil++;
					} else {$hayResultados = false;}

					}




			}
			//Verificamos el listado de datos a retornar
            if ($fil == 0)
            {
                return null;
            }
            else
            {
            	return $tabla;
            }
        }
    }//FIN DE LA FUNCION

    public function select2($select) {
        $rawdata = array(); //creamos un array
        if (ConectorDB::consulta($select)) {
            $resultado=ConectorDB::consulta($select);
                //guardamos en un array multidimensional todos los datos de la consulta

                $i=0;
                while($row = mysqli_fetch_array($resultado,MYSQLI_ASSOC))
                {
                    $rawdata[] = $row;
                    $i++;
                }
        }
        // cambiar llaves a mayusculas
		$rawdata=cambiar_key_mayuscula($rawdata);
        return $rawdata; //devolvemos el array
    }

    public function selectMin($select) {
        $rawdata = array(); //creamos un array
        if (ConectorDB::consulta($select)) {
            $resultado=ConectorDB::consulta($select);
                //guardamos en un array multidimensional todos los datos de la consulta

                $i=0;
                while($row = mysqli_fetch_array($resultado,MYSQLI_ASSOC))
                {
                    $rawdata[] = $row;
                    $i++;
                }
        }
        // cambiar llaves a mayusculas
		// $rawdata=cambiar_key_mayuscula($rawdata);
        return $rawdata; //devolvemos el array
    }

    //FIN DE LA FUNCION

    /**
     * Metodo que se encarga de realizar registros en la base de datos
     * @return
     * @param $update Object
     */
    public function update($update)
    {
         if (ConectorDB::consulta($update))
		{
			return true;
		}
		else{
			return false;
		}
    }//FIN DE LA FUNCION

    // esta funcion nos devuelve -1: si no se realiza la consulta 0: si no actualiza registros >0: si actualiza
    public function update2($update) {
        $mysql= ConectorDB::$descriptor;
        $mysql->query($update);
        //printf("Filas actualizadas: %d\n", $mysql->affected_rows);
        return $mysql->affected_rows;
    }

    //FIN DE LA FUNCION


    // esta funcion nos devuelve -1: si no se realiza la consulta 0: si no elimina registros >0: si elimina
    public function delete2($delete) {
        $mysql= ConectorDB::$descriptor;
        $mysql->query($delete);
        //printf("Filas eliminadas: %d\n", $mysql->affected_rows);
        return $mysql->affected_rows;
    }

    //FIN DE LA FUNCION


    /**
     * Metodo que se encarga de ejecutar sentecias DELETE en la base de datos
     * @return
     * @param $delete Sentencia DELETE a ejecutar
     */
    public function delete($delete)
    {
        if (ConectorDB::consulta($delete))
		{
			return true;
		}
		else{
            return false;
		}
    }//FIN DE LA FUNCION
    /**
     * Metodo para cerrar la conexión en la base de datos
     */
    public function cerrar_conexion()
    {

    }//FIN DE LA FUNCION
    public function truncate($truncate)
    {
        if (ConectorDB::consulta($truncate))
		{
			return true;
		}
		else{
            return false;
		}
    }
	/**
	 * Retorna un array con la informacion de un registro de la BD
	 * @return
	 * @param object $nombre_tabla Nombre de la tabla a la que pertenece el registro.
	 * @param object $id_registro Id del registro que se quiere recuperar por medio del WHERE
	 * @param object $columna_filtro Nombre de la columna a la que se le realizara el WHERE
	 */
	public function consultar_registro($nombre_tabla, $id_registro, $columna_filtro='id') {

	}
  public function string_secure($inp) {
    if(is_array($inp))
        return array_map(__METHOD__, $inp);

    if(!empty($inp) && is_string($inp)) {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
    }

    return $inp;
  }

}//FIN DE LA CLASE
?>
