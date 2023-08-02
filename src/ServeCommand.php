<?php

namespace Compwright\DockerEc2Metadata;

use React\Socket\SocketServer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommand
{
    private ServerFactory $serverFactory;

    public function __construct(ServerFactory $serverFactory)
    {
        $this->serverFactory = $serverFactory;
    }

    public function __invoke(InputInterface $input, OutputInterface $output): void
    {
        $defaultRole = $input->hasArgument('defaultRole')
            ? $input->getArgument('defaultRole')
            : getenv('DEFAULT_IAM_ROLE');

        $logger = new ConsoleLogger($output);

        if ($defaultRole) {
            $logger->notice(sprintf('Using %s as default role', $defaultRole));
        }

        $http = $this->serverFactory->new($logger, $defaultRole ?: null);

        $bind = $input->getOption('listen') . ':' . $input->getOption('port');

        $logger->notice(sprintf('Listening on %s', $bind));

        $http->listen(
            new SocketServer($bind)
        );
    }
}
