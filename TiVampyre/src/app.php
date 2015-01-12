<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use GuzzleHttp\Client as GuzzleClient;
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
use TiVampyre\Twitter as TiVoTwitter;

// Set TimeZone
date_default_timezone_set('America/Chicago');

// Check that config exists
$configFile = __DIR__ . '/../config/tivampyre.json';
if (file_exists($configFile) == false) {
    throw new Exception('No Config File. Fix that.');
}

$app = new Application();
$app->register(new ConfigServiceProvider($configFile));

// Start registering database services
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

// Register the base pieces needed for Silex.
$app->register(new UrlGeneratorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider(), array(
    'twig.path'    => array(__DIR__.'/../templates'),
    //'twig.options' => array('cache' => __DIR__.'/../cache/twig'),
));

// Setup an instance of Symfony Process and Guzzle.
$app['process'] = new Process('');
$app['guzzle']  = new GuzzleClient();

// Setup Monolog Logger.
$logHandler = new StreamHandler(__DIR__.'/../logs/tivampyre.log');
$app['monolog'] = new Logger('tivampyre');
$app['monolog']->pushHandler($logHandler);

// Setup the Twitter services.
$app['twitter'] = new Twitter(
    $app['twitter_consumer_key'],
    $app['twitter_consumer_secret'],
    $app['twitter_access_token'],
    $app['twitter_access_token_secret']
);
$app['tweet'] = new TiVampyre\Twitter\Tweet(
    $app['twitter'],
    $app['monolog'],
    $app['twitter_production']
);

// Setup Twig (this might be optional)
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    // add custom twig globals, filters, tags, ...
    return $twig;
}));

$app['queue'] = new Pheanstalk\Pheanstalk('127.0.0.1:11300');

// If IP isn't set, look it up.
if (!isset($app['tivo_ip'])) {
    $location = new TiVo\Location($app['process'], $app['monolog']);
    $app['tivo_ip'] = $location->find();
}

// Manage the TiVo's connection to Now Playing.
$app['tivo_now_playing'] = function ($app) {
    return new TiVo\NowPlaying(
        $app['tivo_ip'],
        $app['tivampyre_mak'],
        $app['guzzle'],
        $app['monolog']
    );
};

// TiVo Downloader
$app['tivo_downloader'] = function ($app) {
    return new TiVo\Download(
        $app['tivampyre_mak'],
        $app['guzzle'],
        $app['monolog']
    );
};

// TiVo Decoder
$app['tivo_decoder'] = function ($app) {
    return new TiVo\Decode(
        $app['tivampyre_mak'],
        $app['process'],
        $app['monolog']
    );
};

// Video Transcoder
$app['video_transcoder'] = function ($app) {
    return new TiVampyre\Video\Transcode(
        $app['process'],
        $app['monolog']
    );
};
$app['comskip'] = function ($app) {
    return new TiVampyre\Video\Comskip(
        $app['comskip_path'],
        $app['process'],
        $app['monolog']
    );
};
$app['video_cleaner'] = function ($app) {
    return new TiVampyre\Video\Clean(
        $app['process'],
        $app['monolog']
    );
};
// Video Labeler
$app['video_labeler'] = function ($app) {
    return new TiVampyre\Video\Label(
        $app['process'],
        $app['tivampyre_working_directory'],
        $app['monolog']
    );
};

// Manage the TiVo's show list syncing.
$app['sync_service'] = function ($app) {
    return new TiVampyre\Sync(
        $app['orm.em'],
        $app['tivo_now_playing'],
        $app['dispatcher'],
        $app['monolog']
    );
};

// Setup event listeners.
$app['dispatcher']->addListener(TiVoTwitter\TweetEvent::$SHOW_TWEET_EVENT, function($event) use ($app) {
    $app['tweet']->captureShowEvent($event);
});
$app['dispatcher']->addListener(TiVoTwitter\TweetEvent::$PREVIEW_TWEET_EVENT, function($event) use ($app) {
    $app['tweet']->capturePreviewEvent($event);
});

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
