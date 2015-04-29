<?hh

namespace TiVampyre\Twitter;

use Symfony\Component\EventDispatcher\EventDispatcher;
use TiVampyre\Entity\Show;
use TiVampyre\Twitter\TweetEvent;

class TweetDispatcher
{
	/**
	 * @param EventDispatcher $eventDispatcher Symfony's Event Dispatcher
	 */
	public function __construct(
		EventDispatcher $eventDispatcher,
		TweetEvent $tweetEvent
	) { }

	public function tweetShowRecording(Show $show) {
		$event = clone $this->tweetEvent;

		$event->setShow($show);
		$this->dispatcher->dispatch($event::$SHOW_TWEET_EVENT, $event);
	}
}
