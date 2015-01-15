<?php

namespace TiVampyre\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for Job Entity.
 */
class Job extends EntityRepository
{
    /**
     * Returns a count of all tube entries in database
     *
     * @return integer
     */
    public function countAll()
    {
        $dql   = 'SELECT count(j) FROM TiVampyre\Entity\Job j';
        $query = $this->getEntityManager()->createQuery($dql);

        return $query->getSingleScalarResult();
    }
}
