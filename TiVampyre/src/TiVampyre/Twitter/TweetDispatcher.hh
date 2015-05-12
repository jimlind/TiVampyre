<?hh

namespace TiVampyre\Twitter;

use Symfony\Component\EventDispatcher\EventDispatcher;
use TiVampyre\Entity\Show;
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

    public function tweetShowRecording(Show $show) {
        $event = clone $this->tweetEvent;
        $event->setShow($show);

        $this->eventDispatcher->dispatch(
            $event::$SHOW_TWEET_EVENT,
            $event
        );
    }
}
