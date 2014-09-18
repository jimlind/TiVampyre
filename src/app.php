<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Igorw\Silex\ConfigServiceProvider;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Process\Process;

use JimLind\TiVo;

// Set TimeZone
date_default_timezone_set('America/Chicago');

// Check that config exists
$configFile = __DIR__ . '/../config/tivampyre.json';
if (file_exists($configFile) == false) {
    throw new Exception('No Config File. Fix that.');
}
        
$app = new Application();
$app->register(new ConfigServiceProvider($configFile));

// Process Optional Settings
if (!isset($app['tivo_ip'])) {
    $app['tivo_ip'] = false;
}

// Start Registering Services
$app->register(new DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_sqlite',
        'path'   => __DIR__ . '/../db/tivampyre.db',
    ),
));
$app->register(new DoctrineOrmServiceProvider, array(
    'orm.proxies_dir' => __DIR__ . '/../cache/doctrine/proxies',
    'orm.em.options'  => array(
        'mappings' => array(
            array(
                'type'      => 'annotation',
                'namespace' => 'TiVampyre\Entity',
                'path'      => __DIR__ . '/TiVampyre/Entity',
            ),
        ),
    ),
));
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path'    => array(__DIR__.'/../templates'),
    //'twig.options' => array('cache' => __DIR__.'/../cache/twig'),
));

// Setup Monolog Logger
$logHandler = new StreamHandler(__DIR__.'/../logs/tivampyre.log');
$app['monolog'] = new Logger('tivampyre');
$app['monolog']->pushHandler($logHandler);

$app['process'] = new Process('');
$app['twitter'] = new Twitter(
    $app['twitter_consumer_key'],
    $app['twitter_consumer_secret'],
    $app['twitter_access_token'],
    $app['twitter_access_token_secret']
);
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    // add custom twig globals, filters, tags, ...
    return $twig;
}));
$app['tivo_locater'] = function ($app) {
    return new TiVo\Location(
        new Process(''),
        $app['monolog']
    );
};
$app['tivo_now_playing'] = function ($app) {
    return new TiVo\NowPlaying(
        $app['tivo_locater'],
        $app['tivampyre_mak'],
        $app['monolog'],
        $app['process']
    );
};
$app['job_queue'] = function ($app) {
    return new TiVampyre\JobQueue($app['db']);
};
$app['show_service'] = function ($app) {
    return new TiVampyre\Service\Show(
        $app['orm.em'],
        $app['tivo_now_playing'],
        $app['twitter'],
        $app['monolog']
    );
};
$app['google_scraper'] = function ($app) {
    return new Image\Google(
        $app['google_api_key'],
        $app['process']
    );
};
$app['image_service'] = function ($app) {
    return new Image\Builder(
        array(
            'h' => 100,
            'w' => 100,
        ),
        $app['google_scraper']
    );
};

return $app;
