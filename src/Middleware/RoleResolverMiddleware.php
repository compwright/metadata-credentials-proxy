<?php

namespace Compwright\DockerEc2Metadata\Middleware;

use Compwright\DockerEc2Metadata\ContainerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use React\Http\Message\Response;
use Throwable;

class RoleResolverMiddleware implements MiddlewareInterface
{
    private ContainerFactory $containerFactory;

    private LoggerInterface $logger;

    private ?string $defaultRole = null;

    public function __construct(ContainerFactory $containerFactory, LoggerInterface $logger, ?string $defaultRole = null)
    {
        $this->containerFactory = $containerFactory;
        $this->logger = $logger;
        $this->defaultRole = $defaultRole;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'];

        try {
            $container = $this->containerFactory->newFromIp($ip);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            return new Response(500, ['Content-Type' => 'text/plain'], 'Internal Error');
        }

        if (!$container->hasEnvValue('IAM_ROLE')) {
            $this->logger->warning(sprintf(
                'No IAM_ROLE found for %s (%s), using default role (%s)',
                $ip,
                $container->getName(),
                $this->defaultRole
            ));
        }

        $role = $container->hasEnvValue('IAM_ROLE')
            ? $container->getEnvValue('IAM_ROLE')
            : $this->defaultRole;

        if (!$role) {
            $this->logger->error(sprintf(
                'No IAM_ROLE found for %s (%s)',
                $ip,
                $container->getName()
            ));
            return new Response(500, ['Content-Type' => 'text/plain'], 'Internal Error');
        }

        $this->logger->notice(sprintf(
            'GET: %s (from %s) (container=%s / role=%s) (%s)',
            $request->getUri()->getPath(),
            $ip,
            $container->getName(),
            $role,
            $request->getHeader('User-Agent')[0]
        ));

        // Attach containerName and role to request
        $request = $request
            ->withAttribute('containerName', $container->getName())
            ->withAttribute('role', $role);

        return $handler->handle($request);
    }
}
