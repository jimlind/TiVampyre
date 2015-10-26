<?hh

namespace TiVampyre;

use Doctrine\ORM\EntityManager;
use Pheanstalk\Pheanstalk;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TiVampyre\Entity\ShowEntity;
use TiVampyre\Repository\ShowRepository;
use TiVampyre\Service\ShowProvider;
use TiVampyre\Twitter\TweetDispatcher;

/**
 * Synchronize local show data with remote TiVo.
 */
class Synchronizer
{
    /**
     * @var ShowRepository
     */
    private $showRepository = null;

    /**
     * @var LoggerInterface
     */
    private $logger  = null;

    /**
     * Constructor
     *
     * @param EntityManager   $entityManager   Doctrine Entity Manager
     * @param ShowProvider    $showProvider    Provides Show Entities
     * @param TweetDispatcher $tweetDispatcher Dispatch Tweets
     * @param Pheanstalk      $pheanstalk      Job Queue
     */
    public function __construct(
        private EntityManager $entityManager,
        private ShowProvider $showProvider,
        private TweetDispatcher $tweetDispatcher,
        private Pheanstalk $pheanstalk
    ) {
        $this->showRepository = $entityManager
            ->getRepository('TiVampyre\Entity\ShowEntity');

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

    /**
     * Load data from the TiVo and save to the database
     */
    public function rebuildLocalIndex(bool $skipAnnounce = false): void
    {
        $showList = $this->showProvider->getShowEntities();
        foreach ($showList as $show) {
            $this->entityManager->merge($show);
            $this->announceShowRecording($show, $skipAnnounce);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->deleteOutdated();
    }

    /**
     * Announce the show recording
     */
    protected function announceShowRecording(ShowEntity $show, bool $skipAnnounce): void
    {
        if ($skipAnnounce) return; // Exit early

        $showNotFound = ($this->showRepository->find($show->getId()) === null);
        if ($showNotFound) {
            $this->entityManager->flush(); // Flush so other systems have access
            $this->tweetDispatcher->tweetShowRecording($show);

            $optionList = [
                'show' => $input->getArgument('Show Id'),
                'preview' => true;
            ];
            $app['queue']->useTube('download')->put(json_encode($optionList));
        }
    }

    protected function deleteOutdated(): void
    {
        $outdatedShowList = $this->showRepository->findOutdated();
        foreach ($outdatedShowList as $show) {
            $this->entityManager->remove($show);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
