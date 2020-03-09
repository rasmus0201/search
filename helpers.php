<?php

if (!function_exists('dump')) {
    function dump() {
        foreach (func_get_args() as $dump) {
            echo '<pre>';
            var_dump($dump);
            echo '</pre>';
        }
    }
}

if (!function_exists('dd')) {
    function dd() {
        dump(...func_get_args());
        die;
    }
}
