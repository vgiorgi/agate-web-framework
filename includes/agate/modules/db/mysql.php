<?php
/**
 *<b>db.mysqlnd module for a php library</b>
 *@author Vasile Giorgi
 *@license lgpl
 *@copyright 2011 (c) Vasile Giorgi
 *@version 1.01.202
 */

define('DB_RETURN_CELL' ,0);
define('DB_RETURN_ROW' ,1);
define('DB_RETURN_TABLE' ,2);
define('DB_RETURN_LAST_ID' ,3);
define('DB_RETURN_NOTHING', -1);

class aexDbMySql extends a
{
	static private $dbConnection = false;


	private static function dbConnect() {
//debug{
		self::addDebugCall('dbConnect');
//}
		if(!isset(self::$config['db'])
		|| !isset(self::$config['db']['mysql'])
		|| !isset(self::$config['db']['mysql']['server'])
		|| !isset(self::$config['db']['mysql']['user'])
		|| !isset(self::$config['db']['mysql']['password'])
		|| !isset(self::$config['db']['mysql']['database'])) {
			self::$arDebug['error'][] = 'Missing SQL Connection properties!';
			return FALSE;
		}
		if(!isset(self::$config['db']['mysql']['port'])) {
			self::$config['db']['mysql']['port'] = 3306;
		}
		self::$dbConnection = @mysqli_connect(
		self::$config['db']['mysql']['server'],
		self::$config['db']['mysql']['user'],
		self::$config['db']['mysql']['password'],
		self::$config['db']['mysql']['database'],
		self::$config['db']['mysql']['port']);
		if (self::$dbConnection === FALSE) {
			self::$arDebug['error'][] = ' Error #'.mysqli_connect_errno().': Unable to connect to server:'.self::$config['db']['mysql']['server'].'<hr />MySql Server is down or connection is not properly configured. Please check the configuration of sql connection!<hr />'.mysqli_connect_error();
		}
		if (!mysqli_set_charset(self::$dbConnection, 'utf8')) {
			self::$arDebug['error'][] = ' Error #'.mysqli_connect_errno().': Error loading character set utf8: '.mysqli_error(self::$dbConnection);
		}
		self::$arDebug['info'][] = 'Db connection open: ok!';
	}


	public static function dbClose()
	{
//debug{
		self::addDebugCall('dbClose');
//}
		if(self::$dbConnection === FALSE) {
			self::$arDebug['error'][] = 'No MySQL connection to close!';
			return FALSE;
		}
		if(@mysqli_close(self::$dbConnection) === FALSE) {
			self::$arDebug['error'][] = 'Something went wrong while closing MySQL connection!';
			return FALSE;
		}
		self::$arDebug['info'][] = 'Db connection close: ok!';
	}


	public static function dbChange($sNewDatabaseName = FALSE)
	{
//debug{
		self::addDebugCall('dbChange');
//}
		if($sNewDatabaseName === FALSE) {
			$sNewDatabaseName = self::$config['db']['mysql']['database'];
		}

		if(!@mysqli_select_db(self::$dbConnection, $sNewDatabaseName)){
			self::$arDebug['error'][] = 'Something went wrong while changing MYSQL database to'.$sNewDatabaseName.'!';
		}
	}


	public static function dbQuery($sQuery, $isMultiQuery = false)
	{
//debug{
		self::addDebugCall('dbQuery');
//}
		if(self::$dbConnection === FALSE) {
			self::dbConnect();
		}
		if(self::$dbConnection === FALSE) {
			self::$arDebug['error'][] = 'SQL Error on function a::dbQuery: Can not connect to the dabase!';
			return FALSE;
		}
//execute the query(silent)
		if($isMultiQuery) {
			$result = @mysqli_multi_query(self::$dbConnection, $sQuery);
		}
		else {
			$result = @mysqli_query(self::$dbConnection, $sQuery);
		}


//handling errors:
		if(!$result) {
			a::$arDebug['error'][] = 'SQL Error on function a::dbQuery:'.mysqli_error(self::$dbConnection).'<hr />Error on executing sql query:<pre>'.$sQuery.'</pre>';
			return FALSE;
		}
		else {
			self::$arDebug['info'][] = 'Execute okay:'.$sQuery;
		}

		return $result;
	}


	public static function dbExecute($sQuery, $iReturn = DB_RETURN_CELL, $iFetchType = MYSQLI_BOTH)
	{
//debug{
		$time = microtime(true);
		self::addDebugCall('dbExecute');
//}
//parse query
		$result = self::dbQuery($sQuery);

//		if(is_bool($result)) {
//			return($result);
//		}

//if there is a select get the result:
		switch($iReturn) {
			case DB_RETURN_LAST_ID:
				$arResult = mysqli_insert_id(self::$dbConnection);
				break;
			case DB_RETURN_CELL:
				$arResult = @mysqli_fetch_row($result);
				$arResult = $arResult[0];
				break;
			case DB_RETURN_ROW:
				$arResult = @mysqli_fetch_array($result, $iFetchType);
				break;
			case DB_RETURN_TABLE:
				if(function_exists('mysqli_fetch_all')) {
					$arResult = @mysqli_fetch_all($result, $iFetchType);
				}
				else {
					$arResult = array();
					while($row = @mysqli_fetch_array($result, $iFetchType)) {
						$arResult[] = $row;
					}
				}
				break;
			case DB_RETURN_NOTHING:
				$arResult = TRUE; //means success
				break;
		}

		@mysqli_free_result($result);
//debug{
		a::$arDebug['db'][] = array(
			'query' => $sQuery,
			'time' => sprintf('%01.2f', 1000 * $time - microtime(true)));
//}
		return $arResult;
	}


