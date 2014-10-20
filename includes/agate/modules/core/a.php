<?php
/*
* This file is a part of AGATE WEB Framework
* http://agateweb.org/
*
* Copyright (C) 2012 Vasile Giorgi
*
* Date: Mon May 7 16:18:21 2012
*/

ini_set('session.hash_function', 'whirlpool');
session_start();
/**
 *<b>a->gate() web framework</b>
 *module::core::d = the debugging core, used in development mode
 *@author Vasile Giorgi
 *@copyright 2012 (c) Vasile Giorgi
 *@version 0.13.0903
 */
class a
{
	const VERSION = '0.12.0511';//alpha
	const INSERT_BEFORE = 1;
	const INSERT_AFTER = 2;
	const INSERT_CHILD_FIRST = 3;
	const INSERT_CHILD_LAST = 4;

	private static $arJavaScriptsFromSections = array(); //an array with included javascript for displayed sections
	private static $arSections = array(); //an array with displayed sections
	private static $_arNewStatic = ""; //used to extend static methodes for the main class;

	public static $config = array(
		'name' => 'website',
		'homepage' => 'index.html',
		'showErrors' => true,
		'locale' => 'en-US',
		'protocol' => 'http',
		'isInternalPage' => false);
	public static $arSiteMap = array();
	public static $arLayout = array();
	public static $arJavaScriptsFromModules = array();
	public static $page = array();
//	public static $subpage = '';


/**
 * Add debug info about the call, this is called on each method on d core.
 * Method used only in d code.
 * @param unknown_type $sCall
 */
	private static function addDebugCall($sCall) {
//set the time for last call:
		$sLastCall = end(self::$arDebug['calls']);
		self::$arDebug['calls'][key(self::$arDebug['calls'])] = $sLastCall.' - time:'.sprintf('%01.4f ms.', (1000*((microtime(true) - a::$arDebug['time']['current']))));
//add last call:
		self::$arDebug['calls'][] =  $sCall;
		a::$arDebug['time']['current'] = microtime(true);
	}


/**
 *
 * The constructor class, call self::init() to be sure it is initialized also with static init call;
 */
	public function __construct()
	{

		self::init();
	}


/**
 * Used to define a new method outside the class
 * @param string $name
 * @param array $arguments
 */
	public static function __callStatic($name, $arguments)
	{

		if(isset(self::$_arNewStatic[$name])) {
			return call_user_func_array(self::$_arNewStatic[$name], $arguments);
		}
	}


/**
 * Used in modules to add static metodes to main a class
 * @param string $name - the name of the new method in a class
 * @param string $callback - the call made for the new method
 */
	public static function addStaticMethod($name, $callback)
	{

		self::$_arNewStatic[$name] = $callback;
	}


/**
 * Private method to apply default values to an array,
 * mainly used for arrays which can't have specific default values defined
 */
	public static function applyDefault($aAttributes, $aDefaultAttributes)
	{

//		$aDefaultKeys = array_keys($aDefaultAttributes);
		foreach ($aDefaultAttributes as $k => $v) {
			if(!array_key_exists($k, $aAttributes)) {
				$aAttributes[$k] = $v;
			}
		}
/*
		$iMax = count($aDefaultKeys);
		for($i = 0; $i < $iMax; $i++) {
			if(!array_key_exists($aDefaultKeys[$i], $aAttributes)) {
				$aAttributes[$aDefaultKeys[$i]] = $aDefaultAttributes[$aDefaultKeys[$i]];
			}
		}
*/
		return($aAttributes);
	}


/**
 * Check if user has access.
 * @param string $key - the key (GUID v4) to tested;
 */
	public static function hasAccess($key = false) {


//key validation:
		if(!$key) {
			return FALSE;
		}
		if(!is_string($key) || !strlen($key) === 36) {
			return FALSE;
		}

//check if user has the key:
		if(in_array($key, @$_SESSION['agate']['user']['keys'])) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}


/**
 * Redirect method (shortcut to header('location: [page]'))
 * @param string $page - the page name(url, permalink)
 * @param string [$extension] - the page extension (default is html)
 */
	public static function redirect($page = '', $extension = '') {

		session_write_close();
		header('location: /'.$page.$extension);
		exit();
	}


/**
 * Used for initialization and for static calls
 */
	public static function init()
	{

		if(!isset($_SESSION['agate'])) {
			$_SESSION['agate'] = array(
				'user' => array(
					'name' => 'guest',
					'keys' => array()));
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/includes/agate/settings/loader.php');
		$arSpecialPages = array('section');

//redirects:
		if(isset($_GET['page'])) {
			$page = $_GET['page'];

//check if page exist in site map:
			foreach (self::$arSiteMap as $k => $v) {
				if($v['name'] === $page) {
					if(isset($v['security'])) {
						if(!self::hasAccess($v['security'])) {
							$_SESSION['agate']['loginredirect'] = $page;
							a::redirect(a::$config['loginpage']);
						}
					}
					self::$page = $v;
					return;
				}
			}
		}
		else {
			$page = '';
		}

//special pages:
		foreach (self::$config['pages'] as $k => $v) {
			if($v['name'] === $page) {
				if(isset($v['security'])) {
					if(!self::hasAccess($v['security'])) {
						$_SESSION['agate']['loginredirect'] = $page;
						a::redirect(a::$config['loginpage']);
					}
				}
				self::$page = $v;
//specific logic for special pages:
				switch(self::$page['name'].'.html') {
					case self::$config['loginpage']:
						self::$config['root'] =
							$_SERVER['DOCUMENT_ROOT']
							.DIRECTORY_SEPARATOR.'includes'
							.DIRECTORY_SEPARATOR.'agate'
							.DIRECTORY_SEPARATOR.'modules'
							.DIRECTORY_SEPARATOR.'admin';
						self::$config['isInternalPage'] = true;
						break;
					case 'admin':
						self::$config['root'] =
							$_SERVER['DOCUMENT_ROOT']
							.DIRECTORY_SEPARATOR.'includes'
							.DIRECTORY_SEPARATOR.'agate'
							.DIRECTORY_SEPARATOR.'modules'
							.DIRECTORY_SEPARATOR.'admin';
						//self::module('admin/onyx');
						break;
				}
				return;
			}
		}


//page not set or page not forund in site map => redirect to default page:
		foreach (self::$arSiteMap as $k => $v) {
			if(array_key_exists('default', $v)) {
				a::redirect($v['name'].'.html');
				return;
			}
		}

//default page is not defined
		a::redirect(a::$config['404page']);
		die(404);
	}


/**
 * Load cache file
 * @param string $filename = filename,
 * 	it must have php extension and exist in root/cache/ folder
 */
	public static function cacheLoad($filename)
	{

		require_once(self::$config['root']
			.DIRECTORY_SEPARATOR.'cache'
			.DIRECTORY_SEPARATOR.$filename.'.php');
	}


/**
 * Save cache file
 * @param string $filename = filename,
 * 	it must have php extension and exist in root/cache/ folder
 */
	public static function cacheSave($filename) {

		self::$arDebug['calls'][] = 'cacheSave';
		$sFileName = self::$config['root']
			.DIRECTORY_SEPARATOR.'cache'
			.DIRECTORY_SEPARATOR.$filename.'.php';
		switch ($filename) {
		case 'sections':
			$sFileContent = "<?php\na::\$arLayout = ".var_export(a::$arLayout, true).";\n?>";
			break;
		case 'pages':
			$sFileContent = "<?php\na::\$arSiteMap = ".var_export(a::$arSiteMap, true).";\n?>";
			break;
		}
		return(file_put_contents($sFileName, $sFileContent, LOCK_EX));
	}


/**
 *Module method, used to include a class module
 * @param string $sModuleName - module name
 * @tutorial standard modules are maintened and stored in modules folder.
 */
	public static function module($sModuleName)
	{

		$arModule = explode(':', $sModuleName);
		$iMax = count($arModule);
//		$sModulePath = $_SERVER['DOCUMENT_ROOT']
//			.DIRECTORY_SEPARATOR.'includes'
//			.DIRECTORY_SEPARATOR.'agate'
//			.DIRECTORY_SEPARATOR.'modules';
		$sModulePath = $_SERVER['DOCUMENT_ROOT'].'/includes/agate/modules';
		for($i=0; $i<$iMax; $i++) {
			$sModulePath .= '/'.$arModule[$i];
		}
		$sModulePath .= '.php';
		require_once $sModulePath;
		self::$arDebug['modules'][] = $sModuleName;
	}


/**
 * Obtain the section content
 * @param string_type $sSectionName
 * @param array_type $aAttributes
 * @return string
 */
	public static function getSection($sSectionName, $aAttributes = array())
	{

		ob_start();
		self::section($sSectionName, $aAttributes);
		return ob_get_clean();
	}


/**
 * Include section in the code = a particular shortcut of include_once
 * @param string $sSectionName = the name of the section
 * @param array $arFilter = used to filter the result, if not specified no filter is applied
 * 	possible filters are:
 * 		- including the perimited values in $arFilter['permited'], can be specified the default value in case of section is not in permited array
 */
	public static function section($sSectionName, $aAttributes = array())
	{


		$aAttributes = self::applyDefault($aAttributes, array(
			'class' => 'section',
			'id' => $sSectionName,
			'javascript' => array(),
			'type' => 'php',
			'wrapper' => true,
			'tag' => 'div',
			'close' => true,
			'root' => self::$config['root']
		));

		switch($aAttributes['type']) {
			case 'html':
				$sExtension = '.html';
				if($aAttributes['class'] === 'section') {
					$aAttributes['class'] = 'section-html';
				}
				else {
					$aAttributes['class'] .= ' section-html';
				}
				break;
			case 'menu':
				$sExtension = '.php';
				if($aAttributes['class'] === 'section') {
					$aAttributes['class'] = ' section-menu';
				}
				else {
					$aAttributes['class'] .= ' section-menu';
				}
				break;
			case 'post':
				if($aAttributes['class'] === 'section') {
					$aAttributes['class'] = ' section-post';
				}
				else {
					$aAttributes['class'] .= ' section-post';
				}
				break;

			default:
				$sExtension = '.php';
				if($aAttributes['class'] !== 'section') {
					$aAttributes['class'] .= ' section';
				}
		}

		if($aAttributes['wrapper']) {
			echo('<'.$aAttributes['tag'].' ');
			if($aAttributes['type'] !== 'box') {
				echo('id="'.$aAttributes['id'].'" ');
			}
			echo('class="'.$aAttributes['class'].'">');
		}

		switch($aAttributes['type']) {
		case 'html':
		case 'php':
			include(
				$aAttributes['root']
				.DIRECTORY_SEPARATOR.'sections'
				.DIRECTORY_SEPARATOR.$sSectionName.$sExtension);
			break;
		case 'post':
			if(isset($_GET['id'])) {
				if(isset($aAttributes['template'])) {
					$template = $aAttributes['template'];
				}
				else {
					$template =
						'<h1>{title}</h1>'
						.'<h3>Date:{date}, Author:{author}</h3>'
						.'<div class="post-content">{content}</div>';
				}
				$sql =
					"SELECT "
						."`posts`.`content`, "
						."`posts`.`title`, "
						."`posts`.`date`, "
						."`users`.`display_name` AS `author` ";
					if(isset($aAttributes['metaPost'])) {
						$iMax = count($aAttributes['metaPost']);
						for($i = 0; $i < $iMax; $i++) {
							if(!isset($aAttributes['template'])) {
								$template .=
									'<div class="'.$aAttributes['metaPost'][$i].'">'
										.'{'.$aAttributes['metaPost'][$i].'}'
									.'</div>';
							}
							$sql .= ", ("
								."SELECT `value` FROM `postmeta` "
								."WHERE "
									."`postmeta`.`post`= `posts`.`id` "
									."AND `postmeta`.`key` = '".$aAttributes['metaPost'][$i]."') "
									."AS `".$aAttributes['metaPost'][$i]."` ";
						}
					}
					$sql .=
						"FROM "
							."`posts` "
							."LEFT JOIN `users` ON `posts`.`author` = `users`.`id` "
						."WHERE "
							."`posts`.`permalink` = '".$_GET['id']."' ";

				echo(self::dbAtos($sql, $template));

/*

				$permalink = $_GET['id'];
				$arPost = self::dbLookupRow(
						"`title`, "
						."`content`, "
						."(SELECT `value` FROM `postmeta` WHERE `postmeta`.`post`= `posts`.`id` AND `key` = 'album') AS `gallery` ",
						"`posts`", "`permalink` = '".$_GET['id']."'", MYSQLI_ASSOC);
				echo('<h1>'.$arPost['title'].'</h1>');
				echo('<div class="content">'.$arPost['content'].'</div>');
				if($arPost['gallery'] !== null) {
					echo('<div class="galleria">'.$arPost['gallery'].'</div>');
				}
*/
			}
			else {
				//get the top 10 posts from db:
				echo('<div class="list-posts">');
				$sql =
					"SELECT "
						."`posts`.`title`, "
						."`posts`.`excerpt`, "
						."`posts`.`permalink`, "
						."`posts`.`date`, "
						."`users`.`display_name` AS `author` ";
				if(isset($aAttributes['metaList'])) {
					$iMax = count($aAttributes['metaList']);
					for($i = 0; $i < $iMax; $i++) {
						$sql .= ", ("
							."SELECT `value` FROM `postmeta` "
							."WHERE "
								."`postmeta`.`post` = `posts`.`id` "
								."AND `postmeta`.`key` = '".$aAttributes['metaList'][$i]."') "
							."AS `".$aAttributes['metaList'][$i]."` ";
					}
				}
				$sql .=
					"FROM "
						."`posts` "
						."LEFT JOIN `users` ON `posts`.`author` = `users`.`id` "
					."WHERE "
						."`posts`.`key` = '".$aAttributes['key']."' "
					."ORDER BY "
						."`posts`.`date` DESC "
					."LIMIT 0, 10;";

				echo(self::dbAtos($sql, $aAttributes['pattern']));
				echo('</div>');
			}
			break;
		case 'item':
//			echo('<pre>ITEM:'.$sSectionName.'</pre>');
			if(isset($aAttributes['subpage']) && $aAttributes['subpage'] === true) {
				$subpage = self::$page['name'];
			}
			else {
				$subpage = '';
			}

			self::pageMenuItem(
				$sSectionName,
				vsprintf(self::$arLayout[$aAttributes['parent']]['pattern'], $aAttributes['items']),
				$aAttributes['class'],
				$subpage);
			break;

		case 'box':
		default:
//do nothing;
			break;
		}

		if($aAttributes['wrapper'] && $aAttributes['close']) {
			echo('</div>');
		}

		if(count($aAttributes['javascript']) > 0) {
			self::$arJavaScriptsFromSections[] = $aAttributes['name'].'.js';
		}

		if(isset($aAttributes['sid'])) {
			self::$arSections[] = $aAttributes['sid'];
		}
	}


	private function isSectionVisible($sSectionKey)
	{


		$aSection = self::$arLayout[$sSectionKey];
//check hide in:
		if (isset($aSection['hidein'])) {
			if (in_array(self::$page['name'], $aSection['hidein'])) {
				return false;
			}
			if (isset($_GET['id'])) {
				if(in_array(self::$page['name'].'/'.$_GET['id'], $aSection['hidein'])) {
					return false;
				}
			}
		}

//check show in:
		if (isset($aSection['showin'])) {
			if (isset($_GET['id'])) {
				if (!in_array(self::$page['name'].'/'.$_GET['id'], $aSection['showin'])) {
					if (!in_array(self::$page['name'], $aSection['showin'])) {
						return false;
					}
				}
			}
			else {
				if (!in_array(self::$page['name'], $aSection['showin'])) {
					return false;
				}
			}
		}


//check parent visibility:
		if ($aSection['parent'] === '') {
			return true;
		}
		else {
			return self::isSectionVisible($aSection['parent']);
		}
	}


/**
 * Get the content of the website body
 */
	private function getBodyContent()
	{

		$sReturn = '';
/*
		$aReturn = array();

		$aSwitch = array();
		foreach(self::$arLayout as $k => &$v) {
			if(!isset($v['type'])) {
				$v['type'] = 'box';
			}

			if (isset(self::$arLayout[$v['parent']]['show']) && !self::$arLayout[$v['parent']]['show']) {
				$v['show'] = false;
			}
			else {
				if ($v['parent'] !== '' && self::$arLayout[$v['parent']]['type'] === 'switch') {
					if ($v['name'] === self::$page['name']) {
						$v['show'] = true;
					}
					else {
						$v['show'] = false;
					}
				}
				else {
					$v['show'] = true;
				}
			}

			if($v['show'] === true) {
//				$sReturn .= self::getSection($v['name'], $v);
				if($v['parent'] === '') {
					$aReturn[] = $v['name'];
				}
				else {
					$aReturn[self::$arLayout[$v['parent']]['name']][] = $v['name'];
				}
			}
		}
*/
//		$attributes = array(
//				'itemStartTag' => '<div>',
//				'itemEndTag' => '</div>',
//				'groupStartTag' => '<ul>',
//				'groupEndTag' => '</ul>',
//				'pattern' => '<div id="%s" class="%s"><span class="name">%s</span></div>',
//				'defaultType' => 'box',
//				'defaultParent' => ''));

		$sReturn = '';
		$sLastParentKey = '';
		$sLastKey = '';
		$sLastParentType = '';

		foreach (self::$arLayout as $k => &$v) {
			if(self::isSectionVisible($k)) {
				if(!isset($v['type'])) {
					$v['type'] = 'box';
				}

				if(!isset($v['parent'])) {
					$v['parent'] = '';
				}

				if($v['type'] === 'item') {
					$v['wrapper'] = false;
				}

				if($v['parent'] !== $sLastKey) {
					if($sLastParentType !== 'item') {
						$sReturn .= '</div>';
					}
					while($v['parent'] !== $sLastParentKey) {
						$sReturn .= '</div>';
						$sLastParentKey = self::$arLayout[$sLastParentKey]['parent'];
					}
				}

				if($v['type'] === 'item') {
					$v['wrapper'] = false;
				}
				$v['close'] = false;
				$v['sid'] = $k;

				$sReturn .= self::getSection($v['name'], $v);
				$sLastKey = $k;
				$sLastParentKey = $v['parent'];
				$sLastParentType = $v['type'];
			}
		}

		$sReturn .= '</div>';
		while($sLastParentKey !== '') {
			$sReturn .= '</div>';
			$sLastParentKey = self::$arLayout[$sLastParentKey]['parent'];
		}
		//$sReturn .= '</div>';
		return($sReturn);
	}


	private function buildLayoutStyle() {

		$arStyleSections = array();
		foreach(self::$arSections  as $k => $v) {
			if(isset(self::$arLayout[$v]['style']) && self::$arLayout[$v]['style'] === true) {
				$arStyleSections[] = array(
					'file' => $_SERVER['DOCUMENT_ROOT']
						.DIRECTORY_SEPARATOR.'sections'
						.DIRECTORY_SEPARATOR.'style'
						.DIRECTORY_SEPARATOR.self::$arLayout[$v]['name'].'.css',
					'location' => 'relative');
			}
		}
		return($arStyleSections);
	}


/**
 * Output a formated html(5) page
 * @param (array) $aAttributes = page attributes:
 *  @attribute lang <i>string</i>: language; default value: 'en-EN'
 *  @attribute metaCharset<i>string</i>: the charset; default value: 'UTF-8'
 *  @attribute title <i>string</i>: The page title; default value: 'website'
 *  [...]
 */
	public function gate($aAttributes)
	{

		$aAttributes = self::applyDefault($aAttributes, array(
			'lang' => 'en-EN',
			'metaCharset' => 'UTF-8',
			'metaDescription' => 'website',
			'metaRobots' => 'noodp',
			'metaGooglebot' => 'index, follow',
			'title' => 'website',
			'style' => array(),
			'body' => 'website',
			'footer' => 'footer'));


//special pages logic
		if (self::$config['isInternalPage']) {
			$aAttributes['style']['screen'] = array(
				array(
					'file' => $_SERVER['DOCUMENT_ROOT'].'/includes/agate/modules/admin/style.css',
					'location' => 'relative'));
		}

		switch(self::$page['name']) {
			case 'admin':
				$sBodyContent = self::getSection('website');
				break;
			case 'post':
				if(isset($_GET['id'])) {
					$sBodyContent =
						'<div id="post'.$_GET['id'].'">'
							.self::dbLookup(
								"`content`",
								"`posts`",
								"`id` = ".$_GET['id'])
						.'</div>';
				}
				else {
					$sBodyContent = '<div id="post"></div>';
				}
				break;
			case 'section': //one page only with one section used for section preview
				if(isset($_GET['id'])) {
					$sBodyContent = self::getSection($_GET['id'], $_GET);
				}
				else {
					$sBodyContent = '<h1>Missing parameter: GET[id]!</h1>';
				}
				break;
			case 'login': //login page
				$sBodyContent = self::getSection('login');
				break;

			default:
				$sBodyContent = self::getBodyContent();
//				if(count(self::$arLayout) > 0) {
//					print_r($sBodyContent);
//					//echo($sBodyContent);
//				}
//				else {
//					self::section($aAttributes['body']);
//				}
				break;
		}

//the page attribute will overwrite the default attrs:
		foreach($aAttributes as $k => $v) {
			if (isset(self::$page[$k])) {
				if($k === 'javascript') {
					$aAttributes[$k] = array_merge_recursive($aAttributes[$k], self::$page[$k]);
				}
				else {
					$aAttributes[$k] = self::$page[$k];
				}
			}
		}

		header('Content-Type:text/html utf-8');
		echo('<!DOCTYPE html>');
		echo('<html lang="'.$aAttributes['lang'].'">'."\n");

		echo('<head>');
		echo('<meta http-equiv="content-type" content="text/html; charset='.$aAttributes['metaCharset'].'"/>');
		echo('<meta name="description" content="'.$aAttributes['metaDescription'].'"/>');
		echo('<meta name="robots" content="'.$aAttributes['metaRobots'].'"/>');
		echo('<meta name="googlebot" content="'.$aAttributes['metaGooglebot'].'"/>');
		if(isset($aAttributes['metaGoogleSiteVerification'])) {
			echo('<meta name="google-site-verification" content="'.$aAttributes['metaGoogleSiteVerification'].'" />');
		}
		echo('<title>'.$aAttributes['title'].'</title>');

		if(count(self::$arSections) > 0) {
			if(isset($aAttributes['style']['screen'])) {
				$aAttributes['style']['screen'] = array_merge_recursive($aAttributes['style']['screen'], self::buildLayoutStyle());
			}
			else {
				$aAttributes['style']['screen'] = self::buildLayoutStyle();
			}
		}

		if(count($aAttributes['style']) > 0) {
			if(isset($aAttributes['style']['screen'])) {
				$_SESSION['A_TMP_PAGE_STYLE_SCREEN'] = $aAttributes['style']['screen'];
				echo('<link type="text/css" href="/includes/agate/style.php?media=screen" rel="stylesheet" media="screen"/>');
			}

			if(isset($aAttributes['style']['print'])) {
				$_SESSION['A_TMP_PAGE_STYLE_PRINT'] = $aAttributes['style']['print'];
				echo('<link type="text/css" href="/includes/agate/style.php?media=print" rel="stylesheet" media="print"/>');
			}

			if(isset($aAttributes['style']['web'])) {
				$iMax = count($aAttributes['style']['web']);
				for($i=0; $i<$iMax; $i++) {
					echo('<link href="'.$aAttributes['style']['web'][$i].'" rel="stylesheet" type="text/css">');
				}
			}
		}
		echo('</head>'."\n");


//check if page has its own class
		if (isset(self::$page['class'])) {
			echo('<body class="'.self::$page['class'].'">');
		}
		else {
			echo('<body>');
		}

//if user is logged in display the user bar
		if (isset($_SESSION['agate']['user']['id']) && self::$page['name'] !== 'section' && self::$page['name'] !== 'post') {
			self::section('agate-onyx', array(
				'root' => $_SERVER['DOCUMENT_ROOT'].'/includes/agate/modules/admin/',
				'type' => 'php'));
			self::section('agate-onyx-separator', array(
				'type' => 'separator',
				'wrapper' => true));
//			echo('<div id="agate-onyx">');
//			include($_SERVER['DOCUMENT_ROOT'].'/includes/agate/modules/admin/sections/agate-onyx.php');
//			echo('</div>');
//			echo('<div id="agate-onyx-separator"></div>');
		}
		echo($sBodyContent);
//		echo('<div class="push"></div></div>');
//		self::section($aAttributes['footer']);

		if(isset($aAttributes['javascript']) && count($aAttributes['javascript']) > 0) {
			if(isset($aAttributes['javascript']['web'])) {
				$iMax = count($aAttributes['javascript']['web']);
				for($i=0; $i<$iMax; $i++) {
					echo('<script type="text/javascript" src="'.$aAttributes['javascript']['web'][$i].'"></script>');
				}
			}
//			if(isset($aAttributes['javascript']['google']) && isset($aAttributes['javascript']['google']['key'])) {
//				echo('<script type="text/javascript" src="https://www.google.com/jsapi?key='.$aAttributes['javascript']['google']['key'].'"></script>');
//			}
		}

		echo('<script type="text/javascript">');
		$iMax = count(self::$arJavaScriptsFromModules);
		if($iMax > 0) {

			for($i=0; $i<$iMax; $i++) {
				include_once($_SERVER['DOCUMENT_ROOT']
				.DIRECTORY_SEPARATOR.'includes'
				.DIRECTORY_SEPARATOR.'aGatePhp'
				.DIRECTORY_SEPARATOR.'modules'
				.DIRECTORY_SEPARATOR.self::$arJavaScriptsFromModules[$i]);
			}
		}
		$iMax = count(self::$arJavaScriptsFromSections);
		if($iMax > 0) {

			for($i=0; $i<$iMax; $i++) {
				include($_SERVER['DOCUMENT_ROOT']
					.DIRECTORY_SEPARATOR.'sections'
					.DIRECTORY_SEPARATOR.self::$arJavaScriptsFromSections[$i]);
			}
		}
		echo('</script>');

		echo('</body>'."\n");
		echo('</html>');

	}


/**
 * Output a menu item
 * @param (string) $sPageName - page name
 * @param (string) $sLabel - the menu label
 * @param (string) $sClass - the label class, default = ''
 * @param (string) $sPageCategory - the category of the page, default = ''
 * @param (bool) $bIsDefaultMenu - is the default menu, default = FALSE
 * @param (string) $sGetVar - the $_GET member used for check, default = 'page'
 */
	public static function pageMenuItem($sPageName, $sLabel, $sClass = '', $sPageCategory = '', $bIsDefaultMenu = false, $sGetVar='page') {


		if($sClass === '') {
			$sClass .= $sPageName;
		}
		if(
			@$_GET[$sGetVar] === $sPageName
			|| (@$_GET[$sGetVar] === $sPageCategory && @$_GET['id'] === $sPageName)
			|| (@$_GET[$sGetVar] === $sPageCategory && !isset($_GET['id']) && $bIsDefaultMenu === true)
			) {
			$sClass .= ' selected';
		}

		if($sClass !== '') {
			$sClass = 'class="'.$sClass.'"';
		}
		$sUrl = '/';
		if($sPageCategory !== '') {
			$sUrl .= $sPageCategory.'/';
		}
		$sUrl .= $sPageName.'.html';
		echo('<a href="'.$sUrl.'" '.$sClass.'>'.$sLabel.'</a>');
	}


/**
 * Output a 960 grid cell
 * @param (integer) $iGridSize = cell size (1 - 12|16)
 * @param (integer) $iGridPrefix = cell prefix
 * @param (integer) $iGridSuffix = cell suffix
 * @param (integer) $iGridPush = cell push
 */
	public static function gs($iGridSize = 0, $iGridPrefix = 0, $iGridSuffix = 0, $iGridPush = 0)
	{

		if($iGridSize > 0)
		{
			$sClass = 'grid_'.$iGridSize;
		}
		else{
			$sClass = 'clear';
		}
		if($iGridPrefix > 0)
		{
			$sClass .= ' prefix_'.$iGridPrefix;
		}
		if($iGridSuffix > 0)
		{
			$sClass .= ' suffix_'.$iGridSuffix;
		}
		if($iGridPush > 0)
		{
			$sClass .= ' push_'.$iGridPush;
		}
		if($iGridSize === 0)
		{
			echo('<div class="clear"></div>');
		}
		else
		{
			echo('<div class="'.$sClass.'">');
		}
	}


/**
 * Close a 960 grid cell
 */
	public static function gs_x()
	{

		self::$arDebug['calls'][] = 'gs_x';
		echo('</div>');
	}


/**
 * Clear for 960 grid
 */
	public static function gs_clear()
	{

		self::$arDebug['calls'][] = 'gs_clear';
		self::gs();
	}




//in testing:
	public static function arrayToString($array, $attributes = array())
	{

		$attributes = self::applyDefault($attributes, array(
			'fields' => array('key'),
			'where' => false));

		foreach ($array as $k => $v) {
			if($attributes['where'] !== false) {
				$bAddItem = $attributes['where']($k, $v);
			}
			else {
				$bAddItem = true;
			}
			if($bAddItem) {
				$arRow = array($k);
				foreach ($attributes['fields'] as $vf) {
					if(isset($array[$k][$vf])) {
						$arRow = array_merge($arRow, $array[$k][$vf]);
					}
					else {
						$arRow = array_merge($arRow, '');
					}
				}
				$aReturn['data'][] = array($k, $v['name']);
			}
		}
	}
/*
	public static function arrayToString($array, $attributes = array()) {

		$attributes = self::applyDefault($attributes, array(
//			'cellTag' => 'td',
//			'rowTag' => 'tr',
//			'rowPattern' => '%s',
//			'keys' => array('name'), //eg: array('name' => '-', 'type' => 'undefined')
//			'where' => false,
//			'order' => false
		));
		$sReturn = '';

		foreach ($array as $k => $v) {

/*
			if($attributes['where'] !== false) {
				$bAddItem = $attributes['where']($k, $v);
			}
			else {
				$bAddItem = true;
			}

			if($bAddItem) {
				$sReturn .= '<'.$attributes['rowTag'].'>';

				if(is_array($attributes['keys'])) {
					foreach($attributes['keys'] as $kp) {
						if(isset($v[$k][$kp])) {
							$sReturn .=
								'<'.$attributes['cellTag'].'>'
								.$v[$k][$kp]
								.'</'.$attributes['cellTag'].'>';
						}
					}
				}
				else {
					$sReturn .= vsprintf($attributes['pattern'], $v);
				}

				$sReturn .= '</'.$attributes['rowTag'].'>';
			}

		}
	}
*/

/**
 * Format a tree sctrucure of array into html string
 * @param (array) $tree = the tree array
 * @param (array) $attributes = formating attributes
 * @return (string)
 */
	public static function treeToString($tree, $attributes = array())
	{

		$attributes = self::applyDefault($attributes, array(
			'itemStartTag' => '<li>',
			'itemEndTag' => '</li>',
			'groupStartTag' => '<ul>',
			'groupEndTag' => '</ul>',
			'pattern' => '<div id="%s" class="%s"><span class="name">%s</span></div>',
			'defaultType' => 'box',
			'defaultParent' => ''));

		$sReturn = '';
		$sLastParentKey = '';
		$sLastKey = '';

		foreach ($tree as $k => &$v) {
			if(!isset($v['type'])) {
				$v['type'] = $attributes['defaultType'];
			}

			if(!isset($v['parent'])) {
				$v['parent'] = $attributes['defaultParent'];
			}

			if($v['parent'] === $sLastKey) {
				$sReturn .= $attributes['groupStartTag'];
			}
			else {
				$sReturn .= $attributes['itemEndTag'];
				while($v['parent'] !== $sLastParentKey) {
					$sReturn .= $attributes['groupEndTag'].$attributes['itemEndTag'];
					$sLastParentKey = $tree[$sLastParentKey]['parent'];
				}
			}

			$sReturn .= $attributes['itemStartTag'].sprintf($attributes['pattern'], $k, $v['type'], $v['name']);

			$sLastKey = $k;
			$sLastParentKey = $v['parent'];
		}

		$sReturn .= $attributes['itemEndTag'];
		while($sLastParentKey !== '') {
			$sReturn .= $attributes['groupEndTag'].$attributes['itemEndTag'];
			$sLastParentKey = $tree[$sLastParentKey]['parent'];
		}
		$sReturn .= $attributes['groupEndTag'];
		return($sReturn);
	}


/**
 * Check if the item from the tree has a specific parent
 * @param (array) $tree = the tree array
 * @param (string) $index = the index of the tree item
 * @param (string) $parent = the parrent index
 * @return (boolean)
 */
 	public static function treeItemHasParent($tree, $index, $parent) {

		while(isset($tree[$index]['parent']) && $tree[$index]['parent'] !== '') {
			if($tree[$index]['parent'] === $parent) {
				return true;
			}
			$index = $tree[$index]['parent'];
		}
		return false;
	}


/**
 * Reindex tree
 * @param (array refference) $tree
 */
 	public static function treeReIndex(&$tree) {

		self::$arDebug['calls'][] = 'treeReIndex';
		$newArray = array();
		$i = 0;
//rebuild the new index
		foreach ($tree as $k => $v) {
			$i++;
			$newArray['i'.$i] = $tree[$k];
			$tree[$k]['newId'] = 'i'.$i;
		}
//change the parents:
		foreach ($newArray as $k => &$v) {
			if(isset($newArray[$k]['parent']) && $newArray[$k]['parent'] !== '') {
				$newArray[$k]['parent'] = $tree[$newArray[$k]['parent']]['newId'];
			}
			else {
				$newArray[$k]['parent'] = '';
			}
		}
		$tree = $newArray;
	}


/**
 * Used to insert an item into an ORDERED tree, which have the structure based on parent attribute;
 * @param (Array) $tree = the tree;
 * @param (Array) $item = the element to be inserted;
 * @param (Array) $position = specify where to be positioned:
 * 	- (String) $position['key'] = refference key;
 * 	- (Integer) $position['pos'] = refference position can have values:
 * 		INSERT_BEFORE = 1 = isert before the element;
 * 		INSERT_AFTER = 2 = insert after the element;
 * 		INSERT_CHILD_FIRST = 3 = insert as first child;
 * 		INSERT_CHILD_LAST = 4 = insert as last child;
 * @return the new index;
 */
	public static function treeInsertItem(&$tree, $item, $position) {

		$index = 'new_'.a::guid();
		self::$arDebug['calls'][] = 'treeInsertItem';
		$newArray = array();

		if($position['pos'] === self::INSERT_BEFORE || $position['pos'] === self::INSERT_AFTER) {
			if(isset($tree[$position['key']]['parent'])) {
				$item['parent'] = $tree[$position['key']]['parent'];
			}
			else {
				$item['parent'] = '';
			}
		}
		else {
			$item['parent'] = $position['key'];
		}

		foreach ($tree as $k => &$v) {
			$newArray[$k] = $tree[$k];
			unset($tree[$k]);
			if($k === $position['key']) {
				break;
			}
		}
		if($position['pos'] === self::INSERT_BEFORE) {
			$tree[$k] = $newArray[$k];
			unset($newArray[$k]);
		}

		if($position['pos'] === self::INSERT_AFTER || $position['pos'] === self::INSERT_CHILD_LAST) {
			foreach ($tree as $k => &$v) {
				$newArray[$k] = $tree[$k];
				if(self::treeItemHasParent($newArray, $k, $position['key'])) {
					unset($tree[$k]);
				}
				else {
					unset($newArray[$k]);
					break;
				}
			}
		}

		$newArray = array_merge_recursive($newArray, array($index => $item));
		if($position['pos'] === self::INSERT_BEFORE) {
			 $newArray[$k] = $tree[$k];
			unset($tree[$k]);
		}
		$newArray = array_merge_recursive($newArray, $tree);

		$tree = $newArray;
		return($index);
	}


	public static function guid()
	{

	//generate a Globally Unique Identifier, valid v4 RFC 4122
	//credits:The phunction PHP framework http://sourceforge.net/projects/phunction/
		if (function_exists('com_create_guid') === true) {
			return trim(com_create_guid(), '{}');
		}
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		//db version:
		//return mysql_result(mysql_query('SELECT UUID()'), TRUE);
	}



	public static function log($message, $attributes = array())
	{

		$attributes = self::applyDefault($attributes, array(
			'time' => true,
			'priority' => LOG_DEBUG,
			'system' => false,
			'session' => true,
			'request' => true
		));

		if(is_array($message)) {
			if($attributes['session']) {
				$message['session'] = $_SESSION;
			}
			if($attributes['request']) {
				$message['request'] = $_REQUEST;
			}
			$message = print_r($message, true);
		}

		$messageHeader = '>>'.date("Y/m/d H:i:s.u").' - ';
		$messageHeader .= @$_SERVER['REMOTE_ADDR'].'|'.@$_SERVER['HTTP_X_FORWARDED_FOR'].'|'.@$_SERVER['HTTP_CLIENT_IP'].' - ';
		$messageHeader .= $_SERVER['HTTP_USER_AGENT']."\n";

		$message = $messageHeader.$message."\n----------\n\n";

		if($attributes['system'] === TRUE) {
			syslog ($attributes['priority'] , $message);
			return;
		}

		$sFilename = $_SERVER['DOCUMENT_ROOT']
			.DIRECTORY_SEPARATOR.'includes'
			.DIRECTORY_SEPARATOR.'agate'
			.DIRECTORY_SEPARATOR.'logs'
			.DIRECTORY_SEPARATOR;

		switch($attributes['priority']) {
			case LOG_EMERG:// 	system is unusable
				$sFilename .= 'emerg_';
				break;
			case LOG_ALERT://	action must be taken immediately
				$sFilename .= 'alert_';
				break;
			case LOG_CRIT://	critical conditions
				$sFilename .= 'crit_';
				break;
			case LOG_ERR://	error conditions
				$sFilename .= 'err_';
				break;
			case LOG_WARNING://	warning conditions
				$sFilename .= 'warning_';
				break;
			case LOG_NOTICE:// 	normal, but significant, condition
				$sFilename .= 'notice_';
				break;
			case LOG_INFO://	informational message
				$sFilename .= 'info_';
				break;
			case LOG_DEBUG://	debug-level message
				$sFilename .= 'debug_';
				break;
			default:
				$sFilename .= 'unknonw_';
				break;
		}
		$sFilename .= date('Ymd').'.log';

		file_put_contents($sFilename, $message, FILE_APPEND | LOCK_EX);
	}
}
?>