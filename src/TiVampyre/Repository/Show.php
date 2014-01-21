<?php

namespace TiVampyre\Repository;

use Doctrine\ORM\EntityRepository;

class Show extends EntityRepository
{
    /**
     * Returns a count of all shows in database
     * 
     * @return integer
     */
    public function countAll(){
        $query = $this->_em->createQuery('SELECT count(s) FROM TiVampyre\Entity\Show s');
        return $query->getSingleScalarResult();
    }
    
    /**
     * Get all the show records with the most recent timestamp.
     * 
     * @return array - Array of records
     */
    public function getCurrent() {
        $q = 'SELECT s1
            FROM TiVampyre\Entity\Show s1
            WHERE s1.ts=(
                SELECT MAX(s2.ts) FROM TiVampyre\Entity\Show s2
            )
            ORDER BY s1.showTitle, s1.episodeNumber, s1.date';
        var_dump($q);
        
        //return array();
        return $this->_em->createQuery($q)->getResult();
    }
}