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

        $this->adjustAudio($audioTrack);

        $this->mux($videoTrack, $audioTrack, $outputFile);
    }

    protected function merge($inputFileList, $outputFile)
    {
        $command = 'MP4Box -new ' . $outputFile;
        foreach ($inputFileList as $inputFile) {
            $command .= ' -cat ' . $inputFile;
            $this->remux($inputFile);
        }

        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();
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

    protected function mux($videoTrack, $audioTrack, $outputFile)
    {
        $videoRename = $videoTrack . '.h264';
        $audioRename = $audioTrack . '.mp3';

        rename($videoTrack, $videoRename);
        rename($audioTrack, $audioRename);

        $command = 'MP4Box -new ' . $outputFile . ' -add ' . $videoRename . ' -add ' . $audioRename;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        unlink($videoRename);
        unlink($audioRename);
    }

    protected function remux($mp4File)
    {
        $videoTrack = $this->demux($mp4File, 1);
        $audioTrack = $this->demux($mp4File, 2);
        unlink($mp4File);
        $this->mux($videoTrack, $audioTrack, $mp4File);
    }

    protected function adjustAudio($aacFile)
    {
        var_dump($aacFile);
        return false;

        $command = 'MP4Box -new ' . $outputFile . ' -add ' . $videoTrack . ' -add ' . $audioTrack;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();
    }
}
