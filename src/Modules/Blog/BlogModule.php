<?php

namespace Src\Modules\Blog;

use Src\Modules\Blog\Handlers\DefaultHandler;
use Src\Modules\Blog\Interceptors\DefaultInterceptor;
use Src\Packages\Info\InfoPackage;
use Pano\Foundation\Logger;
use Pano\Foundation\View;
use Pano\Kernel\BaseFoundation;
use Pano\Kernel\BaseLogger;
use Pano\Kernel\BaseModule;
use Pano\Kernel\BaseRequest;
use Pano\Kernel\BaseView;

final readonly class BlogModule extends BaseModule
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
        $this->router->get('/info', DefaultHandler::class, 'info', [DefaultInterceptor::class]);
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