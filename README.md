# Flarum Laravel Session <!-- omit in toc -->

![tests](https://github.com/lbausch/flarum-laravel-session/workflows/tests/badge.svg) [![Codacy Badge](https://app.codacy.com/project/badge/Coverage/08e639f60aca4927891fa9e4661003bd)](https://www.codacy.com/manual/lbausch/flarum-laravel-session)

**Disclaimer**: This package is still a proof of concept.

- [What It Does](#what-it-does)
- [Requirements](#requirements)
- [Installation and Configuration](#installation-and-configuration)
  - [Composer](#composer)
  - [Register Middleware](#register-middleware)
  - [Setup Database Connection](#setup-database-connection)
  - [Publish Package Configuration](#publish-package-configuration)
  - [Disable Cookie Encryption](#disable-cookie-encryption)
- [Usage](#usage)
  - [Setup Middleware](#setup-middleware)
  - [Updating Attributes](#updating-attributes)
- [Accessing Session Cookie From Different Domain](#accessing-session-cookie-from-different-domain)

## What It Does
This package allows to use the session of [Flarum](https://flarum.org/) for authentication within a Laravel application.
It accesses Flarum's session cookie and reads the session data from the session storage.
Based on the user information in the Flarum database an user in the Laravel application is created / updated and logged in.

## Requirements
+ PHP 7.2+
+ Laravel 7
+ Working installation of Flarum in the same filesystem as the Laravel application, so Flarum's session files can be read
+ Flarum and Laravel need to share the same domain / subdomain, so Flarum's session cookie can be accessed

## Installation and Configuration

### Composer
Install the package with Composer:
```bash
composer require lbausch/flarum-laravel-session
```

### Register Middleware
Register the middleware in `app/Http/Kernel.php`:
```php
/**
 * The application's route middleware.
 *
 * These middleware may be assigned to groups or used individually.
 *
 * @var array
 */
protected $routeMiddleware = [
    // ...
    'flarum' => \Bausch\FlarumLaravelSession\FlarumSessionMiddleware::class,
    // ...
];
```

### Setup Database Connection
Define a database connection for the Flarum database in `config/database.php`:
```php
'flarum' => [
    'driver' => 'mysql',
    'url' => env('FLARUM_DATABASE_URL'),
    'host' => env('FLARUM_DB_HOST', '127.0.0.1'),
    'port' => env('FLARUM_DB_PORT', '3306'),
    'database' => env('FLARUM_DB_DATABASE', 'forge'),
    'username' => env('FLARUM_DB_USERNAME', 'forge'),
    'password' => env('FLARUM_DB_PASSWORD', ''),
    'unix_socket' => env('FLARUM_DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('FLARUM_MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

### Publish Package Configuration
Publish the package configuration using `php artisan vendor:publish --provider=Bausch\\FlarumLaravelSession\\ServiceProvider` and update `config/flarum.php` with your settings.

### Disable Cookie Encryption
To avoid Laravel from trying to encrypt the Flarum session cookie, add the following to `app/Http/Middleware/EncryptCookies.php`:
```php
/**
 * The names of the cookies that should not be encrypted.
 *
 * @var array
 */
protected $except = [
    'flarum_session',
];
```

## Usage

### Setup Middleware
In `routes/web.php` you may assign the middleware as desired:
```php
Route::middleware(['flarum'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});
```

### Updating Attributes
Attributes which are updated upon a successful authentication can be specified by modifying the array `update_attributes` in `config/flarum.php`.
To track the relationship between your local user and the Flarum user, you may add a column `flarum_id` to your users table.


## Accessing Session Cookie From Different Domain
If Flarum is running on domain.tld and Laravel on sub.domain.tld you need to configure Flarum (`config.php`), so the session cookie can be accessed on the subdomain:
```php
// Note the dot before domain.tld
'cookie' => ['domain' => '.domain.tld'],
```
