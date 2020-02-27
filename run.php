<?php

require 'vendor/autoload.php';

define('ABS_PATH', __DIR__);
define('DATA_PATH', __DIR__ . '/data');

function dump() {
    foreach (func_get_args() as $dump) {
        echo '<pre>';
        var_dump($dump);
        echo '</pre>';
    }
}

function dd() {
    dump(...func_get_args());
    die;
}
