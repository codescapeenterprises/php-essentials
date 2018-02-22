<?php
	/*
		php-essentials
		file: MySQL.php
		author: Daniel Hedlund <daniel@codescape.se>
		
		MySQL class wraps arround the php mysqli extension, it's purpose is to simplify the usage of database related actions
	*/
	
	namespace Codescape\PHP\net;
	
	use Codescape\PHP\util;
	
	class MySQL {
		private $AFR;
		private $charset;
		private $connection;
		private $db;
		private $errors;
		private $logger;
		private $queries;
		private $module;
		private $timestamp;
		
		public function __construct($host = null, $uname = null, $pword = null, $db = null, $timestamp = null, $port = 3306, $logger = null, $AFR = false, $charset = "utf8", $persistent = false) {
			$this->module = explode("\\", get_class($this));
			$this->autoFreeResources($AFR);
			$this->logger($logger);
			$this->connect($host, $uname, $pword, $db, $port, $charset, $persistent);
			$this->errors = array();
			$this->queries = array();
			$this->timestamp($timestamp);
		}
		
		public function autoFreeResources($AFR = null) { return($this->AFR = (is_bool($AFR)) ? $AFR : $this->AFR); }
		
		public function charset($charset = null, $link = null) {
			$link = (is_resource($link)) ? $link : $this->connection();
			$success = false;
			
			if (@mysqli_ping($link)) $success = mysqli_set_charset($link, $charset);
			if ($success && $link == $this->connection()) $this->charset = $charset;
			
			return((empty($charset)) ? $this->charset : $success);
		}
		
		public function close($link = null) {
			$link = (is_resource($link)) ? $link : $this->connection();
			$success = false;
			
			if (@mysqli_ping($link)) $success = mysqli_close($link);
			if ($link == $this->connection()) $this->connection = false;
			
			return($success);
		}
		
		public function connect($host, $uname = null, $pword = null, $db = null, $port = 3306, $charset = "utf8", $persistent = false) {
			$host = ($persistent) ? "p:".$host : $host;
			
			if ($this->connection = @mysqli_connect($host, $uname, $pword, $db, $port)) {
				$this->charset($charset);
				$this->db($db);
			}
			
			// Log action of this method
			if ($this->logger instanceof \Codescape\PHP\util\Logger) {
				if (!$this->connection()) $this->logger->msg("Unable to connect to MySQL server | Host: ".$host.", User: ".$uname.", DB: ".$db, LOGLEVEL_ERROR, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
				else $this->logger->msg("Connected to MySQL server | Host: ".$host.", User: ".$uname.", DB: ".$db, LOGLEVEL_DEBUG, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
			}
			
			return($this->connection());
		}
		
		public function connection($link = null) { return($this->connection = (is_resource($link) && @mysqli_ping($link)) ? $link : $this->connection); }
		
		public function db($db = null, $link = null) {
			$link = (is_resource($link)) ? $link : $this->connection();
			$success = false;
			
			if (@mysqli_ping($link) && is_string($db)) $success = mysqli_select_db($link, $db);
			if ($success && $link == $this->connection()) $this->db = $db;
			
			return((empty($db)) ? $this->db : $success);
		}
		
		public function getErrors($lastError = false) { return(($lastError) ? end($this->errors) : $this->errors); }
		public function getQueries($lastQuery = false) { return(($lastQuery) ? end($this->queries) : $this->queries); }
		public function logger($logger = null) { return($this->logger = ($logger instanceof \Codescape\PHP\util\Logger) ? $logger : $this->logger); }
		
		// Run query on MySQL connection
		public function query($query, $freeResource = false, $link = null) {
			$link = (is_resource($link)) ? $link : $this->connection();
			$array = array('query' => $query, 'resource' => false, 'records' => array(), 'error' => false);
			
			if (@mysqli_ping($link)) {
				$this->db($this->db());
				
				if ($array['resource'] = mysqli_query($link, $query)) {
					if ($array['resource'] instanceof \mysqli_result) while ($row = mysqli_fetch_array($array['resource'])) $array['records'][] = $row;
					else if ($array['resource'] && ($this->timestamp instanceof \Codescape\PHP\util\Timestamp)) $this->timestamp->touch();
					if ($freeResource || $this->AFR) mysqli_free_result($array['resource']);
					
					$this->queries[] = $query;
				} else $this->errors[] = $array['error'] = mysqli_error($link);
			} else $this->errors[] = $array['error'] = "No valid MySQL connection when tried to run query";
			
			// Log action of this method
			if ($this->logger instanceof \Codescape\PHP\util\Logger) {
				$this->logger->msg("Query: ".$query, LOGLEVEL_DEBUG, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
				
				if (!empty($array['error'])) $this->logger->msg($array['error'], LOGLEVEL_ERROR, $_SERVER['SCRIPT_NAME'], strtoupper(end($this->module)));
			}
			
			return($array);
		}
		
		public function timestamp($timestamp = null) { return($this->timestamp = ($timestamp instanceof \Codescape\PHP\util\Timestamp) ? $timestamp : $this->timestamp); }
	}
?>
