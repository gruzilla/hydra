<?php

header('X-Hydra: result');
header('Content-Type: application/json');

// this is dirty.
chdir(__DIR__ . '/../../../../../../../');
ini_set('html_errors', false);

require_once 'vendor/autoload.php';

use Hydra\Hydra,
    Hydra\ServiceProviders\DefaultServiceProvider,
    Hydra\OAuth\HydraTokenStorage;


$reqUrl = empty($_SERVER['REQUEST_URI']) ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
$serviceName = strtolower(trim(array_shift(explode('?', $reqUrl)), '/'));

if (empty($serviceName)) {
    echo 'please provide a service name ' .$reqUrl;
    exit;
}

// TODO: refactor, make configurable/injectable
try {
    $storage = new HydraTokenStorage();
    $serviceProvider = new DefaultServiceProvider($storage);

    $service = $serviceProvider->createService($serviceName);
    $serviceProvider->retrieveAccessToken($serviceName, $_REQUEST);

    echo 'Access token stored!';
} catch (\Exception $e) {
    echo 'ERROR: ' .$e;
}
