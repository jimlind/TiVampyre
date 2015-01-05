<?php

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use TiVampyre\Entity\Show;

/**
 * Label Video Files
 */
class Label
{
    protected $process = null;

    protected $logger = null;

    /**
     * @var string
     */
    protected $output = null;

    public function __construct(Process $process, LoggerInterface $logger)
    {
        $this->process  = $process;
        $this->logger   = $logger;
    }

    public function addMetadata(Show $show, $file)
    {
        $episodeTitle  = $show->getEpisodeTitle();
        $description   = $show->getDescription();
        $showTitle     = $show->getShowTitle();
        $episodeNumber = $show->getEpisodeNumber();
        $station       = $show->getStation();

        $command  = 'AtomicParsley "' . $file . '"';
        $command .= empty($episodeTitle) ? ''  : ' --title "' . $episodeTitle . '"';
        $command .= empty($description) ? ''   : ' --description "' . $description . '"';
        $command .= empty($showTitle) ? ''     : ' --TVShowName "' . $showTitle . '"';
        $command .= empty($episodeNumber) ? '' : ' --TVEpisode "' . $episodeNumber . '"';
        $command .= empty($episodeNumber) ? '' : ' --TVEpisodeNum "' . $episodeNumber . '"';
        $command .= empty($station) ? ''       : ' --TVNetwork "' . $station . '"';

        if (empty($episodeNumber) || $episodeNumber == 0) {
            $command .= ' --stik "TV Show"';
        } else {
            $command .= ' --stik "Movie"';
        }

        $command .= ' --overWrite ';

        $this->process->setCommandLine($command);
        $this->process->setTimeout(60); // 1 minute
        $this->process->run();

        $this->output = $this->process->getOutput();
    }

    public function renameFile(Show $show, $file)
    {
        return 'pretty file name - subtitle.m4v';
    }
}
