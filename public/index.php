<?php

require '../run.php';

$root = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['DOCUMENT_URI'];

if (file_exists($root) && !in_array($_SERVER['DOCUMENT_URI'], ['/index.php', '/', ''])) {
    require __DIR__ . $_SERVER['DOCUMENT_URI'];
    die;
}
?>
