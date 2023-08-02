<?php

namespace Compwright\DockerEc2Metadata;

use Aws\Sts\StsClient;
use League\Route\Router;
use Psr\Log\LoggerInterface;
use React\Http\HttpServer;
use RuntimeException;

class ServerFactory
{
    private ConfigurationResolver $configResolver;

    public function __construct(ConfigurationResolver $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    public function new(LoggerInterface $logger, string $defaultRole = null): HttpServer
    {
        $region = $this->configResolver->resolve('region');

        if (!$region) {
            throw new RuntimeException('Could not resolve region');
        }

        $credentialStore = new CredentialStore(
            new StsClient([
                'version' => '2011-06-15',
                'region' => $region,
            ]),
        );

        $containerFactory = new ContainerFactory();

        $router = new Router();

        $router->middleware(
            new Middleware\RoleResolverMiddleware($containerFactory, $logger, $defaultRole)
        );

        $router->middleware(
            new Middleware\CheckTokenExpirationMiddleware()
        );

        $router->map(
            'GET',
            '/latest/meta-data/iam/security-credentials/',
            new Controllers\SecurityCredentialsController()
        );

        $router->map(
            'GET',
            '/latest/meta-data/iam/security-credentials',
            new Controllers\SecurityCredentialsController()
        );

        $router->map(
            'GET',
            '/latest/meta-data/iam/security-credentials/dev',
            new Controllers\DevCredentialController($credentialStore, $logger)
        );

        $router->map(
            'GET',
            '/latest/dynamic/instance-identity/document',
            new Controllers\IdentityDocumentController($region)
        );

        $router->map(
            'PUT',
            '/latest/api/token',
            new Controllers\TokenController($logger)
        );

        $handler = new RequestHandler($router);

        return new HttpServer($handler);
    }
}
