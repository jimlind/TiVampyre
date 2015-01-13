<?php

namespace TiVampyre;

use Silex\Application;

/**
 * Transcode files
 */
class Transcoder
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
        $rawFilename = $this->app['tivampyre_working_directory'] . $data['show'];

        $chapterList = array();
        if ($data['cut']) {
            $chapterList = $this->app['comskip']->getChapterList($rawFilename . '.mpeg');
        }

        $fileList = $this->app['video_transcoder']->transcode(
            $rawFilename . '.mpeg',
            $chapterList,
            $data['auto']
        );

        $this->app['video_cleaner']->clean(
            $fileList,
            $rawFilename . '.m4v'
        );

        $showId     = $data['show'];
        $repository = $this->app['orm.em']->getRepository('TiVampyre\Entity\Show');
        $showEntity = $repository->find($showId);

        $this->app['video_labeler']->addMetadata($showEntity, $rawFilename . '.m4v');
        $this->app['video_labeler']->renameFile($showEntity, $rawFilename . '.m4v');

        if (!$data['keep']) {
            unlink($rawFilename . '.mpeg');
        }
    }
}