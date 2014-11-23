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

    public function transcode($input, $output)
    {
        if (!Utilities::checkHandBrake($this->process)) {
            $warning = 'The HandBrake tool can not be trusted or found. ';
            TiVoUtilities\Log::warn($warning, $this->logger);
            // Exit early.
            return false;
        }

        $info = new Info($input, $this->process, $this->logger);

        $resolution  = $info->getResolution($input);
        $aspectRatio = $info->getAspectRatio($input);

        var_dump($resolution, $aspectRatio);
        die;
    }

    protected function getVideoQuality($height, $width)
    {
        // computers don't care if this is left unsimplified
        // it'll make it easier to edit later if neccesary.
        $w1 = 704;  //width
        $q1 = 23;   //quality
        $w2 = 1920; //width
        $q2 = 28;   //quality

        // just using a linear equation
        $qOut = (($q2 - $q1) / ($w2 - $w1) * ($width - $w1)) + $q1;
        return round($qOut);
    }

    protected function getOutputDimensions($target, $crop, $resize)
    {
        $s = array(
            'width' => 0,
            'height'  => 0,
        );

        $c = array(
            'top'    => 0,
            'bottom' => 0,
            'left'   => 0,
            'right'  => 0,
        );

        // start command line
        $h  = "HandBrakeCLI ";
        $h .= "-i $target "; //input
        $h .= "-o /dev/null "; //output

        $h .= "--start-at duration:0 ";
        $h .= "--stop-at duration:1 ";

        $h .= "2>&1";

        log_message('debug', $h);
        $o = shell_exec($h);

        $pattern = '|\+ size: (\d+)x(\d+),|';
        preg_match_all($pattern, $o, $matches);

        $s['width']  = intval($matches[1][0]);
        $s['height'] = intval($matches[2][0]);

        // Find aspect ratio
        $pattern = '|, aspect ([\d.]+):([\d.]+),|';
        preg_match_all($pattern, $o, $matches);
        $aspectRatio = $matches[1][0] / $matches[2][0];


        // Adjust height or width if neccesary to match aspect ratio (scale up)
        $ourRatio = $s['width'] / $s['height'];
        $range = 0.1;
        if (($ourRatio < $aspectRatio - $range) || ($ourRatio > $aspectRatio + $range)) {
            // Check adding height
            $newHeight = $s['width'] / $aspectRatio;
            $newHeightArea = $s['width'] * $newHeight;
            // Check adding width
            $newWidth = $s['height'] * $aspectRatio;
            $newWidthArea = $s['height'] * $newWidth;
            // Apply best fit
            if ($newHeightArea > $newWidthArea) {
                $s['height'] = $newHeight;
            } else {
                $s['width'] = $newWidth;
            }
        }

        if ($crop) {
            $pattern = '|autocrop = (\d+)/(\d+)/(\d+)/(\d+)|';
            preg_match_all($pattern, $o, $matches);

            $c['top']    = intval($matches[1][0]);
            $c['bottom'] = intval($matches[2][0]);
            $c['left']   = intval($matches[3][0]);
            $c['right']  = intval($matches[4][0]);
        }

        $outSize = array();
        $outSize['height'] = $s['height'] - $c['top'] - $c['bottom'];
        $outSize['width']  = $s['width'] - $c['left'] - $c['right'];

        // Default HD content to 1024 wide for iPads
        if ($resize && $outSize['width'] > 1024) {
	    $outSize['height'] = 1024/($outSize['width']/$outSize['height']);
	    $outSize['width'] = 1024;
	}

        // iPhone, iPad, etc can't handle higher than 1080p
        if ($outSize['height'] > 1080) {
            $outSize['width'] = 1080/($outSize['height']/$outSize['width']);
	    $outSize['height'] = 1080;
        }

        // Try to match standard HD sizes
        $range = 24;
        $h = 1080;
        $w = 1920;
        if (($outSize['height'] >= $h - $range) && ($outSize['height'] <= $h + $range) &&
            ($outSize['width'] >= $w - $range) && ($outSize['width'] <= $w + $range)) {
            log_message('debug', "Force 1080p Resolution");
	    $outSize['height'] = $h;
            $outSize['width'] = $w;
        }
        $range = 12;
        $h = 720;
        $w = 1280;
        if (($outSize['height'] >= $h - $range) && ($outSize['height'] <= $h + $range) &&
            ($outSize['width'] >= $w - $range) && ($outSize['width'] <= $w + $range)) {
            log_message('debug', "Force 720p Resolution");
	    $outSize['height'] = $h;
            $outSize['width'] = $w;
        }

        // Whole Integers
        $outSize['height'] = intval($outSize['height']);
        $outSize['width'] = intval($outSize['width']);

        return array(
            'size' => $outSize,
            'crop' => $c,
        );
    }

}