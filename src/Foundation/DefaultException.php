<?php

namespace Src\Foundation;

use Pano\Foundation\Exception;
use Pano\Foundation\View;

class DefaultException extends Exception
{
    public function toHtml(bool $debug = false): string
    {
        return (new View(path('public')))
            ->with([
                'exception' => $this,
                'debug' => $debug,
                'code' => $this->getCode() > 300 ? $this->getCode() : 500,
            ])->render('error');
    }
}