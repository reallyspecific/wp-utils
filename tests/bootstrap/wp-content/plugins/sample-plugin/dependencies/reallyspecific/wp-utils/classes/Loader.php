<?php

namespace ReallySpecific\SamplePlugin\Dependencies\RS_Utils;

use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\setup;
class Loader
{
    public function __construct()
    {
        if (!function_exists('ReallySpecific\SamplePlugin\Dependencies\RS_Utils\setup')) {
            require_once __DIR__ . '/../load.php';
            setup();
        }
    }
}
