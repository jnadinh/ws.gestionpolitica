<?php

/**
 * 
 */
class ConectorMSSQL {
	private $cadena="";
    private $usuario="";
    private $clave="";
    private $nombd="";
	function __construct($cadena, $usuario, $clave, $nombre_bd) {
		$this->cadena=$cadena;
        $this->usuario=$usuario;
        $this->clave=$clave;
        $this->nombd=$nombre_bd;
	}
    
    public function insert($sql){
        $bueno = true;
        try{
        
            $link=mssql_connect($this->cadena, $this->usuario, $this->clave);
            //$link=mssql_connect("192.168.0.103\MANTIS", "sa", "mantisdigital") or die("No fue posible conectar con el servidor");
            mssql_select_db($this->nombd, $link);
            $rs=mssql_query($sql,$link) or $bueno=false; 
            //echo "rs: ".$rs;
            $row = mssql_fetch_array($rs);
            $id = $row["id"];
            $_SESSION['id']=$id; 
            /*echo "<br/>";
            print_r($row);
            echo "<br/>".$row[0];*/
            mssql_close($link);
        }catch(Exception $e){
            $bueno = false;
            echo "ocurrio un problema con la base de datos SQL SERVER";
        }
        /*$link=mssql_connect($this->cadena, $this->usuario, $this->clave) or die("No se pudo conectar con el software contable (No fue posible conectar con el servidor)");//die("No fue posible conectar con el servidor");
        
        mssql_select_db($this->nombd, $link) or die("No fue posible selecionar la base de datos");
        $rs=mssql_query($sql,$link) or $bueno=false; 
        
        $row = mssql_fetch_array($rs);
        
        mssql_close($link);*/
        return $bueno;
    }

    public function select($sql){
        $bueno = true;
        try{
            $link=mssql_connect($this->cadena, $this->usuario, $this->clave);
            mssql_select_db($this->nombd, $link) ;
            $rs=mssql_query($sql, $link) or $bueno=false;
            $result = array();   
            
            // Iterate through returned records
            do {
                while ($row = mssql_fetch_row($rs)) {
                    // Handle record ...
                    $result[]=$row;
                }
            } while (mssql_next_result($rs));
            
            // Clean up

            mssql_free_result($rs); 
            mssql_close($link);
            return $result;
            
        }catch(Exception $e){
            return true;
            echo "ocurrio un problema con la base de datos SQL SERVER";
        }
    }


}


?>