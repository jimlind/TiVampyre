<?php

use GuzzleHttp\Client as GuzzleClient;
use Igorw\Silex\ConfigServiceProvider;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Process\ProcessBuilder;

// Set TimeZone
date_default_timezone_set('America/Chicago');

// Check that config exists
$configFile = __DIR__ . '/../config/tivampyre.json';
if (file_exists($configFile) == false) {
    throw new Exception('No Config File. Fix that.');
}

$app = new Application();
$app->register(new ConfigServiceProvider($configFile));

// Register Database Services
\Application\DoctrineConfig::setup($app, __DIR__);

// Register the base pieces needed for Silex.
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path'    => array(__DIR__.'/../templates'),
    //'twig.options' => array('cache' => __DIR__.'/../cache/twig'),
));

// Setup an instance of Symfony ProcessBuilder and Guzzle.
$app['process_builder'] = new ProcessBuilder();
$app['guzzle']          = new GuzzleClient();

// Setup Monolog Logger.
$logHandler = new StreamHandler(__DIR__.'/../logs/tivampyre.log');
$app['monolog'] = new Logger('tivampyre');
$app['monolog']->pushHandler($logHandler);

// Setup Twig (this might be optional)
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    // add custom twig globals, filters, tags, ...
    return $twig;
}));

// Beanstalk Queue
$app['queue'] = new Pheanstalk\Pheanstalk('127.0.0.1:11300');

// Show Entity Provider
$app['show_provider'] = function ($app) {
    return new TiVampyre\Service\ShowProvider(
        $app['tivo_now_playing'],
        new TiVampyre\Factory\ShowListFactory()
    );
};

// TiVo's Show List Synchronizer.
$app['synchronizer'] = function ($app) {
    return new TiVampyre\Synchronizer(
        $app['orm.em'],
        $app['show_provider'],
        $app['tweet_dispatcher']
    );
};

// TiVo's Show Downloader.
$app['downloader'] = function ($app) {
    return new TiVampyre\Downloader(
        $app['orm.em']->getRepository('TiVampyre\Entity\ShowEntity'),
        $app['tivo_downloader'],
        $app['tivo_decoder'],
        $app['tivampyre_working_directory']
    );
};


\Application\TiVoConfig::setup($app);
\Application\TwitterConfig::setup($app);
\Application\VideoConfig::setup($app);



/*
$app['job_queue'] = function ($app) {
    return new TiVampyre\JobQueue($app['db']);
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
 */

return $app;
