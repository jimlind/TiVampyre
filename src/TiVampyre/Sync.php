<?php

namespace TiVampyre;

use Doctrine\ORM\EntityManager;
use JimLind\TiVo;
use Psr\Log\LoggerInterface as Logger;
use TiVampyre\Entity\Show as Entity;
use TiVampyre\Twitter\TweetEvent as Tweet;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Sync local show data.
 */
class Sync
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var JimLind\TiVo\NowPlaying
     */
    private $nowPlaying;

    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var TiVampyre\Repository\Show
     */
    private $repository;

    /**
     * Constructor
     *
     * @param Doctrine\ORM\EntityManager                        $entityManager Doctrine Entity Manager
     * @param JimLind\TiVo\NowPlaying                           $nowPlaying    Access to Now Playing list
     * @param Symfony\Component\EventDispatcher\EventDispatcher $dispatcher    Symfony's Event Dispatcher
     * @param Psr\Log\LoggerInterface                           $logger        Where to log warnings and errors
     */
    public function __construct(
        EntityManager $entityManager,
        TiVo\NowPlaying $nowPlaying,
        EventDispatcher $dispatcher,
        Logger $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->nowPlaying    = $nowPlaying;
        $this->dispatcher    = $dispatcher;
        $this->logger        = $logger;

        $this->repository = $this->entityManager->getRepository('TiVampyre\Entity\Show');
    }

    /**
     * Load data from the TiVo and rebuild the local database.
     */
    public function rebuildLocalIndex()
    {
        $timestamp = new \DateTime('now');
        $factory   = new TiVo\Factory\ShowFactory(new Entity());

        $showIdList = $this->repository->getAllIds();
        $firstRun   = $this->repository->countAll() === 0;
        $xmlList    = $this->nowPlaying->download();
        $showList   = $factory->createFromXmlList($xmlList);

        foreach ($showList as $show) {
            // If not a first run and not previously recorded
            if (!$firstRun && !in_array($show->getId(), $showIdList)) {
                $this->dispatcher->dispatch(Tweet::$SHOW_TWEET_EVENT, new Tweet($show));
            }

            $show->setTimeStamp($timestamp);
            $this->entityManager->merge($show);
        }
        $this->entityManager->flush();
        $this->repository->deleteOutdated();
    }
}