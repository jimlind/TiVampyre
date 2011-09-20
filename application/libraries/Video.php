<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Video {
    
    public function process($job, $input, $output)
    {
        $commercials = false;
        $crop = false;
        $size = $this->getSize($input);
        if ($job->full != 1) {
            $size = $this->getNewSize($size['height'], $size['width']);
        }
        if ($job->crop == 1) {
            $crop = $this->getCrop($input, $size);
        }
        $bits = $this->getVideoBitrate($size['height'], $size['width']);
        if ($job->chop == 1) {
            $this->detectCommercials($input);
        }
        $this->encode($input, $size, $bits, $job->chop, $crop, $output);
        if ($job->chop == 1) unlink($commercials);
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
    
    private function getCrop($target, $size)
    {
        $h = $size['height']-2;
        $w = $size['width'];
        // My source files occasionally have 2 pixels of crap on the top
        $s  = "mplayer $target ";
	$s .= "-vf crop=$w:$h:0:2,cropdetect -ss 300 -frames 10 -vo null -ao null ";
	$s .= "2>/dev/null | grep 'CROP' | tail -1";
        $o = shell_exec($s);
        $pattern = '/crop=([0-9:]*)/';
        preg_match_all($pattern, $o, $matches);
        return $matches[0][0];
    }
    
    private function getNewSize($height, $width)
    {
        $h = ($height / $width) * 1024;
        $w = 1024;
        if ($h > $height || $w > $width) {
            $h = $height;
            $w = $width;
        }
        return array('width'=>$w, 'height'=>$h);
    }
    
    private function getVideoBitrate($height, $width)
    {
        $square = $height * $width;
        $bitrate = (0.0013 * $square) + 410;
        return round($bitrate);
    }
    
    private function detectCommercials($target)
    {
	$ci =& get_instance();
	$vConfig = $ci->config->item('tivampyre');
	$path = $vConfig['comskip_path'];
        $exePath = $path . 'comskip.exe';
        $iniPath = $path . 'comskip.ini';
        $c  = "wine $exePath ";
        $c .= "--ini=$iniPath ";
        $c .= "$target";
	shell_exec($c);
	
        $pos = strrpos($target, '.');
        $fileRoot = substr($target, 0, $pos);
        $edl = $fileRoot . ".edl";
	
	// Parse the EDL file created by comskip
	$lines = file($edl);
	$chapters = array();
	foreach ($lines as $index => $line) {
	    $values = explode("\t", $line); // Double quotes neccesary for escaping
	    $a = floatval($values[0]);
	    $b = floatval($values[1]);
	    if ($index == 0) { // First line
		$chapters[] = array('start' => 0,  'duration' => $a);
	    }
	    if ($index+1 < count($lines)) { // Something is next
		$nextLine = $lines[$index+1];
		$n = floatval(substr($nextLine, 0, strpos($nextLine, "\t")));
		$chapters[] = array('start' => $b, 'duration' => $n-$b);
	    } else { // Nothing next
		$chapters[] = array('start' => $b);
	    }
	}
	
	// Chop up the original MPEG
	$catPieces = "cat "; 
	$workingDir = $vConfig['working_directory'];
	foreach ($chapters as $index=>$chapter){
	    if ($chapter['duration'] == 0) continue; // Don't worry about zero length files.
	    
	    $chapterFile = "{$workingDir}chapter{$index}.mpeg";
	    
	    $f  = "ffmpeg ";
	    $f .= "-ss {$chapter['start']} ";
	    $f .= "-i $target ";
	    if (isset($chapter['duration'])) {
		$f .= "-t {$chapter['duration']} ";
	    }
	    $f .= "-vcodec copy "; // copy video exactly
	    $f .= "-acodec copy "; // copy audio exactly
	    $f .= "-y $chapterFile";
	    log_message('debug', $f);
	    shell_exec($f);
	    
	    $catPieces .= "$chapterFile ";
	}
	$catPieces .= " > {$workingDir}cleaned.mpeg; rm {$workingDir}chapter*.mpeg;";
	log_message('debug', $catPieces);
	shell_exec($catPieces);

	// Remove all the extra files hanging around
        try {
            unlink($edl);
	    unlink($fileRoot . ".log");
            unlink($fileRoot . ".logo.txt");
            unlink($fileRoot . ".txt");
	    unlink($target);
	    rename("{$workingDir}cleaned.mpeg", $target);
        } catch(Exception $e) {
            //do nothing
        }
    }
    
    private function encode($target, $size, $bits, $chop, $crop, $output)
    {
        $addFilter = "";
        $height = $size['height'];
        $width = $size['width'];
	
	$ci =& get_instance();
	$vConfig = $ci->config->item('tivampyre');
	$workDir = $vConfig['working_directory'];

	// start command line
	$f  = "ffmpeg ";		// http://ffmpeg.org/
	$f .= "-i $target ";		// input file
	
	// video encoding and compression
	$f .= "-vcodec libx264 ";	// x264 video codec
	$f .= "-flags2 +bpyramid+mixed_refs+wpred+dct8x8+fastpskip "; // flags for compression algorithm
	$f .= "-refs 1 ";		// p-frame reference (default is 3)
	$f .= "-aq_mode 0 ";		// disable adaptive quantization (enabled by default)
	$f .= "-qcomp 0.6 ";
	$f .= "-qmin 10 ";
	$f .= "-qmax 51 ";
	$f .= "-qdiff 4 ";
	$f .= "-subq 2 ";		// subpixel motion esimation (default is 7, less than 2 is not recommended)
	$f .= "-trellis 1 ";		// trellis quantization (default)	
	$f .= "-bf 0 ";
	$f .= "-cmp +chroma ";		// included in all presets
	
	// video filtering
	$f .= "-vf yadif=0 ";		// deinterlace video with yadif filter
	$f .= "-s {$width}x{$height} ";	// force resize (some commercials change size)
	$f .= "-r 30000/1001 ";		// force NTSC framerate (29.97)
	
	// video settings
	$f .= "-vcodec libx264 ";	// x264 video codec
	$f .= "-flags2 +bpyramid+mixed_refs+wpred+dct8x8+fastpskip "; // flags for x264
	$f .= "-refs 1 ";		// p-frame reference (default is 3)
	$f .= "-aq_mode 0 ";		// disable adaptive quantization (enabled by default)
	$f .= "-partitions +parti8x8+parti4x4+partp8x8+partb8x8 "; // enable all worthwhile partitions (default)	
	$f .= "-me_method dia ";	// diamond motion estimation (fastest estimator)
	$f .= "-qcomp 0.6 ";
	$f .= "-qmin 10 ";
	$f .= "-qmax 51 ";
	$f .= "-qdiff 4 ";	
	$f .= "-b {$bits}k ";		// video bitrate
	
	// audio settings
	$f .= "-acodec libfaac ";	// use AAC audio codec
	$f .= "-ab 128k ";		// audio bitrate
	$f .= "-ac 2 ";			// stereo

	
	// global options
	$f .= "-loglevel quiet ";	// we don't actually need any status message output
	$f .= "-threads 0 ";		// multiple core and multiple processor support
	
	// audio sync
	if ($job->crop == 1) {
	    $f .= "-async 4800 ";		// keeps audio synced with video
	    $f .= "-dts_delta_threshold 1 ";	// also theoretically helps keep sync
	} else {
	    $f .= "-copyts ";			// copy time stamps
	    $f .= "-ss 00:00:02 ";		// skip first 2 seconds, properly seeking
	}
	
	// output options
	$f .= "-f mp4 ";		// MP4 output format
	$f .= "-y $output";		// overwrite existing files
	
	// TODO: support for cropping
	// crop=in_w-100:in_h-100:100:100
	// crop=[desired width]:[desired height]:[x coordinate]:[y coordinate]
	
	log_message('debug', $f);
        shell_exec($f);
	
        return file_exists($output);
    }
}
