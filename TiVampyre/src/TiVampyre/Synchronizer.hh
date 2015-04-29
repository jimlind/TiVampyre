<?hh

namespace TiVampyre;

use Doctrine\ORM\EntityManager;
use TiVampyre\Service\ShowProvider;
use TiVampyre\Twitter\TweetDispatcher;

/**
 * Synchronize local show data.
 */
class Synchronizer
{
    /**
     * Construct synchronizer.
     *
     * @param ShowProvider    $showProvider    Provides Show Entities
     * @param EntityManager   $entityManager   Doctrine Entity Manager
     * @param TweetDispatcher $tweetDispatcher Dispatch Tweets
     */
    public function __construct(
        private ShowProvider $showProvider,
        private EntityManager $entityManager,
        private TweetDispatcher $tweetDispatcher
    ) { }

    /**
     * Load data from the TiVo and save to the database.
     */
    public function rebuildLocalIndex(): void
    {
        $repository = $this->entityManager->getRepository('TiVampyre\Entity\Show');
        $showIdList = $repository->getAllIds();

        $showList = $this->showProvider->getShowEntities();
        foreach ($showList as $show) {
            $shouldTweet = $this->shouldTweet($show->getId(), $showIdList);
            if ($shouldTweet) {
                $this->tweetDispatcher->tweetShowRecording($show);
            }
            //$this->entityManager->merge($show);
        }

        //$this->entityManager->flush();
        $repository->deleteOutdated();
    }

    /**
     * Decide if a Tweet should be dispatched.
     */
    protected function shouldTweet(int $showId, array $showIdList)
    {
        // If first run
        if (count($showIdList) == 0) {
            return false;
        }

        // If already in database
        if (in_array($showId, $showIdList)) {
            return false;
        }

        return true;
    }
}
