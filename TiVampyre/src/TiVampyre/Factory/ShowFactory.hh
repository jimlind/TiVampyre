<?hh

namespace TiVampyre\Factory;

use JimLind\TiVo\Factory\ShowFactory as OriginShowFactory;
use TiVampyre\Entity\Show as Entity;

/**
 * Default show factory to build a show model.
 */
class ShowFactory extends OriginShowFactory
{
    /**
     * Overrides original constructor.
     */
    protected function newShow(): void
    {
        return new Entity();
    }
}
