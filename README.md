# Flarum Laravel Session <!-- omit in toc -->

**Disclaimer**: This package is still a proof of concept.

- [What it does](#what-it-does)
- [Requirements](#requirements)
- [Installation and configuration](#installation-and-configuration)
- [Usage](#usage)
- [Accessing session cookie from different domain](#accessing-session-cookie-from-different-domain)

## What it does
This package allows to use the session of [Flarum](https://flarum.org/) for authentication within a Laravel application.
It accesses Flarum's session cookie and reads the session data from the session storage.
Based on the user information in the Flarum database an user in the Laravel application is created / updated and logged in.

## Requirements
+ PHP 7.4
+ Laravel 7
+ Working installation of Flarum in the same filesystem as the Laravel application, so Flarum's session files can be accessed
+ Flarum and Laravel need to share the same domain / subdomain, so Flarum's session cookie can be read

## Installation and configuration
Install the package by executing `composer require lbausch/flarum-laravel-session:dev-master` and register the middleware in `app/Http/Kernel.php`:
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
    'flarum' => \Bausch\FlarumLaravelSession\FlarumMiddleware::class,
    // ...
];
```
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
Publish the package configuration using `php artisan vendor:publish --provider=Bausch\\FlarumLaravelSession\\ServiceProvider` and update `config/flarum.php` with your settings.

## Usage
In `routes/web.php` you may assign the middleware as desired:
```php
Route::middleware(['flarum'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});
```

## Accessing session cookie from different domain
If Flarum is running on domain.tld and Laravel on sub.domain.tld you need to configure Flarum (`config.php`), so the session cookie can be accessed on the subdomain:
```php
// Note the dot before domain.tld
'cookie' => ['domain' => '.domain.tld'],
```
