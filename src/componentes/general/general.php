<?php

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

function es_numero($dato){
  $dato=trim($dato);
  if((string)$dato=="0")
    return true;
  $aux=(int)$dato;
  if($aux==0)
    return false;
  if(strlen($aux)!=strlen($dato))
    return false;
  if($aux." "!=$dato." ")
    return false;
  return true;
}

function decimal_entero($dato){
  $dato=trim($dato);
  $regexWorks = preg_match('/^[0-9]{1,15}$|^[0-9]{1,15}\.[0-9]{1,15}$/',$dato);
  return ($regexWorks === 1)?true:false;
}

function reemplazar_vacios($cadena){
    $vacios= array ("undefined","'null'","'NULL'","'Null'","''","' '","'  '");
    $texto = str_replace($vacios,"null",$cadena);
    return $texto;
}

function cadena_editar($datos) {
  $keys = array_keys($datos);
  $sets = "";
  
  foreach ($keys as $key) {
    $sets .= $key." = '".$datos[$key]."', ";
  }
  $cadena = rtrim($sets, ', ');
  return $cadena;
}

function count2($arr){
  try {
    if(empty($arr)){
      return 0;
    }else{
      return count($arr);
    }
    //return count($arr);
  } catch (Exception $e) {
    return 0;
}

}

function cambiar_key_mayuscula($arr){
  for ($i=0; $i<count2($arr); $i++) {
      foreach ($arr[$i] as $key) {
          if (is_array($arr[$i])) {
              $arr[$i] = array_change_key_case($arr[$i], CASE_UPPER);
          }
      }
  }
  return $arr;
}

function formato_tiempo($fecha) {
  $date1 = new DateTime($fecha);
  $date2 = new DateTime("now");
  $diff = $date1->diff($date2);
  $str = '';
  $str .= ($diff->invert == 1) ? ' - ' : '';
  if ($diff->y > 0) {
      // Años
      $str .= ($diff->y > 1) ? $diff->y . ' Años ' : $diff->y . ' Año ';
  } if ($diff->m > 0) {
      // Meses
      $str .= ($diff->m > 1) ? $diff->m . ' Meses ' : $diff->m . ' Mes ';
  } if ($diff->d > 0) {
      // Dias
      $str .= ($diff->d > 1) ? $diff->d . ' Dias ' : $diff->d . ' Dia ';
  } if ($diff->h > 0) {
      // Horas
      $str .= ($diff->h > 1) ? $diff->h . ' Horas ' : $diff->h . ' Hora ';
  } if ($diff->i > 0) {
      // Minutos
      $str .= ($diff->i > 1) ? $diff->i . ' Minutos ' : $diff->i . ' Minuto ';
  } if ($diff->s > 0) {
      // Segundos
      $str .= ($diff->s > 1) ? $diff->s . ' Segundos ' : $diff->s . ' Segundo ';
  }

  return $str;
}

function formato_tiempo2($fecha) {
  $date1 = new DateTime($fecha);
  $date2 = new DateTime("now");
  $diff = $date1->diff($date2);
  $str = '';
  $str .= ($diff->invert == 1) ? ' - ' : '';
  if ($diff->y > 0) {
      // Años
      $str = ($diff->y > 1) ? $diff->y . ' Años ' : $diff->y . ' Año ';
  } elseif ($diff->m > 0) {
      // Meses
      $str = ($diff->m > 1) ? $diff->m . ' Meses ' : $diff->m . ' Mes ';
  } elseif ($diff->d > 0) {
      // Dias
      $str = ($diff->d > 1) ? $diff->d . ' Dias ' : $diff->d . ' Dia ';
  } elseif ($diff->h > 0) {
      // Horas
      $str = ($diff->h > 1) ? $diff->h . ' Horas ' : $diff->h . ' Hora ';
  } elseif ($diff->i > 0) {
      // Minutos
      $str =  $diff->i . ' Min ';
  } elseif ($diff->s > 0) {
      // Segundos
      $str =  $diff->s . ' Seg ';
  }

  return $str;
}

function sumar_dias_habiles($fecha,$dias) {
  $datestart= strtotime($fecha);
  $datesuma = 15 * 86400;
  $diasemana = date('N',$datestart);
  $totaldias = $diasemana+$dias;
  $findesemana = intval( $totaldias/5) *2 ; 
  $diasabado = $totaldias % 5 ; 
  if ($diasabado==6) $findesemana++;
  if ($diasabado==0) $findesemana=$findesemana-2;

  $total = (($dias+$findesemana) * 86400)+$datestart ; 
  return $fechafinal = date('Y-m-d', $total);
}

function sumar_dias_calendario($fecha,$dias) {
  return $fechafinal = date('Y-m-d', strtotime($fecha."+ $dias days"));
}


    /**
     * Encuentra nodos XML usando XMLReader, Generadores y SimpleXMLElement
     *
     * @param XMLReader $reader
     * @param string $path Camino del nodo XML que se desea encontrar
     * @return Generator
     */

     function read(XMLReader $reader, $path) {
      // Establece el camino recorrido por el reader
      $pathNode = '';
      // Comenzar a leer el XML desde el primer nodo
      while ($reader->read()) {
          // Nombre y tipo del nodo en el cual se encuentra el reader
          $nodeName = $reader->name;
          $nodeType = $reader->nodeType;
          /**
           * Analizar, si el nodo es un "start element"
           * @see https://secure.php.net/manual/es/class.xmlreader.php
           */
          if (XMLReader::ELEMENT == $nodeType) {
              if (empty($pathNode) ) {
                  $pathNode = $nodeName;
              } else {
                  $newPath = implode('/', [$pathNode, $nodeName]);
                  /**
                   * Adiciona el nombre del nodo actual al camino recorrido si
                   * forma parte del camino que se está analizando
                   */
                  if (false !== strpos($path, $newPath)) {
                      $pathNode = $newPath;
                  }
              }
              // Comparar el camino recorrido con el node que se desea encontrar
              if ($pathNode == $path) {
                  // Eliminar el nombre del nodo del camino recorrido
                  $pathNode = preg_replace("/\/?{$nodeName}$/", '', $pathNode);
                  /**
                   * Obtener la representación XML como cadena del nodo encontrado
                   * se incluyen los tags del nodo, se crea un Objeto SimpleXMLElement
                   * y se retorna un Generador
                   */
                  yield (new SimpleXMLElement($reader->readOuterXML()));
              }
          }
      }
    }
  
?>
