<?php

namespace TiVampyre\Video;

use Symfony\Component\Process\Process;

/**
 * Transcode an MPEG file
 */
class Utilities
{
    /**
     * Check if HandBrake Command Line is installed.
     *
     * @param Symfony\Component\Process\Process $process
     * @return type
     */
    public static function checkHandBrake(Process $process)
    {
        $command = 'HandBrakeCLI --help';

        $process->setCommandLine($command);
        $process->setTimeout(1); // 1 second
        $process->run();

        $output = $process->getOutput();
        // HandBrake Command Line reports "Syntax: HandBrakeCLI [options] -i <device> -o <file>"
        return strpos($output, 'Syntax: HandBrakeCLI') !== false;
    }
}

