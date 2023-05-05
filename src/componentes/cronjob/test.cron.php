<?php

$time = date("G:i:s");
$entry = "Información guardada a las $time.\n";
$file = __DIR__."/test.cron.txt";
$open = fopen($file,"a");

if ( $open ) {
	fwrite($open,$entry);
	fclose($open);
	print_r("Información guardada a las $time.\n");
}

// esto va en el archivo .sh para alpha
// php /home/ciatel/web/ciatel.ws.alpha.juemichica.com/public_html/src/componentes/cronjob/test.cron.php
// esto van el archivo .sh para local
// php /var/www/html/src/componentes/cronjob/test.cron.php

// entra al docker y ejecuta el archivo .sh
// docker exec -i -t e3045689892c /bin/bash
// sh /var/www/html/src/componentes/cronjob/test.cron.sh

?>