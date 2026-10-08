<?php

namespace Src\Foundation;

use Pano\Foundation\Foundation;
use Pano\Kernel\ModuleResolverEnum;
use Src\Modules\Default\DefaultModule;

class DefaultFoundation extends Foundation
{
    protected static array $modules = [
        '' => [
            'class' => DefaultModule::class,
            'resolver' => ModuleResolverEnum::PATH
        ]
    ];

    public static function exception(): string
    {
        return DefaultException::class;
    }
}
