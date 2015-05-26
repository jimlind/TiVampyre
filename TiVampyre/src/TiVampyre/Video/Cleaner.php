<?php

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Clean the h264/aac MP4
 */
class Cleaner
{
    protected $process = null;

    protected $logger = null;

    public function __construct(
        ProcessBuilder $processBuilder,
        LoggerInterface $logger
    ) {
        $this->process     = $processBuilder;
        $this->logger      = $logger;
    }

    public function clean($inputFileList, $outputFile)
    {
        $this->merge($inputFileList, $outputFile);
        $this->adjustAudio($outputFile);
    }

    protected function merge($inputFileList, $outputFile)
    {
        $command = 'MP4Box -new ' . $outputFile;
        foreach ($inputFileList as $inputFile) {
            $command .= ' -cat ' . $inputFile;
        }

        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        foreach ($inputFileList as $inputFile) {
            unlink($inputFile);
        }
    }

    protected function adjustAudio($m4vFile)
    {
        $command = 'aacgain -f -r -c ' . $m4vFile;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();
    }
}
