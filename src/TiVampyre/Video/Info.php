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

    public function getResolution() {
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

    public function getAspectRatio() {
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
        $actualRatio = $resolution['width'] / $resolution['height'];
        $range = 0.1;
        if (($actualRatio < $aspectRatio - $range) || ($actualRatio > $aspectRatio + $range)) {
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
    }
}