<?php

namespace Compwright\DockerEc2Metadata;

use RuntimeException;

class ContainerFactory
{
    public function newFromIp(string $ip): Container
    {
        $domain = gethostbyaddr($ip);

        if ($domain === false || $domain === $ip) {
            throw new RuntimeException('Could not resolve container name from ' . $ip);
        }

        $name = $domain;
        $shortname = explode('.', $name)[0];
        $containerName = sprintf("/%s", $shortname);

        $inspectCommand = sprintf('docker inspect -f json %s', escapeshellarg($containerName));

        try {
            $raw = $this->executeSystemCommand($inspectCommand);
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                sprintf('Could not inspect container %s: "%s"', $containerName, trim($e->getMessage())),
                $e->getCode(),
                $e
            );
        }

        $data = json_decode($raw, null, 512, JSON_THROW_ON_ERROR)[0];
        return new Container($shortname, $data->Config->Env);
    }

    private function executeSystemCommand(string $command): string
    {
        $descriptorspec = [
            0 => ['pipe', 'r'], // STDIN
            1 => ['pipe', 'w'], // STDOUT
            2 => ['pipe', 'w'], // STDERR
        ];

        $proc = proc_open($command, $descriptorspec, $pipes, getcwd() ?: null, null);

        if (is_resource($proc)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            $return_value = proc_close($proc);
            if ($return_value == 0) {
                return $stdout ?: '';
            }
            throw new RuntimeException($stderr ?: 'Exit code ' . $return_value, $return_value);
        }

        throw new RuntimeException('Could not execute command: ' . $command);
    }
}
