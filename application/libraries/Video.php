<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Video {
    
    public function process($job, $input, $output)
    {
        $resize = $job->full != 1;
	$crop = $job->crop == 1;
	$size = $this->getSize($input);
	
        $bits = $this->getVideoBitrate($size['height'], $size['width']);
        $this->encode($input, $bits, $resize, $crop, $output);
	if ($job->chop == 1) {
            $this->chopCommercials($input, $output);
        }
	
        return file_exists($output);
    }
    
    private function getSize($target)
    {
	$s  = "mplayer $target ";
	$s .= "-ss 300 ";		// skip 300 seconds (5 minutes) to get proper size;
	$s .= "-identify -frames 0 -vc null -vo null -ao null ";
	$s .= "2>/dev/null | grep 'ID_VIDEO_WIDTH\|ID_VIDEO_HEIGHT'";
        
	log_message('debug', $s);
	$o = shell_exec($s);
	
        $pattern = '/=([0-9.]*)/';
        preg_match_all($pattern, $o, $matches);
        if(count($matches[1]) == 0) return array('width'=>0, 'height'=>0);
	return array('width'=>$matches[1][0], 'height'=>$matches[1][1]);
    }
    
    private function getVideoBitrate($height, $width)
    {
        $square = $height * $width;
        $bitrate = (0.0013 * $square) + 410;
        return round($bitrate);
    }
 
     private function encode($target, $bits, $resize, $crop, $output)
    {
        $addFilter = "";
        $height = $size['height'];
        $width = $size['width'];
	
	$ci =& get_instance();
	$vConfig = $ci->config->item('tivampyre');
	$workDir = $vConfig['working_directory'];

	// start command line
	$h  = "HandBrakeCLI ";
	$h .= "-i $target "; //input
	$h .= "-o $output "; //output
	
	// video encoding
	$h .= "-e x264 -x ref=2:bframes=0 "; //codec and settings
	$h .= "-b $bits ";	//bitrate
	$h .= "-r 29.97 ";	//framerate
	
	// audio encoding
	$h .= "-E faac ";	//codec
	$h .= "-B 128 ";	//bitrate
	$h .= "-6 stereo ";	//down sample to stereo
	$h .= "-D 1.5 ";	//dynamic volume compression

	// crop and resize
	if ($resize) {
	    $h .= "-X 1024 ";		//max width 1024
	}
	if (!$crop) {
	    $h .= "--crop 0:0:0:0 ";	//no cropping, default is auto crop
	}
	
	// filters
	$h .= "-d slower -5 ";		// deinterlace and decomb
	$h .= "--loose-anamorphic ";	// keep a good aspect ratio

	// enable two pass encoding
	$h .= "-2 -T ";

	log_message('debug', $h);
        shell_exec($h);
	
        return file_exists($output);
    }
    
    private function chopCommercials($mpeg, $mp4)
    {
	$ci =& get_instance();
	$vConfig = $ci->config->item('tivampyre');
	$path = $vConfig['comskip_path'];
        $exePath = $path . 'comskip.exe';
        $iniPath = $path . 'comskip.ini';
        $c  = "wine $exePath ";
        $c .= "--ini=$iniPath ";
        $c .= "$mpeg";
	
	log_message('debug', $c);
	shell_exec($c);
	
        $pos = strrpos($mp4, '.');
        $fileRoot = substr($mp4, 0, $pos);
        $edl = $fileRoot . ".edl";
	$clean = $fileRoot . ".clean.mp4";
	$cleanT1 = $fileRoot . ".clean_track1.h264";
	$cleanT2 = $fileRoot . ".clean_track2.aac";
	$remux = $fileRoot . ".remux.mp4";
	
	$m  = "mencoder $mp4 ";	// input
	$m .= "-edl $edl ";		// use commercials file
	$m .= "-oac faac -faacopts mpeg=4:object=2:raw:br=128 "; //encode audio
	$m .= "-ovc copy ";		// copy video
	$m .= "-of lavf ";		// force standard output
	$m .= "-o $clean";		// output
	
	log_message('debug', $m);
	shell_exec($m);

	$t1 = "MP4Box -raw 1 $clean";
	log_message('debug', $t1);
	shell_exec($t1);	

	$t2 = "MP4Box -raw 2 $clean";
	log_message('debug', $t2);
	shell_exec($t2);

	$r = "MP4Box -new $remux -add $cleanT1 -add $cleanT2";
	log_message('debug', $r);
	shell_exec($r);	

	// Remove all the extra files hanging around
        try {
            unlink($edl);
	    unlink($fileRoot . ".log");
            unlink($fileRoot . ".logo.txt");
            unlink($fileRoot . ".txt");
	    unlink($clean);
	    unlink($cleanT1);
	    unlink($cleanT2);
	    unlink($mp4);
	    rename($remux, $mp4);
        } catch(Exception $e) {
            //do nothing
        }
    }
}
