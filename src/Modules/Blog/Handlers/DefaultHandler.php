<?php


namespace Src\Modules\Blog\Handlers;

use Composer\InstalledVersions;
use Pano\Foundation\Response;
use Pano\Kernel\BaseHandler;

final class DefaultHandler extends BaseHandler
{

    public function info(): Response
    {
        $version = InstalledVersions::getPrettyVersion('pano-php/pano') ?? 'dev';

        return Response::html(
            $this->module->view()
                ->with(['name' => env('APP_NAME', 'Pano'), 'version' => $version])
                ->layout('layout')
                ->render('home')
        );
    }
}