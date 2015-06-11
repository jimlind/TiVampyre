<?hh

namespace TiVampyre;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TiVampyre\Repository\ShowRepository;
use TiVampyre\Video\ChapterGenerator;
use TiVampyre\Video\Cleaner;
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
        private ChapterGenerator $chapterGenerator,
        private FileTranscoder $fileTranscoder,
        private Cleaner $cleaner,
        private Labeler $labeler,
        private string $workingDirectory
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

    public function transcode($data)
    {
        $rawFilename  = $this->workingDirectory . $data['show'];
        $mpegFilename = $rawFilename . '.mpeg';
        $m4vFilename  = $rawFilename . '.m4v';

        $showId         = intval($data['show']);
        $autoCrop       = $data['auto'];
        $cutCommercials = $data['cut'];
        $keepMpegFile   = $data['keep'];

        if (file_exists($mpegFilename) === false) {
            // TODO: Log something here.
            return false;
        }

        // 24 hour single chapter piece
        $chapterList[] = ['start' => 0, 'end' => 24 * 60 * 60];
        if ($cutCommercials) {
            $chapterList = $this->chapterGenerator->generate($mpegFilename);
        }

        $fileList = $this->fileTranscoder->transcode(
            $mpegFilename,
            $chapterList,
            $autoCrop
        );

        $this->cleaner->clean($fileList, $m4vFilename);

        $showEntity = $this->showRepository->find($showId);
        $this->labeler->label($showEntity, $m4vFilename);

        if (!$data['keep']) {
            unlink($mpegFilename);
        }
    }
}
