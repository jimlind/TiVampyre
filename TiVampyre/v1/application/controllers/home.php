<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {
    
    public function index()
    {   
        $this->load->model('shows');
        $this->load->model('file');
        
        $shows = $this->shows->readActive();
        $watchable = $this->file->getWatchableVideos();
        sort($watchable);
        
        $data['shows'] = $shows;
        $data['watchable'] = $watchable;
        $this->load->view('home', $data);
    }
}