<?php

namespace Compwright\DockerEc2Metadata\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use React\Http\Message\Response;

class CheckTokenExpirationMiddleware implements MiddlewareInterface
{
    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getHeader('X-aws-ec2-metadata-token')[0] ?? null;

        if ($token) {
            $timeToken = base64_decode($token);

            if ($timeToken === false) {
                return new Response(401);
            }

            $t = intval($timeToken);

            if ($t === 0) {
                return new Response(401);
            }

            if ($t < time()) {
                return new Response(401);
            }
        }

        return $handler->handle($request);
    }
}
