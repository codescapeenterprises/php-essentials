<?php
	use PHPUnit\Framework\TestCase;
	
	use Codescape\PHP\util\Toolbox;
	
	class RandomStringTest extends TestCase {
		public function testCanCreateRandomString() {
			$this->assertNotEmpty(\Codescape\PHP\util\Toolbox::randStr());
		}
	}
?>