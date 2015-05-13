<?php

namespace TiVampyre\Video;

use JimLind\TiVo\Utilities as TiVoUtilities;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Discern information about an MPEG file
 */
class Info
{
    /**
     * @var string
     */
    protected $filePath = null;

    protected $process = null;

    protected $logger = null;

    /**
     * @var string
     */
    protected $output = null;

    public function __construct($filePath, Process $process, LoggerInterface $logger)
    {
        $this->filePath = $filePath;
        $this->process  = $process;
        $this->logger   = $logger;
    }

    protected function getOutput()
    {
        if (empty($this->output) && $this->isToolAvailable()) {
            $command  = 'HandBrakeCLI -i ' . $this->filePath.  ' -o /dev/null';
            $command .= " --start-at duration:0 --stop-at duration:1";
            $command .= " 2>&1";

            $this->process->setCommandLine($command);
            $this->process->setTimeout(60); // 1 minute
            $this->process->run();

            $this->output = $this->process->getOutput();
        }

        return $this->output;
    }

    protected function isToolAvailable()
    {
        if (!Utilities::checkHandBrake($this->process)) {
            $warning = 'The HandBrake tool can not be trusted or found. ';
            TiVoUtilities\Log::warn($warning, $this->logger);
            // Exit early.
            return false;
        }

        return true;
    }

    protected function getResolution() {
        $matches = array();
        $pattern = '|\+ size: (\d+)x(\d+),|';
        preg_match_all($pattern, $this->getOutput(), $matches);

        if (count($matches) === 3) {
            // Data successfully found.
            return array(
                'width'  => intval($matches[1][0]),
                'height' => intval($matches[2][0]),
            );
        }
        // Nothing found.
        return false;
    }

    protected function getAspectRatio() {
        $matches = array();
        $pattern = '|, aspect ([\d.]+):([\d.]+),|';
        preg_match_all($pattern, $this->getOutput(), $matches);

        if (count($matches) === 3) {
            // Data successfully found.
            return $matches[1][0] / $matches[2][0];
        }
        // Nothing found.
        return false;
    }

    // Adjust height or width if neccesary to match aspect ratio (scale up)
    public function getIdealResolution()
    {
        $resolution  = $this->getResolution();
        $aspectRatio = $this->getAspectRatio();

        if (!$resolution || !$aspectRatio) {
            return false;
        }

        $actualRatio  = $resolution['width'] / $resolution['height'];
        $maxDelta     = 0.1;
        $outsideLower = $actualRatio < $aspectRatio - $maxDelta;
        $outsideUpper = $actualRatio > $aspectRatio + $maxDelta;

        if ($outsideLower || $outsideUpper) {
            // Check adding height.
            $newHeight     = $resolution['width'] / $aspectRatio;
            $newHeightArea = $resolution['width'] * $newHeight;
            // Check adding width.
            $newWidth     = $resolution['height'] * $aspectRatio;
            $newWidthArea = $resolution['height'] * $newWidth;
            // Apply best fit.
            if ($newHeightArea > $newWidthArea) {
                $resolution['height'] = $newHeight;
            } else {
                $resolution['width'] = $newWidth;
            }
        }

        // iPhone, iPad, etc can't handle higher than 1080p
        if ($resolution['height'] > 1080) {
            $resolution['width'] = 1080/($resolution['height']/$resolution['width']);
	    $resolution['height'] = 1080;
        }

        $this->adjustToStandards($resolution);
        return $resolution;
    }

    /**
     * Tweak resolution to match standard HD resolutions.
     *
     * @param int[] $resolution
     */
    protected function adjustToStandards(&$resolution)
    {
        $adjustments = array(
            '1080' => array(
                'height' => 1080,
                'width'  => 1920,
                'limit'  => 24,
            ),
            '720' => array(
                'height' => 720,
                'width' => 1280,
                'limit' => 12,
            )
        );

        foreach ($adjustments as $key => $value) {
            $heightUpper = $resolution['height'] >= ($value['height'] - $value['limit']);
            $heightLower = $resolution['height'] <= ($value['height'] + $value['limit']);
            $widthUpper  = $resolution['width'] >= ($value['width'] - $value['limit']);
            $widthLower  = $resolution['width'] <= ($value['width'] + $value['limit']);

            if ($heightUpper && $heightLower && $widthUpper && $widthLower) {
                TiVoUtilities\Log::warn('Force ' . $key . ' Resolution', $this->logger);
                $resolution['height'] = $value['height'];
                $resolution['width']  = $value['width'];
            }
        }
    }

    public function getCropValues()
    {
        $matches = array();
        $pattern = '|autocrop = (\d+)/(\d+)/(\d+)/(\d+)|';
        preg_match_all($pattern, $this->getOutput(), $matches);

        if (count($matches) === 5) {
            // Data successfully found.
            return array(
                'top'    => intval($matches[1][0]),
                'bottom' => intval($matches[2][0]),
                'left'   => intval($matches[3][0]),
                'right'  => intval($matches[4][0]),
            );
        }
        // Nothing found.
        return false;
    }
}