	public static function dbLookup($sSource, $sDomain, $sCriteria = '0=0')
	{
//debug{
		self::addDebugCall('dbLookup');
//}
		$sQuery = 'SELECT '.$sSource.' FROM '.$sDomain.' WHERE '.$sCriteria.' LIMIT 1;';
//debug{
		self::$arDebug['info'][] = $sQuery;
//}
		return(self::dbExecute($sQuery));
	}


	public static function dbLookupRow($sSource, $sDomain, $sCriteria = '0=0', $iFetchType = MYSQLI_BOTH)
	{
//debug{
		self::addDebugCall('dbLookupRow');
//}
		$sQuery = 'SELECT '.$sSource.' FROM '.$sDomain.' WHERE '.$sCriteria.' LIMIT 1;';
		self::$arDebug['info'][] = $sQuery;
		return(self::dbExecute($sQuery, DB_RETURN_ROW, $iFetchType));
	}

	public static function dbLookupTable($sSource, $sDomain, $sCriteria = '0=0', $iFetchType = MYSQLI_NUM)
	{
//debug{
		self::addDebugCall('dbLookupTable');
//}
		$sQuery = 'SELECT '.$sSource.' FROM '.$sDomain.' WHERE '.$sCriteria;
		self::$arDebug['info'][] = $sQuery;
		return(self::dbExecute($sQuery, DB_RETURN_TABLE, $iFetchType));
	}

/*depricated probably:*/
	public static function dbGetLastInsertId()
	{
//debug{
		self::addDebugCall('dbGetLastInsertId');
//}
		return(v::dbExecute("SELECT LAST_INSERT_ID();"));
	}


	public static function dbLog($action, $note = '', $table = '', $fk = NULL)
	{
//debug{
//		self::addDebugCall('dbLog');
//}

		$arServer = array();
		$sQuery = 'INSERT INTO `logs` ( `action`, `fk`, `ip`, `table`, `user`, `useragent`, `note`, `timestamp`)  VALUES (';
		$sQuery .= self::dbFormat($action, 'str', 'unknown', 255).', ';
		$sQuery .= self::dbFormat($fk, 'int').', ';
		$sQuery .= self::dbFormat(self::is($_SERVER['REMOTE_ADDR']).'|'.self::is($_SERVER['HTTP_X_FORWARDED_FOR']).'|'.self::is($_SERVER['HTTP_CLIENT_IP']), 'str', '0.0.0.0', 127).', ';
		$sQuery .= self::dbFormat($table, 'str', '', 25).', ';
		$sQuery .= self::dbFormat(self::is($_SESSION['agate']['user']['id']), 'int').', ';
		$sQuery .= self::dbFormat($_SERVER['HTTP_USER_AGENT'], 'str', 'unknown', 255).', ';
		$sQuery .= self::dbFormat($note).', ';
		$sQuery .= 'NOW())';

		return(self::dbExecute($sQuery));
	}

