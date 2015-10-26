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

    public function preview($data): void
    {
        $showId     = (int) $data['show'];
        $showEntity = $this->showRepository->find($showId);
        if (null === $showEntity) {
            $this->logger->warning('Show Not Found');
            return;
        }

        $rawFilename  = $this->workingDirectory . $showId;
        $mpegFilename = $rawFilename . '.mpeg';

        $previewFilename = $this->filePreviewer->preview($mpegFilename);
        $this->tweetDispatcher->tweetShowPreview($showEntity, $previewFilename);

        unlink($tivoFilename);
        unlink($mpegFilename);
    }
}
