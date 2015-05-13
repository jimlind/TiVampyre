<?hh

namespace TiVampyre\Twitter;

use Symfony\Component\EventDispatcher\Event;
use TiVampyre\Entity\Show;

class TweetEvent extends Event
{
    public static $SHOW_TWEET_EVENT    = 'Show Tweet Event';
    public static $PREVIEW_TWEET_EVENT = 'Preview Tweet Event';

    /**
     * @var Show
     */
    protected $show;

    /**
     * Set the Show entity for the Tweet event.
     *
     * @param Show $show A show entity
     */
    public function setShow(Show $show) {
        $this->show = $show;
    }

    /**
     * Get the Show entity from the Tweet event.
     *
     * @return Show
     */
    public function getShow() {
        return $this->show;
    }
}
