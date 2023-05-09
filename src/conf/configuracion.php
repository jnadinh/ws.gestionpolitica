<?php
error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__).'/errors.log');
class Variables {

	public static $USUARIO_DB = "gestionpolitica";								// USUARIO DE LA BASE DE DATOS
    public static $CLAVE_DB   = "junimajo123";									// CLAVE DE LA BASE DE DATOS
    public static $HOST_DB    = "192.34.58.242";								// HOST DE LA BASE DE DATOS
//    public static $HOST_DB    = "localhost";									// HOST DE LA BASE DE DATOS
	public static $NOMBRE_DB  = "db_gestion_politica";							// NOMBRE DE LA BASE DE DATO	S

	public static $tiempoSESION = 60;											// TIEMPO MAXIMO DE SESION INACTIVA EN MINUTOS

	public static $nombreRemite="Gestión Política";          					// REMITENTE CORREO PARA MAILGUN Y PHPMAILER
	public static $correoRemite="gestionpolitica2022@gmail.com";			    // CORREO REMITENTE PARA MAILGUN Y PHPMAILER
	public static $nombreDestino="Referido";    						        // NOMBRE DESTINATARIO CORREO PARA MAILGUN Y PHPMAILER

	public static $hostphpmailer="smtp.gmail.com";      						// HOST PHPMAILER
	public static $puertohpmailer=587;         	 								// PUERTO PHPMAILER Port
	public static $Usernamephpmailer="gestionpolitica2022@gmail.com";			// CORREO PHPMAILER Username
    public static $Passwordphpmailer="aeliodsxaxbxpfpq"; 						// PASSWORD PHPMAILER

//	public static $urlBase="http://ws.gestionpolitica.com/";                    // URL BASE
	public static $urlBase="http://localhost:8080/";                            // URL BASE

	public static $urlTemp		=__DIR__ . '/../archivos_temporales/';			// URL DE LOS ARCHIVOS TEMPORALES
	public static $urlArchivos	=__DIR__ . '/../archivos/';						// URL DE LOS ARCHIVOS FIJOS ESQUEMAS
	public static $urlRest="https://gestionpolitica.com/recovery/change-pass/"; // URL RESTABLECER CONTRASEÑA
    public static $urlIngreso="https://gestionpolitica.com/";         			// URL LOGIN
}
?>
