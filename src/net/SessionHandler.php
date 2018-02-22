<?php
	/*
		php-essentials
		file: SessionHandler.php
		author: Daniel Hedlund <daniel@codescape.se>
	*/
	
	namespace Codescape\PHP\net;

	class SessionHandler implements \SessionHandlerInterface {
		private $mysql;
		
		public function __construct($mysql = null, $loginTable = null) {
			$this->mysql = $mysql;
			$this->loginTable = $loginTable;
		}
		
		public function close() {
			return($this->mysql->close());
		}
		
		public function destroy($session_id) {
			$query = $this->mysql->query("DELETE FROM `sessions` WHERE `SID` = '".$session_id."'");
			
			return(($query['error']) ? true : false);
		}
		
		public function gc($maxlifetime) {
			
		}
		
		public function open($save_path, $session_name) {
			$open = false;
			
			if ($this->mysql->connection()) $open = true;
			
			return($open);
		}
		
		public function read($session_id) {
			$query = $this->mysql->query("SELECT `data` FROM `sessions` WHERE `SID` = '".$session_id."'");
			
			return((isset($query['records'][0]['data'])) ? $query['records'][0]['data'] : "");
		}
		
		public function write($session_id, $session_data) {
			$query['error'] = false;
		
			if (isset($_SESSION['userProperties'][$this->loginTable->IDColumn()])) {
				$query = $this->mysql->query("REPLACE INTO `sessions` (`SID`,`UID`,`data`,`access`,`remote_address`) VALUES ('".$session_id."','".$_SESSION['userProperties'][$this->loginTable->IDColumn()]."','".$session_data."','".time()."','".$_SERVER['REMOTE_ADDR']."')");
			} else {
				$query = $this->mysql->query("REPLACE INTO `sessions` (`SID`,`data`,`access`,`remote_address`) VALUES ('".$session_id."','".$session_data."','".time()."','".$_SERVER['REMOTE_ADDR']."')");
			}
			
			return(($query['error']) ? false : true);
		}
	}
?>
