# Remote commands

Helps running commands on remote hosts accesible via `ssh`

## Installation

```sh
composer global require rimi-itk/remote-commands
```

### Completions

```sh
eval "$(remote-drupal-drush --completion)"
```

#### Helpers

@TODO

## Usage

Run

```sh
bin/remote-command --help
```

## Configuration

All configuration of hosts is done in your [OpenSSH client configuration file
(`~/.ssh/config`)](https://man.openbsd.org/ssh_config).

Assuming the following configuration is set in `~/.ssh/config`

```config
# https://man.openbsd.org/ssh_config#IgnoreUnknown
IgnoreUnknown DRUPAL_*,SYMFONY_*

Host drupal.example.com
  …
  DRUPAL_DRUSH_ROOT /data/www/drupal/htdocs/web
  DRUPAL_DRUSH_DRUSH ../vendor/bin/drush
  DRUPAL_DRUSH_URI https://drupal.example.com
  …

# Example with docker-compose
Host drupal.example.com
  …
  DRUPAL_DRUSH_DRUSH docker-compose --env-file .env.docker.local --file docker-compose.server.yml exec phpfpm vendor/bin/drush
  # The directory to change to before running the drush command
  DRUPAL_DRUSH_CWD /data/www/drupal/htdocs
  # Drush root inside docker container
  DRUPAL_DRUSH_ROOT /app
  …

Host symfony.example.com
  …
  SYMFONY_CONSOLE_ROOT /data/www/symfony/htdocs
  …

# Example with docker-compose
  …
  SYMFONY_CONSOLE_ROOT /data/www/symfony/htdocs
  SYMFONY_CONSOLE_CONSOLE itkdev-docker-compose-server exec phpfpm bin/console

```

then running `remote-command drupal:drush drupal.example.com` will be equivalent
to running `ssh -t drupal.example.com drush --root=/data/www/drupal/htdocs
--uri=https://drupal.example.com`.

### Shortcuts

* `remote-drupal-drush` is a shortcut for `remote-command drupal:drush`
* `remote-symfony-console` is a shortcut for `remote-command symfony:console`

## Adding the commands to your `PATH`

If you're using the `bash` shell, run

```sh
echo 'export PATH="'$(git rev-parse --show-toplevel)/bin:$PATH"' >> ~/.bashrc
```

to add the commands to your `PATH`. If you're running `zsh`, run

```sh
echo 'export PATH="'$(git rev-parse --show-toplevel)/bin:$PATH"' >> ~/.zshrc
```
