<?php

namespace Src\Foundation;

use Pano\Foundation\Foundation;
use Pano\Kernel\ModuleResolverEnum;
use Src\Modules\Blog\BlogModule;
use Src\Modules\Default\DefaultModule;

class DefaultFoundation extends Foundation
{
    protected static array $modules = [
        '' => [
            'class' => DefaultModule::class,
            'resolver' => ModuleResolverEnum::PATH
        ],
        'blog' => [
            'class' => BlogModule::class,
            'resolver' => ModuleResolverEnum::QUERY
        ],
    ];

    public static function exception(): string
    {
        return DefaultException::class;
    }
}
