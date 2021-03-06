<?php

namespace TiVampyre\Entity;

use JimLind\TiVo\Model\Show as ShowModel;

/**
 * @Entity(repositoryClass="TiVampyre\Repository\ShowRepository"))
 * @Table(name="show")
 */
class ShowEntity extends ShowModel
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

    /**
     * @Column(type="string", name="preview")
     */
    protected $preview;

    public function getDescription()
    {
        $boring = 'Copyright Tribune Media Services, Inc.';
        return trim(str_replace($boring, '', $this->description));
    }

    public function getDate()
    {
        return new \DateTime($this->date);
    }

    public function setDate($date)
    {
        if (!$date instanceof \DateTime) {
            $date = new \DateTime();
        }
        $this->date = $date->format('c');
    }

    public function getTimeStamp()
    {
        return new \DateTime($this->ts);
    }

    public function setTimeStamp(\DateTime $ts)
    {
        if (!$ts instanceof \DateTime) {
            $ts = new \DateTime();
        }
        $this->ts = $ts->format('c');
    }

    public function getPreview()
    {
        return new \DateTime($this->preview);
    }

    public function setPreview(\DateTime $preview)
    {
        $this->preview = $preview->format('c');
    }

    /**
     * Populate the entity from an XML object
     *
     * @param SimpleXMLElement $xml
     * @return Show
     */
    public function populate($xml) {
        // Gather the important bits from the XML
        $details   = $xml->Details;
        $links     = $xml->Links;
        $matches   = array();
        $detailUrl = (string) $links->TiVoVideoDetails->Url;
        preg_match('/.+?id=([0-9]+)$/', $detailUrl, $matches);
        if (!isset($matches[1])) {
            return false;
        }

        // Fill in the details
        $this->id            = (int)    $matches[1];
        $this->showTitle     = (string) $details->Title;
        $this->episodeTitle  = (string) $details->EpisodeTitle;
        $this->episodeNumber = (int)    $details->EpisodeNumber;
        $this->duration      = (int)    $details->Duration;
        $this->description   = (string) $details->Description;
        $this->channel       = (int)    $details->SourceChannel;
        $this->station       = (string) $details->SourceStation;
        $this->hd            = (string) $details->HighDefinition;
        $this->date          = date('Y-m-d H:i:s', hexdec((string) $details->CaptureDate));
        $this->url           = (string) $links->Content->Url;

        return $this;
    }
}
