<?php

namespace Src\Foundation;

use Pano\Foundation\Foundation;
use Src\Modules\Default\DefaultModule;

class DefaultFoundation extends Foundation
{
    protected static array $modules = [
        '' => DefaultModule::class,
    ];

    public static function exception(): string
    {
        return DefaultException::class;
    }
}
