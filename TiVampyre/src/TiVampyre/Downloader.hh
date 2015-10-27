<?hh

namespace TiVampyre;

use JimLind\TiVo\VideoDecoder as VideoDecoder;
use JimLind\TiVo\VideoDownloader as VideoDownloader;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TiVampyre\Repository\ShowRepository;

/**
 * Download and decode files
 */
class Downloader
{
    /**
     * @var LoggerInterface
     */
    private $logger = null;

    public function __construct(
        private ShowRepository $showRepository,
        private VideoDownloader $videoDownloader,
        private VideoDecoder $videoDecoder,
        private string $workingDirectory)
    {
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

    public function process(int $showId, bool $preview): void
    {
        $showEntity = $this->showRepository->find($showId);
        if (!$showEntity) {
            $this->logger->warn('Show Not Found');
            return;
        }

        $rawFilename  = $this->workingDirectory . $showEntity->getId();
        $tivoFilename = $rawFilename . '.tivo';
        if ($preview) {
            $this->videoDownloader->downloadPreview($showEntity->getURL(), $tivoFilename);
        } else {
            $this->videoDownloader->download($showEntity->getURL(), $tivoFilename);
        }

        $this->decode($rawFilename);
    }

    private function decode(string $rawFilename): void
    {
        $this->videoDecoder->decode(
            $rawFilename . '.tivo',
            $rawFilename . '.mpeg'
        );
        unlink($rawFilename . '.tivo');
    }
}
