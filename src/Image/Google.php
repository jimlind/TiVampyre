<?php

namespace Image;

use Symfony\Component\Process\Process;

class Google {

    private $key;
    private $process;

    function __construct($key, Process $process) {
        $this->key = $key;
        $this->process = $process;
    }

    function getOneURL($keywords, $start) {
        $url = 'http://ajax.googleapis.com/ajax/services/search/images?v=1.0' .
               '&q=' . urlencode($keywords) .
               '&start=' . intval($start) . '&key=' . $this->key .
               '&as_filetype=jpg&imgsz=medium|large';
        
        $command = "curl -s '$url'";

        $this->process->setCommandLine($command);
        $this->process->setTimeout(30); // 30 seconds
        $this->process->run();

        $output = $this->process->getOutput();
        $object = json_decode($output);
        return $object->responseData->results[0]->unescapedUrl;
    }
}