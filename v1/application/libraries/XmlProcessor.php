<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class XmlProcessor {
    private $xml = false;

    public function parseTivoXML($xml)
    {
        $this->xml = $xml;
        $data = array();
        // This data helps us to know how many total files are available.
        if (isset($xml->Details->TotalItems)){
            $data['totalItems'] = (int) $xml->Details->TotalItems;
        }
        if (isset($xml->ItemCount)){
            $data['itemCount'] = (int) $xml->ItemCount;
        }

        // Loop through the items in the TiVo xml
        if (!isset($xml->Item)) return false;
        foreach($xml->Item as $child){
            $show = array();
            $detailsUrl = (string) $child->Links->TiVoVideoDetails->Url;
	    $show['id'] = (int) substr($detailsUrl, strpos($detailsUrl, "?id=")+4);
            
            $details = $child->Details;
	    $show['title'] = (string) $details->Title;
	    $show['duration'] = (string) $details->Duration;
            $show['date'] = date('Y-m-d H:i:s', hexdec((string) $details->CaptureDate));
            $show['description'] = (string) $details->Description;
            $show['channel'] = (string) $details->SourceChannel;
            $show['station'] = (string) $details->SourceStation;
            $show['hd'] = (string) $details->HighDefinition;
            $show['episodeTitle'] = (string) $details->EpisodeTitle;
            $show['episodeNumber'] = (string) $details->EpisodeNumber;
            
            $content = $child->Links->Content;
            $show['url'] = (string) $content->Url;
            
            $data['shows'][] = $show;
        }
        return $data;
    }

}