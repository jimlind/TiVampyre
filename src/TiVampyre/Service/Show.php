<?php

namespace TiVampyre\Service;

use Doctrine\ORM\EntityManager;
use TiVampyre\Entity;
use Symfony\Bridge\Monolog\Logger;
use TiVo\NowPlaying;
use Twitter;

class Show {
    
    private $entityManager;
    private $nowPlaying;
    private $twitter;
    private $logger;
    private $repository;
    private $timestamp;
    private $tweets;
    
    public function __construct(
        EntityManager $entityManager,
        NowPlaying $nowPlaying,
        Twitter $twitter,
        Logger $logger
    ) {
        $this->entityManager = $entityManager;
        $this->nowPlaying = $nowPlaying;
        $this->twitter = $twitter;
        $this->logger = $logger;
        $this->repository = $this->entityManager->getRepository("TiVampyre\Entity\Show");
        $this->tweets = array();
    }
    
    /*
     * Downloads all available shows and send them to be processed.
     */
    public function rebuildLocalIndex()
    {
        $allAvailableShows = $this->nowPlaying->download();
        $isInitialRun = ($this->repository->countAll() == 0);
        $this->timestamp = new \DateTime('now');
        // Word on the street is array_walk is faster than foreach.        
        array_walk(
            $allAvailableShows,
            array($this, 'processShow'),
            $isInitialRun
        );
        $this->entityManager->flush();
    }
    
    private function processShow($showXML, $index, $isInitialRun)
    {
        $show = new Entity\Show();
        $show->setTimeStamp($this->timestamp);
        $show->populate($showXML);
        // Write a tweet if it isn't the first run, and it is a new show.
        $isShowNew = is_null($this->repository->find($show->getId()));
        if (!$isInitialRun && $isShowNew) {
            $this->composeTweet($show);
        }
        // Merge instead of persist because we don't have original data object.
        $this->entityManager->merge($show);
    }
    
    private function composeTweet($show)
    {
        $tweet = 'I started recording ' . $show->getShowTitle() . ' ';
        $episodeTitle = $show->getEpisodeTitle();
        if (!empty($episodeTitle)) {
            $tweet .= '- ' . $episodeTitle . ' ';
        }
        $tweet .= 'on ' . $show->getStation() . ' ' . $show->getChannel();
        if (strtoupper($show->getHD()) == 'YES') {
            $tweet .= ' in HD';
        }
        $this->tweets[] = $tweet . '.';
    } 
    
    public function sendTweets() {
        array_walk(
            $this->tweets,
            array($this, 'sendTweet')
        );
    }
    
    private function sendTweet($tweet)
    {
        try {
            $this->twitter->send($tweet);
        } catch(\Exception $e) {
            $this->logger->addWarning($tweet);
            $this->logger->addWarning($e->getMessage());
        }
    }
}