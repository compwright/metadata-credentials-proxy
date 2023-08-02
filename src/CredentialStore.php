<?php

namespace Compwright\DockerEc2Metadata;

use Aws\Credentials\Credentials;
use Aws\Sts\StsClient;

class CredentialStore
{
    private StsClient $stsClient;

    /** @var array<string, Credentials> */
    private array $credentials;

    public function __construct(StsClient $stsClient)
    {
        $this->stsClient = $stsClient;
    }

    public function getStsTokenJSON(string $name, string $arn): string
    {
        $c = $this->credentials[$arn] ?? null;

        if (!$c || $c->isExpired()) {
            $result = $this->stsClient->assumeRole([
                'RoleSessionName' => $name,
                'RoleArn' => $arn,
            ]);
            $c = $this->stsClient->createCredentials($result);
            $this->credentials[$arn] = $c;
        }

        return json_encode([
            'AccessKeyId' => $c->getAccessKeyID(),
            'SecretAccessKey' => $c->getSecretKey(),
            'Token' => $c->getSecurityToken(),
            'Code' => 'Success',
            'Type' => 'AWS-HMAC',
            'Expiration' => date(DATE_RFC3339, $c->getExpiration() ?? 0),
            'LastUpdated' => date(DATE_RFC3339),
        ], JSON_THROW_ON_ERROR);
    }
}
