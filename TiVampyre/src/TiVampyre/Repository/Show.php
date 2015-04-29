<?php

namespace TiVampyre\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for Show Entity.
 */
class Show extends EntityRepository
{
    /**
     * Returns a list of all episodes roughly sorted.
     *
     * @return TiVampyre\Entity\Show[]
     */
    public function getAllSortedEpisodes()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('s')
            ->from('TiVampyre\Entity\Show', 's')
            ->addOrderBy('s.showTitle', 'ASC')
            ->addOrderBy('s.episodeNumber', 'ASC')
            ->addOrderBy('s.date', 'ASC');

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * Return all currently available show Ids.
     *
     * @return integer[]
     */
    public function getAllIds()
    {
        $dql    = 'SELECT s.id FROM TiVampyre\Entity\Show s';
        $query  = $this->getEntityManager()->createQuery($dql);
        $result = $query->getResult();

        return array_map('current', &$result);
    }

    /**
     * Delete all shows without a current timestamp.
     */
    public function deleteOutdated()
    {
        $dql = '
            DELETE FROM TiVampyre\Entity\Show s1
            WHERE s1.ts < (
                SELECT MAX(s2.ts) FROM TiVampyre\Entity\Show s2
            )
        ';

        $this->getEntityManager()->createQuery($dql)->execute();
    }
}
