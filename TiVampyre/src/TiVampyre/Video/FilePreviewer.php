<?hh

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Preview an MPEG file
 */
class FilePreviewer
{
    /**
     * @var LoggerInterface
     */
    private $logger = null;

    public function __construct(
        protected ProcessBuilder $processBuilder,
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
    }

    public function preview($filePath): string
    {
        $mp4File = $this->handbrakeRunner($filePath);
        $this->avconvRunner($mp4File);
        unlink($mp4File);

        $previewName = "{$filePath}.jpg";
        $previewList = glob("{$mp4File}.*.jpg");
        $lastItem    = array_pop($previewList);
        rename($lastItem, $previewName);

        foreach ($previewList as $preview) {
            unlink($preview);
        }

        return $previewName;
    }

    protected function handbrakeRunner($filePath) {
        $outputFile = "{$filePath}.tmp.mp4";
        $arguments  = [
            '--input=' . $filePath,
            '--quality=1',
            '--rate=5',
            '--decomb',
            '--audio=none',
            '--out=' . $outputFile,
        ];

        $this->processBuilder->setPrefix('HandBrakeCLI');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();

        return $outputFile;
    }

    private function avconvRunner($filePath) {
        $outputFile = "{$filePath}.%04d.jpg";
        $arguments  = [
            '-i',
            $filePath,
            '-q',
            1,
            '-r',
            1,
            '-f',
            'image2',
            $outputFile,
        ];

        $this->processBuilder->setPrefix('avconv');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();
    }
}
