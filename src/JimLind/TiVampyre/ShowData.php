<?php

namespace JimLind\TiVampyre;

use Symfony\Bridge\Monolog\Logger;

/**
 * Reads and writes show data
 */
class ShowData {

    private $connection = null;

    public function __construct(\Doctrine\DBAL\Connection $connection) {
        $this->connection = $connection;
    }

    public function totalCount() {
        $count = $this->connection->fetchColumn('SELECT COUNT(id) FROM shows');
        return intval($count);
    }
}