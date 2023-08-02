<?php

namespace Compwright\DockerEc2Metadata;

use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandler
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->dispatch($request);
    }
}
