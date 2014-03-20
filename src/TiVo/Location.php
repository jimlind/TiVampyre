<?php

namespace TiVo;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Process\Process;

class Location
{
    private $logger;
    private $process;
    private $ip;

    function __construct(Logger $logger, Process $process, $ip) {
        $this->logger = $logger;
        $this->process = $process;
        $this->ip = $ip;
    }

    public function find() {
        if ($this->ip) {
            return $this->ip;
        }

        $command = 'avahi-browse -l -r -t _tivo-videos._tcp';
        $this->process->setCommandLine($command);
        $this->process->setTimeout(60); // 1 minute
        $this->process->run();
        $output = $this->process->getOutput();

        if (empty($output)) {
            $this->logger->addWarning('Problem locating a proper device on the
                network. The avahi-browse tool may not be installed.');
            return false;
        }

        foreach (explode(PHP_EOL, $output) as $line) {
            $pattern = '/^\s+address = \[(\d+\.\d+\.\d+\.\d+)\]$/';
            preg_match($pattern, $line, $matches);
            if (!empty($matches) && isset($matches[1])) {
                return $matches[1];
            }
        }

        $this->logger->addWarning('Unable to parse IP from Avahi.');
        return false;
    }
}
