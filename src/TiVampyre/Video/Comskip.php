<?php

namespace TiVampyre\Video;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Interface with the Comskip Windows executable.
 */
class Comskip
{
    protected $comskipPath = null;

    protected $process = null;

    protected $logger = null;

    public function __construct($comskipPath, Process $process, LoggerInterface $logger)
    {
        $this->comskipPath = $comskipPath;
        $this->process     = $process;
        $this->logger      = $logger;
    }

    public function getChapterList($mpegPath)
    {
        $edlFile        = $this->generateEdl($mpegPath);
        $commercialList = $this->parseEdlFile($edlFile);
        $chapterList    = $this->convertCommercials($commercialList);
        return $this->cleanChapterList($chapterList);
    }

    protected function generateEdl($mpegPath)
    {
        $appPath = $this->comskipPath . 'comskip.exe';
        $iniPath = $this->comskipPath . 'comskip.ini';
        $command = 'wine ' . $appPath . ' --ini=' .  $iniPath . ' ' . $mpegPath;

        $this->process->setCommandLine($command);
        $this->process->setTimeout(0); // No timeout.
        $this->process->run();

        $edlFile = false;
        $rmList  = array('.log', '.txt');

        $fileRoot = substr($mpegPath, 0, strrpos($mpegPath, '.'));
        $fileList = glob($fileRoot . '.*');
        foreach($fileList as $file) {
            $extension = substr($file, strrpos($file, '.'));
            if (in_array($extension, $rmList)) {
                unlink($file);
            }
            if ($extension === '.edl') {
                $edlFile = $file;
            }
        }

        return $edlFile;
    }

    protected function parseEdlFile($edlFile)
    {
        $commercialList = array();
        $edlContent     = file_get_contents($edlFile);
        $edlLineList    = preg_split ('/\r\n|\n|\r/', $edlContent);
        foreach ($edlLineList as $edlLine) {
            $this->parseEdlLine($edlLine, $commercialList);
        }

        return $commercialList;
    }

    protected function parseEdlLine($edlLine, &$outputList)
    {
        $data = preg_split ('/\s/', $edlLine);
        if (count($data) == 3 && $data[2] === '0') {
            $outputList[] = array(
                'start' => $data[0],
                'end'   => $data[1],
            );
        }
    }

    protected function convertCommercials($commercialList)
    {
        $chapterList = array();

        foreach($commercialList as $index => $commercial) {
            if (!isset($commercialList[$index + 1])){
                continue;
            }
            $start = $commercial['end'];
            $end   = $commercialList[$index + 1]['start'];

            $chapterList[] = array(
                'start' => (float) $start,
                'end'   => (float) $end,
            );
        }

        return $this->bookendChapterList($commercialList, $chapterList);
    }

    protected function bookendChapterList($commercialList, $chapterList)
    {
        if (count($commercialList) > 0) {
            $firstChapter = array(
                'start' => (float) 0,
                'end'   => (float) $commercialList[0]['start'],
            );

            $max   = count($commercialList) - 1;
            $start = $commercialList[$max]['end'];

            $lastChapter =  array(
                'start' => (float) $start,
                'end'   => (float) $start + (24 * 60 * 60), // start + 24 hours
            );
        }
        array_unshift($chapterList, $firstChapter);
        array_push($chapterList, $lastChapter);

        return $chapterList;
    }

    protected function cleanChapterList($chapterList, $minimumChapter = 5)
    {
        foreach($chapterList as $index => $chapter) {
            if (($chapter['end'] - $chapter['start']) < $minimumChapter) {
                unset($chapterList[$index]);
            }
        }
        return $chapterList;
    }
}
