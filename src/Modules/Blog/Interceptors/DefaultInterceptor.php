<?php

namespace Src\Modules\Blog\Interceptors;

use Pano\Kernel\BaseInterceptor;
use Pano\Kernel\BaseResponse;

class DefaultInterceptor extends BaseInterceptor
{
    public function onRequest(): void
    {
    }

    public function onResponse(BaseResponse $response): BaseResponse
    {
        return parent::onResponse($response);
    }

}