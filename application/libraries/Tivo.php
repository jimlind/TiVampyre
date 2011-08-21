<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Tivo {
    public $ip = false;
    
    // Find the TiVo and return the IP
    public function locate()
    {
        $output = shell_exec("avahi-browse -l -r -t _tivo-videos._tcp");
        if($output == null) return false;
        $pattern = '/address = \[([0-9.]*)]/';
        preg_match($pattern, $output, $matches);
        if (sizeof($matches) > 1) {
            return $this->ip = $matches[1];
        } else {
            return false;
        }
    }
    
    // Retrieve a chunk of the Now Playing XML list
    public function getXml($anchor = 0)
    {
        if ($this->ip == false) return false;
        $vConfig = $this->config->item('tivampyre');
	
	$url = "https://".$this->ip.":443/".
                   "TiVoConnect?Command=QueryContainer&".
                   "Container=%2FNowPlaying".
                   "&Recurse=Yes".
                   "&AnchorOffset=$anchor";
	$fetchScript = "curl -s '$url' -k --digest -u tivo:" . $vConfig['mak'];
        $output = shell_exec($fetchScript);
        if($output == null) return false;
        $xml = simplexml_load_string($output);
	return $xml;
    }
    
    // Download a specific file from the TiVo
    public function downloadFile($url, $path)
    {
	$vConfig = $this->config->item('tivampyre');
	$mak = $vConfig['mak'];
	
	$c  = "curl '$url' "; //source
	$c .= "--digest -k "; //tivo needs these??
	$c .= "-u tivo:$mak "; //username and password
	$c .= "-c /tmp/cookies.txt "; //storing cookies is necessary, we just don't want them.
	$c .= "--retry 12 --retry-delay 10 "; //help retry??
	$c .= "-o $path"; //output
	log_message('debug', $c);
	shell_exec($c);
    }
    
    public function decodeFile($input, $output)
    {
	$vConfig = $this->config->item('tivampyre');
	$mak = $vConfig['mak'];
	
	$d  = "tivodecode $input ";
	$d .= "-m $mak ";
	$d .= "-o $output ";
	log_message('debug', $d);
	shell_exec($d);
	return file_exists($output);
    }
}