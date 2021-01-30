<?php

namespace App\Command\Drupal;

use App\Command\Command;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DrushCommand extends Command
{
    protected static $defaultName = 'drupal:drush';

    protected function buildHostCommandsCommand(array $host): array
    {
        throw new \RuntimeException(__METHOD__);
    }

    protected function buildHostCommandOptionsCommand(array $host, string $commandName): array
    {
        throw new \RuntimeException(__METHOD__);
    }

    protected function buildCommand(array $host): array
    {
        $drush = 'drush';
        if (isset($host['drush'])) {
            $drush = $host['drush'];
            $slashIndex = strpos($host['drush'], '/');
            if (0 === $slashIndex) {
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

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    protected function configureHostOptions(OptionsResolver $resolver)
    {
        parent::configureHostOptions($resolver);
        $resolver->setDefaults([
            'drush' => null,
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
}
