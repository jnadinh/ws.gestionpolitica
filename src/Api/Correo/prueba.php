#!/usr/bin/php -e
<?php

require_once __DIR__ . '/../../Base/Base.php';

print_r("Creando notificaciones Actividades \n");
(new Base())->notificacionActividadesValidar();
print_r("Creando notificaciones Pqrs \n");
(new Base())->notificacionPqrs();

// esto van el archivo .sh para alpha
// php /home/ciatel/web/ciatel.ws.alpha.juemichica.com/public_html/src/componentes/correos/prueba.php
// esto van el archivo .sh para local
// php /var/www/html/src/componentes/correos/prueba.php


?>