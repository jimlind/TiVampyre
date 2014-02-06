<?php
/**
 * TiVampyre Show Service
 * 
 * PHP version 5
 * 
 * @category Service
 * @package  TiVampyre
 * @author   Jim Lind <spoon.vw@gmail.com>
 * @license  The MIT License
 * @link     https://github.com/jimlind/TiVampyre
 */

namespace TiVampyre\Service;

use Doctrine\ORM\EntityManager;
use TiVampyre\Entity;
use Symfony\Bridge\Monolog\Logger;
use TiVo\NowPlaying;
use Twitter;

/**
 * This service contains all the heavy lifting involved with importing shows.
 * It will get XML data from the NowPlaying Class put it in the appropiate
 * places, and generate and sent Tweets as neccessary.
 * 
 * @category Service
 * @package  TiVampyre
 * @author   Jim Lind <spoon.vw@gmail.com>
 * @license  The MIT License
 * @link     https://github.com/jimlind/TiVampyre
 */
class Show
{
    
    private $_entityManager;
    private $_nowPlaying;
    private $_twitter;
    private $_logger;
    private $_repository;
    private $_timestamp;
    private $_tweets;
       
    /**
     * Constructor.
     * 
     * @param EntityManager $entityManager Doctrine Entity Manager
     * @param NowPlaying    $nowPlaying    Access to Now Playing list
     * @param Twitter       $twitter       Twitter API translator
     * @param Logger        $logger        Where to log errors
     */
    public function __construct(
        EntityManager $entityManager,
        NowPlaying $nowPlaying,
        Twitter $twitter,
        Logger $logger
    ) {
        $this->_entityManager = $entityManager;
        $this->_nowPlaying = $nowPlaying;
        $this->_twitter = $twitter;
        $this->_logger = $logger;
        $this->_repository = $entityManager->getRepository("TiVampyre\Entity\Show");
        $this->_tweets = array();
    }
    
    /**
     * Gets extremely basic show information from the show repository.
     * 
     * @return array Title and Quantity from database.
     */
    public function getHomepageData()
    {
        $dql = 'SELECT s1.showTitle, COUNT(s1.id) as qty ' .
               'FROM TiVampyre\Entity\Show s1 ' .
               'WHERE s1.ts=( ' .
               '   SELECT MAX(s2.ts) FROM TiVampyre\Entity\Show s2 ' .
               ') ' .
               'GROUP BY s1.showTitle';
        $query = $this->_entityManager->createQuery($dql);
        return $query->getResult();
    }
    
    /**
     * Gets all episode entities based on a show name
     * 
     * @param string $name
     * 
     * @return array An array of entities.
     */
    public function getEpisodes($name)
    {
        return $this->_repository->findByShowTitle($name);
    }
    
    /**
     * Downloads all available shows and send them to be processed.
     * 
     * @return null No return
     */
    public function rebuildLocalIndex()
    {
        $allAvailableShows = $this->_nowPlaying->download();
        $isInitialRun = ($this->_repository->countAll() == 0);
        $this->_timestamp = new \DateTime('now');
        // Word on the street is array_walk is faster than foreach.        
        array_walk(
            $allAvailableShows,
            array($this, '_processShow'),
            $isInitialRun
        );
        $this->_entityManager->flush();
    }
    
    /**
     * Writes a show entity to the database and compose a Tweet if neccessary.
     * 
     * @param SimpleXMLElement $showXML   Show data in XML format
     * @param integer          $index     Ignore value from array walk
     * @param boolean          $dontTweet Don't compose a Tweet
     * 
     * @return null                       No return
     */
    private function _processShow($showXML, $index, $dontTweet = false)
    {
        $show = new Entity\Show();
        $show->setTimeStamp($this->_timestamp);
        $show->populate($showXML);
        // Write a tweet if not overridden, and it is a new show.
        $isShowNew = is_null($this->_repository->find($show->getId()));
        if (!$dontTweet && $isShowNew) {
            $this->_composeTweet($show);
        }
        // Merge instead of persist because we don't have original data object.
        $this->_entityManager->merge($show);
    }
    
    /**
     * Using an entity write a Tweet and add it to an array for later usage.
     * 
     * @param Entity/Show $show A show entity
     * 
     * @return null             No return
     */
    private function _composeTweet($show)
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
        $this->_tweets[] = $tweet . '.';
    } 
    
    /**
     * Walk through the tweets array calling a function for each.
     * 
     * @return null No return
     */
    public function sendTweets()
    {
        array_walk(
            $this->_tweets,
            array($this, '_sendTweet')
        );
    }
    
    /**
     * Send a Tweet or log a warning.
     * 
     * @param string $tweet A message indicating that the show started recording.
     * 
     * @return null No return
     */
    private function _sendTweet($tweet)
    {
        try {
            //$this->_twitter->send($tweet);
        } catch(\Exception $e) {
            $this->_logger->addWarning($tweet);
            $this->_logger->addWarning($e->getMessage());
        }
    }   
}