# Pano Skeleton — Developer Documentation

This is the developer reference for the **Pano application skeleton**
([`pano-php/pano`](https://github.com/pano-php/pano)).

It explains how this skeleton is wired on top of the **Pano** nano-framework:
project layout, bootstrap, the default Foundation, modules, packages, handlers,
CLI, configuration, testing, and how to extend the application.

> For philosophy and principles, read the framework [`MANIFESTO.md`](https://github.com/pano-php/framework/blob/main/MANIFESTO.md).  
> For internal system design and runtime model, read the framework [`ARCHITECTURE.md`](https://github.com/pano-php/framework/blob/main/ARCHITECTURE.md).  
> For the complete framework API reference, read the framework [`DOCUMENTATION.md`](https://github.com/pano-php/framework/blob/main/DOCUMENTATION.md).  
> **This document is for developers who want to *build an application* with the official skeleton.**

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Requirements & Installation](#2-requirements--installation)
3. [Quick Start](#3-quick-start)
4. [Project Structure](#4-project-structure)
5. [Mental Model](#5-mental-model)
6. [The Request Lifecycle](#6-the-request-lifecycle)
7. [Configuration](#7-configuration)
8. [Environment Variables](#8-environment-variables)
9. [Helper Functions](#9-helper-functions)
10. [Default Foundation](#10-default-foundation)
11. [Modules](#11-modules)
12. [Packages](#12-packages)
13. [Routing](#13-routing)
14. [Handlers](#14-handlers)
15. [Interceptors](#15-interceptors)
16. [Views & Templates](#16-views--templates)
17. [CLI Commands](#17-cli-commands)
18. [Exceptions & Error Pages](#18-exceptions--error-pages)
19. [Testing](#19-testing)
20. [Web Server Setup](#20-web-server-setup)
21. [Adding a New Module](#21-adding-a-new-module)
22. [Conventions & Best Practices](#22-conventions--best-practices)
23. [Full Example — Blog Module](#23-full-example--blog-module)
24. [Learn More](#24-learn-more)

---

## 1. Introduction

The skeleton is a **ready-to-run application** built on the pure library
[`pano-php/framework`](https://github.com/pano-php/framework).

The framework ships only Kernel contracts and a default Foundation.  
This skeleton adds:

- web and CLI entry points (`public/index.php`, `pano`)
- application configuration (`config/app.php`, `.env`)
- a custom Foundation with a module registry (`Src\Foundation\DefaultFoundation`)
- a working `Default` module with handler, interceptor, and views
- an example package (`Src\Packages\Info`) that registers a CLI command
- PHPUnit layout and a sample test

You keep full architectural control; the skeleton only provides a sensible
starting layout.

---

## 2. Requirements & Installation

### Requirements

- **PHP >= 8.2**
- [Composer](https://getcomposer.org/)
- A web server (Apache with `mod_rewrite`, Nginx, or the PHP built-in server)

### Installation

```bash
composer create-project pano-php/pano my-app
cd my-app
```

Or clone and install manually:

```bash
git clone https://github.com/pano-php/pano.git my-app
cd my-app
composer install
```

After install, `.env` is created from `.env.example` (Composer `post-create-project-cmd`).  
If it is missing:

```bash
cp .env.example .env
```

### Framework dependency

```json
"require": {
    "php": ">=8.2",
    "pano-php/framework": "^1.7"
}
```

---

## 3. Quick Start

Start the built-in PHP development server:

```bash
php -S localhost:8000 -t public
```

Open `http://localhost:8000` — you should see the welcome page from the
`Default` module.

CLI:

```bash
php pano / app:info
```

This runs the `app:info` command provided by the `InfoPackage`.

---

## 4. Project Structure

```text
my-app/
├── pano                              # CLI entry point (executable)
├── public/
│   ├── index.php                     # Web front controller
│   ├── error.php                     # HTML error template
│   └── .htaccess                     # Apache rewrite rules
├── config/
│   └── app.php                       # Application configuration
├── src/
│   ├── Foundation/
│   │   ├── DefaultFoundation.php     # Module registry + custom exception binding
│   │   └── DefaultException.php      # HTML error rendering via public/error.php
│   ├── Modules/
│   │   └── Default/
│   │       ├── DefaultModule.php     # setup(), view(), log(); imports InfoPackage
│   │       ├── Handlers/
│   │       │   └── DefaultHandler.php
│   │       ├── Interceptors/
│   │       │   └── DefaultInterceptor.php
│   │       └── Views/
│   │           ├── layout.php
│   │           └── home.php
│   └── Packages/
│       └── Info/
│           ├── InfoPackage.php       # BasePackage — registers app:info
│           └── Commands/
│               └── InfoCommand.php
├── tests/
│   └── DefaultModuleTest.php
├── .env
├── .env.example
├── composer.json
├── phpunit.xml
└── LICENSE
```

Namespace root: **`Src\`** (see `composer.json` autoload).

### Entry points

**Web — `public/index.php`**

```php
<?php
define("PANO_STARTED", microtime(true));
$basePath = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;

require $basePath . '/vendor/autoload.php';

(new \Pano\Foundation\Boot($basePath, new \Src\Foundation\DefaultFoundation()))->run($_SERVER);
```

**CLI — `pano`**

```php
#!/usr/bin/env php
<?php
define("PANO_STARTED", microtime(true));
$basePath = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

require $basePath . '/vendor/autoload.php';

(new \Pano\Foundation\Boot($basePath, new \Src\Foundation\DefaultFoundation()))->run($argv);
```

| Constant / value | Meaning |
|------------------|---------|
| `PANO_STARTED` | Request start timestamp (microtime) |
| `$basePath` | Project root (passed into `Boot`; also defines `BASE_PATH`) |
| `DefaultFoundation` | Application Foundation: module map + exception class |

---

## 5. Mental Model

| Layer | Role in this skeleton |
|-------|------------------------|
| **Kernel** (`Pano\Kernel\*`) | Contracts only — do not change |
| **Foundation** (`Pano\Foundation\*` + `Src\Foundation\*`) | Default runtime + your module registry |
| **Modules** (`Src\Modules\*`) | Application domains (`setup()`, handlers, views) |
| **Packages** (`Src\Packages\*`) | Reusable pieces attached to a module (`BasePackage`) |

Flow for every request:

1. Entry point constructs `Boot` with `DefaultFoundation`
2. `Boot` resolves the module key and looks up the class via `Foundation::module()`
3. Module is instantiated with optional packages
4. `importPackages()` → each package `setup()`
5. Module `setup()` registers routes / commands
6. Router matches and runs interceptors + handler / command

---

## 6. The Request Lifecycle

```text
Entry point (public/index.php / pano)
   │
   ▼
Boot::__construct($basePath, DefaultFoundation)
   ├── define BASE_PATH
   ├── envLoader()            → parses .env into $_ENV
   ├── configLoader()         → loads config/*.php into $_ENV['#_configs_#']
   ├── define FOUNDATION
   ├── debug / timezone       → from config('app.*')
   │
   ▼
Boot::run($_SERVER | $argv)
   └── dispatcher(Request | CLIRequest)
   │
   ▼
dispatcher()
   ├── request->getModule()              → ModuleResolverEnum match
   ├── DefaultFoundation::module($key)   → module class
   ├── new Module($request, FOUNDATION)  → packages injected in ctor
   ├── setRouter() → importPackages() → setup()
   └── router->handle()
   │
   ▼
Router::handle()
   ├── match route / command
   ├── interceptors (onRequest → handler → onResponse)
   └── response->send()
   │
   ▼
Termination (CLI exit code / process exit)
```

---

## 7. Configuration

All configuration lives under `config/*.php` relative to `BASE_PATH`.  
`Boot::configLoader()` loads them at bootstrap into `$_ENV['#_configs_#']`; `config()` reads from that cache.

### `config/app.php` (shipped)

```php
<?php
return [
    'name'  => env('APP_NAME',  'Pano'),
    'env'   => env('APP_ENV',  'local'),
    'key'   => env('APP_KEY',  null),
    'debug' => env('APP_DEBUG',  false),
    'url'   => env('APP_URL',  null),
];
```

| Key | Description |
|-----|-------------|
| `name` | Application display name |
| `env` | Environment name |
| `key` | Application secret |
| `debug` | Detailed errors |
| `url` | Base URL (helpers / subdomain resolution) |

You may add `timezone` and other keys as needed; `Boot` reads
`config('app.debug')` and `config('app.timezone', 'UTC')`.

### Module registry (not config)

Module key → class mapping lives on **`DefaultFoundation::$modules`**, not in
`config/modules.php`. Class string defaults to `PATH`; or use `['class' => …, 'resolver' => ModuleResolverEnum::…]`:

```php
// src/Foundation/DefaultFoundation.php
protected static array $modules = [
    '' => DefaultModule::class,
];
```

---

## 8. Environment Variables

`.env.example`:

```dotenv
APP_NAME=Pano
APP_ENV=local
APP_KEY=Iur5UWL6KVz/2jsJTfjF+YbzAmnvejpIfYWo0fzZ8Mg=
APP_DEBUG=true
APP_URL=https://neda.tst
```

`true` / `false` / `null` and numeric strings are parsed automatically.

```php
env('APP_NAME', 'Pano');
env('APP_DEBUG', false);
```

---

## 9. Helper Functions

Provided by the framework (autoloaded):

| Function | Description |
|----------|-------------|
| `env(string $key, mixed $default = null): mixed` | Environment variable |
| `config(string $key, mixed $default = null): mixed` | Config with dot notation |
| `url(string $path, ?string $moduleParam = null): string` | Absolute URL; optional module key uses resolver |
| `path(string $path): string` | Absolute filesystem path under `BASE_PATH` |
| `currentUrl(): string` | Current request URL |
| `dd(...$args): void` | Dump and die |

Example used by `DefaultException`:

```php
$view = new View(path('public'));  // BASE_PATH/public
```

---

## 10. Default Foundation

```php
namespace Src\Foundation;

use Pano\Foundation\Foundation;
use Pano\Kernel\ModuleResolverEnum;
use Src\Modules\Default\DefaultModule;

class DefaultFoundation extends Foundation
{
    protected static array $modules = [
        '' => DefaultModule::class,  // PATH by default
        // Example of an explicit resolver:
        // 'blog' => [
        //     'class'    => \Src\Modules\Blog\BlogModule::class,
        //     'resolver' => ModuleResolverEnum::PATH,
        // ],
    ];

    public static function exception(): string
    {
        return DefaultException::class;
    }
}
```

- Extends the framework `Foundation` (inherits request, response, router, view, … bindings).
- Registers the root module under the empty key `''`.
- Overrides the exception class so HTML errors use `public/error.php`.

Module resolution is **per key** via `ModuleResolverEnum`: `PATH` (default), `SUBDOMAIN`,
`HOST`, `QUERY`, `HEADER`. Match order: HOST → SUBDOMAIN → PATH → QUERY → HEADER.
Override `param()` on the Foundation if you need a different query/header name than `module`.

---

## 11. Modules

A module is a `final readonly` class extending `Pano\Kernel\BaseModule`.

Required methods:

```php
public function setup(): void;
public function view(): BaseView;
public function log(): BaseLogger;
```

### Default module (shipped)

```php
namespace Src\Modules\Default;

final readonly class DefaultModule extends BaseModule
{
    public function __construct(BaseRequest $request, BaseFoundation $foundation)
    {
        parent::__construct(
            request: $request,
            foundation: $foundation,
            packages: [InfoPackage::class]
        );
    }

    public function setup(): void
    {
        $this->router->get('/', DefaultHandler::class, 'info', [DefaultInterceptor::class]);
    }

    public function view(): BaseView
    {
        return new View($this->viewPath());
    }

    public function log(): BaseLogger
    {
        return new Logger($this->logPath());
    }
}
```

- Packages are passed into the parent constructor.
- Boot calls `importPackages()` then `setup()`.
- `$this->router` is already set before `setup()`.

### Path helpers

```php
$this->viewPath();    // …/Modules/Default/Views
$this->filePath();    // …/Modules/Default/Files
$this->logPath();     // …/Modules/Default/Logs
$this->path();        // …/Modules/Default
$this->path('Views'); // …/Modules/Default/Views
$this->name();        // "DefaultModule"
```

### Module resolution

Default entries use **`ModuleResolverEnum::PATH`** (first URL segment = module key).

| URL | Module key | Route path |
|-----|------------|------------|
| `/` | `''` | (empty / root) |
| `/blog/posts/12` | `blog` | `posts/12` |

Register every reachable key in `DefaultFoundation::$modules`. For non-path strategies
use the array form with `class` + `resolver` (`PATH`, `SUBDOMAIN`, `HOST`, `QUERY`, `HEADER`).
See the framework documentation for full matching rules and priority order.
---

## 12. Packages

Packages extend `Pano\Kernel\BasePackage` (which extends `BaseModule`).  
They share the parent module’s router and cannot import further packages.

### Info package (shipped)

```php
namespace Src\Packages\Info;

final readonly class InfoPackage extends BasePackage
{
    public function setup(): void
    {
        $this->router->command('app:info', InfoCommand::class);
    }

    public function view(): BaseView
    {
        return new View($this->viewPath());
    }

    public function log(): BaseLogger
    {
        return new Logger($this->logPath());
    }
}
```

Attached from `DefaultModule` via the `packages` constructor argument.

---

## 13. Routing

Register routes inside `setup()` on the injected router:

```php
public function setup(): void
{
    $this->router->get('/', DefaultHandler::class, 'info', [DefaultInterceptor::class]);
    $this->router->post('/posts', PostHandler::class, 'store', [AuthInterceptor::class]);
    $this->router->command('app:info', InfoCommand::class);

    $this->router->group('/admin', function ($router) {
        $router->get('/dashboard', DashboardHandler::class, 'index');
    }, [AuthInterceptor::class]);
}
```

| Method | Purpose |
|--------|---------|
| `get` / `post` / `put` / `delete` | HTTP routes |
| `command` | CLI command |
| `group($prefix, $callback, $interceptors = [])` | Shared prefix + interceptors |

### Route parameters

| Syntax | Meaning |
|--------|---------|
| `[id]` | Required segment |
| `[id?]` | Optional (last segment) |
| `[id*]` | Catch-all (last segment) |

---

## 14. Handlers

Handlers extend `Pano\Kernel\BaseHandler` and return a `Response`.

```php
namespace Src\Modules\Default\Handlers;

use Composer\InstalledVersions;
use Pano\Foundation\Response;
use Pano\Kernel\BaseHandler;

final class DefaultHandler extends BaseHandler
{
    public function info(): Response
    {
        $version = InstalledVersions::getPrettyVersion('pano-php/pano') ?? 'dev';

        return Response::html(
            $this->module->view()
                ->with(['name' => env('APP_NAME', 'Pano'), 'version' => $version])
                ->layout('layout')
                ->render('home')
        );
    }
}
```

`$this->request` and `$this->module` are available.

---

## 15. Interceptors

```php
namespace Src\Modules\Default\Interceptors;

use Pano\Kernel\BaseInterceptor;
use Pano\Kernel\BaseResponse;

class DefaultInterceptor extends BaseInterceptor
{
    public function onRequest(): void
    {
        // before handler
    }

    public function onResponse(BaseResponse $response): BaseResponse
    {
        return parent::onResponse($response);
    }
}
```

Attach per route as the 4th argument, or via `group(..., $interceptors)`.

Order for `[A, B]`:

```text
A::onRequest → B::onRequest → Handler → B::onResponse → A::onResponse
```

Share data with `$this->request->attributes`.

---

## 16. Views & Templates

Views live under the module’s `Views/` directory (or a package’s `Views/`).

`DefaultHandler` uses:

```php
$this->module->view()
    ->with(['name' => …, 'version' => …])
    ->layout('layout')
    ->render('home');
```

Templates use sections (`start` / `end`) as provided by the framework view engine.  
Escape output with the view escape helpers documented in the framework guide.

---

## 17. CLI Commands

### Invocation

```bash
php pano <module-path> <command> [positional args...] [--options...]
```

Root module (`''` key):

```bash
php pano / app:info
```

Named module (e.g. `blog`):

```bash
php pano blog blog:publish 42
```

> On Windows / Git-Bash, prefix with `MSYS_NO_PATHCONV=1` when the module path is `/`.

### Info command (shipped)

```php
namespace Src\Packages\Info\Commands;

class InfoCommand extends BaseCommand
{
    public function handle(array $arguments): ResultCodeEnum
    {
        $version = InstalledVersions::getPrettyVersion('pano-php/pano') ?? 'dev';
        $this->info(config('app.name') . " - " . $version);
        return ResultCodeEnum::OK;
    }
}
```

Return `ResultCodeEnum::OK`, `ERROR`, or `INVALID`.

---

## 18. Exceptions & Error Pages

`DefaultException` extends the framework `Exception` and overrides HTML rendering:

```php
public function toHtml(bool $debug = false): string
{
    $view = new View(path('public'));
    Response::html(
        $view->with([
            'exception' => $this,
            'debug' => $debug,
            'code' => $this->getCode() > 300 ? $this->getCode() : 500,
        ])->render('error'),
        HttpStatusEnum::INTERNAL_SERVER_ERROR
    )->send();
    exit(0);
}
```

Template: `public/error.php`.  
Bound via `DefaultFoundation::exception()`.

---

## 19. Testing

PHPUnit is configured in `phpunit.xml`:

```bash
./vendor/bin/phpunit
```

Sample test:

```php
namespace Tests;

use PHPUnit\Framework\TestCase;
use Src\Modules\Default\DefaultModule;

class DefaultModuleTest extends TestCase
{
    public function test_domain_handle_executes_successfully()
    {
        $this->assertIsBool(true, 'success');
    }
}
```

Autoload-dev maps `Tests\` → `tests/`.

---

## 20. Web Server Setup

### Development

```bash
php -S localhost:8000 -t public
```

### Apache

Point `DocumentRoot` to `public/`. `.htaccess` already:

- removes trailing slashes
- serves real files/directories
- forwards other requests to `index.php`
- preserves the `Authorization` header

### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.test;
    root /var/www/my-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

---

## 21. Adding a New Module

1. Create `src/Modules/Blog/BlogModule.php` extending `BaseModule` with `setup()`, `view()`, `log()`.
2. Register it on the Foundation:

```php
protected static array $modules = [
    ''     => DefaultModule::class,
    'blog' => \Src\Modules\Blog\BlogModule::class,
];
```

3. Add handlers, interceptors, views under that module.
4. Optionally attach packages via the constructor `packages` argument.
5. Test with `./vendor/bin/phpunit`.

Checklist:

- [ ] Module class under `Src\Modules\…`
- [ ] Entry in `$modules`
- [ ] Routes/commands in `setup()`
- [ ] Handlers return `Response`
- [ ] Optional interceptors / packages / CLI commands

---

## 22. Conventions & Best Practices

### Do

- Keep modules isolated under `Src\Modules\`
- Register modules only on the Foundation `$modules` map
- Use `setup()` for routes (router is injected by Boot)
- Prefer packages for reusable cross-module capabilities
- Return `Response` from every handler action
- Use `$request->attributes` to pass data from interceptors to handlers
- Escape template output

### Don't

- Do not put module maps back into `config/modules.php` — the runtime ignores that
- Do not implement `routes()` — the contract is `setup()`
- Do not call `importPackages()` from a package
- Do not echo from handlers
- Do not put application logic into Kernel contracts

---

## 23. Full Example — Blog Module

### 1. Foundation registry

```php
protected static array $modules = [
    ''     => \Src\Modules\Default\DefaultModule::class,
    'blog' => \Src\Modules\Blog\BlogModule::class,
];
```

### 2. Module

```php
namespace Src\Modules\Blog;

use Pano\Foundation\Logger;
use Pano\Foundation\View;
use Pano\Kernel\BaseFoundation;
use Pano\Kernel\BaseLogger;
use Pano\Kernel\BaseModule;
use Pano\Kernel\BaseRequest;
use Pano\Kernel\BaseView;
use Src\Modules\Blog\Handlers\PostHandler;
use Src\Modules\Blog\Interceptors\AuthInterceptor;

final readonly class BlogModule extends BaseModule
{
    public function __construct(BaseRequest $request, BaseFoundation $foundation)
    {
        parent::__construct($request, $foundation, packages: []);
    }

    public function setup(): void
    {
        $this->router->get('/', PostHandler::class, 'index');
        $this->router->get('/posts/[id]', PostHandler::class, 'show');
        $this->router->post('/posts', PostHandler::class, 'store', [AuthInterceptor::class]);
    }

    public function view(): BaseView
    {
        return new View($this->viewPath());
    }

    public function log(): BaseLogger
    {
        return new Logger($this->logPath());
    }
}
```

### 3. Handler

```php
namespace Src\Modules\Blog\Handlers;

use Pano\Foundation\Response;
use Pano\Kernel\BaseHandler;
use Pano\Kernel\HttpStatusEnum;

final class PostHandler extends BaseHandler
{
    public function index(): Response
    {
        return Response::json(['posts' => []]);
    }

    public function show($id): Response
    {
        return Response::json(['id' => $id]);
    }

    public function store(): Response
    {
        $data = $this->request->getData();
        return Response::json(['created' => true], HttpStatusEnum::CREATED);
    }
}
```

URLs:

| URL | Module | Action |
|-----|--------|--------|
| `/blog/` | `blog` | `index` |
| `/blog/posts/12` | `blog` | `show` |
| `POST /blog/posts` | `blog` | `store` (+ AuthInterceptor) |

---

## 24. Learn More

- **Framework source:** [pano-php/framework](https://github.com/pano-php/framework)
- **Framework developer guide:** [DOCUMENTATION.md](https://github.com/pano-php/framework/blob/main/DOCUMENTATION.md)
- **Architecture:** [ARCHITECTURE.md](https://github.com/pano-php/framework/blob/main/ARCHITECTURE.md)
- **Philosophy:** [MANIFESTO.md](https://github.com/pano-php/framework/blob/main/MANIFESTO.md)
- **Skeleton repository:** [pano-php/pano](https://github.com/pano-php/pano)

Pano is deliberately unopinionated — you bring the architecture. The framework
should never make decisions on your behalf.

---

## License

The MIT License (MIT). See [`LICENSE`](LICENSE).
