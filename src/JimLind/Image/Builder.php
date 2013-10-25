<?php

namespace JimLind\Image;

class Builder {

	private $google;
	private $sizes;
	private $data;

	function __construct($sizes, Google $google) {
		$this->sizes = $sizes;
		$this->google = $google;
	}

	function prepare($keywords) {
		$url = $this->google->getOneURL($keywords);
		$image = imagecreatefromjpeg($url);

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