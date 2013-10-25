<?php

namespace JimLind\TiVampyre;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Process\Process;

class JobQueue {

	private $connection = null;

	public function __construct(\Doctrine\DBAL\Connection $connection) {
		$this->connection = $connection;
	}

	public function add($id) {
		$this->connection->insert('shows', array(
			'show_id' => $id,
			'status'  => 1,
			'ts'      => date('Y-m-d H:i:s')
		));
	}
}