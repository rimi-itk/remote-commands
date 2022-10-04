<?php

namespace App\Command\Symfony;

use App\Command\Command;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsoleCommand extends Command
{
    protected static $defaultName = 'symfony:console';

    protected static $ttyCommands = [
        'itk-dev:database:cli',
    ];

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
        $command = $this->getConsole($host);

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    protected function configureHostOptions(OptionsResolver $resolver)
    {
        parent::configureHostOptions($resolver);
        $resolver->setDefaults([
            'console' => null,
            'cwd' => static fn (Options $options) => $options['root'],
        ]);
    }

    private function getConsole(array $host): array
    {
        $console = 'bin/console';
        if (isset($host['console'])) {
            $console = $host['console'];
            $slashIndex = strpos($host['console'], '/');
            if (0 === $slashIndex || preg_match('@(itkdev-)?docker-compose@', $host['console'])) {
                // Absolute path
                $console = $host['console'];
            } elseif (false !== $slashIndex && isset($host['root'])) {
                // Path relative to site root.
                $console = $host['root'].'/'.$host['console'];
            }
        }

        $command = [$console];

        return $command;
    }
}
