<?php

namespace App\Command\Symfony;

use App\Command\Command;

class ConsoleCommand extends Command
{
    protected static $defaultName = 'symfony:console';

    protected function buildCommand(array $site): array
    {
        $command = [$site['root'].'/bin/console'];

        return array_merge($command, $this->getArgumentsAndOptions());
    }
}
