# Flarum Laravel Session <!-- omit in toc -->

![tests](https://github.com/lbausch/flarum-laravel-session/workflows/tests/badge.svg) [![codecov](https://codecov.io/gh/lbausch/flarum-laravel-session/branch/master/graph/badge.svg)](https://codecov.io/gh/lbausch/flarum-laravel-session)

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
  - [Handle An Identified User](#handle-an-identified-user)
- [Accessing Flarum Session Cookie From Different Domain](#accessing-flarum-session-cookie-from-different-domain)

## What It Does
This package allows to use the session of [Flarum](https://flarum.org/) for authentication within a Laravel application.
It accesses Flarum's session cookie and reads the session data from the session storage.
Based on the user information in the Flarum user database an user in the Laravel application is created / updated and logged in.

## Requirements
+ PHP 8.4+
+ Laravel 13
+ Working installation of Flarum in the same filesystem as the Laravel application, so Flarum's session files can be read
+ Flarum and Laravel need to share the same domain / subdomain, so Flarum's session cookie can be accessed

## Installation and Configuration

### Composer
Install the package with Composer:
```bash
composer require lbausch/flarum-laravel-session
```

### Register Middleware
Register the `\Bausch\FlarumLaravelSession\FlarumSessionMiddleware` middleware as an alias in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'flarum' => \Bausch\FlarumLaravelSession\FlarumSessionMiddleware::class,
    ]);

    // ...
})
```

### Setup Database Connection
Define a database connection for the Flarum database in `config/database.php`:

```php
'flarum' => [
    'driver' => 'mariadb',
    'url' => env('FLARUM_DATABASE_URL'),
    'host' => env('FLARUM_DB_HOST', '127.0.0.1'),
    'port' => env('FLARUM_DB_PORT', '3306'),
    'database' => env('FLARUM_DB_DATABASE', 'laravel'),
    'username' => env('FLARUM_DB_USERNAME', 'root'),
    'password' => env('FLARUM_DB_PASSWORD', ''),
    'unix_socket' => env('FLARUM_DB_SOCKET', ''),
    'charset' => env('FLARUM_DB_CHARSET', 'utf8mb4'),
    'collation' => env('FLARUM_DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        Mysql::ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

### Publish Package Configuration
Publish the package configuration using `php artisan vendor:publish --provider=Bausch\\FlarumLaravelSession\\ServiceProvider` and update `config/flarum.php` with your settings.

### Disable Cookie Encryption
To prevent Laravel from encrypting the Flarum session cookie, exclude it in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->encryptCookies(except: ['flarum_session']);

    // ...
})
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

All requests to the `/` route will then be checked by the middleware.

### Handle An Identified User
Once the middleware successfully identified an user, it executes the default handler `\Bausch\FlarumLaravelSession\Actions\HandleIdentifiedUser`. You may configure a different handler by calling `FlarumLaravelSession::handleIdentifiedUser()` in a service provider. This is a perfect place to update attributes or execute further actions, just remember to implement the `\Bausch\FlarumLaravelSession\Contracts\FlarumUserIdentified` interface.
Have a look at the default handler for a reference implementation.

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Bausch\FlarumLaravelSession\FlarumLaravelSession;
use App\Handlers\YourCustomHandler;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        FlarumLaravelSession::handleIdentifiedUser(YourCustomHandler::class);
    }
}
```

If you need to use a different user model than `App\Models\User`, you may call `FlarumLaravelSession::useUserModel(YourUser::class)` in your service provider.

## Accessing Flarum Session Cookie From Different Domain
If Flarum is running on domain.tld and Laravel on sub.domain.tld you need to configure Flarum (`config.php`), so the session cookie can be accessed on the subdomain:

```php
// Note the dot before domain.tld
'cookie' => ['domain' => '.domain.tld'],
```
