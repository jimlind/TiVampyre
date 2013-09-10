<?php

namespace JimLind\TiVo;

use Symfony\Bridge\Monolog\Logger;

class Location
{
	private $logger;

	function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	public function find() {
		$command = 'avahi-browse -l -r -t _tivo-videos._tcp 2>&1';
		$return  = null;
		$status  = null;
		exec($command, $return, $status);

		if ($status !== 0) {
			$this->logger->addWarning(
				'Command avahi-browse not installed or configured. ' .
				'Install avahi-utils package.'
			);
			return false;
		}

		if (count($return) == 0) {
			$this->logger->addWarning('TiVo not found on your network.');
			return false;
		}

		$addressFound = false;
		foreach ($return as $line) {
			$pattern = '/^ address = \[(\d+.\d+.\d+.\d+]*)]$/';
			preg_match($pattern, $line, $matches);
			if (!empty($matches) && isset($matches[1])) {
				return $matches[1];
			}
		}

		$this->logger->addWarning('Unable to parse IP from Avahi.');
		return false;
	}
}
