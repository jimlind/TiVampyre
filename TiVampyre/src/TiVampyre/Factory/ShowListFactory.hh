<?hh

namespace TiVampyre\Factory;

use JimLind\TiVo\Factory\ShowListFactory as OriginShowListFactory;

/**
 * Default show list factory to build a list of show models.
 */
class ShowListFactory extends OriginShowListFactory
{
    /**
     * Constructs the ShowList Factory.
     */
    public function __construct(): void
    {
        parent::__construct();

        $this->showFactory = new ShowFactory();
    }
}
