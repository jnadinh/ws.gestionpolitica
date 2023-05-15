<?php
namespace App\Api\Home;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action
 */
final class Imagen
{
    /**
     * Invoke.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {



        $ip = $_SERVER['REMOTE_ADDR'];
        /*FUNCIÓN QUE RECOGE LA IP DEL USUARIO QUE NAVEGA EN ESTA PÁGINA*/

        if ( $ip=="67.88.123.45" ) { ?>
        <h1>ERES UN PESADO LARGO DE MI WEB</h1>
        /*Si la ip es la del usuario pesado le muestro en pantalla este título*/
        <?php }else{ ?>

        <h1>BIENVENIDO A MI WEB</h1>

        /*si la ip del usuario no es la del pesado, le muestro este otro título*/
        <html>
            <head>
                <title>Mi página de ejemplo</title>
            </head>
            <body>
            <div class="figure">
                <img src="images/dinosaur.jpg"
                    alt="La cabeza y el torso de un esqueleto de dinosaurio;
                        tiene una cabeza grande con dientes largos y afilados"
                    width="400"
                    height="341">

                <p>Exposición de un T-Rex en el museo de la Universidad de Manchester.</p>
            </div>
        </body>
        </html>


        <?php }


        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => 'Bienvenido al WS de Gestion Politica' );
        $response->getBody()->write((string)json_encode($respuesta));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }


}
