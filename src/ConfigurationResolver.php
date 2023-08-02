<?php

namespace Compwright\DockerEc2Metadata;

use Aws\Configuration\ConfigurationResolver as AwsConfigurationResolver;

class ConfigurationResolver
{
    /**
     * As of aws/aws-sdk-php v3.277.1, a bug exists in ConfigurationResolver::ini() where the
     * `~/.aws/config` file stores profile-specific configs under the section `[profile <profile-name>]`,
     * but the method tries to access them under `[<profile-name>]`.
     *
     * This method is needed to work around that bug, and should be future-proof whenever the bug
     * is fixed in aws/aws-sdk-php since precedence is given to ConfigurationResolver::resolve().
     */
    public function resolve(string $key, string $expectedType = 'string'): ?string
    {
        return AwsConfigurationResolver::resolve($key, null, $expectedType)
            ?? $this->attemptIniFileNamedProfileKeyBugWorkaround($key, $expectedType);
    }

    private function attemptIniFileNamedProfileKeyBugWorkaround(string $key, string $expectedType = 'string'): ?string
    {
        $profile = AwsConfigurationResolver::env('profile', 'string');
        if (!empty($profile)) {
            return AwsConfigurationResolver::ini($key, $expectedType, "profile $profile");
        }
        return null;
    }
}
