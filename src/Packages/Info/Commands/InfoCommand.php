<?php

namespace Src\Packages\Info\Commands;

use Composer\InstalledVersions;
use Pano\Kernel\BaseCommand;
use Pano\Kernel\ResultCodeEnum;

class InfoCommand extends BaseCommand
{

    public function handle(array $arguments): ResultCodeEnum
    {
        $version = InstalledVersions::getPrettyVersion('pano-php/pano') ?? 'dev';
        $this->info(config('app.name') . " - " . $version);
        return ResultCodeEnum::OK;
    }
}