<?hh

namespace TiVampyre\Twitter;

use Symfony\Component\EventDispatcher\EventDispatcher;
use TiVampyre\Entity\ShowEntity;
use TiVampyre\Twitter\TweetEvent;

class TweetDispatcher
{
    /**
     * @param EventDispatcher $eventDispatcher Symfony's Event Dispatcher
     * @param TweetEvent      $tweetEvent      Tweet Event Class
     */
    public function __construct(
        private EventDispatcher $eventDispatcher,
        private TweetEvent $tweetEvent
    ) { }

    /**
     * Tweet about a show recording.
     *
     * @param ShowEntity $showEntity A Show Entity
     */
    public function tweetShowRecording(ShowEntity $showEntity) {
        $event = clone $this->tweetEvent;
        $event->setShow($showEntity);

        $this->eventDispatcher->dispatch(
            $event::$SHOW_TWEET_EVENT,
            $event
        );
    }
}
