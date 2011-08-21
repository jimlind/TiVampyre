<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Job extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('file');
	$this->load->model('jobs');
	$this->load->model('shows');
	
	$this->load->library('tivo');
        $this->load->library('video');
        $this->tivo->locate();
    }
    
    public function index()
    {
        return false;
    }
    
    // index.php?/job/queue/2/?keep&chop&full&crop
    public function queue($input)
    {
	$data = array();
        $data['show_id'] = (int) $input;
        
	if(isset($_GET['keep'])){ //keep mp2
	    $data['keep'] = 1;
	}
	if(isset($_GET['chop'])){ //chop commercials
	    $data['chop'] = 1;
	}
	if(isset($_GET['full'])){ //retain full size
	    $data['full'] = 1;
	}
	if(isset($_GET['crop'])){ //crop letterbox
	    $data['crop'] = 1;
	}
	
	$this->jobs->addJob($data);
    }
    
    public function run()
    {
	$job = $this->jobs->getNextJob();
	if (!$job) return false;
	
	$url = $job->url;
	$job_id = $job->jobs_id;
	$show_id = $this->file->id = $job->id;
	$title = $job->show_title;
	$status = $job->status;
	
	if ($this->jobs->anyStatus('downloading')) die; // if any job is downloading, die
	if ($status == 'waiting') {
	    $this->jobs->updateJob($job_id, 'downloading');
	    $this->tivo->downloadFile($url, $this->file->buildPath('tivo'));
	    $this->jobs->updateJob($job_id, 'downloaded');
	}
	if ($this->jobs->anyStatus('encoding')) die; // if any job is encoding, die
	if ($status == 'downloaded') {
	    $this->jobs->updateJob($job_id, 'encoding');
	    $tivoFile = $this->file->buildPath('tivo');  //encoded
	    $mpegFile = $this->file->buildPath('mpeg');  //decoded
	    $mp4File = $this->file->buildPath('mp4');    //processed
	    
	    $status = $this->tivo->decodeFile($tivoFile, $mpegFile);
	    if (!$status) {
		$this->jobs->updateJob($job_id, 'error 1');
		die;
	    } else {
		unlink($tivoFile);
	    }
	    $status = $this->video->process($job, $mpegFile, $mp4File);
	    if (!$status) {
		$this->jobs->updateJob($job_id, 'error 2');
		die;
	    } elseif ($job->keep == 0) {
		// delete the mpeg file
		unlink($mpegFile);
		
		// delete pass logs
		$ci =& get_instance();
		$vConfig = $ci->config->item('tivampyre');
		$dir = $vConfig['working_directory'];
		try {
		    unlink($dir . "pass.log.mbtree");
		    unlink($dir . "pass.log");
		} catch(Exception $e) {
		    //do nothing
		}
	    }

	    // rename file
	    $fileName = $this->file->buildFinalName($job);
	    $this->file->rename($fileName);
	    $this->shows->writeFinalFileName($show_id, $fileName);
	    // add metadata
	    $this->file->addMetaData($job);
	    
	    $this->jobs->updateJob($job_id, 'complete');
	}
    }
}