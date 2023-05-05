<?php

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../componentes/conector/ConectorDBPostgres.php';
require_once __DIR__ . '/../conf/configuracion.php';
require_once __DIR__ . '/../token/Token.php';
require_once __DIR__ . '/../componentes/general/general.php';
require_once __DIR__ . '/../componentes/correos/Mail.php';


class CuentaDLL {

    private $conector;
    
	public function __construct() {        
        $this -> conector = ConectorDBPostgres::get_conectorPostgres(Variables::$HOST_DB,Variables::$USUARIO_DB,Variables::$CLAVE_DB,Variables::$NOMBRE_DB);
    }


    public function validaCuenta($usuario, $clave, $metodo, $ip) {

        $l = new Log();
        $t = new Token();

        // valida el usuario y clave
        $sql = "SELECT c.id AS id_cuenta, c.usuario, c.correo_cuenta AS correo, u.nombre_completo, rc.rol_id 
        FROM cuenta c 
        LEFT JOIN usuario u ON c.id=u.cuenta_id 
        LEFT JOIN rol_cuenta rc ON rc.cuenta_id = c.id   
        WHERE MD5(usuario)='$usuario' AND clave= '$clave' ;";        
        $sql=reemplazar_vacios($sql);

        $res = $this->conector->select2($sql);
                                            
        if(count2($res)==0)  {
            // Registra en el log
            $l->registrarEvento("Validación", $metodo, null, "ACCESO DENEGADO. USUARIO O CLAVE INVALIDO", $ip, $usuario, null);

            // Construye la respuesta 
            return  array('CODIGO' => 2, 'MENSAJE' => 'ACCESO DENEGADO', 'DATOS' => 'USUARIO O CLAVE INVALIDO' );
            
        }else{
            // genera el token
            $token= $t->generarToken($usuario);
             
            $datos=array();
            $datos['TOKEN']= $token;            
            $id_cuenta = $res[0]['ID_CUENTA']!=null?$res[0]['ID_CUENTA']:null; 
            $id_rol = $res[0]['ROL_ID']!=null?$res[0]['ROL_ID']:null;
            // registra en el log
            // este registro no lo envia por registrarEvento para asegurar que se guarde el id de la cuenta
            $sqllog = "INSERT INTO log (evento, id_cuenta, id_metodo, datos, mensaje, fecha_registro, ip, usuario, token) 
            VALUES('Respuesta', '$id_cuenta', '$metodo','VALIDACIÓN', 'ACCESO CONCEDIDO.', now(), '$ip' , '$usuario' , '$token' );" ;
            $sqllog=reemplazar_vacios($sqllog);
            $reslog=$this->conector->insert($sqllog);
            // Deshabilita los token de esta cuenta en la tabla token esto si solo puede tener una sesion activa
//            $sqlelim = "UPDATE token  SET  activo=0  WHERE usuario= '$usuario';" ;
//            $sqlelim=reemplazar_vacios($sqlelim);
//            $restelim=$this->conector->update($sqlelim);            

            // guarda el token en la tabla token
            $sqltok = "INSERT INTO token (token, id_usuario, usuario, ip, fecha_registro, activo) 
            VALUES('$token', '$id_cuenta', '$usuario', '$ip', now() ,  '1' );" ;
            $sqltok=reemplazar_vacios($sqltok);

            $restok=$this->conector->insert($sqltok);  
            // var_dump($sqltok);                      

            //agreaga el token al array
            $res[0]['TOKEN'] = $token;

            // Modulos para los permisos del rol    
            $sql = "SELECT id AS rol_id, nombre AS rol_nombre, estado FROM rol WHERE id=$id_rol ;";    
            $datos = $this->conector->select2($sql);	 

            $sql = "SELECT m.id AS modulo_id, m.nombre as modulo_nombre, rm.crear, rm.editar, rm.leer, rm.eliminar, rm.acceso_total 
            FROM modulos m LEFT JOIN (rol_modulo  rm
            INNER JOIN rol r ON r.id = rm.rol_id  AND r.id=$id_rol)  ON m.id =rm.modulo_id;";    
            $modulos = $this->conector->select2($sql);	 
            // Construye la respuesta HTTP

            $res[0]['ROL_NOMBRE']=$datos[0]['ROL_NOMBRE'];
            $res[0]['MODULOS']=$modulos;

            return  array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => $res[0] );
        }
                
    }
        
    public function validaToken($token) {
        // valida el usuario y token
        $sql = "SELECT usuario, id  FROM users  
        WHERE token ='$token' 
        AND  (updated_at + interval '".Variables::$tiempoSESION."minutes') > now()  ;";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select($sql);
        //var_dump($res, $sql); die;
        // retorna 
       return $res;                
    }
    // < ".Variables::$tiempoSESION."
    public function obtenerUsuario($token) {
        // valida token
        $usuario="";
        $sql = "SELECT id_cuenta FROM log WHERE evento ='Respuesta' 
        AND token='$token' AND id_metodo=1 ;";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select2($sql);
        $Registros = count2($res);
        // obtiene usuario para el log
        
        if($Registros>0) {            
            $usuario = $res[0]['ID_CUENTA']?$res[0]['ID_CUENTA']:"";
        }
        // retorna 
       return $usuario;                
    }
        
    public function crearTokenPassword($usuario, $metodo, $ip, $url)  {

        $l = new Log();
        $t = new Token(); 

        // valida el usuario y clave
        $sql = "SELECT id As id_usuario, correo_cuenta AS correo, usuario FROM cuenta WHERE usuario='$usuario' OR correo_cuenta='$usuario';";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select2($sql);
        $Registros = count2($res);
        
        if($Registros==0) {
            // Registra en el log
            $l->registrarEvento("Validación", $metodo, null, "ACCESO DENEGADO. USUARIO O CORREO INEXISTENTES", $ip, $usuario, null);

            // Construye la respuesta 
            return  array('CODIGO' => 2, 'MENSAJE' => 'NO ENCONTRADO', 'DATOS' => 'USUARIO O CORREO INEXISTENTES' );
            
        }else{
            // genera el reset_password
           $reset_password= $t->generarTokenPassword();

           $id=$res[0]['ID_USUARIO'];
        //    var_dump($id);
           // guarda el token en la db
            $sqlpass = "UPDATE cuenta SET reset_password= '$reset_password', fecha_reset_password=now() where id = $id ;" ;              
            $sqlpass=reemplazar_vacios($sqlpass);
            $respass = $this->conector->update($sqlpass);
            
            if($respass>=1) {
                // System.out.println("Se actualizo el token "+respass); 

            }else {
                // System.out.println("Noo se actualizo el token "+respass); 
            }       

            // agrega el token reset al array
            $res[0]['TOKEN'] = $reset_password;
            
            // envia correo      
			$asunto = "Restauración de clave";
            $cuerpo="<section> 
              <div class=\"cuadro\" style=\"background-color: white; max-width: 400px; border-radius: 5px 5px 5px 5px; -moz-border-radius: 5px 5px 5px 5px; -webkit-border-radius: 5px 5px 5px 5px; border: 0px solid #000000; margin: 0 auto; margin: 4% auto 0 auto; padding: 0px 0px 20px 0px; -webkit-box-shadow: 0px 3px 3px 2px rgba(0,0,0,0.16); -moz-box-shadow: 0px 3px 3px 2px rgba(0,0,0,0.10); box-shadow: 0px 3px3px 2px rgba(0,0,0,0.16);  overflow: hidden;\"> 
               <img style=\"width:100%; height: 180px;\" src=\"https://ciatel.cdn.juemichica.com/imagen-correo.png\"> 
                  <center><p style=\"text-align: center; font-size: 14px; color: #636A76;\">Hola!, bienvenido. <br> A continuación verá el usuario y enlace<br> para restaurar la clave de su cuenta en CIATEL</p></center> 
                  <center><p style= \"padding: 10px 0px 0px 0px;text-align: center; font-size: 16px; color: #636A76; font-weight: bold;\">Datos de acceso</p></center> 
                  <div style=\"padding: 0px 20%;\" class=\"user\"> 
                    <p style=\"text-align: left; font-size: 12px; color: #A0B0CB; height: 12px;\">Usuario</p> 
                    <p style=\"text-align: left; font-size: 14px; color: #448AFC;\">".$res[0]['USUARIO']."</p> 
                  </div> 
                  <center><p style= \"padding: 10px 0px 20px 0px; text-align: center; font-size: 16px; color: #636A76; font-weight: bold;\">Por favor, ingrese desde aquí</p></center> 
                  <center><a href=\" ".$url.$reset_password." \" style=\"padding: 10px 44px; border-radius: 20px; background-color: #448AFC; font-size: 14px; color: white; text-decoration: none;\">INGRESAR</a></center> 
                  <br><br> 
              </div> 
            </section>";
                        
            // enviar correo con phpmailer
            $res[0]['INFO_CORREO'] = enviar_mail($res[0]['CORREO'], $asunto, $cuerpo);

            // crea la respuesta
            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'CORREO DE RESTABLECER CLAVE ENVIADO', 'DATOS' => $res[0] );

            // registra en el log
            $l->registrarEvento("Validación", $metodo, $respuesta, "CORREO DE RESTABLECER CLAVE ENVIADO.", $ip, $usuario, $reset_password);

            return $respuesta;
        }
                
    }
    
    public function cambiarClave($clave, $token, $metodo, $ip) {

        $l = new Log();
        $t = new Token();

        // valida el usuario y clave
        $sql = "SELECT id AS id_usuario, usuario, reset_password FROM cuenta WHERE reset_password='$token';";
        $sql=reemplazar_vacios($sql);
        $res = $this->conector->select2($sql);
        $Registros = count2($res);
        if($Registros==0) {
            // Registra en el log
            $l->registrarEvento("Validación", $metodo, null, "ACCESO DENEGADO. TOKEN NO EXISTENTE", $ip, null, null);

            // crea la respuesta
            $respuesta = array('CODIGO' => 2, 'MENSAJE' => 'NO ENCONTRADO', 'DATOS' => 'ENLACE NO ENCONTRADO' );

            return $respuesta;
        }else{
            $id=$res[0]['ID_USUARIO'];
            $usuario=$res[0]['USUARIO'];
            // guarda el token en la db
            $sqltoken = "UPDATE cuenta SET reset_password = '',clave = MD5('$clave') WHERE id = $id " ;        
            $restoken = $this->conector->update($sqltoken);
            if($restoken!="") {

            }else {

            }
            
            // crea la respuesta
            $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'CLAVE RESTABLECIDA', 'DATOS' => $res );

            // registra en el log
            $l->registrarEvento("Respuesta", $metodo, $res, "CLAVE RESTABLECIDA", $ip, $usuario, $token);

            return $respuesta;
        }
                
    }
 
    public function cerrarSesion($token) {
        // cambia activo de token a 0
        $sql = "UPDATE token SET activo=0 WHERE token='$token' ;";
        $res = $this->conector->update($sql);
        // retorna 
       return $res;                
    }
    
          
}

?>