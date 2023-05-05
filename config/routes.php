<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\UserAuthMiddleware;
use App\Middleware\UserAuthMiddleware2;


return function (App $app) {


    $app->post('/notes', NoteCreateAction::class);
    // Allow preflight requests for /notes
    $app->options('/notes', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $response;
    });

    $app->get('/', \App\Api\Home\Home::class);
    $app->get('/info', \App\Api\Home\Home::class . ':info');

    $app->post('/archivo/subir', \App\Api\Archivo\Archivo::class . ':uploadFile');
    $app->get('/archivo/descargar_archivo/{tabla}/{id_archivo}/{token}', \App\Api\Archivo\Archivo::class . ':downloadArchivo'); // descarga con id
    // http://localhost:8080/archivo/descargar_archivo/1/1/token    tabla 1 archivos esquemas  tabla 2 public (generales)

    $app->get('/archivo/descarga/{token}/{nombre}/', \App\Api\Archivo\Archivo::class . ':descargaArchivo');     // descarga con nombre (cualquier tipo de archivo, no solo imagen)
    // http://localhost:8080/archivo/descarga/token/6e92a1dd68963c43.pdf    el punto en la url da error, no funciona

    $app->post('/iniciar_sesion_super_admin', \App\Api\LoginSuperAdmin\LoginSuperAdmin::class . ':iniciarSesionSuperAdmin');
    $app->options('/iniciar_sesion_super_admin', \App\Api\LoginSuperAdmin\LoginSuperAdmin::class . ':iniciarSesionSuperAdmin');

    // con validacion de token super admin
    $app->group('', function (RouteCollectorProxy $group2) {
        // superAdmin es el administrador de toda la aplicacion el que crea los esquemas
        $group2->post('/cerrar_sesion_super_admin', \App\Api\LoginSuperAdmin\LoginSuperAdmin::class . ':cerrarSesionSuperAdmin');
        $group2->post('/validar_token_super_admin', \App\Api\LoginSuperAdmin\LoginSuperAdmin::class . ':validarTokenSuperAdmin');
        $group2->post('/cambiar_clave_super_admin', \App\Api\LoginSuperAdmin\LoginSuperAdmin::class . ':cambiarClaveSuperAdmin');

        $group2->post('/obtener_parametros', \App\Api\Parametros\Parametros::class . ':obtenerParametros');
        $group2->post('/editar_parametros', \App\Api\Parametros\Parametros::class . ':editarParametros');
        $group2->post('/crear_firma', \App\Api\Parametros\Parametros::class . ':crearFirma');
        $group2->post('/obtener_firmas', \App\Api\Parametros\Parametros::class . ':obtenerFirmas');
        $group2->post('/editar_firma', \App\Api\Parametros\Parametros::class . ':editarFirma');
        $group2->post('/eliminar_firma', \App\Api\Parametros\Parametros::class . ':eliminarFirma');

        $group2->post('/obtener_usuarios', \App\Api\Usuario\Usuario::class . ':obtenerUsuarios');
        $group2->post('/crear_usuario', \App\Api\Usuario\Usuario::class . ':crearUsuario');
        $group2->post('/editar_usuario', \App\Api\Usuario\Usuario::class . ':editarUsuario');

        $group2->post('/obtener_esquemas', \App\Api\Esquemas\Esquemas::class . ':obtenerEsquemas');
        $group2->post('/editar_esquema', \App\Api\Esquemas\Esquemas::class . ':editarEsquema');
        $group2->post('/crear_esquema', \App\Api\Esquemas\Esquemas::class . ':crearEsquema');

        $group2->post('/obtener_pagos_esquemas', \App\Api\Esquemas\Esquemas::class . ':obtenerPagosEsquemas');
        $group2->post('/crear_pago_esquema', \App\Api\Esquemas\Esquemas::class . ':crearPagoEsquema');
        $group2->post('/editar_pago_esquema', \App\Api\Esquemas\Esquemas::class . ':editarPagoEsquema');
        $group2->post('/eliminar_pago_esquema', \App\Api\Esquemas\Esquemas::class . ':eliminarPagoEsquema');

    })->add(UserAuthMiddleware2::class);

    // con validacion de token
    $app->group('', function (RouteCollectorProxy $group) {
        // superAdmin es el administrador de toda la aplicacion el que crea los esquemas
        $group->post('/cerrar_sesion', \App\Api\Login\Login::class . ':cerrarSesion');
        $group->post('/validar_token', \App\Api\Login\Login::class . ':validarToken');
        $group->post('/cambiar_clave', \App\Api\Login\Login::class . ':cambiarClave');

        // queda de ejemplo
        $group->post('/crear_socio', \App\Api\Socio\Socio::class . ':crearSocio');
        $group->post('/obtener_socios', \App\Api\Socio\Socio::class . ':obtenerSocios');
        $group->post('/editar_socio', \App\Api\Socio\Socio::class . ':editarSocio');
        $group->post('/eliminar_socio', \App\Api\Socio\Socio::class . ':eliminarSocio');
        $group->post('/obtener_socios_eliminados', \App\Api\Socio\Socio::class . ':obtenerSociosEliminados');
        $group->post('/habilitar_socio', \App\Api\Socio\Socio::class . ':habilitarSocio');

        $group->post('/obtener_archivos_generales', \App\Api\Archivo\Archivo::class . ':obtenerArchivosGenerales');

        $group->post('/obtener_archivos', \App\Api\Archivo\Archivo::class . ':obtenerArchivos');
        $group->post('/crear_archivo', \App\Api\Archivo\Archivo::class . ':crearArchivo');
        $group->post('/editar_archivo', \App\Api\Archivo\Archivo::class . ':editarArchivo');
        $group->post('/eliminar_archivo', \App\Api\Archivo\Archivo::class . ':eliminarArchivo');

        $group->post('/obtener_publicaciones', \App\Api\Publicacion\Publicacion::class . ':obtenerPublicaciones');
        $group->post('/crear_publicacion', \App\Api\Publicacion\Publicacion::class . ':crearPublicacion');
        $group->post('/editar_publicacion', \App\Api\Publicacion\Publicacion::class . ':editarPublicacion');    // eliminar edita eliminado=true


        /////////////////////////////////////////////////////////////////////////////////

        $group->post('/crear_rol', \App\Api\Usuario\Rol::class . ':crearRol');
        $group->post('/editar_rol', \App\Api\Usuario\Rol::class . ':editarRol');
        $group->post('/obtener_roles', \App\Api\Usuario\Rol::class . ':obtenerRoles');
        $group->post('/obtener_rol', \App\Api\Usuario\Rol::class . ':obtenerRol');
        $group->post('/deshabilitar_rol', \App\Api\Usuario\Rol::class . ':deshabilitarRol');

    })->add(UserAuthMiddleware::class);


};