	/**
	 * Array to string => convert an array to string using a specific pattern
	 * @param string $sQuery
	 * @param string $sPattern
	 * @param array $aConfig
	 *
	 * @example using default config
	 * echo(v::dbAtos(
	 "SELECT `email_address` AS `email`, `id` AS `id` FROM `email` LIMIT 10;",
	 '<tr><td>%s</td><td>%s</td></tr>'));

	 * @example not using print format
	 * echo(v::dbAtos(
	 "SELECT `eml_indirizzo` AS `email`, `idEmail` AS `id` FROM `email` LIMIT 10;",
	 '<tr><td>{email}</td><td>{id}</td></tr>',
	 array('isPrintFormat' => false)));

	 * @example using different start/end tags and not print format
	 * echo(v::dbAtos(
	 "SELECT `eml_indirizzo` AS `email`, `idEmail` AS `id` FROM `email` LIMIT 10;",
	 '<tr><td>{@email@}</td><td>{@id@}</td></tr>',
	 array(
		'isPrintFormat' => false,
		'startTag' => '{@',
		'endTag' => '@}')));

		* @example using eval and not print format
		* echo(v::dbAtos(
		"SELECT `email_address` AS `email`, `id` AS `id` FROM `email` LIMIT 10;",
		'<tr><td>{$_Field[\'email\']}</td><td>{$_Field[\'id\']}</td></tr>',
		array(
		'isPrintFormat' => false,
		'isEval' => true)));
		*/
//TODO: use prequery
	public static function dbAtos($sQuery, $sPattern, $aConfig = array())
	{
//debug{
		self::addDebugCall('dbAtos');
		$time = microtime(true);
//}
		self::$arDebug['atos'][] = $aConfig;
		$aConfig = self::applyDefault($aConfig, array(
			'startTag' => '{',
			'endTag' => '}',
			'isPrintFormat' => false,
			'isEval' => false));
		self::$arDebug['atos'][] = $aConfig;

		$sStringResult = '';
		$result = self::dbQuery($sQuery);
		if(is_bool($result)) {
			return($result);
		}

		while($_Field = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			if($aConfig['isPrintFormat']) {
				$sResult = vsprintf($sPattern, $_Field);
			}
			else {
				$sResult = $sPattern;
				foreach($_Field as $k => $v) {
					$sResult = str_replace($aConfig['startTag'].$k.$aConfig['endTag'], $v, $sResult);
				}

/*
				$iStartTagLen = strlen($aConfig['startTag']);
				$iEndTagLen = strlen($aConfig['endTag']);
				$iTempPos = 0;
				do {
					$iPos = strpos($sResult, $aConfig['startTag'], $iTempPos);
					if($iPos !== FALSE) {
						$iExpLen = strpos($sResult, $aConfig['endTag'], $iPos + $iStartTagLen);
						if($iExpLen !== FALSE) {
							$iExpLen -= $iPos + $iStartTagLen;
							$sBeforeExp = substr($sResult, 0, $iPos);
							$sExp = substr($sResult, $iPos + $iStartTagLen, $iExpLen);

							if($aConfig['isEval']) {
								eval('$sExp='.$sExp.';');
							}
							else {
								$sExp = $_Field[$sExp];
							}

							$sAfterExp = substr($sResult, $iPos + $iExpLen + $iStartTagLen + $iEndTagLen);
							$sResult = $sBeforeExp.$sExp.$sAfterExp;
							$iTempPos = $iPos+strlen($sExp);
						};
					}
				} while($iPos !== FALSE);
*/
			}
			$sStringResult .= $sResult;
		}
		mysqli_free_result($result);
//debug{
		a::$arDebug['db'][] = array(
			'query' => $sQuery,
			'time' => sprintf('%01.2f', 1000 * $time - microtime(true)));
//}
		return($sStringResult);
	}


	public static function dbFormat($variable, $sType = 'string', $default = 'NULL', $format = false)
	{
//debug{
		self::addDebugCall('dbFormat');
//}

		if(self::$dbConnection === FALSE) {
			self::dbConnect();
		}
		if(self::$dbConnection === FALSE) {
			return FALSE;
		}

		$sResult = '';
		switch($sType) {
			case 'string':
			case 'str':
				if ($format > strlen($variable)) { //format means maximum length in case of strings
					$variable = substr($variable, 0, $format);
				}
				$sResult = "'".mysqli_real_escape_string(self::$dbConnection, $variable)."'";
				if($sResult === "''") {
					$sResult = $default;
				}
				break;

			case 'integer':
			case 'int':
				if (is_numeric($variable)) {
					$sResult = (int) $variable;
				}
				else {
					$sResult = $default;
				}
				//add a test if the result is not integer return $format
				break;

			case 'bit':
				if($variable) {
					$sResult = 1;
				}
				else {
					$sResult = 0;
				}
				break;

			case 'date':
				if ($format === false) {
//it is a date
					$sResult = "'".date('Y-m-d H:i:s', $variable).'"';
					//'2012-05-17 20:09:43'
				}
				else {
					$date = DateTime::createFromFormat($format, $variable);
					$sResult = "'".$date -> format('Y-m-d H:i:s')."'";
				}
			break;
		}
		return($sResult);
	}

	/*
	 private static function parseSql($sQuery) {
		$sQuery = mysqli_real_escape_string(self::$dbConnection, $sQuery);
		//PreventSqlInjection($sExpression)
		$sPattern='/(?<cleanexp>([a-z]|[A-Z]|[0-9]| |_|.|@)*)/';
		preg_match($sPattern, $sQuery, $regs);
		return($regs['cleanexp']);
		}
		*/
}

aexDbMySql::addStaticMethod('dbLookup', 'aexDbMySql::dbLookup');
aexDbMySql::addStaticMethod('dbLookupRow', 'aexDbMySql::dbLookupRow');
aexDbMySql::addStaticMethod('dbLookupTable', 'aexDbMySql::dbLookupTable');
aexDbMySql::addStaticMethod('dbExecute', 'aexDbMySql::dbExecute');
aexDbMySql::addStaticMethod('dbClose', 'aexDbMySql::dbClose');
aexDbMySql::addStaticMethod('dbChange', 'aexDbMySql::dbChange');
aexDbMySql::addStaticMethod('dbGetLastInsertId', 'aexDbMySql::dbGetLastInsertId');
aexDbMySql::addStaticMethod('dbAtos', 'aexDbMySql::dbAtos');
aexDbMySql::addStaticMethod('dbFormat', 'aexDbMySql::dbFormat');
aexDbMySql::addStaticMethod('dbLog', 'aexDbMySql::dbLog');

?>