<?php
	/*
		php-essentials
		file: Session.php
		author: Daniel Hedlund <daniel@codescape.se>
		
		Session class simplifies user authentication by matching credentials stored in a MySQL database
	*/
	
	namespace Codescape\PHP\net;
	
	use Codescape\PHP\obj;
	use Codescape\PHP\util;
	
	class Session {
		private $id;
		private $idleMax;
		private $logger;
		private $loginTable;
		private $module;
		private $mysql;
		private $name;
		private $pwhash;
		private $uname;
		private $userProperties;
		private $logMsgTrailing;
		
		// Constructor
		public function __construct($name = null, $mysql = null, $loginTable = null, $logger = null, $idleMax = 0, $pwhash = true) {
			$this->module = explode("\\", get_class($this));
			$this->idleMax($idleMax);
			$this->logger($logger);
			$this->loginTable($loginTable);
			$this->mysql($mysql);
			$this->name($name);
			$this->pwhash($pwhash);
			$this->start();
		}
		
		// Authenticate user or check wheter user is authenticated on the session
		public function auth($uname = null, $pword = null) {
			$auth = false;
			
			// If user is specified, try to authenticate
			if (!empty($uname) && !empty($pword) && $this->mysql instanceof \Codescape\PHP\net\MySQL) {
				$query = $this->mysql->query("SELECT * FROM `".$this->loginTable->name()."` WHERE `".$this->loginTable->unameColumn()."` = '".@mysqli_real_escape_string($this->mysql->connection(), $uname)."';");
				
				// User matched in database
				if (!empty($query['records'])) {
					$userProperties = $query['records'][0];
					
					// Check password
					if ($this->pwhash && password_verify($pword, $userProperties[$this->loginTable->pwordColumn()]) || $pword == $userProperties[$this->loginTable->pwordColumn()]) $auth = true;
					
					if ($auth) {
						$this->uname = $_SESSION['uname'] = $uname;
						$this->userProperties = $_SESSION['userProperties'] = $userProperties;
						
						// Reset last request time so user won't be unauthenticated if idling before auth attempt
						$_SESSION['last_request'] = time();
					}
				}
			}
			
			// Or if user is authenticated
			else if (!empty($this->uname)) $auth = true;
			
			// Log action of this method
			if ($this->logger instanceof \Codescape\PHP\util\Logger) {
				if (!empty($uname)) {
					if ($auth) $this->logger->msg("'".$uname."' successfully authenticated on the session".$this->logMsgTrailing, LOGLEVEL_INFO, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
					else $this->logger->msg("'".$uname."' tried to authenticate but failed".$this->logMsgTrailing, LOGLEVEL_WARNING, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
				} else $this->logger->msg("Checked if user is authenticated on the session".$this->logMsgTrailing,  LOGLEVEL_DEBUG, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
			}
			
			return($auth);
		}
		
		// Return an user property, userProperties are specified as columns in the mysql table that stores the user credentials
		public function getUserProperty($property) { return((isset($this->userProperties[$property])) ? $this->userProperties[$property] : null); }
		
		// Check if user is idling and return wheter max idling time has exceeded (minutes)
		private function idleLimit() {
			$overLimit = false;
			$time = time();
			$last_request = $_SESSION['last_request'] = (isset($_SESSION['last_request'])) ? $_SESSION['last_request'] : $time;
			$idleTime = ($time - $last_request);
			
			if (!empty($this->idleMax) && $idleTime >= ($this->idleMax*60)) $overLimit = true;
			
			return(array("over_limit" => $overLimit, "idle_time" => ($idleTime/60)));
		}
		
		public function idleMax($minutes = null) { return($this->idleMax = (is_int($minutes)) ? $minutes : $this->idleMax); }
		public function logger($logger = null) { return($this->logger = ($logger instanceof \Codescape\PHP\util\Logger) ? $logger : $this->logger); }
		public function loginTable($loginTable = null) {  return($this->loginTable = ($loginTable instanceof \Codescape\PHP\obj\LoginTable) ? $loginTable : $this->loginTable); }
		public function mysql($mysql = null) { return($this->mysql = ($mysql instanceof \Codescape\PHP\net\MySQL) ? $mysql : $this->mysql); }
		
		public function name($name = null) {
			$this->name = (!empty($name)) ? $name : session_name();
			
			return(session_name($name));
		}
		
		public function pwhash($pwhash = null) { $this->pwhash = (!is_bool($pwhash)) ? $pwhash : $this->pwhash; }
		
		// Check if user is idling and return wheter max idling time has exceeded (minutes)
		public function requestLimit() {
			$overLimit = false;
			$time = time();
			$last_request = $_SESSION['last_request'] = (isset($_SESSION['last_request'])) ? $_SESSION['last_request'] : $time;
			$idleTime = ($time - $last_request);
			
			if (!empty($this->idleMax) && $idleTime >= ($this->idleMax*60)) $overLimit = true;
			
			return(array("over_limit" => $overLimit, "idle_time" => ($idleTime/60)));
		}
		
		public function start() {
			$start = session_start();
			$idle = $this->idleLimit();
			$this->id = (isset($_SESSION['id'])) ? $_SESSION['id'] : session_id();
			$this->uname = (isset($_SESSION['uname'])) ? $_SESSION['uname'] : null;
			$this->userProperties = (isset($_SESSION['userProperties'])) ? $_SESSION['userProperties'] : array();
			$this->logMsgTrailing = " | SID: ".$this->id.", Remote Address: ".$_SERVER['REMOTE_ADDR'];
			
			// Check if user has reached idle max, don't start session if true
			if (!empty($this->uname) && $idle['over_limit']) $start = false;
			
			// Log action of this method
			if ($this->logger instanceof \Codescape\PHP\util\Logger) {
				if ($start) {
					if (isset($_SESSION['uname'])) $this->logger->msg("Session resumed as '".$_SESSION['uname']."'".$this->logMsgTrailing, LOGLEVEL_NOTICE, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
					else if (isset($_SESSION['id'])) $this->logger->msg("Session resumed".$this->logMsgTrailing, LOGLEVEL_NOTICE, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
					else $this->logger->msg("New session".$this->logMsgTrailing, LOGLEVEL_NOTICE, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
				} else if (!empty($this->uname) && $idle['over_limit']) $this->logger->msg("Idle max reached on session for '".$this->uname."' (".$this->idleMax." min)".$this->logMsgTrailing, LOGLEVEL_NOTICE, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
				else $this->logger->msg("An unknown error occured when tried to start a new session".$this->logMsgTrailing, LOGLEVEL_ERROR, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
			}
			
			// If sesssion started, add it's id to global variable
			if ($start) $_SESSION['id'] = $this->id;
			
			// Check if user has reached idle max, stop session if true
			if (!empty($this->uname) && $idle['over_limit']) $this->stop();
			
			return($start);
		}
		
		public function stop() {
			// Reset cookie
			if (isset($_COOKIE[$this->name])) {
				$params = session_get_cookie_params();
				
				setcookie($this->name, '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
			}
			
			$destroy = session_destroy();
			
			// Log action of this method
			if ($this->logger instanceof \Codescape\PHP\util\Logger) {
				if ($destroy) {
					if (!empty($this->uname)) $this->logger->msg("'".$this->uname."' ended the session".$this->logMsgTrailing, LOGLEVEL_INFO, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
					else $this->logger->msg("Session was ended".$this->logMsgTrailing, LOGLEVEL_NOTICE, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
				} else $this->logger->msg("An error occured when tried to end the session".$this->logMsgTrailing, LOGLEVEL_ERROR, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
			}
			
			$this->id = null;
			$this->uname = null;
			$this->userProperties = array();
			
			return($destroy);
		}
	}
?>
