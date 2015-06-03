<?hh

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Clean the h264/aac MP4
 */
class Cleaner
{
    protected $logger = null;

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

    public function clean($inputFileList, $outputFile)
    {
        $this->merge($inputFileList, $outputFile);
        $this->adjustAudio($outputFile);
    }

    protected function merge($inputFileList, $outputFile)
    {
        $arguments = ['-new', $outputFile];
        foreach ($inputFileList as $inputFile) {
            $arguments[] = '-cat';
            $arguments[] = $inputFile;
        }

        $this->processBuilder->setPrefix('MP4Box');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();

        foreach ($inputFileList as $inputFile) {
            unlink($inputFile);
        }
    }

    protected function adjustAudio($m4vFile)
    {
        $arguments = ['-f', '-r', '-c', $m4vFile];

        $this->processBuilder->setPrefix('aacgain');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();
    }
}
