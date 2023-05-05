<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
require_once(dirname(__FILE__)."/../conf/configuracion.php");
class Doc
{
	private $endpoints=array();
	private $GLOBAL;
	function __construct()
	{	$this->GLOBAL= array();
		$texto="";
		if(isset(CONFIG::$FILE_FOLDER) && CONFIG::$FILE_FOLDER!=""){
			$archivos=scandir(CONFIG::$FILE_FOLDER);
			foreach ($archivos as $key => $value) {
				if (in_array($value,array(".","..")))
					continue;

				$gestor = fopen(CONFIG::$FILE_FOLDER.$value, "r");

					while(!feof($gestor)) {
						$aux=trim(fgets($gestor));
						if(substr($aux,0,2)!="//")
						$texto .= $aux."<br> ";
					}
			}
		}
		else{
		$gestor = fopen(CONFIG::$FILE_INDEX, "r");
		while(!feof($gestor)) {
			$aux=trim(fgets($gestor));
			if(substr($aux,0,2)!="//")
			$texto .= $aux." ";
		}
	 }
	 	$this->get_globales($texto);
		$texto=$this->replace_globales($texto);
		$pre=preg_split("/(post\(|get\(|put\(|patch\(|delete\()/", $texto,-1,PREG_SPLIT_DELIM_CAPTURE);
		//echo "<div style='position:fixed;width:100%;height:100%;top:0;left:0;z-index:2000;background:#fff'>$texto</div>";
		//echo "<div style='position:fixed;width:100%;height:100%;top:0;left:0;z-index:2000;background:#fff'>".json_encode($pre)."</div>";

		for ($i=1; $i < count($pre) ; $i=$i+2) {
			$descripcion="";
			$parametros=$this->get_parametros($pre[$i+1]);
			$descripcion=(isset($parametros['descrip']))?$parametros['descrip'][0]:"";
			$observaciones=(isset($parametros['observacion']))?$parametros['observacion']:array();
			$entradas=(isset($parametros['in']))?$parametros['in']:array();
			$salidas=(isset($parametros['out']))?$parametros['out']:array();
			$entradas=$this->obetener_propiedades_in($entradas);
			$salidasaux=$this->obetener_propiedades_in($salidas);
			$salidas=array();
			foreach ($salidasaux as $key => $value) {
				$salidas[]= $salidasaux[$key];
			}
			$arraux=array();
			$metodo="POST";
			if($pre[$i]=="get(")
				$metodo="GET";
			if($pre[$i]=="put(")
				$metodo="PUT";
			if($pre[$i]=="patch(")
				$metodo="PATCH";
			if($pre[$i]=="delete(")
				$metodo="DELETE";
		    $prepost=preg_split("/(POST\[|])/",$pre[($i+1)],-1,PREG_SPLIT_DELIM_CAPTURE);
			$arrpost=array();
			for ($j=1; $j < count($prepost); $j++) {
				if($prepost[$j]=="POST[" && isset($prepost[$j+2]) && $prepost[$j+2]=="]"){
					$key=str_replace('"',"",str_replace("'", "",$prepost[$j+1]));
					$arrpost[$key]="1";
				}
			}
		$preget=preg_split("/(GET\[|])/",$pre[($i+1)],-1,PREG_SPLIT_DELIM_CAPTURE);
		$arrget=array();
		for ($j=1; $j < count($preget); $j++) {
			if($preget[$j]=="GET[" && isset($preget[$j+2]) && $preget[$j+2]=="]"){
				$key=str_replace('"',"",str_replace("'", "",$preget[$j+1]));
				$arrget[$key]="1";
			}
		}
			$prefile=preg_split("/(FILES\[|])/",$pre[($i+1)],-1,PREG_SPLIT_DELIM_CAPTURE);
			$arrfile=array();
			for ($j=1; $j < count($prefile); $j++) {
				if($prefile[$j]=="FILES[" && isset($prefile[$j+2]) && $prefile[$j+2]=="]"){
					$key=str_replace('"',"",str_replace("'", "",$prefile[$j+1]));
					$arrfile[$key]="1";
				}
			}
			$pre2=preg_split("/('|\/)/", $pre[$i+1]);


			if(isset($pre2[2])){
				$arraux['tipo']=$metodo;
				$arraux['metodo']=$pre2[2];
				$arraux['descripcion']=($descripcion);
				$arraux['attr']= array();
				$arraux['get']= array();
				$arraux['post']= array();
				$arraux['salidas']=$salidas;
				$arraux['observaciones']=$observaciones;
				foreach ($arrpost as $key => $value) {
					$arrauxpar=array('nombre' =>$key ,'type'=>'string','code'=>'','des'=>'','ej'=>'','opcional'=>(preg_match("/!isset.{1,5}POST.{1,4}($key)/", $pre[$i+1])==1)?0:1 );
					if(isset($entradas[$key])){
						foreach ($entradas[$key] as $kei => $value) {
							$arrauxpar[$kei]=$value;
						}
					}
					if($arrauxpar['opcional']=="")
						$arrauxpar['opcional']=(preg_match("/!isset.{1,5}POST.{1,4}($key)/", $pre[$i+1])==1)?0:1;
					$arraux['post'][]=$arrauxpar;
				}
				foreach ($arrget as $key => $value) {
					$arrauxpar=array('nombre' =>$key ,'type'=>'string','code'=>'','ej'=>'','des'=>'','opcional'=>(preg_match("/!isset.{1,5}POST.{1,4}($key)/", $pre[$i+1])==1)?0:1 );
					if(isset($entradas[$key])){
						foreach ($entradas[$key] as $kei => $value) {
							$arrauxpar[$kei]=$value;
						}
					}
					if($arrauxpar['opcional']=="")
						$arrauxpar['opcional']=(preg_match("/!isset.{1,5}POST.{1,4}($key)/", $pre[$i+1])==1)?0:1;
					$arraux['get'][]=$arrauxpar;
				}
				foreach ($arrfile as $key => $value) {
					$arrauxpar=array('nombre' =>$key ,'type'=>'FILE','code'=>'','ej'=>'','opcional'=>(preg_match("/!isset.{1,5}FILES.{1,4}($key)/", $pre[$i+1])==1)?0:1 );
					if(isset($entradas[$key])){
						foreach ($entradas[$key] as $kei => $value) {
							$arrauxpar[$kei]=$value;
						}
					}
					if($arrauxpar['opcional']=="")
						$arrauxpar['opcional']=(preg_match("/!isset.{1,5}FILES.{1,4}($key)/", $pre[$i+1])==1)?0:1;
					$arraux['post'][]=$arrauxpar;
				}
				for ($k=3; $k < count($pre2) ; $k++) {
				  if(preg_match('/function {0,3}[()]/',$pre2[$k]))
						break;
					$is_parametro=0;
					if (strpos($pre2[$k], ':') !== false) {
    				$is_parametro=1;
					}
					$arrauxpar=array('nombre' =>str_replace(":", "",$pre2[$k]),'parametro'=>$is_parametro,'type'=>'string','code'=>'','ej'=>'','opcional'=>0 );
					if(isset($entradas[str_replace(":", "",$pre2[$k])])){
						foreach ($entradas[str_replace(":", "",$pre2[$k])] as $kei => $value) {
							$arrauxpar[$kei]=$value;
						}
					}
					if($arrauxpar['opcional']=="")
						$arrauxpar['opcional']=1;
					$arraux['attr'][]=$arrauxpar;
				}
				$attrs="";
				foreach ($arraux['attr'] as $key => $value) {
					$attrs.="_".$value['nombre'];
				}
				$arraux['respuesta']=$this->get_respuesta($metodo."_".$pre2[2].$attrs,$arraux['tipo']);
				$this->endpoints[$metodo."_".$pre2[2].$attrs]=$arraux;
			}
		}
		array_multisort(array_column($this->endpoints, 'metodo'), $this->endpoints);
		//echo "<div style='position:fixed;width:100%;height:100%;top:0;left:0;z-index:2000;background:#fff'>".json_encode($this->endpoints)."</div>";
	}
	public function  get_endpoints(){
      return $this->endpoints;
	}
	public function replace_globales($text){
		foreach ($this->GLOBAL as $key => $value) {
			$text=str_replace("@:GET:".$key,$value,$text);
		}
		return $text;
	}
	public function get_globales($text){
		$precomentarios=preg_split("/(\/\*|\*\/|\@\:)/", $text,-1,PREG_SPLIT_DELIM_CAPTURE);
		$arr=array();
		for ($j=0; $j < count($precomentarios); $j++) {
			if(isset($precomentarios[$j+1])){
				if($precomentarios[$j]=="@:"){
					$dato=$precomentarios[$j+1];
					$aux=explode(" ",$dato);
					$aux2=explode(":",$aux[0]);
					if($aux2[0]!="SET")
						continue;
					$nombre=$aux2[1];
					$datof="@:";
					for ($i=1; $i < count($aux); $i++) {
						if($i>1)
						$datof.=" ";
						$datof.=$aux[$i];
					}
					$arr[$nombre]=$datof;
				}
			}
		}
		$this->GLOBAL=$arr;
		return $arr;
	}
	public function get_parametros($text){
		$precomentarios=preg_split("/(\/\*|\*\/|\@\:)/", $text,-1,PREG_SPLIT_DELIM_CAPTURE);
		$textcomentariado="";
		$arr=array();
		for ($j=0; $j < count($precomentarios); $j++) {
			if(isset($precomentarios[$j+1])){
				if($precomentarios[$j]=="@:"){
					$dato=$precomentarios[$j+1];
					$aux=explode(" ",$dato);
					$datof="";
					for ($i=1; $i < count($aux); $i++) {
						if($i>1)
						$datof.=" ";
						$datof.=$aux[$i];
					}
					$arr[$aux[0]][]=$datof;
				}
			}
		}
		return $arr;
	}
	public function obetener_propiedades_in($text){
		$arr=array();
		for ($i=0; $i <  count($text); $i++) {
			$arrp=array();
			$text[$i]=str_replace("  "," ",str_replace("  ", " ",$text[$i]));

			$pro=explode(" @",$text[$i]);
			$arrp['nombre']=$pro[0];
		  for ($j=1; $j < count($pro) ; $j++) {

				$prop=explode(" ",$pro[$j]);
				$dato="";
				for ($k=1; $k <count($prop) ; $k++) {
					if($k>1)
					$dato.=" ";
					$dato.=$prop[$k];
				}
		  	$arrp[$prop[0]]=($dato);
		  }
			$arr[$pro[0]]=$arrp;
		}
   		return $arr;
	}
	public function get_respuesta($metodo,$tipo){
		$nombre_archivo = dirname(__FILE__)."/files/".$tipo."_".$metodo.".txt";
		$linea="";
		 if(file_exists($nombre_archivo)) {
				$fp = fopen($nombre_archivo, "r");

				while (!feof($fp)){
				    $linea .= fgets($fp);

				}
	    }
		return json_decode($linea,true);
		fclose($fp);
	}
	public function guardar_respuesta(){
		  $nombre_archivo = dirname(__FILE__)."/files/".$_POST['tipo']."_".$_POST['metodo'].".txt";

		    if($archivo = fopen($nombre_archivo, "w"))
		    {
		        if(fwrite($archivo,  json_encode( array('fecha' =>date("Y-m-d H:i:s"),'respuesta'=>($_POST['res'])))))
		        {
		            echo "Se ha ejecutado correctamente";
		        }
		        else
		        {
		            echo "Ha habido un problema al crear el archivo";
		        }

		        fclose($archivo);
		    }

	}

}

if(isset($_POST['m'])){
	$doc=new Doc();
	switch ($_POST['m']) {
		case 1:
			$doc->guardar_respuesta();
			break;
		default:
			# code...
			break;
	}
}else{
	$doc=new Doc();
	$endpoints=$doc->get_endpoints();
}
?>
