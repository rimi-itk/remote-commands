<?php

namespace App\Command;

use App\Console\Input\RemoteCommandInput;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class Command extends BaseCommand
{
    use LoggerAwareTrait;
    use LoggerTrait;

    protected static $configPrefix;

    protected $dynamicOptions = [];

    protected function configure()
    {
        $this
            ->addArgument('domain-name', InputArgument::OPTIONAL, 'Domain name')
            ->addArgument('remote options and arguments', InputArgument::IS_ARRAY, 'Options and arguments to pass on to the remote command')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List configures domains')
            ->addOption('completion', null, InputOption::VALUE_NONE, 'Generate completion')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (json or txt)', 'json')
            ->addOption('shell', null, InputOption::VALUE_REQUIRED, 'Shell type ("bash" or "zsh")', isset($_SERVER['SHELL']) ? basename($_SERVER['SHELL'], '.exe') : null);

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

    private $remoteOptionsAndArguments;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        $this->setLogger($logger);

        if ($input->getOption('list')) {
            $format = $input->getOption('format');

            return $this->list($input, $output, $format);
        }

        if ($input->getOption('completion')) {
            return $this->completion($input, $output);
        }

        if ($input instanceof RemoteCommandInput) {
            $this->remoteOptionsAndArguments = $input->getRemoteArguments();
        }
        $domainName = $input->getArgument('domain-name');
        $site = $this->getSite($domainName);
        $tty = false;
        if (false !== ($type = $input->getParameterOption('--site-completion'))) {
            if ('command-options' === $type) {
                $args = $this->getRemoteArguments();
                $commandName = reset($args);
                $command = $this->buildSiteCommandOptionsCommand($site, $commandName ?? '');
            } else {
                $command = $this->buildSiteCommandsCommand($site);
            }
        } else {
            $command = $this->buildCommand($site);
            $tty = true;
        }

        $this->runOnSite($site['host'], $command, $tty, null, null, $input->getStream());

        return 0;
    }

    abstract protected function buildSiteCommandsCommand(array $site): array;

    abstract protected function buildSiteCommandOptionsCommand(array $site, string $commandName): array;

    abstract protected function buildCommand(array $site): array;

    protected function getRemoteArguments(): array
    {
        return array_filter($this->getRemoteOptionsAndArguments(), static function (string $token) {
            return 0 !== strpos($token, '-');
        });
    }

    protected function getRemoteOptionsAndArguments(): array
    {
        return $this->remoteOptionsAndArguments ?? [];
    }

    protected function runOnSite(string $site, array $command, bool $tty = true, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60)
    {
        $command = array_map('escapeshellarg', $command);
        $sshStuff = array_filter([
            'ssh',
            $tty ? '-t' : null,
            '-o', 'LogLevel=QUIET',
            $site,
        ]);
        array_unshift($command, ...$sshStuff);
        $this->debug(implode(PHP_EOL, [
            'command:',
            '',
            ' '.implode(' ', $command),
            '',
        ]));

        $process = new Process($command, $cwd, $env, $input, $timeout);

        try {
            $process
                ->setTty($tty)
                ->setTimeout(null)
                ->mustRun(static function ($type, $buffer) {
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
        if (empty($name)) {
            throw new RuntimeException('Missing site');
        }

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

    protected function list(InputInterface $input, OutputInterface $output, string $format = null)
    {
        $sites = $this->getSites();

        if ('txt' === $format || false !== $input->getParameterOption('--raw')) {
            $output->writeln(array_keys($sites));
        } else {
            $output->writeln(json_encode($sites, JSON_THROW_ON_ERROR, 512));
        }

        return 0;
    }

    protected function completion(InputInterface $input, OutputInterface $output)
    {
        $shell = $input->getOption('shell');

        ob_start();
        include __DIR__.'/../../Resources/'.sprintf('%1$s/completion.%1$s.php', $shell);
        $script = ob_get_clean();

        $output->write($script);

        return 0;
    }

    public function log($level, $message, array $context = [])
    {
        if (null !== $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
