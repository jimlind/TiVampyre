<?php

$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile) == false) {
    throw new Exception('No Autoload File. Install Composer.');
}
require_once($autoloadFile);

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../src/controllers.php';
$app->run();