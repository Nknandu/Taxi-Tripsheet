<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . '/../user/autoload.php';

if (defined('E_DEPRECATED')) {
    error_reporting(error_reporting() | E_DEPRECATED);
}

Hamcrest\Util::registerGlobalFunctions();
