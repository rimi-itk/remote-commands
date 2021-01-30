<?php

namespace App\Command\Symfony;

use App\Command\Command;

class ConsoleCommand extends Command
{
    protected static $defaultName = 'symfony:console';

    protected function buildHostCommandsCommand(array $host): array
    {
        $command = [$this->getConsole($host), 'list', '--raw'];

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    protected function buildHostCommandOptionsCommand(array $host, string $commandName): array
    {
        $command = [$this->getConsole($host), '--help', $commandName, '--raw'];

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    protected function buildCommand(array $host): array
    {
        $command = [$this->getConsole($host)];

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    private function getConsole(array $host)
    {
        return $host['root'].'/bin/console';
    }
}
