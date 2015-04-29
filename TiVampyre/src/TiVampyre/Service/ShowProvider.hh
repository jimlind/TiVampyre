<?hh

namespace TiVampyre\Service;

use DateTime;
use JimLind\TiVo\NowPlaying;
use TiVampyre\Factory\ShowListFactory;

/**
 *
 */
class ShowProvider
{
    /**
     * Constructor for ShowProvider
     *
     * @param NowPlaying      $nowPlaying    Access to Now Playing list
     */
    public function __construct(
        private NowPlaying $nowPlaying,
        private ShowListFactory $factory
    ) { }


    public function getShowEntities()
    {
        $timestamp = new DateTime('now');

        $showList = $this->getShowList();
        foreach ($showList as $index => $show) {
            $show->setTimeStamp($timestamp);
            $showList[$index] = $show;
        }
        return $showList;
    }

    protected function getShowList()
    {
        $xmlList  = $this->nowPlaying->download();
        $showList = $this->factory->createShowListFromXmlList($xmlList);

        return $showList;
    }
}
