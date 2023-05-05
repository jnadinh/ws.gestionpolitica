<?php

namespace App\Test\TestCase\Responder;

use App\Responder\Responder;
use App\Test\TestCase\AppTestTrait;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

/**
 * Test.
 */
class ResponderTest extends TestCase
{
    use AppTestTrait;

    /**
     * Test.
     *
     * @return void
     */
    public function testEncodeJson(): void
    {
        $responder = $this->container->get(Responder::class);

        $response = $responder->json(new Response(), ['success' => true]);

        $this->assertSame('{"success":true}', (string)$response->getBody());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testRedirectUrl(): void
    {
        $responder = $this->container->get(Responder::class);

        $request = $this->createRequest('GET', '/');
        $response = $responder->redirect(new Response(), 'https://www.example.com/');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://www.example.com/', $response->getHeaderLine('Location'));
        $this->assertSame('', (string)$response->getBody());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testRedirectRouteName(): void
    {
        $responder = $this->container->get(Responder::class);

        $this->app->get('/foo', function ($request, $response) use ($responder) {
            return $responder->redirect($response, 'foo');
        })->setName('foo');

        $request = $this->createRequest('GET', '/foo');
        $response = $this->app->handle($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/foo', $response->getHeaderLine('Location'));
        $this->assertSame('', (string)$response->getBody());
    }
}
