<?php

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Clean the MP4
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

    public function clean($inputFile, $outputFile, $edlFile = false)
    {
        $tempFile = $inputFile . '.tmp';
        $command  = 'mencoder ' . $inputFile . ' -o ' .  $tempFile;
        if ($edlFile) {
            $command .= ' -edl ' .  $edlFile;
        }
        $command .= ' -oac faac -faacopts mpeg=4:object=2:raw:br=128';
        $command .= ' -af volnorm=1 -ovc copy -of lavf';

        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        $this->mux($tempFile, $outputFile);
        unlink($tempFile);
    }

    protected function mux($inputFile, $outputFile)
    {
        $command = 'MP4Box -raw 1 ' . $inputFile;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        $command = 'MP4Box -raw 2 ' . $inputFile;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        $fileRoot     = substr($mpegPath, 0, strrpos($mpegPath, '.'));
        $fileList     = glob($fileRoot . '.*');
        $extensionMap = array();
        foreach($fileList as $file) {
            $extension = substr($file, strrpos($file, '.'));
            $extensionMap[$extension] = $file;
        }

        $videoFile = $extensionMap['.h264'];
        $audioFile = $extensionMap['.aac'];

        $command = 'MP4Box -new ' . $outputFile . ' -add ' . $videoFile . ' -add ' . $audioFile;
        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();
    }

        $cleanT1 = $fileRoot . ".clean_track1.h264";
	$cleanT2 = $fileRoot . ".clean_track2.aac";
	$remux = $fileRoot . ".remux.mp4";

	$t1 = "MP4Box -raw 1 $clean";
	log_message('debug', $t1);
	shell_exec($t1);

	$t2 = "MP4Box -raw 2 $clean";
	log_message('debug', $t2);
	shell_exec($t2);

	$r = "MP4Box -new $remux -add $cleanT1 -add $cleanT2";
	log_message('debug', $r);
	shell_exec($r);
}
