<?php

namespace Src\Foundation;

use Pano\Foundation\Exception;
use Pano\Foundation\Response;
use Pano\Foundation\View;
use Pano\Kernel\HttpStatusEnum;

class DefaultException extends Exception
{
    public function toHtml(bool $debug = false): string
    {
        $view = new View(path('public'));
        Response::html(
            $view->with([
                'exception' => $this,
                'debug' => $debug,
                'code' => $this->getCode() > 300 ? $this->getCode() : 500,
            ])->render('error'), HttpStatusEnum::INTERNAL_SERVER_ERROR)
            ->send();
        exit(0);
    }
}