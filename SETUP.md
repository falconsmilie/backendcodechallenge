# Setup

After cloning this repository to your local machine only a couple of setup actions are required. Knowledge of Composer
and Docker is assumed.

## Prerequisites
* Git
* Docker
* Composer

## Clone the Project
```
git clone git@github.com:falconsmilie/Circunomics-hiring_backendcodechallenge_shane.git
```

## Setup Dependencies
Run the following `composer` command from the `source/` folder of the application. This will also create the `.env` for
the application.

```
composer install
```

## Build and Run Docker Containers
Run the following `docker-compose` commands from the `source/` folder of the application.

```
docker-compose build
docker-compose up -d
```

## Run Database Migration
From a terminal within your `app` container run the following command to setup the database tables. This command will drop
the table if it exists, which is handy for resetting the database during development.

```
php database/migrate.php
```
NB if you get connection refused errors for this command, please wait a few seconds and try again.

### phpMyAdmin
For your convenience phpMyAdmin is included.  It can be accessed at:

[http://localhost:8080/](http://localhost:8080/)

The credentials are listed in the project's [.env-development](source/.env-development) file.

## PHPUnit
When in the `source/` folder, PHPUnit is available at:
```
vendor/bin/phpunit
vendor/bin/phpunit --coverage-html coverage-report
```