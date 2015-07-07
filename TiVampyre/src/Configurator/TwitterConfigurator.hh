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
		$application['twitter'] = new Twitter(
		    $application['twitter_consumer_key'],
		    $application['twitter_consumer_secret'],
		    $application['twitter_access_token'],
		    $application['twitter_access_token_secret']
		);

		$application['tweet'] = new Tweet(
		    $application['twitter'],
		    $application['monolog'],
		    $application['twitter_production']
		);

		// Dispatch Twitter Event
		$application['tweet_dispatcher'] = function($app) {
		    return new TweetDispatcher(
		        $app['dispatcher'],
		        new TweetEvent()
		    );
		};

		// Setup Twitter Event Listeners
		$application['dispatcher']->addListener(TweetEvent::$SHOW_TWEET_EVENT, function($event) use ($application) {
		    $application['tweet']->captureShowEvent($event);
		});
		$application['dispatcher']->addListener(TweetEvent::$PREVIEW_TWEET_EVENT, function($event) use ($application) {
		    $application['tweet']->capturePreviewEvent($event);
		});
	}
}
