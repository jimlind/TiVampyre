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

	function getBase64($keywords) {
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

		ob_start();
		imagepng($thumb);
		$png = ob_get_clean();

		return base64_encode($png);
	}

}