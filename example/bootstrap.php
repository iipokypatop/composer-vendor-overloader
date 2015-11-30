<?php

require_once __DIR__ . "/vendor/autoload.php";

/** to overload autoload vendors from ./project_root/vendor/your-vendor-1 to  ./project_root/../vendor/your-vendor-1*/

\Overloader\Overloader::load([
    'your-vendor-1',
    'your-vendor-2',
]);