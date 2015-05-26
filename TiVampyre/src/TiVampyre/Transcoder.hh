<?hh

namespace TiVampyre;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TiVampyre\Repository\ShowRepository;
use TiVampyre\Video\Cleaner;
use TiVampyre\Video\ComskipRunner;
use TiVampyre\Video\Labeler;
use TiVampyre\Video\FileTranscoder;

/**
 * Transcode files
 */
class Transcoder
{
    /**
     * @var LoggerInterface
     */
    private $logger = null;

    public function __construct(
        private ShowRepository $showRepository,
        private ComskipRunner $comskipRunner,
        private FileTranscoder $fileTranscoder,
        private Cleaner $cleaner,
        private Labeler $labeler,
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

    public function process($data)
    {
        $rawFilename = $this->workingDirectory . $data['show'];

        $chapterList = array();
        if ($data['cut']) {
            $chapterList = $this->app['comskip']->getChapterList($rawFilename . '.mpeg');
        }

        $fileList = $this->fileTranscoder->transcode(
            $rawFilename . '.mpeg',
            $chapterList,
            $data['auto']
        );

        $this->app['video_cleaner']->clean(
            $fileList,
            $rawFilename . '.m4v'
        );

        $showId     = intval($data['show']);
        $showEntity = $this->showRepository->find($showId);

        $this->app['video_labeler']->addMetadata($showEntity, $rawFilename . '.m4v');
        $this->app['video_labeler']->renameFile($showEntity, $rawFilename . '.m4v');

        if (!$data['keep']) {
            unlink($rawFilename . '.mpeg');
        }
        die;
    }
}
