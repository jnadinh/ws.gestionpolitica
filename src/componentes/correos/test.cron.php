<?php

$time = date("G:i:s");
$entry = "test de /correos. Información guardada a las $time.\n";
$file = __DIR__."/test.cron.txt";
$open = fopen($file,"a");
// var_dump("open: ", $open, "\n");

if ( $open ) {
	fwrite($open,$entry);
	fclose($open);
	print_r($entry);
}

// esto va en el archivo .sh para alpha
// php /home/ciatel/web/ciatel.ws.alpha.juemichica.com/public_html/src/componentes/cronjob/test.cron.php
// esto van el archivo .sh para local
// php /var/www/html/src/componentes/cronjob/test.cron.php

?>