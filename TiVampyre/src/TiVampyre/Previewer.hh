<?hh

namespace TiVampyre;

use JimLind\TiVo\VideoDecoder as VideoDecoder;
use JimLind\TiVo\VideoDownloader as VideoDownloader;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TiVampyre\Repository\ShowRepository;
use TiVampyre\Twitter\TweetDispatcher;
use TiVampyre\Video\FilePreviewer;

/**
 * Download and decode files
 */
class Previewer
{
    /**
     * @var LoggerInterface
     */
    private $logger = null;

    public function __construct(
        private ShowRepository $showRepository,
        private VideoDownloader $videoDownloader,
        private VideoDecoder $videoDecoder,
        private FilePreviewer $filePreviewer,
        private TweetDispatcher $tweetDispatcher,
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

    public function preview(int $showId): void
    {
        $showEntity = $this->showRepository->find($showId);
        if (!$showEntity) {
            $this->logger->warning('Show Not Found');
            return;
        }

        $rawFilename  = $this->workingDirectory . $showEntity->getId();
        $tivoFilename = $rawFilename . '.preview.tivo';
        $mpegFilename = $rawFilename . '.preview.mpeg';

        // This actually needs to tap into the _download_ queue to avoid an
        // attempt at multiple downloads. This means the _preview_ queue should
        // be deleted.

        $this->videoDownloader->downloadPreview($showEntity->getURL(), $tivoFilename);
        $this->videoDecoder->decode($tivoFilename, $mpegFilename);

        $previewFilename = $this->filePreviewer->preview($mpegFilename);
        $this->tweetDispatcher->tweetShowPreview($showEntity, $previewFilename);

        unlink($tivoFilename);
        unlink($mpegFilename);
    }
}
