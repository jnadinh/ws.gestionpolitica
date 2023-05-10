#!/usr/bin/php -e
<?php
print_r($_SERVER['argv']);

// asi se ejecuta este archivo por consola:
// /Documentos/proyectos/ciatel.ws.php/src/src/Api/Home$ php hola.php hola a todos "de nuevo :)"
// asi devuelve:
// Array
// (
//     [0] => hola.php
//     [1] => hola
//     [2] => a
//     [3] => todos
//     [4] => de nuevo :)
// )

/*
asi entra al docker por linea de comando
docker exec -i -t 4d160cefe12f /bin/bash
el nombre del docker lo obtiene con 
docker ps
desde docker este archivo esta en esta ruta:
/var/www/html/src/componentes/correos
cd /var/www/html/src/componentes/correos
despues de ir a la ruta ejecuta el archivo php asi
php hola.php hola a todos "de nuevo :)"
y devuelve:
Array
(
    [0] => hola.php
    [1] => hola
    [2] => a
    [3] => todos
    [4] => de nuevo :)
)
*/

?>