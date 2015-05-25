<?hh

namespace TiVampyre;

use JimLind\TiVo\Decode as TiVoDecoder;
use JimLind\TiVo\Download as TiVoDownloader;
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
        private TiVoDownloader $tivoDownloader,
        private TiVoDecoder $tivoDecoder,
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

    public function process(integer $showId): void
    {
        $showEntity = $this->showRepository->find($showId);
        if (!$showEntity) {
            $this->logger->warn('Show Not Found');
            return;
        }

        $rawFilename = $this->workingDirectory . $showEntity->getId();

        // TODO: Download full, not preview.
        $this->tivoDownloader->storePreview(
            $showEntity->getURL(),
            $rawFilename . '.tivo'
        );

        $this->decode($rawFilename);
    }

    private function decode(string $rawFilename): void
    {
        $this->tivoDecoder->decode(
            $rawFilename . '.tivo',
            $rawFilename . '.mpeg'
        );
        unlink($rawFilename . '.tivo');
    }
}
