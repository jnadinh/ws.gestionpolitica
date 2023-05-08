<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Middleware\UserAuthMiddleware;
use App\Middleware\UserAuthMiddleware2;
use App\Middleware\UserAuthMiddlewareSocio;


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

    $app->post('/iniciar_sesion', \App\Api\Login\Login::class . ':iniciarSesion');
    $app->options('/iniciar_sesion', \App\Api\Login\Login::class . ':iniciarSesion');


    // con validacion de token super admin
    $app->group('', function (RouteCollectorProxy $groupSuperAdmin) {
        // superAdmin es el administrador de toda la aplicacion el que crea los esquemas
        $groupSuperAdmin->post('/cerrar_sesion_super_admin', \App\Api\LoginSuperAdmin\LoginSuperAdmin::class . ':cerrarSesionSuperAdmin');
        $groupSuperAdmin->post('/validar_token_super_admin', \App\Api\LoginSuperAdmin\LoginSuperAdmin::class . ':validarTokenSuperAdmin');
        $groupSuperAdmin->post('/cambiar_clave_super_admin', \App\Api\LoginSuperAdmin\LoginSuperAdmin::class . ':cambiarClaveSuperAdmin');

        $groupSuperAdmin->post('/obtener_parametros', \App\Api\Parametros\Parametros::class . ':obtenerParametros');
        $groupSuperAdmin->post('/editar_parametros', \App\Api\Parametros\Parametros::class . ':editarParametros');

        $groupSuperAdmin->post('/obtener_usuarios', \App\Api\Usuario\Usuario::class . ':obtenerUsuarios');
        $groupSuperAdmin->post('/crear_usuario', \App\Api\Usuario\Usuario::class . ':crearUsuario');
        $groupSuperAdmin->post('/editar_usuario', \App\Api\Usuario\Usuario::class . ':editarUsuario');

        $groupSuperAdmin->post('/obtener_esquemas', \App\Api\Esquema\Esquema::class . ':obtenerEsquemas');
        $groupSuperAdmin->post('/editar_esquema', \App\Api\Esquema\Esquema::class . ':editarEsquema');
        $groupSuperAdmin->post('/crear_esquema', \App\Api\Esquema\Esquema::class . ':crearEsquema');

        $groupSuperAdmin->post('/obtener_recargas', \App\Api\Recarga\Recarga::class . ':obtenerRecargas');
        $groupSuperAdmin->post('/crear_recarga', \App\Api\Recarga\Recarga::class . ':crearRecarga');
        $groupSuperAdmin->post('/editar_recarga', \App\Api\Recarga\Recarga::class . ':editarRecarga');
        $groupSuperAdmin->post('/eliminar_recarga', \App\Api\Recarga\Recarga::class . ':eliminarRecarga');

        $groupSuperAdmin->post('/obtener_departamentos_sa', \App\Api\Listado\Listado::class . ':obtenerDepartamentos');
        $groupSuperAdmin->post('/obtener_municipios_sa', \App\Api\Listado\Listado::class . ':obtenerMunicipios');
        $groupSuperAdmin->post('/obtener_corporaciones_sa', \App\Api\Listado\Listado::class . ':obtenerCorporaciones');
        $groupSuperAdmin->post('/obtener_puestos_sa', \App\Api\Listado\Listado::class . ':obtenerPuestos');

    })->add(UserAuthMiddleware2::class);


    // con validacion de token

    // valida por modulos
    $app->group('', function (RouteCollectorProxy $misReferidos) {

        $misReferidos->post('/obtener_departamentos_sa_xx', \App\Api\Listado\Listado::class . ':obtenerDepartamentos');

    })->add(UserAuthMiddleware::class . ':Socio');


    $app->group('', function (RouteCollectorProxy $group) {

        $group->post('/cerrar_sesion', \App\Api\Login\Login::class . ':cerrarSesion');
        $group->post('/validar_token', \App\Api\Login\Login::class . ':validarToken');
        $group->post('/cambiar_clave', \App\Api\Login\Login::class . ':cambiarClave');

        // queda de ejemplo modulo socio
        $group->post('/crear_socio', \App\Api\Socio\Socio::class . ':crearSocio');
        $group->post('/obtener_socios', \App\Api\Socio\Socio::class . ':obtenerSocios');
        $group->post('/editar_socio', \App\Api\Socio\Socio::class . ':editarSocio');
        $group->post('/eliminar_socio', \App\Api\Socio\Socio::class . ':eliminarSocio');
        $group->post('/obtener_socios_eliminados', \App\Api\Socio\Socio::class . ':obtenerSociosEliminados');
        $group->post('/habilitar_socio', \App\Api\Socio\Socio::class . ':habilitarSocio');

        // $group->post('/obtener_archivos_generales', \App\Api\Archivo\Archivo::class . ':obtenerArchivosGenerales');
        // $group->post('/obtener_archivos', \App\Api\Archivo\Archivo::class . ':obtenerArchivos');
        // $group->post('/crear_archivo', \App\Api\Archivo\Archivo::class . ':crearArchivo');
        // $group->post('/editar_archivo', \App\Api\Archivo\Archivo::class . ':editarArchivo');
        // $group->post('/eliminar_archivo', \App\Api\Archivo\Archivo::class . ':eliminarArchivo');

        // $group->post('/obtener_publicaciones', \App\Api\Publicacion\Publicacion::class . ':obtenerPublicaciones');
        // $group->post('/crear_publicacion', \App\Api\Publicacion\Publicacion::class . ':crearPublicacion');
        // $group->post('/editar_publicacion', \App\Api\Publicacion\Publicacion::class . ':editarPublicacion');    // eliminar edita eliminado=true

        /////////////////////////////////////////////////////////////////////////////////

        // $group->post('/crear_rol', \App\Api\Usuario\Rol::class . ':crearRol');
        // $group->post('/editar_rol', \App\Api\Usuario\Rol::class . ':editarRol');
        // $group->post('/obtener_roles', \App\Api\Usuario\Rol::class . ':obtenerRoles');
        // $group->post('/obtener_rol', \App\Api\Usuario\Rol::class . ':obtenerRol');
        // $group->post('/deshabilitar_rol', \App\Api\Usuario\Rol::class . ':deshabilitarRol');

    })->add(UserAuthMiddleware::class);

};
