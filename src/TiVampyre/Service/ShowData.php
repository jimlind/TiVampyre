<?php

namespace JimLind\TiVampyre;

use JimLind\TiVo\Show;

/**
 * Reads and writes show data
 */
class ShowData {

    const INSERT = "show_data_insert";
    const UPDATE = "show_data_update";
    
    private $connection = null;

    public function __construct(\Doctrine\DBAL\Connection $connection) {
        $this->connection = $connection;
    }

    /**
     * Get all the show records with the most recent timestamp.
     * 
     * @return array - Array of records
     */
    public function getCurrent() {
        $q = 'SELECT * 
            FROM shows
            WHERE ts=(
                SELECT MAX(ts) FROM shows
            )
            ORDER BY show_title, episode_number, date';
        return $this->connection->fetchAll($q);
    }
    
    public function getCurrentFormatted() {
        $data = $this->getCurrent();
        foreach($data as $index => $dataPoint) {
            $data[$index]['episode'] = $this->formatEpisodeTitle($dataPoint);
        }
        return $data;
    }
    
    /**
     * Writes a show to the show database table.
     * 
     * @param Show $show
     * @param \DateTime $timestamp
     * @return string - Constant in this class.
     */
    public function write(Show $show, \DateTime $timestamp) {        
        // Does the show already exist?
        if (!$this->showExists($show->getId())) {
            // Insert it.
            $this->insert($show, $timestamp);
            return self::INSERT;
        } else {
            // Update it.
            $this->update($show, $timestamp);
            return self::UPDATE;
        }
    }
    
    /**
     * Check if the show already exists
     * 
     * @param string,int $id
     * @return boolean
     */
    private function showExists($id) {
        $count = $this->connection->fetchColumn(
            'SELECT COUNT(id) FROM shows WHERE id = ?',
            array($id)
        );
        return intval($count) == 1;
    }
    
    /**
     * Format the date in a SQL friendly format.
     * 
     * @param \DateTime $timestamp
     * @return string
     */
    private function formatTimestamp(\DateTime $timestamp) {
        return $timestamp->format('Y-m-d H:i:s');
    }
    
    /**
     * Format the episode data as something universal and readable.
     * @param array $show
     * @return string
     */
    private function formatEpisodeTitle($show) {
        $fullEpisode = '';
        if ($show['episode_number'] != '0') {
            $fullEpisode .= $show['episode_number'] . ' - ';
        }
        $fullEpisode .= $show['episode_title'];
        if ($show['episode_title'] == '') {
            $recordedDate = new \DateTime($show['date']);
            $fullEpisode .= $recordedDate->format('m/d/Y');
        }
        return $fullEpisode;
    }
    
    /**
     * Inserts a new show into the database.
     * 
     * @param \JimLind\TiVo\Show $show
     * @param \DateTime $timestamp
     */
    private function insert(Show $show, \DateTime $timestamp) {
        $showArray       = $show->getAsDBALArray();
        $showArray['ts'] = $this->formatTimestamp($timestamp);
        $this->connection->insert('shows', $showArray);
    }
    
    /**
     * Updates a show in the database.
     * 
     * @param \JimLind\TiVo\Show $show
     * @param \DateTime $timestamp
     */
    private function update(Show $show, \DateTime $timestamp) {
        $showArray       = $show->getAsDBALArray();
        $showArray['ts'] = $this->formatTimestamp($timestamp);
        $identifier      = array('id' => $showArray['id']);
        $this->connection->update(
            'shows',
            $showArray,
            $identifier
        );
    }
    
    /**
     * Get the total number of shows in the database.
     * 
     * @return int
     */
    public function totalCount() {
        $count = $this->connection->fetchColumn('SELECT COUNT(id) FROM shows');
        return intval($count);
    } 
}