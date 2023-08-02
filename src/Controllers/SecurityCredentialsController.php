<?php

namespace Compwright\DockerEc2Metadata\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

class SecurityCredentialsController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'text/plain'], 'dev');
    }
}
