<?php
define("PANO_STARTED", microtime(true));
$basePath = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;

require $basePath . '/vendor/autoload.php';

(new \Pano\Foundation\Boot($basePath, new \Src\Foundation\DefaultFoundation()))->run($_SERVER);
