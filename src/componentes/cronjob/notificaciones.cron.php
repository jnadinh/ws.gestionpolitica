#!/usr/bin/php -e
<?php

require_once __DIR__.'/Notificaciones.php';

print_r("Creando notificaciones notificacionActividades \n");
(new Notificaciones())->notificacionActividades();

print_r("Creando notificaciones notificacionCotizaciones \n");
(new Notificaciones())->notificacionCotizaciones();

print_r("Creando notificaciones notificacionVisitadores \n");
(new Notificaciones())->notificacionVisitadores();

print_r("Creando notificaciones notificacionCompromisos \n");
(new Notificaciones())->notificacionCompromisos();

print_r("Creando notificaciones notificacionPlanillas \n");
(new Notificaciones())->notificacionPlanillas();

print_r("Creando notificaciones notificacionActividadesValidar \n");
(new Notificaciones())->notificacionActividadesValidar();

print_r("Creando notificaciones notificacionPlanillasValidar \n");
(new Notificaciones())->notificacionPlanillasValidar();

print_r("Creando notificaciones notificacionVerificacionCumplimiento \n");
(new Notificaciones())->notificacionVerificacionCumplimiento();

print_r("Creando notificaciones notificacionVerificacionContractual \n");
(new Notificaciones())->notificacionVerificacionContractual();

print_r("Creando notificaciones Pqrs \n");
(new Notificaciones())->notificacionPqrs();

print_r("Creando notificaciones notificacionRiesgos \n");
(new Notificaciones())->notificacionRiesgos();

print_r("Creando notificaciones notificacion_presupuesto \n");
(new Notificaciones())->notificacion_presupuesto();

// esto van el archivo .sh para alpha
// php /home/ciatel/web/ciatel.ws.alpha.juemichica.com/public_html/src/componentes/cronjob/notificaciones.cron.php
// esto van el archivo .sh para local
// php /var/www/html/src/componentes/cronjob/notificaciones.cron.php


?>