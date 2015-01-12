<?php

namespace TiVampyre;

use Silex\Application;

/**
 * Transcode files
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

        /*
        $app['video_labeler']->addMetadata($showEntity, $rawFilename . '.m4v');
        $cleanFilename = $app['video_labeler']->renameFile($showEntity, $rawFilename . '.m4v');

        $output->write('Downloaded to ' . $cleanFilename, true);

        if ($optionList['keep']) {
            $output->write('Original MPEG written to ' . $rawFilename . '.mpeg', true);
        } else {
            unlink($rawFilename . '.mpeg');
        }
         */
    }
}