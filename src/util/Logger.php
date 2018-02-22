<?php
	/*
		php-essentials
		file: Logger.php
		author: Daniel Hedlund <daniel@codescape.se>
		
		The logger class is used to log messages to any text file or mysql connection, it's used within the php-essentials library
	*/
	
	namespace Codescape\PHP\util;
	
	use Codescape\PHP\net;
	
	// Loglevels, used to specify loglevel on individual messages and the loglevel on any instance of this class
	// Ex. If you set the loglevel to LOGLEVEL_DEBUG on an instance of this class, all messages that has an equal or higher loglevel will be logged by that instance
	
	define("LOGLEVEL_DEBUG", 0);
	define("LOGLEVEL_NOTICE", 1);
	define("LOGLEVEL_INFO", 2);
	define("LOGLEVEL_WARNING", 3);
	define("LOGLEVEL_ERROR", 4);
	
	class Logger {
		private $fields = array("time", "script", "module", "level", "message");
		private $loglevels = array("DEBUG" => LOGLEVEL_DEBUG, "INFO" => LOGLEVEL_INFO, "NOTICE" => LOGLEVEL_NOTICE, "WARNING" => LOGLEVEL_WARNING, "ERROR" => LOGLEVEL_ERROR);
		private $level;
		private $log_db;
		private $mysqlTable;
		private $output;
		
		// Constructor, you can set output as a textfile or mysql connection
		// Specify mysql table if mysql connection is used
		public function __construct($output = null, $level = LOGLEVEL_INFO, $mysqlTable = null) {
			$this->output($output);
			$this->level($level);
			//$this->log_db($log_db);
			$this->mysqlTable($mysqlTable);
		}
		
		// Takes a value and checks wheter it exists in the $loglevels array
		private function isLogLevel($level) { return(in_array($level, $this->loglevels, true)); }
		
		// Set or return the loglevel
		public function level($level = null) { return($this->level = ($this->isLogLevel($level)) ? $level : $this->level); }
		
		// Set or return the loglevel
		//public function log_db($log_db = null) { return($this->log_db = (is_string($log_db)) ? $log_db : $this->log_db); }
		
		// Print message to log output
		public function msg($msg, $level = null, $script = null, $module = "SYSTEM") {
			$level = ($this->isLogLevel($level)) ? $level : $this->level();
			//$module = strtoupper($module);
			
			if ($level >= $this->level()) {
				$level = ($l = array_search($level, $this->loglevels)) ? $l : key($this->loglevels);
				
				if (($this->output instanceof \Codescape\PHP\net\MySQL) && $this->output->connection() && is_string($this->mysqlTable)) $this->msgMysqlTable($msg, $level, $script, $module);
				else $this->msgFile($msg, $level, $script, $module);
			}
		}
		
		// Print message to file
		private function msgFile($msg, $level, $script, $module) {
			$filename = (is_string($this->output)) ? $this->output : strtoupper(date("MY")).".log";
			$filename = (defined("WEBASSETS_LOG_DIR")) ? $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.WEBASSETS_LOG_DIR.DIRECTORY_SEPARATOR.$filename : $filename;
			$fields = null;
			$header = null;
			
			for ($i = 1; $i <= count($this->fields); $i++) $fields .= ($i != count($this->fields)) ? $this->fields[($i - 1)]."," : $this->fields[($i - 1)]."\r\n";
			
			if ($file = @fopen($filename, "r+")) $header = fgets($file);
			else {
				file_put_contents($filename, $fields);
				
				if ($file = @fopen($filename, "r+")) $header = fgets($file);
			}
			
			if ($header == $fields) {
				fseek($file, 0, SEEK_END);
				fwrite($file, date("Y-m-d H:i:s")." [".$script.":".$module."]-".$level."-> ".$msg."\r\n");
			}
			
			@fclose($file);
		}
		
		// Print message to mysql table
		private function msgMysqlTable($msg, $level, $script, $module) {
			$msg = mysqli_real_escape_string($this->output->connection(), $msg);
			//$last_db = $this->output->db();
			$fields = null;
			$values = null;
			
			for ($i = 1; $i <= count($this->fields); $i++) {
				$field = $this->fields[($i - 1)];
				
				if ($field != "time") {
					$comma = ($i != count($this->fields)) ? ',' : null;
					
					switch ($field) {
						case $this->fields[1]: $values .= "'".$script."'".$comma; break;
						case $this->fields[2]: $values .= "'".$module."'".$comma; break;
						case $this->fields[3]: $values .= "'".$level."'".$comma; break;
						case $this->fields[4]: $values .= "'".$msg."'".$comma; break;
					}
					
					$fields .= '`'.$field.'`'.$comma;
				}
			}
			
			//if (!empty($this->log_db)) $this->output->db($this->log_db);
			if (!empty($fields)) $this->output->query("INSERT INTO `".$this->mysqlTable."` (".$fields.") VALUES (".$values.");", false);
			//if (!empty($this->log_db)) $this->output->db($last_db);
		}
		
		// Will set or return the mysql table, creates the table and it's structure if no table exists
		public function mysqlTable($mysqlTable = null) {
			$mysqlTable = (is_string($mysqlTable)) ? $mysqlTable : strtoupper(date("MY"));
			
			if (($this->output instanceof \Codescape\PHP\net\MySQL) && $this->output->connection()) {
				//$last_db = $this->output->db();
				
				//if (!empty($this->log_db)) $this->output->db($this->log_db);
				
				$query = $this->output->query("SHOW TABLES;");
				$array = array();
				
				// Collect all tables in the database
				foreach ($query['records'] as $record) $array[] = $record['Tables_in_'.$this->output->db()];
				
				// If table exists, check it's structure
				if (in_array($mysqlTable, $array)) {
					$query = $this->output->query("SHOW COLUMNS FROM `".$mysqlTable."`;");
					$array = array();
					
					// Collect all columns in the table
					foreach ($query['records'] as $record) if ($record['Field'] != "id") $array[] = $record['Field'];
					
					// Set mysql table as false if there where no table match, or as specified
					$this->mysqlTable = ($array == $this->fields) ? $mysqlTable : false;
				}
				
				// If no table exists, create a new one
				else {
					$set = array_keys($this->loglevels);
					$fields = null;
					$levels = null;
					
					// Build the SQL structure for 'levels' field
					for ($i = 1; $i <= count($set); $i++) $levels .= ($i != count($set)) ? "'".$set[($i - 1)]."'," : "'".$set[($i - 1)]."'";
					
					// Build the SQL structure for the columns
					foreach ($this->fields as $field) {
						switch ($field) {
							case $this->fields[0]: $fields .= "`".$field."` timestamp null DEFAULT CURRENT_TIMESTAMP,"; break;
							case $this->fields[1]: $fields .= "`".$field."` varchar(255),"; break;
							case $this->fields[2]: $fields .= "`".$field."` varchar(30) DEFAULT 'SYSTEM',"; break;
							case $this->fields[3]: $fields .= "`".$field."` enum(".$levels.") DEFAULT '".reset($set)."',"; break;
							case $this->fields[4]: $fields .= "`".$field."` text,"; break;
						}
					}
					
					// Run the query to create the table
					$query = $this->output->query("CREATE TABLE `".$mysqlTable."` (`id` int(11) NOT null AUTO_INCREMENT,".$fields."PRIMARY KEY (`id`)) Engine=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
					$this->mysqlTable = (!$query['error'])  ? $mysqlTable : false;
				}
				
				//if (!empty($this->log_db)) $this->output->db($last_db);
			}
			
			
			return($this->mysqlTable);
		}
		
		public function output($output = null) { return($this->output = (!empty($output)) ? $output : $this->output); }
	}
?>
