<?php
	/*
		php-essentials
		file: Toolbox.php
		author: Daniel Hedlund <daniel@codescape.se>
	*/
	
	namespace Codescape\PHP\util;
	
	class Toolbox {
		public static function randBool() { return((mt_rand(0, 1) == 1) ? true : false); }
		
		public static function randChr($letter = true, $capital = false) {
			$chr = null;
			
			if ($letter) $chr = ($capital) ? chr(mt_rand(65, 90)) : chr(mt_rand(97, 122));
			else $chr = mt_rand(0, 9);
			
			return($chr);
		}
		
		public static function randStr($length = 6, $numbers = true, $letters = true, $capitals = true, $capitalsOnly = false) {
			$str = null;
			
			for ($i = 0; $i < $length; $i++) {
				if ($numbers) {
					if ($letters) {
						if ($capitals) {
							if ($capitalsOnly) $str .= self::randChr(self::randBool(), true);
							else $str .= self::randChr(self::randBool(), self::randBool());
						} else $str .= self::randChr(self::randBool());
					} else $str .= self::randChr(false);
				} else if ($letters) {
					if ($numbers) {
						if ($capitals) {
							if ($capitalsOnly) $str .= self::randChr(self::randBool(), true);
							else $str .= self::randChr(self::randBool(), self::randBool());
						} else $str .= self::randChr(false);
					} else {
						if ($capitals) {
							if ($capitalsOnly) $str .= self::randChr(true, true);
							else $str .= self::randChr(true, self::randBool());
						} else $str .= self::randChr(true);
					}
				}
			}
			
			return($str);
		}
		
		public static function reload($page = '.') {
			header("Location: ".$page);
			exit();
		}
		
		public static function urlVars($url, $vars, $del = false) {
			parse_str((isset($url['query'])) ? $url['query'] : null, $url_query);
			
			var_dump($url_query);
			
			$url = parse_url($url);
			$query = (isset($url['query']) && !$del) ? array_merge($url_query, $vars) : $url_query;
			$new_url = null;
			
			if ($del) foreach ($vars as $var) unset($query[$var]);
			
			foreach ($url as $element => $value) {
				switch ($element) {
					case "scheme": $url[$element] = $value."://"; break;
					case "user": $url[$element] = (isset($url['pass'])) ? $value : $value.'@'; break;
					case "pass": $url[$element] = ':'.$value.'@'; break;
					case "query": $url[$element] = '?'.http_build_query($query); break;
					case "fragment": $url[$element] = '#'.$value; break;
				}
				
				$new_url .= $url[$element];
			}
			
			return($new_url);
		}
	}
?>
