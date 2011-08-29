<?php if (!defined("BASEPATH")) exit("No direct script access allowed");

class File extends CI_Model
{
    private $dir = '';
    
    public $id = '000';
    public $extension = 'txt';
    public $path = '';
    
    function __construct()
    {
        parent::__construct();
        
	$ci =& get_instance();
	$vConfig = $ci->config->item('tivampyre');
	$this->dir = $vConfig['working_directory'];
	
	$this->buildPath();
    }

    public function buildPath($e = false)
    {
        if($e) $this->extension = $e;
        $this->path = $this->dir . $this->id . '.' . $this->extension;
        return $this->path;
    }

    public function buildFinalName($data)
    {
        // start with the show title
        $showString = $data->show_title;
        
        // add episode title if applicable.
	if ($data->episode_title != "") {
            $showString .= " - " . $data->episode_title; 
	}
	// add episode number or date
	if ($data->episode_number != 0) {
            $showString .= " - e" . $data->episode_number;
	} else {
	    $showString .= " - " . date('n.d-Gi', strtotime($data->date));
	}
        // clean it up
        $showString = preg_replace("/[^A-Za-z0-9 -]/", "", $showString);
        return $showString;
    }

    public function rename($filename)
    {
        $oldFileName = $this->buildPath();
        $this->id = $filename;
        $newFileName = $this->buildPath();
        return rename($oldFileName, $newFileName);
    }
    
    public function addMetaData($data)
    {
        $file = $this->buildPath();
        $m  = "AtomicParsley \"$file\" ";
        $m .= isset($data->episode_title) ? "--title \"".$data->episode_title."\" " : "";
        $m .= isset($data->description) ? "--description \"".$data->description."\" " : "";
        $m .= isset($data->show_title) ? "--TVShowName \"".$data->show_title."\" " : "";
        $m .= isset($data->episode_number) ? "--TVEpisode \"".$data->episode_number."\" " : "";
        $m .= isset($data->episode_number) ? "--TVEpisodeNum \"".$data->episode_number."\" " : "";
        $m .= isset($data->channel) ? "--TVNetwork \"".$data->channel."\" " : "";
        $m .= isset($data->episode_number) && $data->episode_number != 0 ? "--stik \"TV Show\" " : "--stik \"Movie\" ";
        $m .= "--overWrite ";
        shell_exec($m);
    }
}