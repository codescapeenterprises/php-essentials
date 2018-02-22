<?php
	/*
		php-essentials
		file: LoginTable.php
		author: Daniel Hedlund <daniel@codescape.se>
		
		LoginTable defines columns in the table which represent ex. user accounts
	*/
	
	namespace Codescape\PHP\obj;
	
	class LoginTable {
		private $name;
		private $IDColumn;
		private $unameColumn;
		private $pwordColumn;
		
		public function __construct($name, $unameColumn, $pwordColumn, $IDColumn = "UID") {
			$this->name($name);
			$this->unameColumn($unameColumn);
			$this->pwordColumn($pwordColumn);
			$this->IDColumn($IDColumn);
		}
		
		public function IDColumn($IDColumn = null) { return($this->IDColumn = (empty($IDColumn)) ? $this->IDColumn : $IDColumn); }
		public function name($name = null) { return($this->name = (empty($name)) ? $this->name : $name); }
		public function pwordColumn($pwordColumn = null) { return($this->pwordColumn = (empty($pwordColumn)) ? $this->pwordColumn : $pwordColumn); }
		public function unameColumn($unameColumn = null) { return($this->unameColumn = (empty($unameColumn)) ? $this->unameColumn : $unameColumn); }
	}
?>
