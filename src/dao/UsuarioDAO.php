<?php

class UsuarioDAO {
    
    public  $id_usuario;
    public  $correo_cuenta;
    public  $nombre_completo;
    public  $tipo_documento;
    public  $num_documento;
    public  $ciudad_id;
    public  $direccion;
    public  $telefono;
    public  $correo_personal;
    public  $correo_empresa;
    public  $tipo_actividad_id;
    public  $area_gestion_id;
    public  $estado;
    public  $fecha_creado;
    public  $permisos;
    public  $perfil_nombre;
    public  $perfil_nombre_nuevo;
    public  $firma_nombre;
    public  $firma_nombre_nuevo;
    public  $archivo;
    
    // public function get_correo_cuenta() {
    //     return $correo_cuenta;
    // }

    // public function get_tipo_documento() {
    //     return $tipo_documento;
    // }

    // public function get_num_documento() {
    //     return $num_documento;
    // }
    
    // public function get_ciudad_id() {
    //     return $ciudad_id;
    // }

    // public String get_direccion() {
    //     return direccion;
    // }

    // public String get_nombre_completo() {
    //     return nombre_completo;
    // }

    // public String get_telefono() {
    //     return telefono;
    // }

    // public String get_correo_personal() {
    //     return correo_personal;
    // }

    // public String get_correo_empresa() {
    //     return correo_empresa;
    // }
    
    // public String get_tipo_actividad_id() {
    //     return tipo_actividad_id;
    // }

    // public String get_area_gestion_id() {
    //     return area_gestion_id;
    // }

    // public String get_permisos() {
    //     return permisos;
    // }
    
    // public String get_id_usuario() {
    //     return id_usuario;
    // }

    // public String get_fecha_creado() {
    //     return fecha_creado;
    // }

    // public String get_estado() {
    //     return estado;
    // }
    

    // public String get_perfil_nombre() {
    //     return perfil_nombre;
    // }
    
    
    // public String get_perfil_nombre_nuevo() {
    //     return perfil_nombre_nuevo;
    // }
    
    
    // public String get_firma_nombre() {
    //     return firma_nombre;
    // }
    
    
    // public String get_firma_nombre_nuevo() {
    //     return firma_nombre_nuevo;
    // }

    // public String get_archivo() {
    //     return archivo;
    // }

    
    
    // @Override
    // public String toString() {
    //     return "Usuario [id=" + id_usuario + ", nombre=" + nombre_completo + "]";
    // }
    
    // public static boolean comprobarCampo(String campo){
    //     // Funcion para validar los campos de la clase 
    //     // obtiene la clase
    //     Class clase = UsuarioDAO.class;
    //     // obtiene los campos de la clase en un array
    //     Field[] fields = clase.getDeclaredFields();
    //     // hace un ciclo validando los campos
    //     for (Field field : fields) {
    //         // si el campo que recibe esta en la clase devuelve tru
    //         if(campo.toUpperCase().equals(field.getName().toUpperCase())){
    //             return true;
    //         }
    //     }
    //     // si el campo no existe en la clase devuelve false
    //     return false;
    // }
    
    public function comprobarCampo($campo){
        // Funcion para validar los campos de la clase 
        // obtiene la clase
        // obtiene los campos de la clase en un array
        // Field[] fields = clase.getDeclaredFields();
        // hace un ciclo validando los campos
        // for (Field field : fields) {
        //     // si el campo que recibe esta en la clase devueve tru
        //     if(campo.toUpperCase().equals(field.getName().toUpperCase())){
        //         return true;
        //     }
        // }
        // si el campo no existe en la clase devuelve false
        return false;
    }    

}

?>