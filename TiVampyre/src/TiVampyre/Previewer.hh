<?hh

namespace TiVampyre;

use Doctrine\ORM\EntityManager;
use Pheanstalk\Pheanstalk;
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
        private EntityManager $entityManager,
        private Pheanstalk $pheanstalk,
        private TweetDispatcher $tweetDispatcher,
        private FilePreviewer $filePreviewer,
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

    public function getPreviews($skipTwitter): void
    {
        $showList = $this->showRepository->findAvailableForPreview();
        foreach ($showList as $show) {
            $durationInMinutes = $show->getDuration() / 60000 ;
            if (5 < $durationInMinutes) {
                $optionList = [
                    'show' => $show->getId(),
                    'preview' => true,
                ];

                if (false === $skipTwitter) {
                    $this->pheanstalk
                        ->useTube('download')
                        ->put(json_encode($optionList));
                }

                $show->setPreview(new \DateTime());
                $this->entityManager->persist($show);
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
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

        unlink($mpegFilename);
    }

}
