<?php

namespace Compwright\DockerEc2Metadata\Controllers;

use Compwright\DockerEc2Metadata\CredentialStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\Message\Response;
use Throwable;

class DevCredentialController
{
    private CredentialStore $credentialStore;

    private LoggerInterface $logger;

    public function __construct(CredentialStore $credentialStore, LoggerInterface $logger)
    {
        $this->credentialStore = $credentialStore;
        $this->logger = $logger;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $sts = $this->credentialStore->getStsTokenJSON(
                $request->getAttribute('containerName'),
                $request->getAttribute('role')
            );
            return new Response(200, ['Content-Type' => 'application/json'], $sts);
        } catch (Throwable $e) {
            $this->logger->error($e);
            return new Response(
                500,
                ['Content-Type' => 'text/plain'],
                sprintf('Internal Error: %s', $e->getMessage())
            );
        }
    }
}
