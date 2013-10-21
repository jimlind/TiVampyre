<?php

namespace JimLind\Image;

use Symfony\Component\Process\Process;

class Google {

	private $key;
	private $process;

	function __construct($key, Process $process) {
		$this->key = $key;
		$this->process = $process;
	}

	function getOneURL($keywords) {
		$images = array();

		$url = 'http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=';
		$url .= urlencode($keywords).'&start=0&key='.$this->key;

		$command = "curl -s '$url'";

		$this->process->setCommandLine($command);
		$this->process->setTimeout(30); // 30 seconds
		$this->process->run();

		$output = $this->process->getOutput();
		$object = json_decode($output);
		return $object->responseData->results[0]->unescapedUrl;
	}
}