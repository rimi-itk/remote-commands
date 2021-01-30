<?php

namespace App\Command\Drupal;

use App\Command\Command;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DrushCommand extends Command
{
    protected static $defaultName = 'drupal:drush';

    protected function buildSiteCommandsCommand(array $site): array
    {
        throw new \RuntimeException(__METHOD__);
    }

    protected function buildSiteCommandOptionsCommand(array $site, string $commandName): array
    {
        throw new \RuntimeException(__METHOD__);
    }

    protected function buildCommand(array $site): array
    {
        $drush = 'drush';
        if (isset($site['drush'])) {
            $drush = $site['drush'];
            $slashIndex = strpos($site['drush'], '/');
            if (0 === $slashIndex) {
                // Absolute path
                $drush = $site['drush'];
            } elseif (false !== $slashIndex && isset($site['root'])) {
                // Path relative to site root.
                $drush = $site['root'].'/'.$site['drush'];
            }
        }

        $command = [$drush];
        foreach (['root', 'uri'] as $key) {
            if (isset($site[$key])) {
                $command[] = '--'.$key.'='.$site[$key];
            }
        }

        return array_merge($command, $this->getRemoteOptionsAndArguments());
    }

    protected function configureSiteOptions(OptionsResolver $resolver)
    {
        parent::configureSiteOptions($resolver);
        $resolver->setDefaults([
            'drush' => null,
            'uri' => null,
        ]);
    }

    protected function getSites()
    {
        $sites = parent::getSites();

        foreach ($sites as $name => &$site) {
            if (!isset($site['uri'])) {
                $site['uri'] = 'https://'.$site['host'];
            }
        }

        return $sites;
    }
}
