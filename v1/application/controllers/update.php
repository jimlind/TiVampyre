<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Update extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->library('tivo');
        $this->load->library('xmlProcessor');
        $this->tivo->locate();
        
        $this->load->model('configure');
        $this->load->model('shows');
        $this->load->model('availability');
    }
    
    public function index()
    {
        $ip = $this->tivo->ip;
        if (strlen($ip) >= 7) {
            $this->configure->write(array('key'=>'ip', 'value'=>$ip));
        } else {
            $this->tivo->ip = $this->configure->read('ip');
        }
        
        $anchor = 0;
        $timestamp = date('Y-m-d H:i:s');
        while(true) {
            $xml = $this->tivo->getXml($anchor);
            if ($xml === false) return false;
            $data = $this->xmlprocessor->parseTivoXML($xml);
            if (isset($data['shows'])) {
                foreach ($data['shows'] as $show) {
                    $this->shows->write($show);
                    $this->availability->write($show['id'], true, $timestamp);
                }
            }
            // Data is no good.  Break loop.
            if (!isset($data['itemCount']) || !isset($data['totalItems'])) {
                break;
            }
            // Last bits of data gathered.  Break loop.
            if ($data['itemCount'] + $anchor >= $data['totalItems']) {
                break;
            }
            // Advance
            $anchor = $data['itemCount'];
        }
        
        // Deactivate old shows.
        $this->availability->rebuildAvailability();
    }
}