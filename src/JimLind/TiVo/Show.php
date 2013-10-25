<?php

namespace JimLind\TiVo;

class Show {

	const INSERT = 1;
	const UPDATE = 2;

	private $id = null;
	private $showTitle = null;
	private $episodeTitle = null;
	private $episodeNumber = null;
	private $duration = null;
	private $date = null;
	private $description = null;
	private $channel = null;
	private $station = null;
	private $hd = null;
	private $url = null;

	public function __construct(\SimpleXMLElement $xml) {
		$details   = $xml->Details;
		$links     = $xml->Links;
		$detailUrl = (string) $links->TiVoVideoDetails->Url;

		$matches = array();
		preg_match('/.+?id=([0-9]+)$/', $detailUrl, $matches);
		if (isset($matches[1])) {
			$this->id = $matches[1];
		}

		$this->showTitle     = (string) $details->Title;
		$this->episodeTitle  = (string) $details->EpisodeTitle;
		$this->episodeNumber = (int)    $details->EpisodeNumber;
		$this->duration      = (int)    $details->Duration;
		$this->description   = (string) $details->Description;
		$this->channel       = (int)    $details->SourceChannel;
		$this->station       = (string) $details->SourceStation;
		$this->hd            = (string) $details->HighDefinition;
		$this->date          = (string) $details->CaptureDate;
		$this->url           = (string) $links->Content->Url;
	}

	public function getDetail() {
		return $this->showTitle . ':' . $this->episodeTitle . ':' . $this->episodeNumber;
	}

	public function writeToDatabase(\Doctrine\DBAL\Connection $connection, \DateTime $timeStamp) {
		$count = $connection->fetchColumn('SELECT COUNT(id) FROM shows WHERE id = ?', array($this->id));
		if (intval($count) == 0) {
			$connection->insert('shows', array(
				'id'             => $this->id,
				'show_title'     => $this->showTitle,
				'episode_title'  => $this->episodeTitle,
				'episode_number' => $this->episodeNumber,
				'duration'       => $this->duration,
				'description'    => $this->description,
				'channel'        => $this->channel,
				'station'        => $this->station,
				'hd'             => $this->hd,
				'date'           => date('Y-m-d H:i:s', hexdec((string) $this->date)),
				'url'            => $this->url,
				'ts'             => $timeStamp->format('Y-m-d H:i:s')
			));
			return self::INSERT;
		} else {
			$connection->update('shows',
				array(
					'duration' => $this->duration,
					'date' => $this->date,
				),
				array(
					'id' => $this->id,
				)
			);
			return self::UPDATE;
		}
	}

	public function startedRecordingMessage() {
		$message  = 'I started recording ' . $this->showTitle . ' ';
		if (!empty($this->episodeTitle)) {
			$message .= '- ' . $this->episodeTitle . ' ';
		}
		$message .= 'on ' . $this->station . ' ' . $this->channel;
		if (strtoupper($this->hd) == 'YES') {
			$message .= ' in HD';
		}
		$message .= '.';
		return $message;
	}

}