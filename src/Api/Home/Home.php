<?php
namespace App\Api\Home;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action
 */
final class Home
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
        $respuesta = array('CODIGO' => 1, 'MENSAJE' => 'OK', 'DATOS' => 'Bienvenido al WS de Gestion Politica' );
        $response->getBody()->write((string)json_encode($respuesta));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function info(ServerRequestInterface $request, ResponseInterface $response)
    {
        phpinfo();
        $response->getBody()->write("OK");
        return $response->withHeader('allow','Content-Type', 'application/json')->withStatus(200);
    }

}
