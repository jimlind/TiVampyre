<?hh

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\ProcessBuilder;
use TiVampyre\Video\FileTranscoder\AutocropFinder;
use TiVampyre\Video\FileTranscoder\ResolutionCalculator;

/**
 * Transcode an MPEG file
 */
class FileTranscoder
{
    /**
     * @var LoggerInterface
     */
    private $logger = null;

    public function __construct(
        protected ProcessBuilder $processBuilder,
        protected ResolutionCalculator $resolutionCalculator,
        protected AutocropFinder $autocropFinder,
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
        $this->resolutionCalculator->setLogger($logger);
        $this->autocropFinder->setLogger($logger);
    }

    public function transcode($filePath, $chapterList, $autocrop = false)
    {
        $idealResolution = $this->resolutionCalculator->calculateIdealResolution($filePath);
        $idealQuality    = $this->getVideoQuality($idealResolution);

        $autoCropValues = ['top' => 0, 'bottom' => 0, 'left' => 0, 'right' => 0];
        if ($autocrop) {
            $autoCropValues = $this->autocropFinder->findAutocrop($filePath);
        }

        $outputList = [];
        foreach($chapterList as $index => $chapter) {
            $outputList[] = $this->encode(
                $filePath,
                $index,
                $chapter,
                $idealResolution,
                $autoCropValues,
                $idealQuality
            );
        }
        return $outputList;
    }

    protected function getVideoQuality($resolution)
    {
        $w1 = 700;  // Width
        $q1 = 25;   // Quality
        $w2 = 1920; // Width
        $q2 = 30;   // Quality

        // Linear equation to find a reasonable quality setting.
        $width   = $resolution['width'];
        $quality = (($q2 - $q1) / ($w2 - $w1) * ($width - $w1)) + $q1;
        return round($quality);
    }

    protected function encode($input, $index, $chapter, $resolution, $crop, $quality)
    {
        // Output Filename
        $outputFile = $input . $index . '.m4v';

        $io = [
            '--input=' . $input,
            '--out=' . $outputFile,
        ];

        $audio = [
            '--aencoder=faac',
            '--ab=128',
            '--mixdown=stereo',
        ];

        $video = [
            '--encoder=x264',
            '--x264-preset=medium',
            '--h264-profile=high',
            '--h264-level=3.1',
            '--quality=' . $quality,
            '--rate=29.97',
            '--cfr',
        ];

        $filter = [
            '--height=' . $resolution['height'],
            '--width=' . $resolution['width'],
            '--crop=' . implode(':', $crop),
            '--decomb',
            '--start-at=duration:' . $chapter['start'],
            '--stop-at=duration:' . ($chapter['end'] - $chapter['start']),
        ];

        $arguments = array_merge($io, $audio, $video, $filter);
        $this->processBuilder->setPrefix('HandBrakeCLI');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();

        return $outputFile;
    }
}
