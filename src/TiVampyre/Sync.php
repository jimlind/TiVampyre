<?php

namespace TiVampyre;

use Doctrine\ORM\EntityManager;
use JimLind\TiVo;
use Psr\Log\LoggerInterface as Logger;
use TiVampyre\Entity\Show as Entity;
use Twitter;

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
     * @var Twitter
     */
    private $twitter;

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
     * @param Doctrine\ORM\EntityManager $entityManager Doctrine Entity Manager
     * @param JimLind\TiVo\NowPlaying    $nowPlaying    Access to Now Playing list
     * @param Twitter                    $twitter       Twitter API translator
     * @param Psr\Log\LoggerInterface    $logger        Where to log warnings and errors
     */
    public function __construct(
        EntityManager $entityManager,
        TiVo\NowPlaying $nowPlaying,
        Twitter $twitter,
        Logger $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->nowPlaying    = $nowPlaying;
        $this->twitter       = $twitter;
        $this->logger        = $logger;

        $this->repository = $this->entityManager->getRepository('TiVampyre\Entity\Show');
    }

    /**
     * Load data from the TiVo and rebuild the local database.
     *
     * @param boolean $twitterStatus Use production-grade twitter.
     */
    public function rebuildLocalIndex($twitterStatus)
    {
        $timestamp = new \DateTime('now');
        $factory   = new TiVo\Factory\ShowFactory(new Entity());

        $activeTweeting = $twitterStatus && $this->repository->countAll();
        $xmlList        = $this->nowPlaying->download();
        $showList       = $factory->createFromXmlList($xmlList);

        foreach ($showList as $show) {
            if ($this->worthTweeting($show, $activeTweeting)) {
                $this->sendTweet($show);
            }

            $show->setTimeStamp($timestamp);
            $this->entityManager->merge($show);
        }
        $this->entityManager->flush();
    }

    /**
     * Return if this possibly new show worth Tweeting.
     *
     * @param TiVampyre\Entity\Show $show           A possibly new show.
     * @param boolean               $activeTweeting Should we tweet at all.
     *
     * @return boolean
     */
    protected function worthTweeting($show, $activeTweeting)
    {
        if (!$activeTweeting) {
            // Sometimes tweeting isn't appropriate.
            return false;
        }

        // Do tweet if show can't be found.
        return empty($this->repository->find($show->getId()));
    }

    /**
     * Send a Tweet that the show has started recording.
     *
     * @param TiVampyre\Entity\Show $show
     */
    protected function sendTweet($show)
    {
        $tweet = $this->composeTweet($show);
        try {
            $this->twitter->send($tweet);
        } catch (\Exception $e) {
            $this->logger->addWarning($tweet);
            $this->logger->addWarning($e->getMessage());
        }
    }

    /**
     * Compose a wonderful Tweet about the show.
     *
     * @param TiVampyre\Entity\Show $show
     *
     * @return string
     */
    protected function composeTweet($show)
    {
        $tweet        = 'I started recording ' . $show->getShowTitle() . ' ';
        $episodeTitle = $show->getEpisodeTitle();
        if (!empty($episodeTitle)) {
            $tweet .= '- ' . $episodeTitle . ' ';
        }
        $tweet .= 'on ' . $show->getStation() . ' ' . $show->getChannel();
        if ($show->getHd()) {
            $tweet .= ' in HD';
        }

        return $tweet . '.';
    }
}