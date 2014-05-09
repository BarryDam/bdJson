<?php
	/**
	 * Example .. test this file:
	 * go in your browser to example.php?a='test'&value=1
	 */
	require 'bdJson.php';
	class bdJsonExample extends bdJson 
	{
		public function __construct()
		{
			$action = $this->getAndCheckRequests('a');
			switch ($action) {
				case 'test':
					$this->exampleTest();
					break;
				
				case 'test2' :
					$this->exampleTest2();
					break;
			}
		}
		
		private function exampleTest()
		{
			$value = $this->getAndCheckRequests('value');
			$this->testvalue = 'passed by browser:'.$value;
		}

		private function exampleTest2()
		{
			$this->error = 'EXAMPLE ERROR';
		}
	};
	// exec the class
	new bdJsonExample();
?>