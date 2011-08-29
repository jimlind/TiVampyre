<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Video {
    
    public function process($job, $input, $output)
    {
        $commercials = false;
        $crop = false;
        $size = $this->getSize($input);
        if ($job->full == 1) {
            $size = $this->getNewSize($size['height'], $size['width']);
        }
        if ($job->crop == 1) {
            $crop = $this->getCrop($input, $size);
        }
        $bits = $this->getVideoBitrate($size['height'], $size['width']);
        if ($job->chop == 1) {
            $commercials = $this->detectCommercials($input);
        }
        $this->encode($input, $size, $bits, $commercials, $crop, $output);
        if ($job->chop == 1) unlink($commercials);
        return file_exists($output);
    }
    
    private function getSize($target)
    {
	$s  = "mplayer $target ";
	$s .= "-identify -frames 0 -vc null -vo null -ao null ";
	$s .= "2>/dev/null | grep 'ID_VIDEO_WIDTH\|ID_VIDEO_HEIGHT'";
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
	$s .= "-vf crop=$w:$h:0:2,cropdetect -ss 1000 -frames 10 -vo null -ao null ";
	$s .= "2>/dev/null | grep 'CROP' | tail -1";
        $o = shell_exec($s);
        $pattern = '/crop=([0-9:]*)/';
        preg_match_all($pattern, $o, $matches);
        return $matches[0][0];
    }
    
    private function getNewSize($height, $width)
    {
        $h = ($width / $height) * 1024;
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
        try {
            unlink($fileRoot . ".log");
            unlink($fileRoot . ".logo.txt");
            unlink($fileRoot . ".txt");
        } catch(Exception $e) {
            //do nothing
        }
        // return the edl file if it exists
        if (file_exists($edl)){
            return $edl;
        } else {
            return false;
        }
    }
    
    private function encode($target, $size, $bits, $commercials, $crop, $output)
    {
        $addFilter = "";
        $height = $size['height'];
        $width = $size['width'];
	
	$ci =& get_instance();
	$vConfig = $ci->config->item('tivampyre');
	$workDir = $vConfig['working_directory'];
	
        $p  = "mencoder $target -o /dev/null ";
        $p .= "-ss 00:00:02 ";
	$p .= "-ovc x264 ";
        $p .= "-x264encopts pass=1:global_header:level_idc=30:bitrate=$bits:bframes=0:qcomp=0.8:me=dia:subq=1:frameref=1:threads=auto ";
        if ($crop !== false) $addFilter = ",".$crop;
        $p .= "-vf pp=md,scale=$width:$height,harddup$addFilter ";
        $p .= "-nosound ";
        $p .= "-ofps 30000/1001 ";
        $p .= "-passlogfile {$workDir}pass.log ";
        log_message('debug', $p);
        shell_exec($p);
        
        $m  = "mencoder $target -o $output ";
	$m .= "-ss 00:00:02 ";
	$m .= "-ovc x264 ";
        $m .= "-x264encopts pass=2:global_header:level_idc=30:bitrate=$bits:bframes=0:qcomp=0.8:me=dia:subq=1:frameref=1:threads=auto ";
        if ($crop !== false) $addFilter = ",".$crop;
        $m .= "-vf pp=md,scale=$width:$height,harddup$addFilter ";
        $m .= "-oac faac ";
	$m .= "-faacopts mpeg=4:object=2:raw:br=128 ";
	$m .= "-af volnorm=1 ";
        $m .= "-ofps 30000/1001 ";
        $m .= "-passlogfile {$workDir}pass.log ";
        $m .= "-of lavf ";
	$m .= "-lavfopts format=mp4 ";
        if ($commercials) {
            $m .= "-edl $commercials ";
        }
	log_message('debug', $p);
        shell_exec($m);
	
        return file_exists($output);
    }
}
