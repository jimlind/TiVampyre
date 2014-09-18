<?php

namespace JimLind\TiVampyre;

use Symfony\Component\Process\Process;

class JobQueue {

    private $connection = null;

    public function __construct(\Doctrine\DBAL\Connection $connection) {
        $this->connection = $connection;
    }

    public function add($id) {
        $this->connection->insert('job_queue', array(
            'show_id' => (int) $id,
            'status'  => 1,
            'ts'      => date('Y-m-d H:i:s')
        ));
    }

    public function getAll() {        
        $q = 'SELECT * FROM job_queue ORDER BY ts';
        return $this->connection->fetchAll($q);
    }
}