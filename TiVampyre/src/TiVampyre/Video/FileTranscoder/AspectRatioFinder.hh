<?hh

namespace TiVampyre\Video\FileTranscoder;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Find resolution information in an MPEG file's metadata.
 */
class AspectRatioFinder
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

    public function findAspectRatio(string $filePath): float
    {
        $process = $this->getProcess($filePath);
        $process->run();

        // HandBrake outputs lots of information on error
        $processOutput = $process->getErrorOutput();
        $aspectRatio   = $this->parseAspectRatio($processOutput);

        return $aspectRatio;
    }

    protected function parseAspectRatio(string $processOutput): float
    {
        $matches = [];
        $pattern = '|, aspect ([\d.]+):([\d.]+),|';
        preg_match_all($pattern, $processOutput, $matches);

        if (count($matches) === 3) {
            // Data successfully found.
            return $matches[1][0] / $matches[2][0];
        }
        // Nothing found.
        return 1.0;
    }
    /**
     * Build a Process with standard output at a 60 second timeout
     */
    protected function getProcess(string $filePath): Process
    {
        $arguments = [
            '--input=' . $filePath,
            '--out=/dev/null',
            '--start-at=duration:0',
            '--stop-at=duration:1',
        ];

        $this->processBuilder->setPrefix('HandBrakeCLI');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(60);
        return $this->processBuilder->getProcess();
    }
}
