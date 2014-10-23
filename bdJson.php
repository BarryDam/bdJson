<?php
	/**
	*	@version 1.2.0 
	*	last edit : 06-11-2013 By Barry
	**/

	/**
	*	
	*	This class will return a JSON file whenn called by Ajax, when called in browser the json array will be <pre>printed array</pre> for debugging.
	*	@uses class ExampleClass extends bdJson #always extend this class
	*	@example 	below this file on rule 146
	*	@return 	when called by ajax it returns encoded JSON
	*	@return 	when called from browser or direct it prints the array for debugging
	*	@copyright 	Barry Dam 2013
	*
	*	ON SUCCESS
	*		When the there's no error thrown the json array will have a array key 'success' set to true
	*			Array
	*			(
	*				[success] => true
	*				[example] => Example value
	*			)
	*	ON ERROR
	*		When throwing an error the json array will have a array key 'error' set with the error text and a key 'error_trace' containing a array for backtracing
	*			Array
	*			(
	*			    [error] => a is empty or not set!
	*			    [error_trace] => Array
	*			        (
	*			            [0] => modules\bicwork\classJSON.php on line 55
	*			            [1] => modules\bicwork\exec.portfolio.php on line 9
	*			            [2] => modules\bicwork\exec.portfolio.php on line 231
	*			        )	
	*			)
	*
	* 	USE THESE FUNCTIONS	
	*		$arrRequests = $this->getAndCheckRequests('id,pid,order',false) 		# 	check for empty value's in $_REQUEST['id']; $_REQUEST['pid']; $_REQUEST['order']; and returns array('id'=>value,'pid'=>value,'order'=>value);
	*																					in case of one value $intID = $this->getAndCheckRequests('id',false); it returns the value 
	*																					second param = allow empty (default = false) 
	*
	*		$this->error  = 'wer';													# 	immediately stops the script from executing and throws an error json format
	*		
	*		$strSafe 	 = self::safeForDB('This is an "example"');					# 	use this for every request you want to save in the DB!
	*																					makes the string safe for DB this example will return > This is an \"example\"
	*
	*		$this->dbQuery('SELECT * FROM bla')										#	$this->dbQuery when an dbquery error occurs the json will return array('error'=>mysql_error(),'query'=>'SELECT * FROM bla') 
	*																				#	if you using a SELECT query > an array containing the values will be returned
	*
	*		$this->example = 'Example value';										#	will add a array('example' => 'Example value') to the json array which will be returned!
	*
	**/
	abstract class bdJson {
		public 	$arrJSON 	= array(),
				$phpHeader  = 'application/json'
			;
		
		public function __set($name, $value){
			if($name == 'error'){
				$arrDebugBackTrace		= debug_backtrace();
				$arrErrorBackTrace		= array();
				foreach($arrDebugBackTrace as $arrDebug){
					$strFileName			= substr($arrDebug['file'], strlen(FILE_PATH));
					$intLine				= $arrDebug['line'];
					$arrErrorBackTrace[] 	= $strFileName.' on line '.$intLine;
				}
				$this->arrJSON['error'] 		= $value;
				$this->arrJSON['error_trace'] 	= $arrErrorBackTrace;
				exit;
			}else if($name == 'error_keys'){
				$this->arrJSON['error_keys'][] = $value;
				return false;
			}
			$this->arrJSON[$name] = $value;
		}

		public function __get($getName){
			if( array_key_exists($getName, $this->arrJSON) ) return $this->arrJSON[$getName];
		}

		/**
		 * 
		 */
		public function getRequest(){
			$arrRequestKeys = func_get_args();
			$arrReturn		= array();
			if (! count($arrRequestKeys)) return;
			foreach($arrRequestKeys as $strKey) {
				$strKey = trim($strKey); 
				if(! isset($_REQUEST[$strKey]) ||$_REQUEST[$strKey] == '' ) {
					$this->error_keys = $strKey;
				} else {
					$arrReturn[$strKey] = $_REQUEST[$strKey];
					unset($_REQUEST[$strKey]);
				}
			}
			if(! empty($this->arrJSON['error_keys'])){
				$this->error = '<strong>'.implode(',', $this->arrJSON['error_keys']).'</strong> empty or not set!';
				return false;
			}
			return (count($arrReturn)>1) ? $arrReturn : $arrReturn[$strKey] ;
		}

		/**
		* 	Check for empty $_REQUEST
		*	@param $getItems = false #example 1  'id,pid,order' example 2 'id'
		*	@return false when all $_REQUESTS are not set
		*	@return array('id'=>value,'pid'=>value,'order'=>value); when @param == 'id,pid,order' and requests are set
		*	@return $_REQUEST['id'] when @param == 'id';
		*	will unset the givin $_REQUEST for security reasons!
		**/
		public function getAndCheckRequests($getItems=false,$boolAllowEmpty=false){
			if($getItems){
				$arrReturn	= array();
				$arrItems 	= explode(',',$getItems);
				foreach($arrItems as $strRequest){
					$strRequest = trim($strRequest);
					if((!isset($_REQUEST[$strRequest]) && !$boolAllowEmpty) || (!$boolAllowEmpty && $_REQUEST[$strRequest] === '') ){
						$this->error_keys = $strRequest;
					}else{
						$arrReturn[$strRequest] = (!empty($_REQUEST[$strRequest]))?$_REQUEST[$strRequest]:false;
						unset($_REQUEST[$strRequest]);
					}
				}
				if(!empty($this->arrJSON['error_keys'])){
					$this->error = '<strong>'.implode(',',$this->arrJSON['error_keys']).'</strong> empty or not set!';
					return false;
				}
				return (count($arrReturn)>1)? $arrReturn : $arrReturn[$strRequest] ;
			}
		}

		public function __destruct(){
			if(!$this->nodestruct){
				$this->deprecated = 'This version of bdJson is deprecated, use bdJson2 instead!';
				if(!$this->error) $this->success = true;
				if(true || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_REQUEST['forcejson'])) {
					header('Content-type: '.$this->phpHeader);
					if(isset($_REQUEST['callback'])) echo $_REQUEST['callback'] ;
					echo json_encode($this->arrJSON);
				}else{
					echo '<pre>'.print_r($this->arrJSON,true).'</pre>';
				}
			}
		}
	}	
?>