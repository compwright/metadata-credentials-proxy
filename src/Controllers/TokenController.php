<?php

namespace Compwright\DockerEc2Metadata\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\Message\Response;

class TokenController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $ttlStr = $request->getHeader('X-aws-ec2-metadata-token-ttl-seconds')[0];

        if ($ttlStr == '') {
            $this->logger->error('Request was missing the X-aws-ec2-metadata-token-ttl-seconds header');
            return new Response(400, ['Content-Type' => 'text/plain'], 'Invalid token');
        }

        $ttl = intval($ttlStr);

        if ($ttl === 0) {
            $this->logger->error(sprintf(
                'Parsing X-aws-ec2-metadata-token-ttl-seconds (%s) failed: could not parse TTL',
                $ttlStr
            ));
            return new Response(400, ['Content-Type' => 'text/plain'], 'Invalid token');
        }

        $timeToken = sprintf('%d', time() + $ttl);

        $encodedToken = base64_encode($timeToken);
        return new Response(200, ['Content-Type' => 'text/plain'], $encodedToken);
    }
}
