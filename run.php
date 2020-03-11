<?php
require 'vendor/autoload.php';
require 'helpers.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('ABS_PATH', __DIR__);
