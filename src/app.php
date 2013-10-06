<?php

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\Process\Process;

$app = new Application();
$app->register(new Igorw\Silex\ConfigServiceProvider(
	__DIR__ . '/../config/tivampyre.json'
));
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/../db/tivampyre.db',
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
    //'twig.options' => array('cache' => __DIR__.'/../cache/twig'),
));
$app['process'] = new Process('');
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
}));
$app['tivo_locater'] = function ($app) {
    return new JimLind\TiVo\Location($app['monolog'], $app['process']);
};
$app['tivo_now_playing'] = function ($app) {
	return new JimLind\TiVo\NowPlaying(
		$app['tivo_locater'],
		$app['tivampyre_mak'],
		$app['monolog'],
		$app['db']
	);
};

return $app;
