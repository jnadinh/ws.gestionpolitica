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
class ConectorDBJ 
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
    public static $TIPO_ORACLE='oracle';
    public static $tipo="mysql";
    /**
     * Contructor de la clase, es privado para permitir el funcionamiento del patron Singleton
     * @return
     * @param $servidor Object
     * @param $usuario Object
     * @param $clave Object
     * @param $base_datos Object
     */
    private function __construct($servidor, $usuario, $clave, $base_datos, $tipo="mysql")
    {
        ConectorDBJ::$servidor = $servidor;
        ConectorDBJ::$usuario = $usuario;
        ConectorDBJ::$clave = $clave;
        ConectorDBJ::$base_datos = $base_datos;
        ConectorDBJ::$tipo = $tipo;
	    ConectorDBJ::$descriptor= ADONewConnection(ConectorDBJ::$tipo); 
	    ConectorDBJ::$descriptor->Connect(ConectorDBJ::$servidor,ConectorDBJ::$usuario, ConectorDBJ::$clave, ConectorDBJ::$base_datos);
	    //if(isset(Variables::$ACTIVAR_DEBUG_BD) && Variables::$ACTIVAR_DEBUG_BD && ConectorDBJ::$profiler==null){
		//ConectorDBJ::$profiler = new PhpQuickProfiler(PhpQuickProfiler::getMicroTime());
		//ConectorDBJ::$obj_debug = new MySqlDatabase();
		//$enlace =  mysql_connect($servidor, $usuario, $clave);
		//ConectorDBJ::$obj_debug->connect(false,$enlace);
		//ConectorDBJ::$obj_debug->changeDatabase(ConectorDBJ::$base_datos);
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
        if(ConectorDBJ::$servidor!=$servidor || ConectorDBJ::$usuario!=$usuario || ConectorDBJ::$clave!=$clave || ConectorDBJ::$base_datos!=$base_datos || ConectorDBJ::$tipo!=$tipo )
        {
	    ConectorDBJ::$conector = new ConectorDBJ($servidor, $usuario, $clave, $base_datos,$tipo);
        }
        return ConectorDBJ::$conector;
    }//FIN DE LA FUNCION
    
    /**
     * Metodo que se encarga de hacer las peticiones a la base de datos
     * @return
     * @param string $consulta Sql de solicitud a la base de datos
     */
    private static function consulta($consulta)
    {
	//if(isset(Variables::$ACTIVAR_DEBUG_BD) && Variables::$ACTIVAR_DEBUG_BD){
	    	//ConectorDBJ::$obj_debug->query($consulta);
	//}
        if (ConectorDBJ::$resultado = ConectorDBJ::$descriptor->Execute($consulta))
        {
        		//$id_new=ConectorDBJ::$descriptor->Insert_ID();
        		$id_new = sprintf( "%u", mysql_insert_id() );
				$_SESSION['id']=$id_new;
                return ConectorDBJ::$resultado;
        }
        else
        {
            return null;
        }
    }
  
    /**
     * Metodo que se encarga de ejecutar los insert en la base de datos
     * @return
     * @param $insert Sql de insert para realizar un registro en la base de datos
     */
    public function insert($insert)
    {
        if (!ConectorDBJ::consulta($insert))
		{
			return false;
		}
	else{
		return true;
	}
        
    }//FIN DE LA FUNCION
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
        $tabla = array ();
        if (ConectorDBJ::consulta($select))
        {
            $fil = 0;
            $col = 0;
	    	$sec=0;
	    	$resultado = ConectorDBJ::$resultado;
			while(!$resultado->EOF){
				for ($i=0, $max=$resultado->FieldCount(); $i < $max; $i++){
					$tabla[$fil][$col]= $resultado->fields[$i];
					$col++;
				}
				$resultado->MoveNext();
				$fil++;
				$col=0;
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
    /**
     * Metodo que se encarga de realizar registros en la base de datos
     * @return
     * @param $update Object
     */
    public function update($update)
    {
         if (ConectorDBJ::consulta($update))
		{
			return true;
		}
		else{
			return false;
		}
    }//FIN DE LA FUNCION
    /**
     * Metodo que se encarga de ejecutar sentecias DELETE en la base de datos
     * @return
     * @param $delete Sentencia DELETE a ejecutar
     */
    public function delete($delete)
    {
         if (ConectorDBJ::consulta($delete))
		{
			echo "Eliminaci&oacute;n exitosa";
		}
		else{
				echo "Error al eliminar";
		}
    }//FIN DE LA FUNCION
    /**
     * Metodo para cerrar la conexión en la base de datos
     */
    public function cerrar_conexion()
    {
       
    }//FIN DE LA FUNCION
	
	/**
	 * Retorna un array con la informacion de un registro de la BD
	 * @return 
	 * @param object $nombre_tabla Nombre de la tabla a la que pertenece el registro.
	 * @param object $id_registro Id del registro que se quiere recuperar por medio del WHERE
	 * @param object $columna_filtro Nombre de la columna a la que se le realizara el WHERE
	 */
	public function consultar_registro($nombre_tabla, $id_registro, $columna_filtro='id') {
	
	}
}//FIN DE LA CLASE
?>