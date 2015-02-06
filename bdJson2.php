<?php
	abstract class bdJson2 {
		protected 	$arrJSON 	= array(),
					$phpHeader  = 'application/json',
					$boolDebug	= false;

		public function __set($getKey, $getValue){
			switch ($getKey) {
				case 'error':
					$arrDebugBackTrace		= debug_backtrace();
					$arrErrorBackTrace		= array();
					foreach($arrDebugBackTrace as $arrDebug){
						$strFileName			= (defined('FILE_PATH')) ? substr($arrDebug['file'], strlen(FILE_PATH)) : $arrDebug['file'] ;
						$intLine				= $arrDebug['line'];
						$arrErrorBackTrace[] 	= $strFileName.' on line '.$intLine;
					}
					$this->arrJSON['error'] 		= $getValue;
					if ($this->boolDebug)
						$this->arrJSON['error_trace'] 	= $arrErrorBackTrace;
					// instantly stop the script
					exit;
					break;

				case 'error_keys': // error keys are added to array
					$this->arrJSON['error_keys'][] = $getValue;
					break;
				
				default:
					$this->arrJSON[$getKey] = $getValue;
					break;
			}
		}

		public function __get($getKey){
			if( array_key_exists($getKey, $this->arrJSON) ) return $this->arrJSON[$getKey];
		}

		/**
		 * if you work with bdJson.js
		 * the action is 
		 * @return [type] [description]
		 */
		protected function getAction()
		{
			$action = (isset($_REQUEST['bdJson_Action']))
				? $_REQUEST['bdJson_Action']
				: (isset($_REQUEST['a']) ? $_REQUEST['a'] : false );
			// unset action request
			if (isset($_REQUEST['bdJson_Action']))
				unset($_REQUEST['bdJson_Action']);
			elseif (isset($_REQUEST['a']))
				unset($_REQUEST['a']);
			// throw error when no action passed
			if (! $action)		
				$this->error = 'Action is not passed';
			// return action
			return $action;
		}

		/**
		 * get the request value
		 * @param  string  $getStrKey		the request key $_REQUEST[$getStrKey]
		 * @param  mixed bool or string 	$getBoolRequiredOrErrorMessage  when boolean > >true trhow error.. when string > throw error and error messages is $getBoolRequiredOrErrorMessage
		 * @param  boolean $getErrorValue   throw an error when $_REQUEST[$getStrKey] == $getErrorValue
		 * @return mixed
		 */
		protected function getRequest($getStrKey, $getBoolRequiredOrErrorMessage = true, $getErrorValue =false, $getType="REQUEST") 
		{
			$arr = $_REQUEST;
			if ($getType == 'POST')
				$arr = $_POST;
			if ($getType == 'GET')
				$arr = $_GET;
			if ($getType == 'FILES')
				$arr = $_FILES;

			if (
				isset($arr[$getStrKey]) && 
				$arr[$getStrKey] !== '' && 
				($getErrorValue === false || $arr[$getStrKey] !=  $getErrorValue )
			){
				return $arr[$getStrKey];
			} elseif ($getBoolRequiredOrErrorMessage) { 	// throw error when necessary
				// set the error key
				$this->error_keys = $getStrKey;
				// throw the error and stop the script
				$this->error = (is_string($getBoolRequiredOrErrorMessage))
					? $getBoolRequiredOrErrorMessage
					: (isset($arr[$getStrKey]) 
						? 'no value set for "'.$getStrKey.'"'
						: $getType.' param "'.$getStrKey.'" not passsed'
					);
			}
			return false;
		}

		protected function getFileRequest($getStrKey, $getTargetDirectory, $getBoolRequiredOrErrorMessage = true, $getErrorValue =false)
		{
			$filedata = $this->getRequest($getStrKey, $getBoolRequiredOrErrorMessage, $getErrorValue, 'FILES');
			
			if(!is_dir($getTargetDirectory))
				$this->error = "target destination (".$getTargetDirectory.") does not excist";


			$filedata['destination'] = $getTargetDirectory.$filedata['name'];
			$pathinfo = pathinfo($filedata['destination']);
			$i=1;
			$filedata['renamed'] = false;
			while( file_exists($filedata['destination']) )
			{
				$filedata['destination'] = $pathinfo['dirname'].DIRECTORY_SEPARATOR.$pathinfo['filename'].'_('.($i++).').'. $pathinfo['extension'];
				$filedata['renamed'] = true;
			}

			$filedata['moved'] = move_uploaded_file($filedata['tmp_name'], $filedata['destination']);

			return $filedata;
		}

		protected function POST($getStrKey, $getBoolRequiredOrErrorMessage = true, $getErrorValue = false) {
			return $this->getRequest($getStrKey, $getBoolRequiredOrErrorMessage, $getErrorValue, 'POST');
		}
		protected function GET($getStrKey, $getBoolRequiredOrErrorMessage = true, $getErrorValue = false) {
			return $this->getRequest($getStrKey, $getBoolRequiredOrErrorMessage, $getErrorValue, 'GET');
		}
		protected function REQUEST($getStrKey, $getBoolRequiredOrErrorMessage = true, $getErrorValue = false) {
			return $this->getRequest($getStrKey, $getBoolRequiredOrErrorMessage, $getErrorValue, 'REQUEST');
		}
		protected function FILES($getStrKey, $getTargetDirectory, $getBoolRequiredOrErrorMessage = true, $getErrorValue = false) {
			return $this->getFileRequest($getStrKey, $getTargetDirectory, $getBoolRequiredOrErrorMessage, $getErrorValue);
		}

		/**
		 * get multiple requets
		 * when using this function .. all request are REQUIRED
		 * @params multiple getters
		 * array() request values
		 */
		protected function getRequests()
		{
			$arrRequestKeys = func_get_args();
			$arrReturn		= array();
			if (! count($arrRequestKeys)) return;
			foreach($arrRequestKeys as $strKey) {
				if(! isset($_REQUEST[$strKey]) || $_REQUEST[$strKey] == '' )
					$this->error_keys = $strKey;
				else
					$arrReturn[$strKey] = $_REQUEST[$strKey];
				
			}
			if(! empty($this->arrJSON['error_keys'])){
				$this->error = '<strong>'.implode(',', $this->arrJSON['error_keys']).'</strong> empty or not set!';
				return false;
			}
		}

		/**
		 * destructor will output json when called by ajax
		 * and a printed array when called directely in browser (4 debugging)		 * 
		 */
		public function __destruct(){
			// set success when no error
			if(! $this->error) $this->success = true;
			// ajax or direct in browser?
			if((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_REQUEST['forcejson'])) {
				header('Content-type: '.$this->phpHeader);
				// callback passed by Ajax (JSONP)?
				if(isset($_REQUEST['callback'])) {
					echo $_REQUEST['callback'].'('.json_encode($this->arrJSON).')' ;
				} else {
					// echo json data
					echo json_encode($this->arrJSON);
				}
			}else{
				// print for debug
				echo '<pre>'.print_r($this->arrJSON,true).'</pre>';
			}
		}
	}
?>