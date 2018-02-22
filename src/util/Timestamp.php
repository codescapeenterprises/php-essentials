<?php
	/*
		php-essentials
		file: Timestamp.php
		author: Daniel Hedlund <daniel@codescape.se>
	*/
	
	namespace Codescape\PHP\util;
	
	class Timestamp {
		private $file;
		
		public function __construct($file) { $this->file = $file; }
		public function file($file = null) {return($this->file = (is_string($file)) ? $file : $this->file); }
		public function time() { return(($t = @file_get_contents($this->file)) ? $t : null); }
		public function touch($time = null) {
			$time = (!empty($time)) ? $time : time();
			
			@file_put_contents($this->file, $time);
			return($time);
		}
	}
?>
