<?hh

namespace TiVampyre;

use TiVampyre\Repository\ShowRepository;

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
        private VideoComskip $videoComskip,
        private Transcoder $videoTranscoder,
        private Cleaner $videoCleaner,
        private string $workingDirectory)
    {
        // Default to the NullLogger
        $this->setLogger(new NullLogger());
    }

    public function process($data)
    {
        var_dump($data);
        die;
        $rawFilename = $this->workingDirectory . $data['show'];

        $chapterList = array();
        if ($data['cut']) {
            $chapterList = $this->app['comskip']->getChapterList($rawFilename . '.mpeg');
        }

        $fileList = $this->app['video_transcoder']->transcode(
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
    }
}
