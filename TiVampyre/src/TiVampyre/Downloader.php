<?php

namespace TiVampyre;

use Silex\Application;

/**
 * Download and decode files
 */
class Downloader
{
    private $app    = null;
    private $logger = null;

    public function __construct(Application $app)
    {
        $this->app    = $app;
        $this->logger = $app['monolog'];
    }

    public function process($data)
    {
        $showId = $data['show'];

        $repository = $this->app['orm.em']->getRepository('TiVampyre\Entity\Show');
        $showEntity = $repository->find($showId);
        if (!$showEntity) {
            $this->logger->warn('Show Not Found');
            return;
        }

        $rawFilename = $this->app['tivampyre_working_directory'] . $showEntity->getId();

        // TODO: Download full, not preview.
        $this->app['tivo_downloader']->storePreview(
            $showEntity->getURL(),
            $rawFilename . '.tivo'
        );

        $this->decode($rawFilename);
    }

    private function decode($rawFilename) {
        $this->app['tivo_decoder']->decode(
            $rawFilename . '.tivo',
            $rawFilename . '.mpeg'
        );
        unlink($rawFilename . '.tivo');
    }
}