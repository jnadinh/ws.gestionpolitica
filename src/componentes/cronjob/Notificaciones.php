<?php

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once __DIR__ . '/../conector/ConectorDB.php';
require_once __DIR__ . '/../../conf/configuracion.php';
require_once __DIR__ . '/../general/general.php';
require_once __DIR__ . '/../../Base/Base.php';

class Notificaciones {

    private $conector;
    
	public function __construct() {        
        $this -> conector = ConectorDB::get_conector(Variables::$HOST_DB, Variables::$USUARIO_DB,
        Variables::$CLAVE_DB, Variables::$NOMBRE_DB, ConectorDB::$TIPO_MYSQLI);    
    }

    public function notificacionActividades() 
    {

        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos de la actividad para crear la notificacion
        $modulo_id = 3; // Planeacion
        
        $sql="SELECT COUNT(id) AS cant, id AS id_actividad, proyecto_id, nombre, codigo, GROUP_CONCAT(rol_id) as rol_id, fecha_fin 
        FROM actividades
        WHERE activo=1 AND estado_actividad_id IN(1,4) GROUP BY proyecto_id";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $id_actividad = $resps['ID_ACTIVIDAD'];
                $rol_id =       $resps["ROL_ID"];
                $proyecto_id =  $resps['PROYECTO_ID'];
                $nombre =       $resps['NOMBRE'];
                $fecha =        $resps['FECHA_FIN'];

                if($cant==1) {
                    $descripcion = "Tiene 1 actividad pendiente por realizar: $nombre, su fecha límite es: $fecha ";

                }else if ($cant>1) {
                    $descripcion = "Tiene $cant actividades pendientes por realizar, recuerde hacer su revisión";
                }

                $titulo = "Actividad";
                $usuario_notificacion_id = "";
                $palabras_clave = "actividad planeacion ";
            
                // selecciona los usuarios del proyecto con rol de la actividad
                $sqlrol="SELECT rc.cuenta_id 
                FROM rol_cuenta rc 
                INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id 
                AND  pc.proyecto_id = '$proyecto_id' AND rc.rol_id IN($rol_id) ";
                $sqlrol = reemplazar_vacios($sqlrol);
                // var_dump($sqlrol);
                $resrol = $this->conector->select2($sqlrol);
                if(count2($resrol)>0) {
                    for($i=0; $i<count2($resrol);$i++) {
                        $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                        (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
                    }
                }
            }
        }
        return "OK";
    }

    public function notificacionCotizaciones() 
    {

        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos de la actividad para crear la notificacion
        $modulo_id = 3; // Planeacion
        
        $sql="SELECT COUNT(vv.visita_id) AS cant, v2.proyecto_id, v3.nombre 
        FROM visitas_visitadores vv
        INNER JOIN visitas v2 ON v2.id = vv.visita_id
        INNER JOIN visitadores v3 ON v3.id = vv.visitador_id 
        WHERE vv.estado =2 AND vv.requiere_tiquetes =1 
        GROUP BY v2.proyecto_id ";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $proyecto_id =  $resps['PROYECTO_ID'];
                $nombre =       $resps['NOMBRE'];

                if($cant==1) {
                    $descripcion = "Tiene cotizaciones pendientes de subir del visitador: $nombre ";

                }else if ($cant>1) {
                    $descripcion = "Tiene cotizaciones pendientes de subir de $cant visitadores. ";
                }

                $titulo = "Cotizaciones";
                $usuario_notificacion_id = "";
                $palabras_clave = "subir cotizacion cotizaciones ";

                // Busca el rol responsable de las actividades tipo 3 (subir cotizaciones)
                $sqlroles = "SELECT GROUP_CONCAT(rol_id) AS roles
                FROM actividades 
                WHERE tipo_actividad_id = 3 AND proyecto_id = $proyecto_id ";
                $resroles = $this->conector->select2($sqlroles); 
                $roles=$resroles[0]['ROLES'];

                // selecciona los usuarios del proyecto con rol de la actividad
                $sqlrol="SELECT rc.cuenta_id 
                FROM rol_cuenta rc 
                INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id 
                AND  pc.proyecto_id = '$proyecto_id' AND rc.rol_id IN($roles) ";
                $sqlrol = reemplazar_vacios($sqlrol);
                $resrol = $this->conector->select2($sqlrol);
                if(count2($resrol)>0) {
                    for($i=0; $i<count2($resrol);$i++) {
                        $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                        (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
                    }
                }
            }
        }
        return "OK";
    }

    public function notificacionVisitadores()
    {

        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos de la actividad para crear la notificacion
        $modulo_id = 3; // Planeacion
        
        $sql="SELECT COUNT(v.id) AS cant, v.id AS visita_id,  v.proyecto_id 
        FROM visitas v
        WHERE (SELECT COUNT(visita_id) FROM visitas_visitadores vv INNER JOIN visitadores vs ON(vv.visitador_id = vs.id )  WHERE visita_id = v.id AND vs.activo =1) = 0 
        GROUP BY v.proyecto_id ";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $proyecto_id =  $resps['PROYECTO_ID'];
                $visita_id =       $resps['VISITA_ID'];

                if($cant==1) {
                    $descripcion = "Tiene una visita por asignar visitadores: $visita_id ";

                }else if ($cant>1) {
                    $descripcion = "Tiene $cant visitas por asignar visitadores. ";
                }

                $titulo = "Asignar Visitadores";
                $usuario_notificacion_id = "";
                $palabras_clave = "asignar visitadores visitador ";

                // Busca el rol responsable de las actividades tipo 2 (asignar visitadores)
                $sqlroles = "SELECT GROUP_CONCAT(rol_id) AS roles
                FROM actividades 
                WHERE tipo_actividad_id = 2 AND proyecto_id = $proyecto_id ";
                $resroles = $this->conector->select2($sqlroles); 
                $roles=$resroles[0]['ROLES'];

                // selecciona los usuarios del proyecto con rol de la actividad
                $sqlrol="SELECT rc.cuenta_id 
                FROM rol_cuenta rc 
                INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id 
                AND  pc.proyecto_id = '$proyecto_id' AND rc.rol_id IN($roles) ";
                $sqlrol = reemplazar_vacios($sqlrol);
                $resrol = $this->conector->select2($sqlrol);
                if(count2($resrol)>0) {
                    for($i=0; $i<count2($resrol);$i++) {
                        $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                        (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
                    }
                }
            }
        }
        return "OK";
    }

    public function notificacionCompromisos() 
    {

        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos del compromiso para crear la notificacion
        $modulo_id = 11; // Compromisos
        
        // compromisos de actas  // tipo=1
        $sql="SELECT COUNT(c.id) AS cant, c.id AS compromiso_id, c.descripcion, c.fecha_limite, p2.usuario_id,
        p.id AS proyecto_id
        FROM compromisos c 
        INNER JOIN temas t ON t.id = c.tema_id
        INNER JOIN actas a ON a.id = t.acta_id
        INNER JOIN proyectos p ON p.id = a.proyecto_id
        INNER JOIN participantes p2 ON p2.id = c.responsable_participante_id AND p2.interno = 1 
        WHERE c.tipo = 1 AND c.estado = 1
        GROUP BY a.proyecto_id, p2.usuario_id ";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $proyecto_id =  $resps['PROYECTO_ID'];
                $desc =       $resps['DESCRIPCION'];
                $usuario_notificacion_id = $resps['USUARIO_ID'];

                if($cant==1) {
                    $descripcion = "Recuerde que tiene un compromiso que aun no ha resuelto en Actas: $desc ";

                }else if ($cant>1) {
                    $descripcion = "Recuerde que tiene $cant compromisos que aun no ha resuelto en Actas";
                }

                $titulo = "Compromisos";
                $palabras_clave = "compromisos pendientes ";

                (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
            }
        }

        // compromisos de hallazgos // tipo 2
        $sql="SELECT COUNT(c.id) AS cant, c.id AS compromiso_id, c.descripcion, c.fecha_limite, c.usuario_responsable_id,
        p.id AS proyecto_id
        FROM compromisos c 
        INNER JOIN hallazgos h ON h.id = c.hallazgo_id 
        INNER JOIN proyectos p ON p.id = h.id
        WHERE c.tipo = 2 AND c.estado = 1
        GROUP BY p.id, c.usuario_responsable_id ";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $proyecto_id =  $resps['PROYECTO_ID'];
                $desc =       $resps['DESCRIPCION'];
                $usuario_notificacion_id = $resps['USUARIO_RESPONSABLE_ID'];

                if($cant==1) {
                    $descripcion = "Recuerde que tiene un compromiso que aun no ha resuelto en Hallazgos: $desc ";

                }else if ($cant>1) {
                    $descripcion = "Recuerde que tiene $cant compromisos que aun no ha resuelto en Hallazgos";
                }

                $titulo = "Compromisos";
                $palabras_clave = "compromisos pendientes hallazgos  ";

                (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
            }
        }

        // compromisos de mejoras
        $sql="SELECT COUNT(id) AS cant, id AS mejora_id, nombre, descripcion, fecha_limite, usuario_responsable_id,
        proyecto_id
        FROM mejoras 
        WHERE estado IN(1,4,5)
        GROUP BY proyecto_id, usuario_responsable_id ";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $proyecto_id =  $resps['PROYECTO_ID'];
                $nombre =       $resps['NOMBRE'];
                $desc =       $resps['DESCRIPCION'];
                $usuario_notificacion_id = $resps['USUARIO_RESPONSABLE_ID'];

                if($cant==1) {
                    $descripcion = "Recuerde que tiene un compromiso que aun no ha resuelto en Mejoras: $nombre ";

                }else if ($cant>1) {
                    $descripcion = "Recuerde que tiene $cant compromisos que aun no ha resuelto en Mejoras";
                }

                $titulo = "Compromisos";
                $palabras_clave = "compromisos pendientes mejoras $desc  ";

                (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
            }
        }
        return "OK";
    }

    public function notificacionPlanillas()
    {

        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos de la actividad para crear la notificacion
        $modulo_id = 19; // Planillas
        
        $sql="SELECT COUNT(vv.visita_id) AS cant, vv.visita_id, vv.visitador_id,
        v.proyecto_id
        FROM visitas_visitadores vv
        INNER JOIN visitas v ON v.id=vv.visita_id 
        INNER JOIN proyectos p ON p.id = v.id
        WHERE vv.activo = 1 AND vv.es_hon = 0
        GROUP BY v.proyecto_id ";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $proyecto_id =  $resps['PROYECTO_ID'];

                if($cant>1) {
                    $descripcion = "Tiene planillas pendientes por generar";
                }

                $titulo = "Generar planillas";
                $usuario_notificacion_id = "";
                $palabras_clave = "generar planillas pendientes ";

                // Busca el rol responsable de las actividades tipo 4 (Generar planilla)
                $sqlroles = "SELECT GROUP_CONCAT(rol_id) AS roles
                FROM actividades 
                WHERE tipo_actividad_id = 4 AND proyecto_id = $proyecto_id ";
                $resroles = $this->conector->select2($sqlroles); 
                $roles=$resroles[0]['ROLES'];

                // selecciona los usuarios del proyecto con rol de la actividad
                $sqlrol="SELECT rc.cuenta_id 
                FROM rol_cuenta rc 
                INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id 
                AND  pc.proyecto_id = '$proyecto_id' AND rc.rol_id IN($roles) ";
                $sqlrol = reemplazar_vacios($sqlrol);
                $resrol = $this->conector->select2($sqlrol);
                if(count2($resrol)>0) {
                    for($i=0; $i<count2($resrol);$i++) {
                        $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                        (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
                    }
                }
            }
        }
        return "OK";
    }


    public function notificacionActividadesValidar()
    {
    
        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos de la actividad para crear la notificacion
        $modulo_id = 3; // Planeacion
        
        $sql="SELECT COUNT(id) AS cant, id AS id_actividad, proyecto_id, nombre, codigo, GROUP_CONCAT(rol_id) as rol_id, fecha_fin 
        FROM actividades
        WHERE activo=1 AND estado_actividad_id IN(2) GROUP BY proyecto_id";
        // echo($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $id_actividad = $resps['ID_ACTIVIDAD'];
                $rol_id =       $resps["ROL_ID"];
                $proyecto_id =  $resps['PROYECTO_ID'];
                $nombre =       $resps['NOMBRE'];
                $fecha =        $resps['FECHA_FIN'];

                if($cant==1) {
                    $descripcion = "Tiene 1 actividad pendiente por validar: $nombre, su fecha límite es: $fecha ";

                }else if ($cant>1) {
                    $descripcion = "Tiene $cant actividades pendientes por validar.";
                }

                $titulo = "Actividad";
                $usuario_notificacion_id = "";
                $palabras_clave = "actividad validar ";
            
                // selecciona los usuarios del proyecto con rol de la actividad
                $sqlrol="SELECT rc.cuenta_id 
                FROM rol_cuenta rc 
                INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id 
                AND  pc.proyecto_id = '$proyecto_id' AND rc.rol_id IN($rol_id) ";
                $sqlrol = reemplazar_vacios($sqlrol);
                // var_dump($sqlrol);
                $resrol = $this->conector->select2($sqlrol);
                if(count2($resrol)>0) {
                    for($i=0; $i<count2($resrol);$i++) {
                        $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                        (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
                    }
                }
            }
        }
        return "OK";
    }

    public function notificacionPlanillasValidar()
    {

        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos de la actividad para crear la notificacion
        $modulo_id = 19; // Planillas
        
        $sql="SELECT COUNT(id) AS cant, proyecto_id 
        FROM planillas
        WHERE activo=1 AND estado IN(2) GROUP BY proyecto_id
        UNION 
        SELECT COUNT(id) AS cant, proyecto_id 
        FROM planillas_sin_gastos
        WHERE activo=1 AND estado IN(2) GROUP BY proyecto_id
        UNION 
        SELECT COUNT(id) AS cant, proyecto_id 
        FROM planillas_novedades
        WHERE activo=1 AND estado IN(2) GROUP BY proyecto_id ";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $cant =         $resps['CANT'];
                $proyecto_id =  $resps['PROYECTO_ID'];

                if($cant>1) {
                    $descripcion = "Tiene planillas pendientes por validar";
                }

                $titulo = "Validar planillas";
                $usuario_notificacion_id = "";
                $palabras_clave = "validar planillas pendientes ";

                // selecciona los usuarios con rol financiero
                $sqlrol="SELECT rc.cuenta_id, pc.proyecto_id, p.nombre AS proyecto_nombre
                FROM rol_cuenta rc 
                INNER JOIN rol r ON r.id = rc.rol_id 
                INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id
                INNER JOIN proyectos p ON p.id = pc.proyecto_id
                WHERE UPPER(r.nombre) = 'FINANCIERO' AND p.id = '$proyecto_id' ";
                $resrol = $this->conector->select2($sqlrol);
                if(count2($resrol)>0) {
                    for($i=0; $i<count2($resrol);$i++) {
                        $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                        (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
                    }
                }
            }
        }
        return "OK";
    }

    public function notificacionVerificacionCumplimiento()
    {

        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos de la actividad para crear la notificacion
        $modulo_id = 16; // Proceso administrativo
        
        $titulo = "Verificación y cumplimiento del personal";
        $usuario_notificacion_id = "";
        $palabras_clave = "Verificación cumplimiento personal";

        // selecciona los usuarios con rol administrativo
        $sqlrol="SELECT rc.cuenta_id, pc.proyecto_id, p.nombre AS proyecto_nombre
        FROM rol_cuenta rc 
        INNER JOIN rol r ON r.id = rc.rol_id 
        INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id
        INNER JOIN proyectos p ON p.id = pc.proyecto_id
        WHERE UPPER(r.nombre) = 'ADMINISTRATIVO'";
        $resrol = $this->conector->select2($sqlrol);
        if(count2($resrol)>0) {
            for($i=0; $i<count2($resrol);$i++) {
                $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                $proyecto_id = $resrol[$i]['PROYECTO_ID'];
                $proyecto_nombre = $resrol[$i]['PROYECTO_NOMBRE'];
                $descripcion = "Recuerde realizar la verificación y cumplimiento del personal proyecto: $proyecto_nombre";
                (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
            }
        }
        return "OK";
    }

    public function notificacionVerificacionContractual() 
    {

        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos de la actividad para crear la notificacion
        $modulo_id = 16; // Proceso administrativo
        
        $titulo = "Verificación contractual";
        $usuario_notificacion_id = "";
        $palabras_clave = "Verificación contractual";

        // selecciona los usuarios con rol juridico
        $sqlrol="SELECT rc.cuenta_id, pc.proyecto_id, p.nombre AS proyecto_nombre
        FROM rol_cuenta rc 
        INNER JOIN rol r ON r.id = rc.rol_id 
        INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id
        INNER JOIN proyectos p ON p.id = pc.proyecto_id
        WHERE UPPER(r.nombre) = 'JURIDICO'";
        $resrol = $this->conector->select2($sqlrol);
        if(count2($resrol)>0) {
            for($i=0; $i<count2($resrol);$i++) {
                $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                $proyecto_id = $resrol[$i]['PROYECTO_ID'];
                $proyecto_nombre = $resrol[$i]['PROYECTO_NOMBRE'];
                $descripcion = "Recuerde realizar la verificación contractual del proyecto: $proyecto_nombre ";
                (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
            }
        }
        return "OK";
    }


    public function notificacionPqrs()
    {
        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos del compromiso para crear la notificacion
        $modulo_id = 18; // pqrs
        
        // 
        $sql="SELECT p.id, p.proyecto_id, p.responsable_id, p.fecha_limite, tp.dias_calendario, tp.dias_habiles,
        DATEDIFF(p.fecha_limite, CURDATE() ) AS diferencia
        FROM pqrs p
        INNER JOIN tipos_pqrs tp ON tp.id = p.tipo_pqr_id 
        WHERE p.estado_pqr_id = 2 
        AND ((tp.dias_calendario > 9 OR tp.dias_habiles > 9) AND DATEDIFF(p.fecha_limite, CURDATE()) = 5)
        OR ((tp.dias_calendario < 10 OR tp.dias_habiles < 10) AND DATEDIFF(p.fecha_limite, CURDATE()) = 1) ";
        // die($sql);
        $res=$this->conector->select2($sql);

        if (count2($res)>0) {
            foreach ($res as $key => $resps) {
                $pqr_id =           $resps['ID'];
                $proyecto_id =      $resps['PROYECTO_ID'];
                $fecha_limite =     $resps['FECHA_LIMITE'];
                $usuario_notificacion_id = $resps['RESPONSABLE_ID'];

                $descripcion = "Tiene 1 pqrs pendiente por dar respuesta. id: $pqr_id. su fecha limite es hasta el $fecha_limite";

                $titulo = "Pqrs";
                $palabras_clave = "pqrs pendiente respuesta ";

                (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);

            }
        }
        return "OK";
    }

    public function notificacionRiesgos()
    {

        $usuario_id=0;  // las notificaciones las genera el sistema
        $modulo_id = 7;    // riesgos
        
        $sql = "SELECT rm.id, r.nombre, rm.descripcion, rm.periodicidad, rm.riesgo_id, rm.fecha_registro, r.proyecto_id 
        FROM riesgos_monitoreo rm
        INNER JOIN riesgos r ON r.id = rm.riesgo_id 
        WHERE rm.activo = 1 AND r.resuelto = 0 AND rm.activo = 1 AND 
        (periodicidad=1) OR -- 1=diario
        (periodicidad=2 AND WEEKDAY(CURDATE())=WEEKDAY(rm.fecha_registro)) OR -- 2=semanal
        (periodicidad=3 AND  (DAY(CURDATE())=DAY(rm.fecha_registro)) OR 
        (DAY(LAST_DAY(CURDATE()))=DAY(CURDATE()) AND DAY(rm.fecha_registro) > DAY(CURDATE()) )) OR -- 3=mensual
        (periodicidad=4 AND (DAY(CURDATE())=DAY(rm.fecha_registro) OR 
        ((DAY(LAST_DAY(CURDATE()))=DAY(CURDATE()) AND DAY(rm.fecha_registro) > DAY(CURDATE() ))) AND 
        ((MOD(MONTH(rm.fecha_registro),2) = 0 AND MOD(MONTH(CURDATE()),2) = 0) OR 
        (MOD(MONTH(rm.fecha_registro),2) <> 0 AND MOD(MONTH(CURDATE()),2) <> 0)) )) OR -- 4=bimensual
        (periodicidad=5 AND ((DAY(CURDATE())=DAY(rm.fecha_registro)) OR 
        (DAY(LAST_DAY(CURDATE()))=DAY(CURDATE()) AND DAY(rm.fecha_registro) > DAY(CURDATE() ))) AND 
        (MONTH(rm.fecha_registro) = MONTH(CURDATE()) ) OR 
        ((MONTH(rm.fecha_registro) - MONTH(CURDATE()) = 6) OR 
        (MONTH(rm.fecha_registro) - MONTH(CURDATE()) = -6)) ) OR -- 5=semestral
        (periodicidad=6 AND MONTH(CURDATE())=MONTH(rm.fecha_registro) AND (DAY(CURDATE())=DAY(rm.fecha_registro)) OR 
        (DAY(LAST_DAY(CURDATE()))=DAY(CURDATE()) AND DAY(rm.fecha_registro) > DAY(CURDATE())) ) -- anual";
        $sql=reemplazar_vacios($sql);
        // die($sqlnot);
        $res = $this->conector->select2($sql);
        if(count($res)>0){                
            $proyecto_id =       $res[0]['PROYECTO_ID'];
        }
    
        $titulo = "Riesgos";
        $descripcion = "Recuerde que se ha activado un riesgo El riesgo: ".$res[0]['NOMBRE']." 
        y tiene compromisos pendientes por cumplir: ".$res[0]['DESCRIPCION']." ";
        $palabras_clave =  $res[0]['NOMBRE'] . " " . $res[0]['DESCRIPCION'];

        // A los usuarios del proyecto
        $sqlrol="SELECT cuenta_id
        FROM proyecto_cuenta WHERE proyecto_id ='$proyecto_id' ";
        $sqlrol = reemplazar_vacios($sqlrol);
        $resrol = $this->conector->select2($sqlrol);
        if(count2($resrol)>0) {
            for($i=0; $i<count2($resrol);$i++) {
                $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
            }
        }
        return "OK";
    }

    public function notificacion_presupuesto()
    {
    
        $usuario_id=0;  // las notificaciones las genera el sistema

        // obtiene los datos del proyecto para crear la notificacion
        $modulo_id = 5; // Reportes
        
        $titulo = "Ejecucion presupuesto";
        $usuario_notificacion_id = "";
        $palabras_clave = "ejecucion presupuesto";
        $proyecto_id = "";
        $descripcion = "";

        // hace la consulta
        
        // selecciona los usuarios con rol financiero
        $sqlrol="SELECT rc.cuenta_id, pc.proyecto_id, p.nombre AS proyecto_nombre
        FROM rol_cuenta rc 
        INNER JOIN rol r ON r.id = rc.rol_id 
        INNER JOIN proyecto_cuenta pc ON pc.cuenta_id = rc.cuenta_id
        INNER JOIN proyectos p ON p.id = pc.proyecto_id
        WHERE UPPER(r.nombre) = 'FINANCIERO' AND p.id = '$proyecto_id' ";
        $resrol = $this->conector->select2($sqlrol);
        if(count2($resrol)>0) {
            for($i=0; $i<count2($resrol);$i++) {
                $usuario_notificacion_id = $resrol[$i]['CUENTA_ID'];
                (new Base())->crearNotificacion($titulo, $descripcion, $proyecto_id, $modulo_id, $usuario_notificacion_id, $palabras_clave, $usuario_id);
            }
        }
        return "OK";
    }

}

?>