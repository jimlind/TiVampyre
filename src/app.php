<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Igorw\Silex\ConfigServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Process\Process;

error_reporting(E_ALL);
ini_set('display_errors', '1');

$app = new Application();
$app->register(new ConfigServiceProvider(
    __DIR__ . '/../config/tivampyre.json'
));
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
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/tivampyre.log',
    'monolog.level'   => \Monolog\Logger::WARNING,
));
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path'    => array(__DIR__.'/../templates'),
    'twig.options' => array('cache' => __DIR__.'/../cache/twig'),
));
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
    return new TiVo\Location($app['monolog'], $app['process']);
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
