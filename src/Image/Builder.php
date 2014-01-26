<?php

namespace Image;

class Builder {

	private $google;
	private $sizes;

	function __construct($sizes, Google $google) {
		$this->sizes = $sizes;
		$this->google = $google;
	}

	function prepare($keywords) {
                // GD might throw warnings, and there's nothing I can do about it.
                // Suppress the warnings.
                $reportingLevel = error_reporting();
                error_reporting($reportingLevel ^ E_WARNING);
            
                for ($i = 0; $i < 4; $i++) {
                    $url = $this->google->getOneURL($keywords, $i);
                    $image = @imagecreatefromjpeg($url);
                    if ($image) {
                        break;
                    }
                }
                
                // Reenable previous error reporting leve..
                error_reporting($reportingLevel ^ E_WARNING);

		$width  = imagesx($image);
		$height = imagesy($image);

		if ($width > $height) {
			$smallestSide = $height;
		} else {
			$smallestSide = $width;
		}

		$thumb = imagecreatetruecolor($this->sizes['h'], $this->sizes['w']);
		imagecopyresampled($thumb, $image, 0, 0, 0, 0, $this->sizes['h'], $this->sizes['w'], $smallestSide, $smallestSide);
		return $thumb;
	}

	function getPNG($keywords) {
		$imageRef = $this->prepare($keywords);
		return imagepng($imageRef);
	}

	function getBase64($keywords) {
		$imageRef = $this->prepare($keywords);
		ob_start();
		imagepng($imageRef);
		$png = ob_get_clean();

		return base64_encode($png);
	}

}