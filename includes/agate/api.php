<?php
error_reporting(-1);
/**
 *Agate API</b>
 *@author Vasile Giorgi
 *@license lgpl
 *@copyright 2010 (c) Vasile Giorgi
 *@version 0.12.0511
 *
 * Calls:
 *	login
 *		input:
 *			email = the user email
 *			password = the user password
 *			[response] = json | redirect
 *			[okay] = the page to redirect on success, only in case: response = redirect
 *			[err] = the page to redirect on errors, only in case: response = redirect
 *		output: ajax response: user
 *
 *
 *	guid
 *		output: ajax response: guid
 *
 * session
 *		output: ajax response: session
 **/

require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
a::module('ajax/response');

class apiAjax extends ajax
{
	const MSG_LOGIN_INVALID_EMAIL = 'Email not found !';
	const MSG_LOGIN_SALT_NOT_FOUND = 'Salt not found !';
	const MSG_LOGIN_INVALID_PASSWORD = 'Wrong password!';
	const MSG_LOGIN_OKAY = 'Login Okay!';
	const MSG_LOGIN_INVALID_KEY = 'Invalid key!';
	const MSG_REGISTER_EMAIL_EXIST = 'Another user with this email address is already registered!';
	const MSG_REGISTER_INVALID_EMAIL = 'Invalid email address!';
	const MSG_REGISTER_FAILED = 'Registration failed!';
	const MSG_REGISTER_OKAY = 'Registration Okay!';


	public function call($sAction)
	{
		a::log('API call:'.$sAction."\nRequest:".print_r($_REQUEST, true));
		switch ($sAction) {
		case 'guid':
			$this -> bSuccess = true;
			$this -> sMessage = 'New GUID generated!';
			$this -> setData('guid', a::guid());
			break;
		case 'login':
			$this -> callLogin();
			break;
		case 'logout':
			$this -> callLogout();
			break;
		case 'register':
			$this -> callRegister();
			break;
		case 'session':
			$this -> setData('session', $_SESSION);
			break;
		case 'phpinfo':
			phpinfo();
			die();
			break;
		}
	}


	private function callLogin() {
		a::module('db/mysql');

//login with key:
		if(isset($_GET['key'])) {
			require_once($_SERVER['DOCUMENT_ROOT'].'/includes/agate/settings/loader.php');
			if($_GET['key'] === a::$config['roles']['i0']['key']) {
				$_SESSION['agate']['user'] = array(
					'id' => 0,
					'email' => 'no-reply@email',
					'name' => 'Super User');
//load all keys(full access):
				$keys = a::dbLookupTable(
					"DISTINCT `key`",
					"`security`",
					"`object` = 'user' AND `property` = 'id'",
					MYSQLI_ASSOC);
				$iMax = count($keys);
				$_SESSION['agate']['user']['keys'] = array();
				for($i = 0; $i < $iMax; $i++) {
					$_SESSION['agate']['user']['keys'][] = $keys[$i]['key'];
				}

				$this -> bSuccess = true;
				$this -> sMessage = self::MSG_LOGIN_OKAY;
				$this -> setData('redirect', @$_SESSION['agate']['loginredirect']);
				a::dbLog('login', self::MSG_LOGIN_OKAY, 'users', 0);

				return true;
			}
			else {
				$this -> sMessage = self::MSG_LOGIN_INVALID_KEY;
				$this -> bSuccess = false;
				a::dbLog('login', self::MSG_LOGIN_INVALID_KEY, 'users');
				return false;
			}
		}

		//check the log to find id user was trying at least 3 times:
		//[...]

		//get user data from db:
		$userDetails = a::dbLookupRow(
			"`id`, `email`, `password`, `display_name` AS `name`",
			"`users`",
			"`email` = ".a::dbFormat(@$_REQUEST['email'], 'string', '', 100),
			MYSQLI_ASSOC);
		if($userDetails === null || $userDetails === false) {
			$this -> sMessage = self::MSG_LOGIN_INVALID_EMAIL;
			$this -> bSuccess = false;
			a::dbLog('login', self::MSG_LOGIN_INVALID_EMAIL, 'users');
			return false;
		}

		//get salt:
		$salt = a::dbLookup(
			"`key`",
			"`security`",
			"`object` = 'user' AND `property` = 'salt' AND `fk` = ".$userDetails['id']);
		if($salt === false) {
			$this -> sMessage = self::MSG_LOGIN_SALT_NOT_FOUND;
			$this -> bSuccess = false;
			a::dbLog('login', self::MSG_LOGIN_SALT_NOT_FOUND, 'users');
			return false;
		};

		//check password hash:
		if (hash('sha512', @$_REQUEST['password'].$salt) !== $userDetails['password']) {
			$this -> sMessage = self::MSG_LOGIN_INVALID_PASSWORD;
			$this -> bSuccess = false;
			a::dbLog('login', self::MSG_LOGIN_INVALID_PASSWORD, 'users');
			return false;
		}

		//load user keys:
		$_SESSION['agate']['user'] = $userDetails;
		unset($_SESSION['agate']['user']['password']);

		$keys = a::dbLookupTable(
			"`key`",
			"`security`",
			"`object` = 'user' AND `property` = 'id' AND `fk` = ".$userDetails['id'],
			MYSQLI_ASSOC);
		$iMax = count($keys);
		$_SESSION['agate']['user']['keys'] = array();
		for($i = 0; $i < $iMax; $i++) {
			$_SESSION['agate']['user']['keys'][] = $keys[$i]['key'];
		}

		if (isset($_SESSION['agate']['loginredirect'])) {
			$redirect = $_SESSION['agate']['loginredirect'].'.html';
			unset($_SESSION['agate']['loginredirect']);
		}
//set last login date:
		a::dbExecute(
			"UPDATE `users` "
			."SET `last_login` = NOW() "
			."WHERE `users`.`id` = 1");
//return:
		$this -> bSuccess = true;
		$this -> sMessage = self::MSG_LOGIN_OKAY;
		$this -> setData('redirect', $redirect);
		a::dbLog('login', self::MSG_LOGIN_OKAY, 'users', $userDetails['id']);

		return true;
	}


