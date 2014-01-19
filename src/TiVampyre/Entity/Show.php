<?php

namespace TiVampyre\Entity;

/**
 * @Entity(repositoryClass="TiVampyre\Repository\Show"))
 * @Table(name="show")
 */
class Show
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;
    
    /**
     * @Column(type="string", name="show_title")
     */
    protected $showTitle;

    /**
     * @Column(type="string", name="episode_title")
     */
    protected $episodeTitle;

    /**
     * @Column(type="integer", name="episode_number")
     */
    protected $episodeNumber;
    
    /**
     * @Column(type="integer", name="duration")
     */
    protected $duration;
    
    /**
     * @Column(type="string", name="date")
     */
    protected $date;
    
    /**
     * @Column(type="string", name="description")
     */
    protected $description;
    
    /**
     * @Column(type="integer", name="channel")
     */
    protected $channel;
    
    /**
     * @Column(type="string", name="station")
     */
    protected $station;
    
    /**
     * @Column(type="string", name="hd")
     */
    protected $hd;
    
    /**
     * @Column(type="string", name="url")
     */
    protected $url;
    
    /**
     * @Column(type="string", name="ts")
     */
    protected $ts;
}
