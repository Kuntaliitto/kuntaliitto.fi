# Avoin Kuntaliitto

Avoin kuntaliitto on Drupal 8 -pohjainen alusta verkkopalvelun pohjaksi.

## Getting Started

This project is based on BLT, an open-source project template and tool that enables building, testing, and deploying Drupal installations following Acquia Professional Services best practices.

For local development we are using docker4drupal - https://github.com/wodby/docker4drupal/ but you can use vagrant-vm if you like. 

Basic configuration files:
.env
blt/blt.yml
sites/default/settings/local.settings.php

## Make commands

* make up
* make shell

# Composer inside docker

* make shell
* run: composer run-script blt-alias
* run: source /home/wodby/.bashrc

* run: blt blt:init:settings 
This will generate docroot/sites/default/settings/local.settings.php and docroot/sites/default/local.drushrc.php. Update these with your local database credentials and your local site URL.

* run: blt setup
This will build the drupal based on the composer.json and running site-install for you based on the configuration provided in config/default folder.

# Set local settings

file: sites/default/settings/local.settings.php

```php
// Set active config_split environments: local, prod
$config['config_split.config_split.local']['status'] = TRUE;
$config['config_split.config_split.prod']['status'] = FALSE;
```

Check the credentials are the same as in .env

```php
/**
 * Database configuration.
 */
$databases = array(
  'default' =>
  array(
    'default' =>
    array(
      'database' => $db_name,
      'username' => 'drupal',
      'password' => 'drupal',
      'host' => 'avoin_kl_mariadb',
      'port' => '3306',
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
      'driver' => 'mysql',
      'prefix' => '',
    ),
  ),
);
```

file: .env

```
PROJECT_NAME=avoin_kl
PROJECT_BASE_URL=avoin.kuntaliitto.fi

DB_NAME=drupal
DB_USER=drupal
DB_PASSWORD=drupal
DB_ROOT_PASSWORD=password
DB_HOST=mariadb
DB_DRIVER=mysql
```

# /etc/hosts

You may need to edit you local /etc/hosts file (or /private/etc/hosts)

```
127.0.0.1 avoin.kuntaliitto.fi portainer.avoin.kuntaliitto.fi  adminer.avoin.kuntaliitto.fi
```