<?php

namespace TiVampyre\Entity;

/**
 * @Entity(repositoryClass="TiVampyre\Repository\Job"))
 * @Table(name="Job")
 */
class Job
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="integer", name="show_id")
     */
    protected $showId;

    /**
     * @Column(type="string", name="tube")
     */
    protected $tube;

    /**
     * Get job Id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set job Id.
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = intval($id);
    }

    /**
     * Get show Id.
     *
     * @return integer
     */
    public function getShowId()
    {
        return $this->showId;
    }

    /**
     * Set show Id.
     *
     * @param integer $id
     */
    public function setShowId($showId)
    {
        $this->showId = intval($showId);
    }

    /**
     * Get tube name.
     *
     * @return string
     */
    public function getTube()
    {
        return $this->tube;
    }

    /**
     * Set tube name.
     *
     * @param string $showTitle
     */
    public function setTube($tube)
    {
        $this->tube = (string) $tube;
    }
}
