<?php

namespace App\Command\Drupal;

use App\Command\Command;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DrushCommand extends Command
{
    protected static $defaultName = 'drupal:drush';

    protected static $ttyCommands = [
      'sql:cli',
      'sql-cli',
    ];

    protected function buildHostCommandsCommand(array $host): array
    {
        $command = array_merge($this->getDrush($host), ['list', '--raw']);

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    protected function buildHostCommandOptionsCommand(array $host, string $commandName): array
    {
        $command = array_merge($this->getDrush($host), ['help']);

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    protected function buildCommand(array $host): array
    {
        $command = $this->getDrush($host);

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    protected function configureHostOptions(OptionsResolver $resolver)
    {
        parent::configureHostOptions($resolver);
        $resolver->setDefaults([
            'drush' => null,
            'cwd' => null,
            'uri' => null,
        ]);
    }

    protected function getHosts()
    {
        $hosts = parent::getHosts();

        foreach ($hosts as $name => &$host) {
            if (!isset($host['uri'])) {
                $host['uri'] = 'https://'.$host['host'];
            }
        }

        return $hosts;
    }

    private function getDrush(array $host): array
    {
        $drush = 'drush';
        if (isset($host['drush'])) {
            $drush = $host['drush'];
            $slashIndex = strpos($host['drush'], '/');
            if (0 === $slashIndex || preg_match('/^[a-z-]*docker[ -]compose/', $host['drush'])) {
                // Absolute path
                $drush = $host['drush'];
            } elseif (false !== $slashIndex && isset($host['root'])) {
                // Path relative to site root.
                $drush = $host['root'].'/'.$host['drush'];
            }
        }

        $command = [$drush];
        foreach (['root', 'uri'] as $key) {
            if (isset($host[$key])) {
                $command[] = '--'.$key.'='.$host[$key];
            }
        }

        return $command;
    }
}
