<?hh

namespace Configurator;

use TiVampyre\Twitter\Tweet;
use TiVampyre\Twitter\TweetDispatcher;
use TiVampyre\Twitter\TweetEvent;
use Twitter;
use Silex\Application;

class TwitterConfigurator
{
	static function setup(Application $application)
	{
		// Setup the Twitter services.
		$twitter = new Twitter(
		    $application->offsetGet('twitter_consumer_key'),
		    $application->offsetGet('twitter_consumer_secret'),
		    $application->offsetGet('twitter_access_token'),
		    $application->offsetGet('twitter_access_token_secret')
		);
                $application->offsetSet('twitter', $twitter);

		$tweet = new Tweet(
		    $application->offsetGet('twitter'),
		    $application->offsetGet('monolog'),
		    $application->offsetGet('twitter_production')
		);
                $application->offsetSet('tweet', $tweet);

		// Dispatch Twitter Event
		$tweetDispatcher = function($app) {
		    return new TweetDispatcher(
		        $app['dispatcher'],
		        new TweetEvent()
		    );
		};
                $application->offsetSet('tweet_dispatcher', $tweetDispatcher);

		// Setup Twitter Event Listeners
                $dispatcher = $application->offsetGet('dispatcher');
		$dispatcher->addListener(TweetEvent::$SHOW_TWEET_EVENT, function($event) use ($application) {
		    $tweet = $application->offsetGet('tweet');
                    $tweet->captureShowEvent($event);
		});
		$dispatcher->addListener(TweetEvent::$PREVIEW_TWEET_EVENT, function($event) use ($application) {
		    $tweet = $application->offsetGet('tweet');
                    $tweet->capturePreviewEvent($event);
		});
	}
}
