<?hh

namespace TiVampyre\Twitter;

use Symfony\Component\EventDispatcher\Event;
use TiVampyre\Entity\ShowEntity;

class TweetEvent extends Event
{
    public static $SHOW_TWEET_EVENT    = 'Show Tweet Event';
    public static $PREVIEW_TWEET_EVENT = 'Preview Tweet Event';

    /**
     * @var ShowEntity
     */
    protected $showEntity;

    protected $previewPath;

    /**
     * Set the Show entity for the Tweet event.
     *
     * @param ShowEntity $showEntity A show entity
     */
    public function setShow(ShowEntity $showEntity) {
        $this->showEntity = $showEntity;
    }

    public function setPreview($previewPath) {
        $this->previewPath = $previewPath;
    }

    /**
     * Get the Show entity from the Tweet event.
     *
     * @return ShowEntity
     */
    public function getShow() {
        return $this->showEntity;
    }

    public function getPreview() {
        return $this->previewPath;
    }
}
