<?hh

namespace TiVampyre\Twitter;

use Monolog\Logger;
use TiVampyre\Twitter\TweetEvent;
use Twitter;

class Tweet
{
    /**
     * @param Twitter $twitter    Twitter service
     * @param Logger  $logger     Logging service
     * @param boolean $production Production or development mode
     */
    public function __construct(
        private Twitter $twitter,
        private Logger $logger,
        private boolean $production
    ) { }

    /**
     * Capture an event to tweet a show.
     *
     * @param TweetEvent $event The Event to Tweet About
     */
    public function captureShowEvent(TweetEvent $event)
    {
        $tweetString = $this->composeShowTweet($event->getShow());
        if ($this->production) {
            $this->sendTweet($tweetString);
        } else {
            echo $tweetString;
            echo PHP_EOL;
        }
    }

    /**
     * Capture an event to tweet a preview.
     *
     * @param TweetEvent $event The Event to Tweet About
     */
    public function capturePreviewEvent(TweetEvent $event)
    {
        $preview = $event->getPreview();
        if ($this->production) {
            $this->sendTweet('tweet a preview image');
        } else {
            echo 'don't tweet a preview image';
        }
    }

    /**
     * Tweet a message.
     *
     * @param string $tweetString Twitter Message
     */
    protected function sendTweet($tweetString)
    {
        try {
            $this->twitter->send($tweetString);
        } catch (\Exception $e) {
            $this->logger->addWarning($e->getMessage());
            $this->logger->addWarning($tweetString);
        }
    }

    /**
     * Compose a Tweet about starting to record a show.
     *
     * @param Show $show A Show Entity
     *
     * @return string
     */
    protected function composeShowTweet($show) : string
    {
        $tweet        = 'I started recording ' . $show->getShowTitle() . ' ';
        $episodeTitle = $show->getEpisodeTitle();
        if (!empty($episodeTitle)) {
            $tweet .= '- ' . $episodeTitle . ' ';
        }
        $tweet .= 'on ' . $show->getStation() . ' ' . $show->getChannel();
        if ($show->getHd()) {
            $tweet .= ' in HD';
        }

        return $tweet . '.';
    }
}
