<?php

namespace TiVampyre\Twitter;

use Monolog\Logger;
use TiVampyre\Twitter\TweetEvent;

class Tweet
{
    protected $twitter;
    protected $logger;

    /**
     * @var boolean
     */
    protected $production;

    public function __construct(\Twitter $twitter, Logger $logger, $production) {
        $this->twitter    = $twitter;
        $this->logger     = $logger;
        $this->production = $production;
    }

    /**
     * Capture an event to tweet a show.
     *
     * @param TiVampyre\Twitter\TweetEvent $event
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
     * @param TiVampyre\Twitter\TweetEvent $event
     */
    public function capturePreviewEvent(TweetEvent $event)
    {
        $preview = $event->getPreview();
        if ($this->production) {
            $this->sendTweet('preview');
        } else {
            echo 'preview';
        }
    }

    /**
     * Tweet a message.
     *
     * @param string $tweetString
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
     * Compose a wonderful Tweet about the show.
     *
     * @param TiVampyre\Entity\Show $show
     *
     * @return string
     */
    protected function composeShowTweet($show)
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