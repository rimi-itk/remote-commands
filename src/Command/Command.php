<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

abstract class Command extends BaseCommand
{
    protected static $configPrefix;

    protected $dynamicOptions = [];

    protected function configure()
    {
        $this
            ->addArgument('site')
            ->addArgument('arguments and options', InputArgument::IS_ARRAY)
            ->addOption('list-sites');

        // @see https://stackoverflow.com/a/39400593
        $this->setDefinition(new class($this->getDefinition(), $this->dynamicOptions) extends InputDefinition {
            protected $dynamicOptions = [];

            public function __construct(InputDefinition $definition, array &$dynamicOptions)
            {
                parent::__construct();
                $this->setArguments($definition->getArguments());
                $this->setOptions($definition->getOptions());
                $this->dynamicOptions = &$dynamicOptions;
            }

            public function getOption($name)
            {
                if (!parent::hasOption($name)) {
                    $this->addOption(new InputOption($name, $name, InputOption::VALUE_OPTIONAL));
                    $this->dynamicOptions[] = $name;
                }

                return parent::getOption($name);
            }

            public function hasOption($name)
            {
                return true;
            }
        });
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('list-sites')) {
            $sites = $this->getSites();
            $output->writeln(Yaml::dump($sites));

            return 0;
        }
        $name = $input->getArgument('site');
        if (null === $name) {
            throw new RuntimeException('Not enough arguments (missing: "site").');
        }
        $site = $this->getSite($name);

        $command = $this->buildCommand($site);

        $this->runOnSite($site['host'], $command, null, null, $input->getStream());

        return 0;
    }

    abstract protected function buildCommand(array $site): array;

    protected function getArguments(): array
    {
        return array_filter($this->getArgumentsAndOptions(), static function (string $token) {
            return 0 !== strpos($token, '-');
        });
    }

    protected function getArgumentsAndOptions(): array
    {
        // Remove script, command and site.
        $args = \array_slice($_SERVER['argv'], 3);

        return array_filter($args, static function (string $arg) {
            // Only options starting with two dashes are supported.
            return preg_match('/^([^-]|--)[a-z0-9]/i', $arg);
        });
    }

    protected function runOnSite(string $site, array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60)
    {
        $command = array_map('escapeshellarg', $command);
        array_unshift($command, 'ssh', '-t', $site);

        $process = new Process($command, $cwd, $env, $input, $timeout);

        try {
            $process->setTty(true);
            $process->mustRun(static function ($type, $buffer) {
                fwrite(Process::OUT === $type ? STDOUT : STDERR, $buffer);
            });
        } catch (ProcessFailedException $e) {
            echo $e->getMessage();
        }
    }

    protected function getSites()
    {
        $home = posix_getpwuid(posix_getuid())['dir'];
        $sites = $this->parse(file_get_contents($home.'/.ssh/config'));

        foreach ($sites as $name => &$site) {
            if (!isset($site['host'])) {
                $site['host'] = $name;
            }
            if (isset($site['root'])) {
                $site['root'] = rtrim($site['root'], '/');
            }
        }

        return $sites;
    }

    protected function getSite(string $name = null)
    {
        $sites = $this->getSites();

        if (!isset($sites[$name])) {
            throw new RuntimeException(sprintf('Invalid site: %s', $name));
        }

        return $this->validateSite($sites[$name]);
    }

    protected function configureSiteOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['host', 'root']);
    }

    protected function validateSite(array $site)
    {
        $resolver = new OptionsResolver();
        $this->configureSiteOptions($resolver);

        return $resolver->resolve($site);
    }

    protected function parse(string $config): array
    {
        if (empty(static::$configPrefix)) {
            static::$configPrefix = str_replace(':', '_', $this->getName()).'_';
        }

        $hosts = [];
        $lines = array_map('trim', explode(PHP_EOL, $config));
        $name = null;
        $configNamePattern = '/^'.preg_quote(static::$configPrefix, '/').'/i';
        foreach ($lines as $line) {
            if (empty($line) || preg_match('/^(#|IgnoreUnknown)/', $line)) {
                continue;
            }
            if (preg_match('/(\w+)(?:\s*=\s*|\s+)(.+)/', $line, $matches)) {
                [, $key, $value] = $matches;
                if ('Host' === $key) {
                    $name = $value;
                } elseif (isset($name)
                    && preg_match($configNamePattern, $key)
                ) {
                    $key = strtolower(preg_replace($configNamePattern, '', $key));
                    $hosts[$name][$key] = $value;
                }
            }
        }

        return array_filter($hosts);
    }
}
