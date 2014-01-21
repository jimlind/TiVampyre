<?php

namespace TiVo;

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
        $this->ip = '192.168.42.102';
    }

    public function download() {
        if ($this->ip === false) {
            $this->logger->addWarning('Can not download without a TiVo.');
            return array();
        }

        $anchorOffset = 0;
        $xmlFile = $this->downloadXmlFile($anchorOffset);
        $showList = $this->xmlFileToArray($xmlFile);

        while ($xmlFile) {
            $anchorOffset = count($showList);
            $xmlFile = $this->downloadXmlFile($anchorOffset);
            if ($xmlFile) {
                $showList = array_merge($showList, $this->xmlFileToArray($xmlFile));
            }
        }

        return $showList;
    }

    private function downloadXmlFile($anchorOffset) {
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
        if (!is_object($xml)) {
            return false;
        }
        if (!isset($xml->ItemCount)) {
            return false;
        }
        $itemCount = (int) $xml->ItemCount;
        if ($itemCount == 0) {
            return false;
        } else {
            return $xml;
        }
    }

    private function xmlFileToArray($simpleXml) {
        $shows = array();
        if (!isset($simpleXml->Item)) {
            return $shows;
        }
        foreach ($simpleXml->Item as $show) {
            $shows[] = $show;
        }
        return $shows;
    }

}
