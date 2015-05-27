<?hh

namespace TiVampyre\Video\FileTranscoder;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TiVampyre\Video\FileTranscoder\AspectRatioFinder;
use TiVampyre\Video\FileTranscoder\ResolutionFinder;

/**
 * Calculate best resolution for an MPEG file.
 */
class ResolutionCalculator
{
    /**
     * @var LoggerInterface
     */
    private $logger = null;

    public function __construct(
        protected ResolutionFinder $resolutionFinder,
		protected AspectRatioFinder $aspectRatioFinder
    ) {
        // Default to the NullLogger
        $this->setLogger(new NullLogger());
    }

    /**
     * Set the Logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

		$this->resolutionFinder->setLogger($logger);
		$this->aspectRatioFinder->setLogger($logger);
    }

    public function calculateIdealResolution(string $filePath): array
    {
        $resolution  = $this->resolutionFinder->findResolution($filePath);
        $aspectRatio = $this->aspectRatioFinder->findAspectRatio($filePath);

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
			$currentRatio = $resolution['height'] / $resolution['width'];
			$resolution['width'] = 1080 / $currentRatio;
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
                $this->logger->warning('Force ' . $key . ' Resolution');
                $resolution['height'] = $value['height'];
                $resolution['width']  = $value['width'];
            }
        }
    }
}
