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
        $arguments = [
            '-i',
            $filePath,
            '-deinterlace',
            '-q',
            1,
            '-r',
            1,
            '-f',
            'image2',
            "{$filePath}.%04d.jpg",
        ];

        $this->processBuilder->setPrefix('avconv');
        $this->processBuilder->setArguments($arguments);
        $this->processBuilder->setTimeout(0);
        $this->processBuilder->getProcess()->run();

        $previewName = "{$filePath}.jpg";
        $previewList = glob("{$filePath}.*.jpg");
        $lastItem    = array_pop($previewList);
        rename($lastItem, $previewName);

        foreach ($previewList as $preview) {
            unlink($preview);
        }

        return $previewName;
    }
}
