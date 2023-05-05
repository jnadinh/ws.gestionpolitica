<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once("ConectorADO.php");
$conector=ConectorADO::get_conector("localhost", "edwin.ariza", "emico35813", "plataforma_ole",ConectorADO::$TIPO_POSTGRES);
$tabla=$conector->select("SELECT * FROM ustele.proyectos");
//$conector=ConectorBD::get_conector("localhost", "root", "eaac112358", "jumichicas_systemico",ConectorBD::$TIPO_MYSQL);
//$tabla=$conector->select("SELECT * FROM areas");
//$conector->insert("INSERT INTO log_sistema (id ,Persona_id, Agregado_id, Objeto_id ,Tipo_Log_id ,operacion ,fecha)VALUES (NULL , '2', '2', '3', '3', 'asdfasdfasdfasdf',CURRENT_TIMESTAMP)");
//$conector->insert("delete from log_sistema where id=4183");

//$tabla=$conector->select("SELECT * FROM log_sistema  order by id desc limit 0, 30");

echo "<table style='font-size:11px;' border='1'>";
foreach($tabla as $fila){
	echo "<tr>";
	foreach($fila as $celda){
		echo "<td>";
		echo "&nbsp;".$celda."&nbsp;";
		echo "</td>";
	}
	echo "</tr>";
}
?>