	private function callLogout()
	{
		unset($_SESSION['agate']['user']);
		$this -> bSuccess = true;
		$this -> sMessage = self::MSG_LOGIN_OKAY;
		$this -> setData('redirect', '');
	}


	private function callRegister()
	{
		require_once($_SERVER['DOCUMENT_ROOT'].'/includes/agate/settings/loader.php');
		a::module('db/mysql');

//validations:
////email:
		if(filter_var(@$_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
			$sEmail = filter_var($_REQUEST['email'], FILTER_SANITIZE_EMAIL);
			$sEmail = a::dbFormat($sEmail, 'string', 100);
		}
		else {
			$this -> bSuccess = false;
			$this -> sMessage = self::MSG_REGISTER_INVALID_EMAIL;
			return;
		}

		$iEmailInDb = (int) a::dbLookup("COUNT(`email`) AS `test`", "`users`", "`email` = ".$sEmail);
		if($iEmailInDb > 0) {//email must be unique
			$this -> bSuccess = false;
			$this -> sMessage = self::MSG_REGISTER_EMAIL_EXIST;
			return;
		}

		$sSalt = a::guid();
		$sPassword = a::dbFormat(hash('sha512', @$_REQUEST['password'].$sSalt), 'string', 128);
		$sFirstName = a::dbFormat(@$_REQUEST['firstName'], 'string', 100);
		$sLastName = a::dbFormat(@$_REQUEST['lastName'], 'string', 100);
		$sDisplayName = a::dbFormat(@$_REQUEST['displayName'], 'string', 100);

//add user to database:
		$iUserId = a::dbExecute(
			"INSERT INTO `users` ("
				."`email`, `password`, `first_name`, `last_name`, `display_name`, `registered`) "
			."VALUES ("
				.$sEmail.', '.$sPassword.', '.$sFirstName.', '.$sLastName.', '.$sDisplayName.', NOW());',
			DB_RETURN_LAST_ID);

		if($iUserId === null) {
			$this -> sMessage = self::MSG_REGISTER_FAILED;
			$this -> setData('error', 'Db insert error!');
			$this -> bSuccess = false;
			return;
		}

//add user salt to database:
		a::dbExecute(
			"INSERT INTO `security` ("
				."`fk`, `key`, `object`, `property`)"
			."VALUES ("
				.a::dbFormat($iUserId, 'integer').", '".$sSalt."', 'user', 'salt')");

//add role key:
		a::dbExecute(
			"INSERT INTO `security` ("
				."`fk`, `key`, `object`, `property`)"
			."VALUES ("
				.a::dbFormat($iUserId, 'integer').", '".a::$config['roles']['i1']['key']."', 'user', 'id')");

//add access to admin page:
		a::dbExecute(
				"INSERT INTO `security` ("
				."`fk`, `key`, `object`, `property`)"
				."VALUES ("
				.a::dbFormat($iUserId, 'integer').", '".a::$config['pages']['i1']['security']."', 'user', 'id')");

//everything is fine:
		$this -> bSuccess = true;
		$this -> sMessage = self::MSG_REGISTER_OKAY;
		$this -> callLogin();
	}
}

$oApiResponse = new apiAjax;
$oApiResponse -> response();
?>