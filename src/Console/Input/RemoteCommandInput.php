<?php

namespace App\Console\Input;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;

class RemoteCommandInput extends ArgvInput
{
    private $remoteArguments = [];

    public function __construct(array $argv = null, InputDefinition $definition = null)
    {
        $argv = $argv ?? $_SERVER['argv'] ?? [];
        // Skip application name and command.
        $index = 2;
        // Skip any options.
        while ($index < \count($argv) && 0 === strpos($argv[$index], '-')) {
            ++$index;
        }
        // Skip the domain name.
        ++$index;

        $commandArgs = \array_slice($argv, 0, $index);
        $this->remoteArguments = \array_slice($argv, $index);

        parent::__construct($commandArgs, $definition);
    }

    public function getRemoteArguments(): array
    {
        return $this->remoteArguments;
    }
}
