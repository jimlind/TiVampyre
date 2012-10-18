<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Video {
    
    public function process($job, $input, $output)
    {
        $resize = $job->full != 1;
	$crop = $job->crop == 1;
	$outputDimensions = $this->getOutputDimensions($input, $crop, $resize);
        $size = $outputDimensions['size'];
        $crop = $outputDimensions['crop'];
        
        $quality = $this->getVideoQuality($size['height'], $size['width']);     
        $this->encode($input, $quality, $size, $crop, $output);
	$this->fix($output, $job->chop, $input);
	
        return file_exists($output);
    }
    
    // I don't know if I can trust mplayer.
    // This is depreciated.
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
    
    private function getOutputDimensions($target, $crop, $resize)
    {   
        $s = array(
            'width' => 0,
            'height'  => 0,
        );
        
        $c = array(
            'top'    => 0,
            'bottom' => 0,
            'left'   => 0,
            'right'  => 0,
        );
        
        // start command line
        $h  = "HandBrakeCLI ";
        $h .= "-i $target "; //input
        $h .= "-o /dev/null "; //output

        $h .= "--start-at duration:0 ";
        $h .= "--stop-at duration:1 ";

        $h .= "2>&1";

        log_message('debug', $h);
        $o = shell_exec($h);

        $pattern = '|\+ size: (\d+)x(\d+),|';
        preg_match_all($pattern, $o, $matches);
        
        $s['width']  = intval($matches[1][0]);
        $s['height'] = intval($matches[2][0]);

        // Find aspect ratio
        $pattern = '|, aspect (\d+):(\d+),|';
        preg_match_all($pattern, $o, $matches);
        $aspectRatio = $matches[1][0] / $matches[2][0];
        
        
        // Adjust height or width if neccesary to match aspect ratio (scale up)
        $ourRatio = $s['width'] / $s['height'];
        $range = 0.1;
        if (($ourRatio < $aspectRatio - $range) || ($ourRatio > $aspectRatio + $range)) {
            // Check adding height
            $newHeight = $s['width'] / $aspectRatio;
            $newHeightArea = $s['width'] * $newHeight;
            // Check adding width
            $newWidth = $s['height'] * $aspectRatio;
            $newWidthArea = $s['height'] * $newWidth;
            // Apply best fit
            if ($newHeightArea > $newWidthArea) {
                $s['height'] = $newHeight;
            } else {
                $s['width'] = $newWidth;
            }
        }
        
        if ($crop) {
            $pattern = '|autocrop = (\d+)/(\d+)/(\d+)/(\d+)|';
            preg_match_all($pattern, $o, $matches);

            $c['top']    = intval($matches[1][0]);
            $c['bottom'] = intval($matches[2][0]);
            $c['left']   = intval($matches[3][0]);
            $c['right']  = intval($matches[4][0]);
        }

        $outSize = array();
        $outSize['height'] = $s['height'] - $c['top'] - $c['bottom'];
        $outSize['width']  = $s['width'] - $c['left'] - $c['right']; 

        // Default HD content to 1024 wide for iPads
        if ($resize && $outSize['width'] > 1024) {
	    $outSize['height'] = 1024/($outSize['width']/$outSize['height']);
	    $outSize['width'] = 1024;
	}
        
        // iPhone, iPad, etc can't handle higher than 1080p
        if ($outSize['height'] > 1080) {
            $outSize['width'] = 1080/($outSize['height']/$outSize['width']);
	    $outSize['height'] = 1080;
        }
        
        // Try to match standard HD sizes
        $range = 24;
        $h = 1080;
        $w = 1920;
        if (($outSize['height'] >= $h - $range) && ($outSize['height'] <= $h + $range) &&
            ($outSize['width'] >= $w - $range) && ($outSize['width'] <= $w + $range)) {
            log_message('debug', "Force 1080p Resolution");
	    $outSize['height'] = $h;
            $outSize['width'] = $w;
        }
        $range = 12;
        $h = 720;
        $w = 1280;
        if (($outSize['height'] >= $h - $range) && ($outSize['height'] <= $h + $range) &&
            ($outSize['width'] >= $w - $range) && ($outSize['width'] <= $w + $range)) {
            log_message('debug', "Force 720p Resolution");
	    $outSize['height'] = $h;
            $outSize['width'] = $w;
        }
        
        // Whole Integers
        $outSize['height'] = intval($outSize['height']);
        $outSize['width'] = intval($outSize['width']);
        
        return array(
            'size' => $outSize,
            'crop' => $c,
        );
    }
        
    
    private function getVideoQuality($height, $width)
    {
	// computers don't care if this is left unsimplified
	// it'll make it easier to edit later if neccesary.
	$w1 = 704;	//width
	$q1 = 23;	//quality
	$w2 = 1920;	//width
	$q2 = 28;	//quality
	
	// just using a linear equation
	$qOut = (($q2 - $q1) / ($w2 - $w1) * ($width - $w1)) + $q1;
        return round($qOut);
    }
 
    private function encode($target, $quality, $size, $crop, $output)
    {
        $addFilter = "";
	
	$ci =& get_instance();
	$vConfig = $ci->config->item('tivampyre');
	$workDir = $vConfig['working_directory'];

	// start command line
	$h  = "HandBrakeCLI ";
	$h .= "-i $target "; //input
	$h .= "-o $output "; //output
	
	// video encoding
	$h .= "-e x264 -x b-adapt=2:rc-lookahead=50 "; //Normal Encoding Preset
	$h .= "-q $quality ";	//constant quality
	$h .= "-r 29.97 ";	//framerate
	
	// audio encoding
	$h .= "-E faac ";	//codec
	$h .= "-B 320 ";	//bitrate
	$h .= "-6 stereo ";	//down sample to stereo
	$h .= "-D 1.0 ";	//dynamic volume compression

	// crop and resize
	$h .= "-w {$size['width']} ";	//width
        $h .= "-l {$size['height']} ";	//height
        $h .= "--crop {$crop['top']}:{$crop['bottom']}:{$crop['left']}:{$crop['right']} ";
	
	// filters		
	$h .= "--detelecine --decomb ";	// detelecine and decomb

	log_message('debug', $h);
        shell_exec($h);
	
        return file_exists($output);
    }
    
    private function fix($mp4, $chop, $mpeg)
    {
	$fileRoot = substr($mp4, 0, strrpos($mp4, '.'));
	$clean = $fileRoot . ".clean.mp4";
	
	if ($chop == 1) {
	    $this->chopCommercials($mp4, $mpeg, $fileRoot, $clean);
	} else {
	    $m  = "mencoder $mp4 ";	// input
	    $m .= "-oac faac -faacopts mpeg=4:object=2:raw:br=128 "; //encode audio
	    $m .= "-af volnorm=1 ";	// normalize audio volume
	    $m .= "-ovc copy ";		// copy video
	    $m .= "-of lavf ";		// force standard output
	    $m .= "-o $clean";		// output
	    
	    log_message('debug', $m);
	    shell_exec($m);
	}
	
	$cleanT1 = $fileRoot . ".clean_track1.h264";
	$cleanT2 = $fileRoot . ".clean_track2.aac";
	$remux = $fileRoot . ".remux.mp4";
	
	$t1 = "MP4Box -raw 1 $clean";
	log_message('debug', $t1);
	shell_exec($t1);	

	$t2 = "MP4Box -raw 2 $clean";
	log_message('debug', $t2);
	shell_exec($t2);

	$r = "MP4Box -new $remux -add $cleanT1 -add $cleanT2";
	log_message('debug', $r);
	shell_exec($r);

	// Remove or rename files
        try {
	    unlink($clean);
	    unlink($cleanT1);
	    unlink($cleanT2);
	    unlink($mp4);
	    rename($remux, $mp4);
	} catch(Exception $e) {
            //do nothing
        }
    }
    
    private function chopCommercials($mp4, $mpeg, $root, $clean)
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
	
        $edl = $root . ".edl";
	
	$m  = "mencoder $mp4 ";		// input
	$m .= "-edl $edl ";		// use commercials file
	$m .= "-oac faac -faacopts mpeg=4:object=2:raw:br=128 "; //encode audio
	$m .= "-af volnorm=1 ";		// normalize audio volume
	$m .= "-ovc copy ";		// copy video
	$m .= "-of lavf ";		// force standard output
	$m .= "-o $clean";		// output
	
	log_message('debug', $m);
	shell_exec($m);

	// Remove all the extra files hanging around
        try {
            unlink($edl);
	    unlink($root . ".log");
            unlink($root . ".logo.txt");
            unlink($root . ".txt");
        } catch(Exception $e) {
            //do nothing
        }
    }
}
