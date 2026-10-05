# Pano Application Skeleton

A minimal, ready-to-run application skeleton built on top of the
**[Pano](https://github.com/pano-php/framework)** nano-framework.

Pano is a lightweight PHP runtime that gives you an explicit, predictable
foundation with **full architectural control**. This skeleton wires up that
runtime with a sensible project layout, a working `Default` module, an example
package, configuration, and web + CLI entry points so you can start building
your own domains immediately.

> Built for **Pano Framework `^1.7`**.  
> The framework itself is distributed as a **pure library** — this skeleton
> provides the application scaffolding (`public/`, `config/`, `src/`, CLI, …).

---

## Requirements

- **PHP >= 8.2**
- [Composer](https://getcomposer.org/)

---

## Installation

Create a new project with Composer:

```bash
composer create-project pano-php/pano my-app
cd my-app
```

Or clone this repository and install dependencies manually:

```bash
git clone https://github.com/pano-php/pano.git my-app
cd my-app
composer install
```

The `.env` file is created automatically from `.env.example` after install.
If it isn't, copy it yourself:

```bash
cp .env.example .env
```

---

## Quick Start

Start the built-in PHP development server:

```bash
php -S localhost:8000 -t public
```

Open `http://localhost:8000` — you should see the welcome page from the
`Default` module.

CLI example (root module + `app:info` command from `InfoPackage`):

```bash
php pano / app:info
```

---

## Project Structure

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
│   │   ├── DefaultFoundation.php     # Module registry + exception binding
│   │   └── DefaultException.php      # HTML errors via public/error.php
│   ├── Modules/
│   │   └── Default/
│   │       ├── DefaultModule.php     # setup(), view(), log(); imports InfoPackage
│   │       ├── Handlers/
│   │       ├── Interceptors/
│   │       └── Views/
│   └── Packages/
│       └── Info/
│           ├── InfoPackage.php       # BasePackage — registers app:info
│           └── Commands/
│               └── InfoCommand.php
├── tests/
├── .env
├── .env.example
├── composer.json
└── phpunit.xml
```

Namespace root: **`Src\`** (PSR-4 via `composer.json`).

### Entry points

Both entry points pass the project base path and a custom Foundation into Boot:

```php
// public/index.php
(new \Pano\Foundation\Boot($basePath, new \Src\Foundation\DefaultFoundation()))
    ->run($_SERVER);

// pano (CLI)
(new \Pano\Foundation\Boot($basePath, new \Src\Foundation\DefaultFoundation()))
    ->run($argv);
```

| Value | Meaning |
|-------|---------|
| `PANO_STARTED` | Request start timestamp (microtime) |
| `$basePath` / `BASE_PATH` | Project root (set by Boot) |
| `FOUNDATION` | Active Foundation instance (set by Boot) |

---

## Configuration

### `.env`

```dotenv
APP_NAME=Pano
APP_ENV=local
APP_KEY=base64:your-key-here
APP_DEBUG=true
APP_URL=https://example.test
```

### `config/app.php`

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

Access values with `config('app.name')`, `env('APP_DEBUG')`, etc.

### Module registry

Modules are **not** listed in a config file. They are registered on the Foundation:

```php
// src/Foundation/DefaultFoundation.php
namespace Src\Foundation;

use Pano\Foundation\Foundation;
use Src\Modules\Default\DefaultModule;

class DefaultFoundation extends Foundation
{
    protected static array $modules = [
        '' => DefaultModule::class,   // root module
    ];

    public static function exception(): string
    {
        return DefaultException::class;
    }
}
```

Add more keys as you add modules (e.g. `'blog' => BlogModule::class`).

---

## Core Concepts (in this skeleton)

| Concept | Role |
|---------|------|
| **Foundation** | Module map + which concrete classes implement request/response/router/… |
| **Module** | Domain unit: `setup()`, `view()`, `log()`; may import packages |
| **Package** | `BasePackage` attached to a module; shares the router; cannot import packages |
| **Handler** | Action that returns a `Response` |
| **Interceptor** | Runs before/after the handler on a route |
| **Command** | CLI action returning `ResultCodeEnum` |

Default path resolution: first URL segment = module key; empty key `''` serves `/`.

---

## The Default Module

```php
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

- Routes and commands are registered in **`setup()`** (the router is injected by Boot before `setup()` runs).
- Packages listed in the constructor are imported via `importPackages()` before `setup()`.

---

## Packages

`InfoPackage` registers the CLI command `app:info`:

```bash
php pano / app:info
```

Packages live under `src/Packages/`, extend `BasePackage`, and implement `setup()`, `view()`, and `log()` like modules.

---

## Building a Module

1. Create `src/Modules/Blog/BlogModule.php` extending `BaseModule`.
2. Implement `setup()`, `view()`, and `log()`.
3. Register the class on `DefaultFoundation::$modules`.
4. Add handlers (extend `BaseHandler`, return `Response`), optional interceptors and views.
5. Optionally attach packages via the constructor `packages` argument.
6. Test with `./vendor/bin/phpunit`.

Example registration:

```php
protected static array $modules = [
    ''     => DefaultModule::class,
    'blog' => \Src\Modules\Blog\BlogModule::class,
];
```

Example routes inside `setup()`:

```php
public function setup(): void
{
    $this->router->get('/', PostHandler::class, 'index');
    $this->router->get('/posts/[id]', PostHandler::class, 'show');
    $this->router->post('/posts', PostHandler::class, 'store', [AuthInterceptor::class]);
    $this->router->command('blog:publish', PublishCommand::class);

    $this->router->group('/admin', function ($router) {
        $router->get('/dashboard', DashboardHandler::class, 'index');
    }, [AuthInterceptor::class]);
}
```

---

## CLI

```bash
php pano <module-path> <command> [args...] [--options...]
```

| Example | Meaning |
|---------|---------|
| `php pano / app:info` | Root module (`''`), command `app:info` |
| `php pano blog blog:publish 42` | Module `blog`, command `blog:publish` |

> On Windows / Git-Bash, use `MSYS_NO_PATHCONV=1 php pano / app:info` if `/` is mangled.

---

## Testing

```bash
./vendor/bin/phpunit
```

`phpunit.xml` defines the Pano Test Suite under `tests/`. Autoload-dev maps `Tests\` → `tests/`.

---

## Web Server Setup

### Development

```bash
php -S localhost:8000 -t public
```

### Apache

Point `DocumentRoot` to `public/`. The included `.htaccess` removes trailing slashes, serves real files, forwards other requests to `index.php`, and preserves the `Authorization` header.

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

## Learn More

- **Skeleton developer guide:** [DOCUMENTATION.md](DOCUMENTATION.md)
- **Framework source & docs:** [pano-php/framework](https://github.com/pano-php/framework)
- **Framework API:** [DOCUMENTATION.md](https://github.com/pano-php/framework/blob/main/DOCUMENTATION.md)
- **Architecture:** [ARCHITECTURE.md](https://github.com/pano-php/framework/blob/main/ARCHITECTURE.md)
- **Philosophy:** [MANIFESTO.md](https://github.com/pano-php/framework/blob/main/MANIFESTO.md)

Pano is deliberately unopinionated — you bring the architecture. The framework
should never make decisions on your behalf.

---

## License

The MIT License (MIT). See [`LICENSE`](LICENSE).
