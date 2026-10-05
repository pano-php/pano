<?php

namespace Src\Packages\Info;

use Src\Packages\Info\Commands\InfoCommand;
use Pano\Foundation\Logger;
use Pano\Foundation\View;
use Pano\Kernel\BaseLogger;
use Pano\Kernel\BasePackage;
use Pano\Kernel\BaseView;

final readonly class InfoPackage extends BasePackage
{

    public function view(): BaseView
    {
        return new View($this->viewPath());
    }

    public function log(): BaseLogger
    {
        return new Logger($this->logPath());
    }

    public function setup(): void
    {
        $this->router->command('app:info', InfoCommand::class);
    }
}