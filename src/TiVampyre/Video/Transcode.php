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

    public function transcode($input, $output, $edlFile, $autocrop = false)
    {
        if (!Utilities::checkHandBrake($this->process)) {
            $warning = 'The HandBrake tool can not be trusted or found. ';
            TiVoUtilities\Log::warn($warning, $this->logger);
            // Exit early.
            return false;
        }

        $videoInfo  = new Info($input, $this->process, $this->logger);
        $resolution = $videoInfo->getIdealResolution($input);
        $quality    = $this->getVideoQuality($resolution['height'], $resolution['width']);

        if ($autocrop) {
            $crop = $videoInfo->getCropValues();
        } else {
            $crop = false;
        }

        $this->encode($input, $output, $resolution, $crop, $quality);
    }

    protected function getVideoQuality($height, $width)
    {
        $w1 = 704;  // Width
        $q1 = 23;   // Quality
        $w2 = 1920; // Width
        $q2 = 28;   // Quality

        // Linear equation to find a reasonable quality setting.
        $qOut = (($q2 - $q1) / ($w2 - $w1) * ($width - $w1)) + $q1;
        return round($qOut);
    }

    protected function encode($input, $output, $resolution, $crop, $quality)
    {
	$command  = 'HandBrakeCLI -i ' . $input . ' -o ' . $output;

	// Video Encoder
	$command .= ' -e x264 -x b-adapt=2:rc-lookahead=50'; // Normal Encoding Preset
	$command .= ' -q ' . $quality . ' -r 29.97';	     // Quality and Framerate

	// Audio Encoder
	$command .= ' -E faac -B 128 -6 stereo'; // Codec, Bitrate, and Channels
	$command .= ' -D 1.0 ';	                 // Dynamic Volume Compression

	// Resize and Crop
	$command .= ' -w ' . $resolution['width'];
        $command .= ' -l ' . $resolution['height'];
        if ($crop) {
            $command .= ' --crop ' . implode(':', $crop);
        } else {
            $command .= ' --crop 0:0:0:0';
        }

	// Filters
	$command .= " --detelecine --decomb "; // Detelecine and Decomb

        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // Don't timeout.
        $this->process->run();
    }
}