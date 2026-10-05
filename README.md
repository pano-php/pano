# Pano Application Skeleton

A minimal, ready-to-run application skeleton built on top of the
**[Pano](https://github.com/pano-php/pano-framework)** nano-framework.

Pano is a lightweight PHP runtime that gives you an explicit, predictable
foundation with **full architectural control**. This skeleton wires up that
runtime with a sensible project layout, a working `Default` module, configuration,
and a web + CLI entry point so you can start building your own domains
immediately.

> Built for **Pano Framework `^1.6`** (currently `v1.6.0`).  
> The framework itself is distributed as a **pure library** — this skeleton
> provides the application scaffolding (`public/`, `config/`, modules, CLI, …).

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

Open `http://localhost:8000` in your browser — you should see the welcome page.
The skeleton is alive.

---

## Project Structure

```text
my-app/
├── pano                      # CLI entry point (executable)
├── public/
│   ├── index.php             # Web front controller
│   └── .htaccess             # Apache rewrite rules
├── config/
│   ├── app.php               # Application configuration
│   └── modules.php           # Module ↔ domain/resolver mapping
├── modules/                  # Your application lives here
│   └── Default/
│       ├── DefaultModule.php
│       ├── Handlers/
│       ├── Interceptors/
│       ├── Commands/
│       └── Views/
├── tests/                    # PHPUnit test suite
├── .env                      # Environment variables (not committed)
├── .env.example
└── composer.json
```

Two constants are defined at the very start of every entry point and drive the
whole runtime:

| Constant       | Meaning                                              |
|----------------|------------------------------------------------------|
| `PANO_STARTED` | Request start timestamp (microtime), for timing      |
| `BASE_PATH`    | Absolute path to the project root, with trailing `/` |

Everything — config loading, `.env` resolution, module paths — is relative to
`BASE_PATH`, so keep that in mind if you move entry points.

---

## Configuration

### `.env`

```dotenv
APP_NAME=Pano
APP_ENV=local          # "local" enables the dev/pano module
APP_KEY=base64:key     # Application key
APP_DEBUG=true         # Show detailed errors (disable in production)
APP_URL=https://example.test
MODULE_RESOLVER=path   # "path" or "subdomain"
```

> **Tip:** Pano parses `.env` values. `true`/`false`/`null` become proper
> booleans/null, and numeric strings become numbers.

### `config/app.php`

Reads environment values into a config array. Access any value anywhere using
the `config()` helper:

```php
config('app.name');         // "Pano"
config('app.debug', false); // true, with a fallback
```

### `config/modules.php`

Maps a **module key** to a module class. This is how Pano decides which module
handles the current request:

```php
<?php

use Src\Modules\Default\DefaultModule;

return [
    'pano' => env('APP_ENV', 'production') === 'local' ? DefaultModule::class : null,
    ''     => DefaultModule::class,
];
```

- The empty key `''` is the **default** module.
- `'pano'` is only active in the `local` environment (useful for a development dashboard).

---

## The Module Resolver

Pano routes an incoming request to a module **before** it routes to a handler.
How the module is chosen depends on `MODULE_RESOLVER` / `config('app.resolver')`:

### `path` (default)

The **first URL segment** is the module key. The remainder is the route path.

| URL              | Module key | Route       |
|------------------|------------|-------------|
| `/blog/posts/12` | `blog`     | `/posts/12` |
| `/`              | `''`       | `/`         |

### `subdomain`

The **subdomain** is the module key. The root domain is derived from `APP_URL`,
so only the leading sub-part of that host is treated as a module:

| Host                                             | Module key |
|--------------------------------------------------|------------|
| `blog.example.test` (APP_URL=`https://example.test`) | `blog`  |
| `api.v2.example.test`                            | `api.v2`   |
| `example.test` (the root itself)                 | `''`       |

If the resolver is neither `path` nor `subdomain`, an `Exception` is thrown.

> If no module matches the resolved key, Pano throws
> `"No module found for '<name>'"`. Make sure every reachable key is registered
> in `config/modules.php`.

---

## Core Concepts

Pano is intentionally small. Five concepts carry the whole runtime:

| Concept         | Responsibility                                              |
|-----------------|-------------------------------------------------------------|
| **Module**      | A self-contained domain; defines routes, views, logging     |
| **Handler**     | A controller-like class that produces a `Response`          |
| **Interceptor** | Runs before handlers (`onRequest`) and after (`onResponse`) |
| **Command**     | A CLI action, like a console controller                     |
| **View**        | Renders templates with layouts and sections                 |

The base contracts live in the `Pano\Kernel` namespace; ready-to-use concrete
implementations live in `Pano\Foundation`.

| Concept       | Base (abstract) contract          | Concrete implementation        |
|---------------|-----------------------------------|--------------------------------|
| Module        | `Pano\Kernel\BaseModule`          | *(you extend it)*              |
| Router        | `Pano\Kernel\BaseRouter`          | `Pano\Foundation\Router`       |
| Request       | `Pano\Kernel\BaseRequest`         | `Pano\Foundation\Request`      |
| Response      | `Pano\Kernel\BaseResponse`        | `Pano\Foundation\Response`     |
| View          | `Pano\Kernel\BaseView`            | `Pano\Foundation\View`         |
| Handler       | `Pano\Kernel\BaseHandler`         | *(you extend it)*              |
| Interceptor   | `Pano\Kernel\BaseInterceptor`     | *(you extend it)*              |
| Command       | `Pano\Kernel\BaseCommand`         | *(you extend it)*              |
| Logger        | `Pano\Kernel\BaseLogger`          | `Pano\Foundation\Logger`       |
| Exception     | `Pano\Kernel\BaseException`       | `Pano\Foundation\Exception`    |
| Bag           | `Pano\Kernel\BaseBag`             | `Pano\Foundation\Bag`          |

> **Important:** Always extend the `Pano\Kernel\Base*` contracts (and use the
> `Pano\Foundation\*` implementations). Application modules belong under the
> `Src\Modules\` namespace (not `Pano\Src\Modules\`). The older `Pano\Core` / `Pano\Enum`
> namespaces no longer exist.

---

## Building a Module

A module is a `final readonly` class extending `BaseModule`. It must define three
methods: `routes()`, `view()`, and `log()`.

```php
<?php

namespace Src\Modules\Blog;

use Pano\Foundation\Logger;
use Pano\Foundation\Router;
use Pano\Foundation\View;
use Pano\Kernel\BaseLogger;
use Pano\Kernel\BaseModule;
use Pano\Kernel\BaseRouter;
use Pano\Kernel\BaseView;
use Src\Modules\Blog\Handlers\PostHandler;
use Src\Modules\Blog\Interceptors\AuthInterceptor;
use Src\Modules\Blog\Commands\PublishCommand;

final readonly class BlogModule extends BaseModule
{
    public function routes(): BaseRouter
    {
        $router = new Router($this->request, $this);

        // HTTP routes: $router->METHOD(path, Handler::class, action, [interceptors])
        $router->get('/posts', PostHandler::class, 'index');
        $router->get('/posts/[id]', PostHandler::class, 'show');
        $router->post('/posts', PostHandler::class, 'store', [AuthInterceptor::class]);

        // CLI commands
        $router->command('blog:publish', PublishCommand::class);

        return $router;
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

`BaseModule` gives you convenient path helpers, all resolved relative to the
module's own directory via reflection:

```php
$this->viewPath();   // .../Modules/Blog/Views
$this->filePath();   // .../Modules/Blog/Files
$this->logPath();    // .../Modules/Blog/Logs
$this->path();       // .../Modules/Blog
$this->name();       // "BlogModule" (short class name)
```

Register the module in `config/modules.php`:

```php
return [
    ''     => \Src\Modules\Default\DefaultModule::class,
    'blog' => \Src\Modules\Blog\BlogModule::class,
];
```

Now requests to `/blog/...` are handled by `BlogModule`.

---

## Routing

Routes are registered inside the module's `routes()` method on the injected
`$router`. Supported HTTP verbs:

```php
$router->get('/path', Handler::class, 'action');
$router->post('/path', Handler::class, 'action');
$router->put('/path', Handler::class, 'action');
$router->delete('/path', Handler::class, 'action');
```

### Route Parameters

Parameters are declared with brackets and are passed as method arguments
**in declaration order**:

```php
// Route
$router->get('/users/[id]/posts/[postId]', UserHandler::class, 'post');

// Handler — argument order matches the route declaration
public function post($id, $postId): Response { ... }
```

Parameter flags:

| Syntax   | Meaning                                  |
|----------|------------------------------------------|
| `[id]`   | Required segment                         |
| `[id?]`  | Optional (must be the **last** segment)  |
| `[id*]`  | Catch-all (must be the **last** segment) |

```php
$router->get('/files/[path*]', FileHandler::class, 'show');
```

> Optional and catch-all parameters must always be the last route segment.

---

## Handlers

A handler is a class extending `BaseHandler`. Each **action** is a public method
that returns a `Response`. The return type **must** be declared and must be
`BaseResponse` (or a subclass) — Pano enforces this.

The handler receives the request and its module via constructor injection:

```php
<?php

namespace Src\Modules\Blog\Handlers;

use Pano\Foundation\Response;
use Pano\Kernel\BaseHandler;
use Pano\Kernel\HttpStatusEnum;

final class PostHandler extends BaseHandler
{
    public function index(): Response
    {
        return Response::json([
            'posts' => ['Pano 101', 'Routing in depth'],
        ]);
    }

    public function show($id): Response
    {
        return Response::json(['id' => $id, 'title' => 'Hello Pano']);
    }

    public function store(): Response
    {
        // getData() auto-parses the body based on Content-Type:
        // $_POST → application/json → application/x-www-form-urlencoded
        $data = $this->request->getData();

        // ...persist...

        return Response::json(['created' => true], HttpStatusEnum::CREATED);
    }
}
```

`BaseHandler` exposes:

```php
$this->request;   // the current Request
$this->module;    // the owning Module
```

### Method override

HTML forms can only `GET`/`POST`. To submit `PUT`/`DELETE`/`PATCH`, Pano
honors a method override **on POST requests** via either:

- the `X-HTTP-Method-Override` header, or
- a `_method` field in the POST body.

```html
<form method="POST" action="/posts/12">
    <input type="hidden" name="_method" value="DELETE">
</form>
```

The resolved verb becomes the route's HTTP method automatically.

---

## Responses

`Pano\Foundation\Response` offers expressive factory methods:

```php
Response::html($htmlString);                                   // text/html
Response::json($arrayOrObject);                                // application/json
Response::text($plain);                                        // text/plain
Response::redirect($url);                                      // 302 redirect
Response::stream(fn() => readfile($path), 'application/pdf');  // streamed body
Response::terminal('Done');                                    // CLI: green text
Response::terminal('Failed', ResultCodeEnum::ERROR);           // CLI: red text
Response::make($body, HttpStatusEnum::CREATED, ['X-Foo' => 'bar']); // custom
```

Every method accepts an optional `HttpStatusEnum` and a headers array. The JSON
helper encodes with `JSON_UNESCAPED_UNICODE` so Persian/Arabic text is preserved.

### Fluent mutation

Every mutator returns `$this`, so you can chain after construction:

```php
return Response::json($data)
    ->setStatus(HttpStatusEnum::CREATED)
    ->setHeader('X-Request-Id', $id)
    ->setHeaders(['Cache-Control' => 'no-store']);

// Or override the body after creation
return Response::make(null)
    ->setStatus(HttpStatusEnum::NO_CONTENT);
```

Useful `HttpStatusEnum` values: `OK`, `CREATED`, `NO_CONTENT`, `MOVED_PERMANENTLY`,
`FOUND`, `BAD_REQUEST`, `UNAUTHORIZED`, `FORBIDDEN`, `NOT_FOUND`,
`UNPROCESSABLE_ENTITY`, `INTERNAL_SERVER_ERROR`, … (all standard HTTP codes).

### Automatic error rendering

If a handler (or anything in the pipeline) throws, `Response::exception($e, $request)`
turns the throwable into an appropriate response:

| Context         | Output                                         |
|-----------------|------------------------------------------------|
| CLI request     | colored terminal line, `ResultCodeEnum::ERROR` |
| `expectsJson()` | `$e->toArray($debug)` as JSON                  |
| otherwise       | `$e->toHtml($debug)` as HTML                   |

This is wired into the global handler, so you never need to wrap your handlers
in try/catch for rendering.

### Sending

Calling `send()` writes the HTTP status line, headers, and body to the output
stream. It is **idempotent** — a response can only be sent once. The router
calls `send()` for you; you normally just `return` the response.

---

## The Bag

`Bag` (`Pano\Foundation\Bag`, extending `Pano\Kernel\BaseBag`) is Pano's
lightweight, chainable key/value container. You already meet it as
`$request->attributes`. You can also use it anywhere you need structured data.

It behaves like an array — it implements `ArrayAccess`, `IteratorAggregate`,
and `Countable`:

```php
use Pano\Foundation\Bag;

$bag = new Bag(['name' => 'Pano', 'tags' => ['php', 'web']]);

$bag['name'];        // get
$bag['name'] = 'X';  // set
isset($bag['name']); // has
count($bag);
foreach ($bag as $k => $v) { ... }
```

### Basic operations

```php
$bag->get('name', $default);
$bag->set('key', $value);   // returns $this (mutable)
$bag->has('key');
$bag->remove('key');        // returns $this
$bag->all();
```

### Functional helpers

Most helpers return a **new** `Bag` (immutable style), so they chain:

```php
$bag->merge($otherBagOrArray);     // union by key
$bag->replace($otherBagOrArray);   // array_replace semantics
$bag->only(['name', 'email']);     // keep only these keys
$bag->except(['password']);        // drop these keys
$bag->map(fn($v, $k) => strtoupper($v));
$bag->filter(fn($v, $k) => $v !== null);
```

### Deep search

Bags can be nested (arrays and other Bags inside). Pano finds values **or**
keys anywhere in the tree and returns their dot-paths:

```php
$bag->find('php');         // 'tags.0'   — first path to the VALUE
$bag->findAll('php');      // ['tags.0'] — all paths to the value
$bag->findKey('tags');     // 'tags'     — first path to the KEY
$bag->findAllKeys('tags'); // ['tags']   — all paths to the key
```

Paths use `.` as the separator (e.g. `user.address.city`), making `Bag` handy
for config trees, JSON payloads, and nested request data.

---

## Interceptors

Interceptors are cross-cutting filters. They run **before** the handler
(`onRequest`) and **after** it returns (`onResponse`), in registration order.

```php
<?php

namespace Src\Modules\Blog\Interceptors;

use Pano\Kernel\BaseInterceptor;
use Pano\Kernel\BaseResponse;
use Pano\Foundation\Exception;
use Pano\Kernel\HttpStatusEnum;

final class AuthInterceptor extends BaseInterceptor
{
    public function onRequest(): void
    {
        // headers are LOWERCASED
        $token = $this->request->getHeaders()['authorization'] ?? '';

        if (!str_starts_with($token, 'Bearer ')) {
            throw new Exception(
                'Unauthorized',
                401,
                HttpStatusEnum::UNAUTHORIZED
            );
        }

        // pass data downstream to the handler via the shared attributes Bag
        $this->request->attributes->set('userId', $this->resolve($token));
    }

    public function onResponse(BaseResponse $response): BaseResponse
    {
        // Add headers to every response handled by this interceptor
        return $response->setHeader('X-Module', 'Blog');
    }
}
```

Attach interceptors to a route as the fourth argument:

```php
$router->post('/posts', PostHandler::class, 'store', [AuthInterceptor::class]);
```

### Execution order

For a route registered with `[A::class, B::class]`:

```text
A::onRequest()   →
  B::onRequest() →
    Handler::action()        (returns a Response)
  B::onResponse(response)    ←
A::onResponse(response)      ←
response->send()
```

So `onRequest` runs in **registration order** and `onResponse` runs in
**reverse order** — exactly like layered middleware (Russian-doll model).

### Sharing state with the handler

The **same** `$request` instance is shared across all interceptors and the
handler, so data left on `$request->attributes` in `onRequest()` is visible in
the handler. This is the recommended way to pass the authenticated user, a
request ID, etc. (See [The Bag](#the-bag).)

---

## Requests

The current request is available on handlers and interceptors as `$this->request`
(`Pano\Foundation\Request`). Key accessors:

```php
$this->request->getMethod();     // HttpMethodEnum (honors _method / X-HTTP-Method-Override)
$this->request->getUrl();        // path part of the URL (without the module segment)
$this->request->getQueries();    // parsed query params (associative)
$this->request->getData();       // body, auto-parsed by Content-Type
$this->request->getHeaders();    // all headers — keys are LOWERCASED
$this->request->getFiles();      // $_FILES, normalized (multi-file flattened)
$this->request->getSegments();   // URL segments as array
$this->request->getHost();       // scheme + host
$this->request->expectsJson();   // true if Accept header asks for JSON
```

### Body parsing (`getData()`)

`getData()` decodes the request body based on the `Content-Type`:

| Content-Type                        | `getData()` returns   |
|-------------------------------------|-----------------------|
| (form submitted)                    | `$_POST`              |
| `application/json`                  | decoded JSON array    |
| `application/x-www-form-urlencoded` | `parse_str` array     |
| other / empty                       | `[]` (empty array)    |

### File uploads (`getFiles()`)

`getFiles()` returns `$_FILES` **normalized**. Multi-file inputs
(e.g. `<input name="photos[]">`) are restructured into an indexed list, so you
always iterate a flat array:

```php
foreach ($this->request->getFiles()['photos'] ?? [] as $file) {
    move_uploaded_file($file['tmp_name'], $target);
}
```

### Headers

Keys are lowercased, so read them in lowercase regardless of how the client
sent them:

```php
$this->request->getHeaders()['authorization'];  // not 'Authorization'
```

### Sharing state — `$request->attributes`

The request carries a mutable `Bag` named `attributes`. It is the idiomatic
channel for passing data **from an interceptor to the handler** — the
authenticated user, a request ID, feature flags, etc. (See [The Bag](#the-bag).)

```php
// in an interceptor (runs before the handler)
$this->request->attributes->set('user', $user);

// in the handler
$user = $this->request->attributes->get('user');
```

---

## Views & Templating

A module renders templates from its `Views/` directory using
`Pano\Foundation\View`. Templates are plain PHP, with layout + section support.

**Render a view with data:**

```php
return Response::html(
    $this->module->view()
        ->with(['post' => $post])
        ->layout('layout')
        ->render('post/show')
);
```

**`Views/layout.php`** — the wrapper:

```php
<!DOCTYPE html>
<html>
<head><title><?= $this->section('title', 'Pano') ?></title></head>
<body>
    <?php $this->section('content') ?>
</body>
</html>
```

**`Views/post/show.php`** — a page that fills the layout's sections:

```php
<?php $this->start('title') ?><?= $this->e($post['title']) ?><?php $this->end() ?>

<div class="post">
    <h1><?= $this->e($post['title']) ?></h1>
    <p><?= $this->e($post['body']) ?></p>
</div>
```

Template helpers available as `$this` inside views:

| Method                                    | Description                              |
|-------------------------------------------|------------------------------------------|
| `$this->start('name')` / `$this->end()`   | Open/close a named section               |
| `$this->section('name', 'default')`       | Echo a section's content (with fallback) |
| `$this->fragment('partials/card', $data)` | Include a sub-template (with extra data) |
| `$this->e($value)`                        | HTML-escape a stringable value           |

> Always escape untrusted output with `$this->e()`.

---

## Logging

Each module owns its logs. Create the logger via `$this->module->log()` and call any
PSR-style level method:

```php
$this->module->log()->info('Post created', ['id' => $post['id']]);
$this->module->log()->error('DB connection failed', ['host' => $host]);
$this->module->log()->warning('Slow query', ['ms' => 1200]);
```

Available levels: `emergency`, `alert`, `critical`, `error`, `warning`,
`notice`, `info`, `debug`.

By default the framework's `Logger` writes to a daily file under the module's
`Logs/` directory (`log-YYYY-MM-DD.log`).

---

## CLI Commands

Pano has a single CLI entry point: the `pano` executable at the project root.

### Invocation format

```bash
php pano <module-path> <command> [positional args...] [--options...]
```

The **first positional argument is the module path** (matching a key in
`config/modules.php`), the **second is the command name**.

Examples for the skeleton's `Default` module:

```bash
# Named module (registered under 'pano' key — only active in local env)
php pano pano app:info

# Root module (registered under '' key)
php pano / app:info

# Pass positional arguments and options
php pano blog blog:publish 42 --dry-run --batch=100
```

> **Windows note:** The `/` used for the root module can be mangled by
> `cmd.exe` / Git Bash path conversion. Prefix the command with
> `MSYS_NO_PATHCONV=1` when invoking from Git Bash, or use a named module key
> instead:
>
> ```bash
> MSYS_NO_PATHCONV=1 php pano / app:info
> ```

### Registering a command

Inside a module's `routes()`, call `command()` with a command name and a
command class:

```php
$router->command('app:info', \Src\Modules\Default\Commands\DefaultCommand::class);
$router->command('blog:publish', \Src\Modules\Blog\Commands\PublishCommand::class);
```

The command class must extend `BaseCommand` and implement `handle()`:

```php
<?php

namespace Src\Modules\Blog\Commands;

use Pano\Kernel\BaseCommand;
use Pano\Kernel\ResultCodeEnum;

final class PublishCommand extends BaseCommand
{
    public function handle(array $arguments): ResultCodeEnum
    {
        // Positional arguments: $arguments (indexed array)
        $id = $arguments[0] ?? null;

        // --options are available on the request
        $dryRun = $this->request->getOptions()['dry-run'] ?? false;

        if ($id === null) {
            $this->error('Usage: blog:publish <id>');
            return ResultCodeEnum::INVALID;
        }

        $this->info("Published post {$id}" . ($dryRun ? ' (dry-run)' : ''));
        return ResultCodeEnum::OK;
    }
}
```

### Inside a command

`BaseCommand` gives you:

```php
$this->request;       // the CLIRequest
$this->module;        // the owning module (so $this->module->log() works in CLI too)
$this->info($text);   // print a green line
$this->error($text);  // print a red line
```

### Return codes

`handle()` returns a `ResultCodeEnum`:

| Value     | Meaning                     |
|-----------|-----------------------------|
| `OK`      | success                     |
| `ERROR`   | general failure             |
| `INVALID` | invalid input / usage error |

This drives the terminal output color and signals failure to the shell.

---

## Exceptions & Errors

Throw `Pano\Foundation\Exception` to control the HTTP response status, message,
and optional payload. Pano formats it automatically based on the request:

```php
use Pano\Foundation\Exception;
use Pano\Kernel\HttpStatusEnum;

throw new Exception(
    'Post not found',
    code: 404,
    status: HttpStatusEnum::NOT_FOUND,
    payload: ['hint' => 'Check the post id']
);
```

### How it renders

| Context         | Output                                      |
|-----------------|---------------------------------------------|
| CLI request     | colored terminal line                       |
| `expectsJson()` | JSON body `{ "message": ..., "data": ... }` |
| otherwise       | HTML error page                             |

In `APP_DEBUG=true` mode, the rendered body includes the exception class name and
stack trace; in production it is hidden.

### Custom exception types

For richer domain errors, extend `BaseException` and implement `toArray()` and
`toHtml()`:

```php
namespace Src\Modules\Blog\Exceptions;

use Pano\Kernel\BaseException;

final class ValidationException extends BaseException
{
    public function toArray(bool $debug = false): array
    {
        return [
            'message' => $this->getMessage(),
            'errors'  => $this->payload ?? [],
        ];
    }

    public function toHtml(bool $debug = false): string
    {
        return '<h1>Validation failed</h1><pre>'
             . htmlspecialchars($this->getMessage())
             . '</pre>';
    }
}
```

Throw it anywhere in a handler or interceptor — the global handler will render
it correctly.

### Non-`BaseException` throwables

Plain `\Throwable` instances (PHP errors, third-party exceptions) are rendered as
a generic `500 Server Error`, with the real message shown only in debug mode.

---

## Helper Functions

These globals are always available (autoloaded by the framework):

| Function                        | Description                                          |
|---------------------------------|------------------------------------------------------|
| `env($key, $default = null)`    | Read a value from `.env`                             |
| `config($key, $default = null)` | Dot-notation config read (e.g. `config('app.name')`) |
| `url($path)`                    | Build an absolute URL using `APP_URL`                |
| `currentUrl()`                  | Return the current request URL                       |
| `dd(...$args)`                  | Dump and die (CLI or HTML aware)                     |

```php
env('APP_NAME', 'Pano');
config('app.debug', false);
url('posts/42');
currentUrl();
dd($user, $request->getData());
```

---

## Testing

The skeleton ships with PHPUnit. Tests live in `tests/` under the `Tests\`
namespace.

```bash
./vendor/bin/phpunit
```

A starter test is included. Example:

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Src\Modules\Default\DefaultModule;

class DefaultModuleTest extends TestCase
{
    public function test_module_class_exists()
    {
        $this->assertTrue(class_exists(DefaultModule::class));
    }
}
```

`phpunit.xml` is preconfigured with the Pano Test Suite.

---

## Web Server Setup

### Apache

`public/.htaccess` is already configured. Point your virtual host `DocumentRoot`
to the `public/` directory. It handles:

- Removing trailing slashes
- Serving real files/directories directly
- Forwarding everything else to `public/index.php`
- Preserving the `Authorization` header (for JWT / Bearer tokens)

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

### Development server

```bash
php -S localhost:8000 -t public
```

---

## Build Your First Feature — Checklist

1. **Create a module** under `modules/<Name>/<Name>Module.php` extending
   `Pano\Kernel\BaseModule`.
2. **Register it** in `config/modules.php` under a resolver key.
3. **Add handlers** (extending `Pano\Kernel\BaseHandler`) returning a
   `Pano\Foundation\Response`.
4. **Define routes** in the module's `routes()` method.
5. **(Optional)** Add interceptors for auth/validation, commands for CLI tasks,
   and views for HTML.
6. **Test it** with `./vendor/bin/phpunit`.

---

## Learn More

- **Framework source & docs:** [pano-php/pano-framework](https://github.com/pano-php/pano-framework)
- **Full developer guide:** [DOCUMENTATION.md](https://github.com/pano-php/pano-framework/blob/main/DOCUMENTATION.md)
- **Architecture:** [ARCHITECTURE.md](https://github.com/pano-php/pano-framework/blob/main/ARCHITECTURE.md)
- **Philosophy:** [MANIFESTO.md](https://github.com/pano-php/pano-framework/blob/main/MANIFESTO.md)

Pano is deliberately unopinionated — you bring the architecture. The framework
should never make decisions on your behalf.

---

## License

The MIT License (MIT). See [`LICENSE`](LICENSE).
