<?php

namespace TiVampyre\Video;

use JimLind\TiVo\Utilities as TiVoUtilities;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Generate a file of commericals from an MPEG file
 */
class Comskip
{
    protected $comskipPath = null;

    protected $process = null;

    protected $logger = null;

    public function __construct($comskipPath, Process $process, LoggerInterface $logger)
    {
        $this->comskipPath = $comskipPath;
        $this->process     = $process;
        $this->logger      = $logger;
    }

    public function generateEdl($mpegPath)
    {
        $appPath = $this->comskipPath . 'comskip.exe';
        $iniPath = $this->comskipPath . 'comskip.ini';
        $command = 'wine ' . $appPath . ' --ini=' .  $iniPath . ' ' . $mpegPath;

        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        $edlFile  = false;
        $rmList = array('.log', '.txt');

        $fileRoot = substr($mpegPath, 0, strrpos($mpegPath, '.'));
        $fileList = glob($fileRoot . '.*');
        foreach($fileList as $file) {
            $extension = substr($file, strrpos($file, '.'));
            if (in_array($extension, $rmList)) {
                unlink($file);
            }
            if ($extension === '.edl') {
                $edlFile = $file;
            }
        }

        return $edlFile;
    }
}
