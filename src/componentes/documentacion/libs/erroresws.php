<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require_once(dirname(__FILE__)."/../conf/configuracion.php");
class Errores
{

  function __construct()
  {

  }
  public function panel_errores(){
    $errores=$this->get_errores_ws();
    $html="";
    if(isset($_POST['fecha'])){
      for ($i=0; $i < count($errores[$_POST['fecha']]) ; $i++) {
        $error=$errores[$_POST['fecha']][$i];
        $color="#449d44";
        switch (strtolower(trim($error['tipo']))) {
          case 'fatal error':
            $color="#d9534f";
            break;
          case 'parse error':
            $color="#286090";
            break;
          default:
            # code...
            break;
        }
        $tipo='<span style="background:'.$color.';" class="badge badge-success">'.$error['tipo'].'</span>';
        $error['descripcion']=preg_replace('( on line \d{1,})', '<span style="color:red;">$0</span>', $error['descripcion']);
        $html.='<div class="panel panel-default">
        <div class="panel-heading" style="height:35px;">
        <div style="" class="col-md-6">'.$tipo.'</div><div class="col-md-6" style="text-align:right;">'.$error['fecha'].' '.$error['hora'].'</div>
        </div>
        <div class="panel-body"><pre style="background:#333;color:#fff;font-weight:bold;">
        '.$error['descripcion'].'</pre>
        </div>
        </div>';
      }
    }
    echo $html;
  }
  public function mostrar_errores(){
    $errores=$this->get_errores_ws();
    $errores=array_slice($errores, 0, 60);
    //print_r($errores);
    $html="<div class='row'>
    <div class='col-md-12'>
    <div class='col-md-3' style='height: 500px;overflow-y: auto;'>
    ";
    foreach ($errores as $key => $value) {
      $html.="<div class='list-group' style='margin:0' onclick='panel_errores(\"".$key."\")'>
          <a href='#' class='list-group-item'>
            <i class='fa fa-exclamation-triangle fa-fw'></i> $key <span style='min-width: 35px;' class='badge badge-success'>".count($value)."</span>
          </a>
      </div>";
    }
    $html.="</div>
    <div class='col-md-9' id='panel_errores' style='height: 500px;overflow-y: auto;'></div>
    </div>
    </div>
    ";
    echo $html;
  }
  public function get_errores_ws()
  {
    $texto="";
    $gestor = fopen(CONFIG::$FILE_ERRORES_WS, "r");
    while(!feof($gestor)) {
      $aux=trim(fgets($gestor));
      $texto .= $aux."<br> ";
    }
    $pre=preg_split("/(\[.{1,50}\] PHP )/", $texto,-1,PREG_SPLIT_DELIM_CAPTURE);
    $arr=array();
    for ($i=0; $i < count($pre) ; $i++) {
      $arraux=array();
      if(!(strpos($pre[$i], '] PHP ') !== false))
        continue;
      $timestr=str_replace("[","",$pre[$i]);
      $timestr=str_replace("] PHP ","",$timestr);
      $time=strtotime($timestr);
      $timearr=explode(" ",$timestr);
      $dateTime = new DateTime ($timearr[0]." ".$timearr[1], new DateTimeZone($timearr[2]));
      $dateTime->setTimezone(new DateTimeZone('America/Bogota'));
      $fecha= $dateTime->format('Y-m-d');
      $hora= $dateTime->format('H:i:s');
      $preaux=explode(":",$pre[$i+1]);
      $tipo=$preaux[0];
      unset($preaux[0]);
      $descripcion="";
      foreach ($preaux as $key => $value) {
        $descripcion.=($descripcion!="")?":":"";
        $descripcion.=$value;
      }
      $arraux['tipo']=$tipo;
      $arraux['time']=$time;
      $arraux['fecha']=$fecha;
      $arraux['hora']=$hora;
      $arraux['descripcion']=$descripcion;
      $arr[]=$arraux;
    }
    $arr2=array();
    for ($i=count($arr)-1; $i >=0  ; $i--) {
      if(!isset($arr2[$arr[$i]['fecha']]))
        $arr2[$arr[$i]['fecha']]=array();
      $arr2[$arr[$i]['fecha']][]=$arr[$i];
    }
    //array_multisort(array_column($arr,'time'),SORT_DESC,SORT_NUMERIC,$arr);
    return $arr2;
  //  echo $texto;

  }

}
if(isset($_GET['m'])){
  switch ($_GET['m']) {
    case '1':
      (new Errores())->mostrar_errores();
      break;

    default:
      # code...
      break;
  }
}
if(isset($_POST['m'])){
  switch ($_POST['m']) {
    case '1':
      (new Errores())->panel_errores();
      break;

    default:
      # code...
      break;
  }
}

 ?>
