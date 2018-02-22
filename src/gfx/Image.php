<?php
	/*
		php-essentials
		file: Image.php
		author: Daniel Hedlund <daniel@codescape.se>
	*/
	
	namespace Codescape\PHP\gfx;
	
	class Image {
		private $data;
		private $type;
		private $x;
		private $y;
		
		public function __construct($file, $output = false, $setHeader = true, $destroy = true) {
			if ($this->type = @exif_imagetype($file)) {
				switch ($this->type) {
					case IMAGETYPE_GIF: $this->data = $file = imagecreatefromgif($file); break;
					case IMAGETYPE_JPEG: $this->data = $file = imagecreatefromjpeg($file); break;
					case IMAGETYPE_PNG: $this->data = $file = imagecreatefrompng($file); break;
				}
			}
			
			$this->x = (is_resource($file)) ? $this->getX($file) : false;
			$this->y = (is_resource($file)) ? $this->getY($file) : false;
			
			if ($output) $this->output($setHeader, $destroy);
		}
		
		public function boundaries($x = null, $y = null, $keepAspectRatio = true, $image = null) {
			$image = (is_resource($image)) ? $image : $this->data;
			
			if (is_int($x) && $this->getX($image) > $x) $image = $this->resize($x, null, $keepAspectRatio, $image);
			if (is_int($y) && $this->getY($image) > $y) $image = $this->resize(null, $y, $keepAspectRatio, $image);
			
			return($image);
		}
		
		public function data($data = null) { return($this->data = (is_resource($data)) ? $data : $this->data); }
		public function destroy($image = null) { (is_resource($image)) ? imagedestroy($image) : imagedestroy($this->data); }
		public function getX($image = null) { return((is_resource($image)) ? imagesx($image) : $this->x); }
		public function getY($image = null) { return((is_resource($image)) ? imagesy($image) : $this->y); }
		
		public function output($file = null, $setHeader = true, $destroy = true) {
			if (empty($file) && $setHeader && !headers_sent()) header("Content-Type: ".image_type_to_mime_type($this->type));
			if (!empty($file)) $file = (strrpos($file, '.')) ? $file : $file.image_type_to_extension($this->type);
			
			switch ($this->type) {
				case IMAGETYPE_GIF: imagegif($this->data, $file); break;
				case IMAGETYPE_JPEG: imagejpeg($this->data, $file); break;
				case IMAGETYPE_PNG: imagepng($this->data, $file); break;
			}
			
			if ($destroy) $this->destroy();
		}
		
		public function resize($x = null, $y = null, $keepAspectRatio = true, $image = null) {
			$image = (is_resource($image)) ? $image : $this->data;
			$imageX = $this->getX($image);
			$imageY = $this->getY($image);
			$xChange = ($keepAspectRatio && is_int($y)) ? ($imageX * ($y/$imageY)) : $imageX;
			$yChange = ($keepAspectRatio && is_int($x)) ? ($imageY * ($x/$imageX)) : $imageY;
			$x = (is_int($x)) ? $x : $xChange;
			$y = (is_int($y)) ? $y : $yChange;
			$tmpim = imagecreatetruecolor($x, $y);
			
			imagealphablending($tmpim, false);
			imagesavealpha($tmpim, true);
			imagecolorallocatealpha($tmpim, 255, 255, 255, 127);
			imagecopyresampled($tmpim, $image, 0, 0, 0, 0, $x, $y, $imageX, $imageY);
			
			if ($image == $this->data) {
				$this->data($tmpim);
				$this->x = $x;
				$this->y = $y;
			}
			
			return($tmpim);
		}
		
		public function type($type = null) { return($this->type = (is_int($type)) ? $type : $this->type); }
	}
?>
