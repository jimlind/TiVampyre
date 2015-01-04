<?php

namespace TiVampyre\Twitter;

use Symfony\Component\EventDispatcher\Event;
use TiVampyre\Entity\Show;

class TweetEvent extends Event
{
    public static $SHOW_TWEET_EVENT    = 'Show Tweet Event';
    public static $PREVIEW_TWEET_EVENT = 'Preview Tweet Event';

    /**
     * @var TiVampyre\Entity\Show
     */
    protected $show;

    /**
     * Constructor
     *
     * @param TiVampyre\Entity\Show $show
     */
    public function __construct(Show $show) {
        $this->show = $show;
    }

    /**
     * Get the Show object from the Tweet event.
     *
     * @return TiVampyre\Entity\Show
     */
    public function getShow() {
        return $this->show;
    }
}