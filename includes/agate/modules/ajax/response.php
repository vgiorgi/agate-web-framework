<?php
class ajax
{
	public $bSuccess;
	public $aData;
	public $sMessage;
	public $sAction;


	function __construct()
	{
//debug{
		a::addDebugCall('response__construct');
//}
		$this -> sAction = isset($_REQUEST['get']) ? $_REQUEST['get'] : '-';
		$this -> bSuccess = false;
		$this -> sMessage = 'Undefined action!';
		$this -> aData = array();
	}


	public function setData($sKey, $vValue)
	{
		$this -> aData[$sKey] = $vValue;
	}


	private function action()
	{
		if($this -> sAction === 'content') {
			$this -> getSection();
		}
		else {
			$this -> call($this -> sAction);
		}
	}

	private function getSection()
	{
		if(isset($_REQUEST['section'])) {
			$sFileName = a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.$_REQUEST['section'].'.php';
			if (is_file($sFileName)) {
				ob_start();
				echo('<div id="'.$_REQUEST['section'].'">');
				include($sFileName);
				echo('</div>');
				$this -> setData('html', ob_get_contents());
				ob_end_clean();
			}
			else {
				$this -> sMessage('Error: missing file:['.$sFileName.'] !');
			}
		}
		else {
			$this -> sMessage('Null section!');
			$this -> bSuccess(false);
		}
	}


	public function response()
	{
//debug{
		a::$arDebug['time']['start'] = microtime(true);
//}
		$this -> action();

		switch(@$_GET['response']) {
		case 'redirect':
			a::log('response: redirect='.$this -> aData['redirect']);
			a::redirect($this -> aData['redirect']);
			break;
		case 'html':
			echo($this -> aData);
			break;
		case 'json':
		default:
			echo(json_encode(array(
				'action' => $this -> sAction,
				'success' => $this -> bSuccess,
				'message' => $this -> sMessage,
				'data' => $this -> aData)));
			break;
		}
		a::log(
			'response for '.$this -> sAction.":\n"
			.'success='.$this -> bSuccess."; "
			.'message='.$this -> sMessage."\n"
			.'data='.print_r($this -> aData, true));


//debug{
		a::addDebugCall('end');
		a::$arDebug['time']['current'] = microtime(true);
		a::$arDebug['time']['total'] = sprintf('%01.4F ms.', ((float)a::$arDebug['time']['current'] - (float)a::$arDebug['time']['start']));
		a::log(a::$arDebug);
//}
	}
}
?>