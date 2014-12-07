<?php

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Clean the h264/aac MP4
 */
class Clean
{
    protected $process = null;

    protected $logger = null;

    public function __construct(Process $process, LoggerInterface $logger)
    {
        $this->process     = $process;
        $this->logger      = $logger;
    }

    public function clean($inputFileList, $outputFile)
    {
        $this->merge($inputFileList, $outputFile);

        $videoTrack = $this->demux($outputFile, 1);
        $audioTrack = $this->demux($outputFile, 2);
        unlink($outputFile);

        $videoFile = $videoTrack . '.h264';
        $audioFile = $audioTrack . '.aac';

        rename($videoTrack, $videoFile);
        rename($audioTrack, $audioFile);

        $this->adjustAudio($audioFile);

        $this->mux($videoFile, $audioFile, $outputFile);
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

    protected function demux($inputFile, $track)
    {
        $command = 'MP4Box -raw ' . $track . ' ' . $inputFile;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        $fileRoot = substr($inputFile, 0, strrpos($inputFile, '.'));
        return $fileRoot . '_track' . $track;
    }

    protected function mux($videoFile, $audioFile, $outputFile)
    {
        $command = 'MP4Box -new ' . $outputFile . ' -add ' . $videoFile . ' -add ' . $audioFile;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        unlink($videoFile);
        unlink($audioFile);
    }

    protected function adjustAudio($aacFile)
    {
        $command = 'aacgain -f -r -c ' . $aacFile;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();
    }
}
