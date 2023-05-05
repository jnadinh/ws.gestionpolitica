<?php

class CONFIG
{
	public static  $FILE_INDEX="";
	public static  $FILE_FOLDER="";
	public static  $FILE_ERRORES_WS="";
	
}
CONFIG::$FILE_INDEX=dirname(__FILE__)."/../../index.php";
CONFIG::$FILE_FOLDER=dirname(__FILE__)."/../../../apis/";
CONFIG::$FILE_ERRORES_WS=dirname(__FILE__)."/../../../logs/ws.log";
?>
