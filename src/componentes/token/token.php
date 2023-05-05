<?php

class token {
  private $conector;
	function __construct() {
    $this -> conector = ConectorDB::get_conector(Variables::$HOST_DB, Variables::$USUARIO_DB, Variables::$CLAVE_DB, Variables::$NOMBRE_DB, ConectorDB::$TIPO_MYSQLI);
	}
	public function crear_token($user_id)
  {
     $codigo  = md5($user_id.time()."token_key")."_".md5("user_id_".$user_id);
     $refresh = md5($user_id.time()."refresh_key");
     $sql     = "INSERT INTO TOKEN(TOKEN,REFRESH_TOKEN) VALUES ('$codigo','$refresh')";
     $ar      = $this->conector->insert($sql);
     $sql     = "SELECT ID_TOKEN FROM TOKEN WHERE TOKEN='$codigo' AND REFRESH_TOKEN='$refresh'";
     $datos   = $this->conector->select($sql);
     $id_token= $datos[0][0];
     $sql     = "INSERT INTO TOKEN_USUARIO(FK_ID_USUARIO,FK_ID_TOKEN) VALUES($user_id,$id_token)";
     $this->conector->insert($sql);
     $sql     = "SELECT ID_TOKEN_USUARIO FROM TOKEN_USUARIO WHERE FK_ID_USUARIO=$user_id AND FK_ID_TOKEN=$id_token";
     $datos   = $this->conector->select($sql);
     $id_conexion = $datos[0][0];
     $token = array(
                 'token'=>$codigo."_".md5("id_conexion_".$id_conexion),
                 'type'=>"Bearer",
                 'refresh_token'=>$refresh,
                 'expired'=>Variables::$TOKEN_TIME
               );
     return $token;
  }
  public function validar_token($token)
  {
    $token=trim($token);
    $aux=explode("_",$token);
    if(count($aux)!=3)
      die(json_encode(array('status_code'=>4,'status_description'=>RESPONSE::$STATUS[4])));
    $usermd5=$aux[1];
    $id_conexionmd5=$aux[2];
    $codigo=$aux[0]."_".$aux[1];
    $sql="SELECT t.ID_TOKEN,ut.FK_ID_USUARIO FROM TOKEN t, TOKEN_USUARIO ut WHERE md5(concat('id_conexion_',ut.ID_TOKEN_USUARIO))='$id_conexionmd5' AND  t.TOKEN='$codigo'  AND ut.FK_ID_TOKEN=t.ID_TOKEN AND md5(concat('user_id_',ut.ID_TOKEN_USUARIO))='$usermd5' AND t.CREATE + INTERVAL ".Variables::$TOKEN_TIME." SECOND > NOW()";
    $datos=$this->conector->select($sql);
    if(count($datos)>0){
      return array('id_usuario' => $datos[0][1]);
    }else{
      $sql="SELECT t.ID_TOKEN,ut.FK_ID_USUARIO FROM TOKEN t, TOKEN_USUARIO ut WHERE md5(concat('id_conexion_',ut.ID_TOKEN_USUARIO))='$id_conexionmd5' AND t.TOKEN='$codigo'  AND ut.FK_ID_TOKEN=t.ID_TOKEN AND md5(concat('user_id_',ut.ID_TOKEN_USUARIO))='$usermd5' AND t.CREATE + INTERVAL 300 SECOND > NOW()";
      $datos=$this->conector->select($sql);
      if(count($datos)>0)
      {
        return array('id_usuario' => $datos[0][1]);
      }
      else
      {
        $sql="DELETE FROM TOKEN_USUARIO WHERE md5(concat('id_conexion_',ID_TOKEN_USUARIO))='$id_conexionmd5' AND md5(concat('user_id_',FK_ID_USUARIO))=$usermd5";
        $this->conector->delete($sql);
        die(json_encode(array('status_code'=>4,'status_description'=>RESPONSE::$STATUS[4])));
      }
    }
  }
  public function refresh_token($user_id,$token,$refresh)
  {
      $token=trim($token);
      $refresh=trim($refresh);
      $aux=explode("_",$token);
      if(count($aux)!=3)
        die(json_encode(array('status_code'=>4,'status_description'=>RESPONSE::$STATUS[4])));
      $id_conexionmd5=$aux[2];
      $codigo=$aux[0]."_".$aux[1];
      $sql="SELECT t.ID,ut.LAST_TOKEN_ID FROM WS_TOKEN t, WS_USER_TOKEN ut WHERE md5(concat('id_conexion_',ut.ID))='$id_conexionmd5' AND  t.TOKEN='$codigo' AND t.REFRESH_TOKEN='$refresh' AND ut.TOKEN_ID=t.id AND ut.USER_ID=$user_id AND NOW() BETWEEN t.CREATE + INTERVAL ".Variables::$TOKEN_REFRESH_TIME_MIN." SECOND AND  t.CREATE + INTERVAL ".Variables::$TOKEN_REFRESH_TIME_MAX." SECOND";
      $datos=$this->conector->select($sql);
      if(count($datos)>0){
        $ncodigo=md5($user_id.time()."token_key")."_".$aux[1];
        $nrefresh=md5($user_id.time()."refresh_key");
        $sql="INSERT INTO WS_TOKEN (token,refresh_token) VALUES ('$ncodigo','$nrefresh')";
        $this->conector->insert($sql);
        $id_token=$_SESSION['id'];
        $sql="DELETE  FROM WS_TOKEN WHERE ID=".$datos[0][1];
        $this->conector->delete($sql);
        $last_token_id=$datos[0][0];
        $sql="UPDATE WS_USER_TOKEN SET TOKEN_ID=$id_token,LAST_TOKEN_ID=$last_token_id WHERE md5(concat('id_conexion_',ID))='$id_conexionmd5'";
        $this->conector->update($sql);
        $token=array(
          'token'=>$ncodigo."_".$id_conexionmd5,
          'type'=>"Bearer",
          'refresh_token'=>$nrefresh,
          'expired'=>Variables::$TOKEN_TIME
        );
        return array('status_code'=>1,'status_description'=>RESPONSE::$STATUS[1],'data'=>$token);
      }
      else{
        $sql="DELETE FROM WS_USER_TOKEN WHERE md5(concat('id_conexion_',ID))='$id_conexionmd5' AND USER_ID=$user_id";
        //$this->conector->delete($sql);
        return array('status_code'=>4,'status_description'=>RESPONSE::$STATUS[4]);
      }
  }

}
?>
