<?php

namespace TiVampyre\Video;

use JimLind\TiVo\Utilities as TiVoUtilities;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use TiVampyre\Video\Info;

/**
 * Transcode an MPEG file
 */
class Transcode
{
    /**
     * @var Symfony\Component\Process\Process
     */
    protected $process = null;

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger = null;

    public function __construct(Process $process, LoggerInterface $logger = null)
    {
        $this->process = $process;
        $this->logger  = $logger;
    }

    public function transcode($input, $chapterList, $autocrop = false)
    {
        if (!Utilities::checkHandBrake($this->process)) {
            $warning = 'The HandBrake tool can not be trusted or found. ';
            TiVoUtilities\Log::warn($warning, $this->logger);
            // Exit early.
            return array();
        }

        $videoInfo  = new Info($input, $this->process, $this->logger);
        $resolution = $videoInfo->getIdealResolution($input);
        $quality    = $this->getVideoQuality($resolution['height'], $resolution['width']);

        if ($autocrop) {
            $crop = $videoInfo->getCropValues();
        } else {
            $crop = false;
        }

        if (count($chapterList) === 0) {
            $chapterList[] = array(
                'start' => 0,
                'end'   => 24 * 60 * 60, // 24 hours
            );
        }

        $outputList = array();
        foreach($chapterList as $index => $chapter) {
            $outputList[] = $this->encode(
                $input, $index,
                $chapter['start'], $chapter['end'],
                $resolution, $crop, $quality
            );
        }
        return $outputList;
    }

    protected function getVideoQuality($height, $width)
    {
        $w1 = 700;  // Width
        $q1 = 23;   // Quality
        $w2 = 1920; // Width
        $q2 = 28;   // Quality

        // Linear equation to find a reasonable quality setting.
        $qOut = (($q2 - $q1) / ($w2 - $w1) * ($width - $w1)) + $q1;
        return round($qOut);
    }

    protected function encode($input, $index, $start, $end, $resolution, $crop, $quality)
    {
        // Output Filename
        $output = $input . $index . '.m4v';

        $command  = 'HandBrakeCLI -i ' . $input . ' -o ' . $output;

        // Video Encoder
        $command .= ' -e x264 ';
        $command .= ' --x264-preset medium --h264-profile high --h264-level 3.1';
        $command .= ' -q ' . $quality . ' -r 29.97 --cfr'; // Quality and Framerate

        // Audio Encoder
        $command .= ' -E faac -B 128 -6 stereo'; // Codec, Bitrate, and Channels

        // Resize and Crop
        $command .= ' -w ' . $resolution['width'];
        $command .= ' -l ' . $resolution['height'];
        if ($crop) {
            $command .= ' --crop ' . implode(':', $crop);
        } else {
            $command .= ' --crop 0:0:0:0';
        }

        // Filters
        $command .= " --decomb "; // Decomb

        $command .= ' --start-at duration: ' . $start;
        $command .= ' --stop-at duration: ' . $end;

        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // Don't timeout.
        $this->process->run();

        return $output;
    }
}
