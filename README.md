# ETAAMB
Exercice Technique Appliqué Au Moniteur Belge

Currently migrating the 10 year old legacy app to a newer environment.

## Development
Run a docker instance :
```bash
docker build -t "etaamb" -f ./Dockerfile . && docker run --rm -it --name etaamb etaamb
```

## Configuration
Create a `config.php` file in the `etaamb` folder and set your settings.

Recommended settings :

```php
<?php
// Etaamb Local config file

define("LANG_METHOD","url");
define("ENVIRONMENT","dev");
define("DOMAIN" ,'localhost:8042');

define("URLMASK"
    ,"http://%host/%ln%page");

// Usefull logs to activate
// define('RENDERED_TEXT_CACHE',false);
// define('PARSER_LINKS_CACHE',false);
// define ('REDIRECTION_TEST',true);
// define ('CONNECTOR_CLASS_LOG',true);
// define ('COLLECTION_CLASS_LOG',true);
// define ('PARSER_CLASS_LOG',true);
// define ('REFERER_CLASS_LOG',true);
// define ('NUMAC_CLASS_LOG',true);
// define('DEFAULT_CLASS_LOG',true);
// define ('URL_CLASS_LOG',true);
// define ('INDEX_LOG',true);
// define ('OBSERVER_FILE_LOG',true);
// define ('OBSERVER_SILENT_LOG',true);
```

## Setup
Using **docker-compose**, and after configuration, the whole environment can be run as follows :

1. `docker-compose up` in the root repository directory will initiate a local development environment
2. Once launched, run `docker exec -it etaamb_steward run db_setup` in another terminal to create the database tables
3. TODO

## Logging
There is an extenstive logging and debugging functionnality available. See `config.default.php` to see which loggers you could activate in your local `config.php`.

## Agent
ETAAMB comes with a "steward" docker for maintenance operations. It accepts commands such as :

### Basic operations
```bash
# Show help
> docker exec -it etaamb_steward run -h
# Configure Database
> docker exec -it etaamb_steward run db_setup
# Attach shell
> docker exec -it etaamb_steward bash
```

### Scheduled tasks
Regular tasks are executed to crawl and collect the belgian official journal.
These scripts are mostly written in **Perl** by the agent.

- moniteur_import/recupId.pl : get the document identifiers
- getRaws.pl : get the raw content of the documents
- parseRaws.pl : parse and store documents in the database
- precalc
- manager.php del_unused
- manager.php set_anon



## Pecularities
### URL handling
Etaamb parses and redirects the URL through a combination of apache `.htaccess` and Regex. See class `url.class.php` for more details. (to see the parser, activate log **REDIRECTION_TEST**)

### Notable updates from the original Etaamb website
Etaamb was written in 2010, some things are not needed anymore.

- No more Internet Explorer 6 and 7 support
- PHP 8 instead of PHP 5.6.40
- Switched to MariaDB instead of MySQL
- Perl using DBD:MariaDB driver
- Using the native php MySQL driver Mysqlnd

## Notes and Documentation
- https://tighten.co/blog/converting-a-legacy-app-to-laravel/

