<?php

namespace App\Command\Symfony;

use App\Command\Command;

class ConsoleCommand extends Command
{
    protected static $defaultName = 'symfony:console';

    protected function buildSiteCommandsCommand(array $site): array
    {
        $command = [$this->getConsole($site), 'list', '--raw'];

        return array_merge($command, $this->getArgumentsAndOptions());
    }

    protected function buildSiteCommandOptionsCommand(array $site, string $commandName): array
    {
        $command = [$this->getConsole($site), '--help', $commandName, '--raw'];

        return array_merge($command, $this->getArgumentsAndOptions());
    }

    protected function buildCommand(array $site): array
    {
        $command = [$this->getConsole($site)];

        return array_merge($command, $this->getArgumentsAndOptions());
    }

    private function getConsole(array $site)
    {
        return $site['root'].'/bin/console';
    }
}
