# Avoin Kuntaliitto

Avoin kuntaliitto on Drupal 8 -pohjainen alusta verkkopalvelun pohjaksi.

## Getting Started

This project is based on [BLT](https://blt.readthedocs.io/en/latest/), an open-source project template and tool that enables building, testing, and deploying Drupal installations. We use [Docker4drupal](https://github.com/wodby/docker4drupal/) environment for development
```
git clone git@github.com:Kuntaliitto/kuntaliitto.fi.git
cd kuntaliitto.fi
```
## Start the docker environment

* make up
* make shell

## Composer inside docker

* make shell
* run: composer install
* run: composer run-script blt-alias
* run: source /home/wodby/.bashrc
* run: blt blt:init:settings 

## Set local settings

file: sites/default/settings/local.settings.php


Modify the **host**:
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

* run: blt setup
This will build the drupal based on the composer.json and running site-install for you based on the configuration provided in config/default folder.

* run: drush uli -l http://avoin.kuntaliitto.fi:8000 and open the browser


## /etc/hosts

You may need to edit you local /etc/hosts file (or /private/etc/hosts)

```
127.0.0.1 avoin.kuntaliitto.fi portainer.avoin.kuntaliitto.fi  adminer.avoin.kuntaliitto.fi
```

## With browser
Access your site: http://avoin.kuntaliitto.fi:8000
