<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {
    
    public function index()
    {   
        $this->load->model('shows');
        $shows = $this->shows->readActive();
        
        $data['shows'] = $shows;
        $this->load->view('home', $data);
    }
}