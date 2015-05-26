<?php

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ProcessBuilder;
use TiVampyre\Entity\Show;

/**
 * Label Video Files
 */
class Labeler
{
    protected $process = null;

    protected $workingDirectory = null;

    protected $logger = null;

    /**
     * @var string
     */
    protected $output = null;

    public function __construct(ProcessBuilder $processBuilder, $directory, LoggerInterface $logger)
    {
        $this->process          = $processBuilder;
        $this->workingDirectory = $directory;
        $this->logger           = $logger;
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

        if ($episodeTitle || $episodeNumber) {
            $command .= ' --stik "TV Show"';
        } else {
            $command .= ' --stik "Movie"';
        }

        $command .= ' --overWrite ';

        $this->process->setCommandLine($command);
        $this->process->setTimeout(60); // 1 minute
        $this->process->run();
    }

    public function renameFile(Show $show, $originalFile)
    {
        $showTitle     = $show->getShowTitle();
        $episodeTitle  = $show->getEpisodeTitle();
        $episodeNumber = $show->getEpisodeNumber();

        $rawFilename = $showTitle;
        if ($episodeNumber !== 0) {
            $rawFilename .= ' ' . $episodeNumber;
        }
        if ($episodeTitle !== '') {
            $rawFilename .= ' ' . $episodeTitle;
        }
        $rawFilename .= '.m4v';
        $cleanFilename = preg_replace(['/[^A-Za-z0-9 \.]/', '/\s\s+/'], ' ', $rawFilename);
        $cleanFile     = $this->workingDirectory . $cleanFilename;

        $command = 'mv "' . $originalFile . '" "' . $cleanFile . '"';

        $this->process->setCommandLine($command);
        $this->process->setTimeout(60); // 1 minute
        $this->process->run();

        return $cleanFile;
    }
}
