<?php

namespace JimLind\TiVo;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Process\Process;

class NowPlaying {

	private $ip;
	private $mak;
	private $logger;
	private $process;

	function __construct(Location $location, $mak, Logger $logger, Process $process) {
		$this->ip = $location->find();
		$this->mak = $mak;
		$this->logger = $logger;
		$this->process = $process;

		//TODO Disable this override.
		$this->ip = '192.168.42.101';
	}

	public function download() {
		if ($this->ip === false) {
			$this->logger->addWarning('Can not download without a TiVo.');
			return false;
		}

		$anchorOffset = 0;
		$xmlPiece = $this->downloadXmlPiece($anchorOffset);
		$showList = $this->xmlToShows($xmlPiece);

		while ($xmlPiece) {
			$anchorOffset = count($showList);
			$xmlPiece = $this->downloadXmlPiece($anchorOffset);
			if ($xmlPiece) {
				$showList = array_merge($showList, $this->xmlToShows($xmlPiece));
			}
		}

		return $showList;
	}

	private function downloadXmlPiece($anchorOffset) {
		$data = array(
			'Command' => 'QueryContainer',
			'Container' => '/NowPlaying',
			'Recurse' => 'Yes',
			'AnchorOffset' => $anchorOffset,
		);
		$url = 'https://' . $this->ip . '/TiVoConnect?' . http_build_query($data);
		$command = "curl -s '$url' -k --digest -u tivo:" . $this->mak;

		$this->process->setCommandLine($command);
		$this->process->setTimeout(600); // 10 minutes
		$this->process->run();

		$xml = simplexml_load_string($this->process->getOutput());
		$itemCount = (int) $xml->ItemCount;
		if ($itemCount == 0) {
			return false;
		} else {
			return $xml;
		}
	}

	public function xmlToShows($simpleXml) {
		$shows = array();
		foreach ($simpleXml->Item as $item) {
			$show = new Show();
			$show->translateXML($item);
			$shows[] = $show;
		}
		return $shows;
	}

}
