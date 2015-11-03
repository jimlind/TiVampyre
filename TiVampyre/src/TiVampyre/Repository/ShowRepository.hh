<?hh

namespace TiVampyre\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for Show Entity.
 */
class ShowRepository extends EntityRepository
{
    /**
     * Returns a list of all shows entities roughly sorted.
     *
     * @return TiVampyre\Entity\ShowEntity[]
     */
    public function getAllSortedEpisodes(): array
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->addOrderBy('s.showTitle', 'ASC');
        $queryBuilder->addOrderBy('s.episodeNumber', 'ASC');
        $queryBuilder->addOrderBy('s.date', 'ASC');

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * Returns a list of all shows entities without a preview
     *
     * @return TiVampyre\Entity\ShowEntity[]
     */
    public function findAvailableForPreview(): array
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->where('s.preview is null');

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * Returns a list of all outdated shows entities.
     *
     * @return TiVampyre\Entity\ShowEntity[]
     */
    public function findOutdated(): array
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->where('s.ts < :timeStamp');
        $queryBuilder->setParameter('timeStamp', $this->findMaxTimeStamp());

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * Returns the most recent show entity timestamp.
     *
     * @return string
     */
    public function findMaxTimeStamp(): string
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('MAX(s.ts)');

        $query = $queryBuilder->getQuery();

        return $query->getSingleScalarResult();
    }
}
