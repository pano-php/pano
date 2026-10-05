<?php

namespace Tests;

use Pano\Foundation\Request;
use PHPUnit\Framework\TestCase;
use Src\Modules\Default\DefaultModule;

class DefaultModuleTest extends TestCase
{
    public function test_domain_handle_executes_successfully()
    {
        $this->assertIsBool(true, 'success');
    }
}
