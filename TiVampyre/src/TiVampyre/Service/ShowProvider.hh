<?hh

namespace TiVampyre\Service;

use DateTime;
use JimLind\TiVo\XmlDownloader;
use TiVampyre\Factory\ShowListFactory;

/**
 *
 */
class ShowProvider
{
    /**
     * Constructor for ShowProvider
     *
     * @param XmlDownloader      $xmlDownloader    Access to Now Playing list
     */
    public function __construct(
        private XmlDownloader $xmlDownloader,
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
        $xmlList  = $this->xmlDownloader->download();
        $showList = $this->factory->createShowListFromXmlList($xmlList);

        return $showList;
    }
}
