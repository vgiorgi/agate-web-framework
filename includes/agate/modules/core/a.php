<?php
session_start();
/**
 *<b>a::gate web framework</b>
 *module::core::a = the most performant core, used in production mode
 *@author Vasile Giorgi
 *@license lgpl
 *@copyright 2010 (c) Vasile Giorgi
 *@version a.120.421
 */
class a
{
	const VERSION = '2.1.15';
	const INSERT_BEFORE = 1;
	const INSERT_AFTER = 2;
	const INSERT_CHILD_FIRST = 3;
	const INSERT_CHILD_LAST = 4;

	private static $arJavaScriptsFromSections = array();
	private static $arSections = array();
	private static $arStyleSections = array();
	private static $_arNewStatic = ""; //used to extend static methodes for the main class;

	public static $config = array(
		'name' => 'website',
		'homepage' => 'index.html',
		'showErrors' => true,
		'locale' => 'en-EN',
		'protocol' => 'http');
	public static $arSiteMap = array();
	public static $arLayout = array();
	public static $arJavaScriptsFromModules = array();
	public static $page = '';


	/**
	 *
	 * The constructor class, call self::init() to be sure it is initialized also with static init call;
	 */
	public function __construct()
	{
		self::init();
	}


	/**
	 *
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
	 *
	 * Used in modules to add static metodes to main a class
	 * @param string $name - the name of the new method in a class
	 * @param string $callback - the call made for the new method
	 */
	public static function addStaticMethod($name, $callback)
	{
		self::$arDebug['calls'][] = 'addStaticMethod';
		self::$_arNewStatic[$name] = $callback;
	}


	/**
	 * Used for initialization and for static calls
	 */
	public static function init()
	{
		self::$arDebug['calls'][] = 'init';
		$arSpecialPages = array('admin', 'section');

//redirects:
		if(isset($_GET['page'])) {
			self::$page = $_GET['page'];

			if(in_array(self::$page, $arSpecialPages)) {
				return;
			}
//-check if page exist in site map:
			foreach (self::$arSiteMap as $k => $v) {
				if($v['name'] === self::$page) {
					return;
				}
			}
//-page not forund in site map => redirect to default page:
			foreach (self::$arSiteMap as $k => $v) {
				if(array_key_exists('default', $v)) {
					header('location:'.$v['name'].'.html');
					//exit();
					return;
				}
			}

//-default page is not defined
			die(404);
		}
		else {
//	search for default page:
			foreach (self::$arSiteMap as $k => $v) {
				if(array_key_exists('default', $v)) {
					self::$page = $v['name'];
					return;
				}
			}
			die(404);
		}
	}


/**
 * Load cache file
 * @param string $filename = filename,
 * 	it must have php extension and exist in root/cache/ folder
 */
	 public static function cacheLoad($filename) {
		self::$arDebug['calls'][] = 'cacheLoad';
		require_once(self::$config['root'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$filename.'.php');
	}


/**
 * Save cache file
 * @param sting $sFile
 */
	public static function cacheSave($sFile) {
		self::$arDebug['calls'][] = 'cacheSave';
		$sFileName = self::$config['root'].DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$sFile.'.php';
		switch ($sFile) {
		case 'sections':
			$sFileContent = "<?php\na::\$arLayout = ".var_export(a::$arLayout, true).";\n?>";
			break;
		case 'pages':
			$sFileContent = "<?php\na::\$arSiteMap = ".var_export(a::$arSiteMap, true).";\n?>";
			break;
		}
		return(file_put_contents($sFileName, $sFileContent, LOCK_EX));
/*or:
		if (is_writable($sFileName)) {
			if (!$handle = @fopen($sFileName, 'w')) {
				echo 'Cannot open file ('.$sFileName.')';
				return false;
			}
			if (fwrite($handle, $sFileContent) === FALSE) {
				echo 'Cannot write to file ('.$sFileName.')';
				return false;
			}
			fclose($handle);
			return true;
		}
		else {
			echo 'The file '.$filename.' is not writable';
			return false;
		}
*/
	}
/**
 *
 * aGatePhp Library info
 */
	public static function info() {
		self::$arDebug['calls'][] = 'info';
		echo('<h1>a::lib</h1><ul><li><span>version:</span><span>'.self::VERSION.'</span></li></ul>');
		echo('<pre>'.print_r(self::$arSections, true).'</pre>');
	}

	/**
	 *Module method, used to include a a class module
	 * @param string $sModuleName - module name
	 * @tutorial standard modules are maintened and stored in modules folder.
	 */
	public static function module($sModuleName)
	{
		self::$arDebug['calls'][] = 'module';
		$arModule = explode(':', $sModuleName);
		$iMax = count($arModule);
		$sModulePath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'aGatePhp'.DIRECTORY_SEPARATOR.'modules';
		for($i=0; $i<$iMax; $i++) {
			$sModulePath .= DIRECTORY_SEPARATOR.$arModule[$i];
		}
		$sModulePath .= '.php';
		require_once $sModulePath;
		self::$arDebug['modules'][] = $sModulePath;
	}


	public static function getSection($sSectionName, $aAttributes = array())
	{
		self::$arDebug['calls'][] = 'getSection';
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
		self::$arDebug['calls'][] = 'section';
		$aAttributes = self::applyDefault($aAttributes, array(
				'class' => 'section',
				'id' => $sSectionName,
				'javascript' => array(),
				'type' => 'php',
				'wrapper' => true
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
				$aAttributes['id'] = 'menu'.$aAttributes['id'];
				break;
			default:
				$sExtension = '.php';
				if($aAttributes['class'] !== 'section') {
					$aAttributes['class'] .= ' section';
				}
		}

		if($aAttributes['wrapper']) {
			echo('<div id="'.$aAttributes['id'].'" class="'.$aAttributes['class'].'">');
		}

		switch($aAttributes['type']) {
		case 'html':
		case 'php':
			include(
				self::$config['root']
				.DIRECTORY_SEPARATOR.'sections'
				.DIRECTORY_SEPARATOR.$sSectionName.$sExtension);
			break;
		case 'item':
			echo('<pre>ITEM:'.$sSectionName.'</pre>');
			break;

		case 'box':
		default:
//do nothing;
			break;
		}
/*
		if($aAttributes['type'] === 'menu') {
			if(!isset($aAttributes['default'])) {
				$aAttributes['default'] = '';
			}
			if(!isset($aAttributes['parent'])) {
				$aAttributes['parent'] = '';
			}
	/*		foreach ($aAttributes['children'] as $menuTile => $menuLabels) {
				if($menuTile === $aAttributes['default']) {
					$bIsDefault = true;
				}
				else {
					$bIsDefault = false;
				}
				a::pageMenuItem(
					$menuTile,
					vsprintf($aAttributes['pattern'], $menuLabels),
					'',
					$aAttributes['parent'],
					$bIsDefault);
			}

		}
		else {

		}
*/
		if($aAttributes['wrapper']) {
			echo('</div>');
		}

		if(count($aAttributes['javascript']) > 0) {
			self::$arJavaScriptsFromSections[] = $aAttributes['javascript'];
		}
		if(!in_array($sSectionName, self::$arSections)) {
			self::$arSections[] = $aAttributes['id'];
		}
	}


/**
 * Private method to apply default values to an array, mainly used for arrays which can't have specific default values defined
 */
	public static function applyDefault($aAttributes, $aDefaultAttributes)
	{
		self::$arDebug['calls'][] = 'applyDefault';
		$aDefaultKeys = array_keys($aDefaultAttributes);
		$iMax = count($aDefaultKeys);
		for($i=0; $i<$iMax; $i++)
		{
			if(!array_key_exists($aDefaultKeys[$i], $aAttributes))
			{
				$aAttributes[$aDefaultKeys[$i]] = $aDefaultAttributes[$aDefaultKeys[$i]];
			}
		}
		return($aAttributes);
	}

//this will be depricated, because the style will be integrated into the section istelf
	private function buildLayoutStyle() {
		self::$arDebug['calls'][] = 'buildLayoutStyle';
		foreach(self::$arSections  as $k => $v) {
			if(!isset($v['type'])) {
				$v['type'] = 'box';
			}

			switch ($v['type']) {
			case 'box':
			case 'switch':
			case 'widget':
//case 1 = the style must be specified to be included:
				if(isset($v['style']) && $v['style'] === true) {
					self::$arStyleSections[] = $v['name'];
				}
				break;
			case 'html':
			case 'menu':
			case 'php':
//case 2 = the style is included by default, if it is spefified (false) will not be included;
				if(!isset($v['style']) || $v['style'] === true) {
					self::$arStyleSections[] = $v['name'];
				}
				break;
//case 3 = no style for this section:
			case 'item':
				//no style for this section type
				break;
			}
		}
	}


	private function getBodyContent()
	{
		self::$arDebug['calls'][] = 'getBodyContent';
		$sReturn = '';

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
					if ($v['name'] === self::$page) {
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
				$sReturn .= self::getSection($v['name'], $v);
			}
		}

		return($sReturn);
	}


	private function buildLayoutStyleOld($sSectionName, $arSection) {
		self::$arDebug['calls'][] = 'buildStyleOld';
		if(!isset($arSection['type'])) {
			$arSection['type'] = 'box';
		}

		switch($arSection['type']) {
		case 'box':
			if(isset($arSection['style']) && $arSection['style'] === true) {
				self::$arStyleSections[] = $sSectionName;
			}
			if (isset($arSection['children']) && count($arSection['children']) > 0) {
				foreach($arSection['children'] as $k => $v) {
					self::buildLayoutStyle($k, $v);
				}
			}
			break;
		case 'html':
		case 'php':
			if(!isset($arSection['style']) || $arSection['style'] === true) {
				self::$arStyleSections[] = $sSectionName;
			}
			break;
		case 'menu':
			self::$arStyleSections[] = 'menu.'.$sSectionName;
			break;
		case 'mnu':
			self::$arStyleSections[] = 'menu.'.$sSectionName;
			break;
		case 'list':
		case 'switch':
			if(isset($arSection['style']) && $arSection['style'] === true) {
				self::$arStyleSections[] = $sSectionName;
			}
			if (isset($arSection['children']) && count($arSection['children']) > 0) {
				if(array_key_exists(self::$page, $arSection['children'])) {
					self::buildLayoutStyle(self::$page, $arSection['children'][self::$page]);
				}
			}

//			if(isset($arSection['style']) && $arSection['style'] === true) {
//				self::$arStyleSections[] = $sSectionName;
//			}

			break;
		}
	}


/**
 *
 * Enter description here ...
 * @param unknown_type $sSectionName
 * @param unknown_type $arSection
 */
	private function buildLayoutSection($sSectionName, $arSection) {
		self::$arDebug['calls'][] = 'buildLayoutSection';
		$arDebug['layoutSection'][] = array('name' => $sSectionName, 'section' => $arSection);
		if(isset($arSection['showin'])) {
			if(!in_array(self::$page, $arSection['showin'])) {
				return;
			}
		}
		if (!isset($arSection['type'])) {
			$arSection['type'] = 'box';
		}
		$sClass = '';
		if(isset($arSection['class'])) {
			$sClass .= $arSection['class'].' ';
		}
		$sId = '';
		switch($arSection['type']) {
		case 'box':
			$sClass .= 'section-box';
			if(isset($arSection['style'])) {
				$sId = 'id="'.$sSectionName.'" ';
			}
			echo('<div '.$sId.' class="'.$sClass.'">');
			if (isset($arSection['children']) && count($arSection['children']) > 0) {
				foreach($arSection['children'] as $k => $v) {
					self::buildLayoutSection($k, $v);
				}
			}
			echo('</div>');
			break;
		case 'php':
			self::section($sSectionName, $arSection);
			break;
		case 'html':
			self::section($sSectionName, $arSection);
			break;
		case 'mnu':
			$sClass .= 'section-menu';
			$sId='menu'.$sSectionName;
			echo('<div id="'.$sId.'" class="'.$sClass.'">');
			include($_SERVER['DOCUMENT_ROOT']
				.DIRECTORY_SEPARATOR.'sections'
				.DIRECTORY_SEPARATOR.'menu.'.$sSectionName.'.php');
			echo('</div>');
			break;
		case 'menu':
			self::section($sSectionName, $arSection);
			break;
		case 'list':
//			$sClass .= ' section-list';
//			echo('<div id="'.$sSectionName.'" class="'.$sClass.'">list:'.$sSectionName.'</div>');
			break;
		case 'switch':
			$sClass .= 'section-switch';
			echo('<div id="'.$sSectionName.'" class="'.$sClass.'">');
			if (isset($arSection['children']) && count($arSection['children']) > 0) {
				if(array_key_exists(self::$page, $arSection['children'])) {
					//switch based by $_GET[page]
					self::buildLayoutSection(self::$page, $arSection['children'][self::$page]);
				}
				else {
					if(isset($_GET['id'])) {
						if(array_key_exists($_GET['id'], $arSection['children'])) {
							self::buildLayoutSection(self::$page.'-'.$_GET['id'], $arSection['children'][$_GET['id']]);
						}
					}
					else {
						//search for default section...
						foreach ($arSection['children'] as $k => $v) {
							if(array_key_exists('default', $v) && $v['default'] === true) {
								self::buildLayoutSection(self::$page.'-'.$k, $arSection['children'][$k]);
								return;
							}
						}
					}
				}
			}
			echo('</div>');
			break;
		}
//		self::section($arSection);
	}


	/**
	 * Output a formated html(5) page
	 * @param array $aAttributes = page attributes:
	 *  @attribute lang <i>string</i>: language; default value: 'en-EN'
	 *  @attribute metaCharset<i>string</i>: the charset; default value: 'UTF-8'
	 *  @attribute title <i>string</i>: The page title; default value: 'website'
	 *  [...]
	 */
	public function page($aAttributes)
	{
		self::$arDebug['calls'][] = 'page';
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

		$sBodyContent = self::getBodyContent();

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
//			foreach(self::$arLayout as $k => $v) {
//				self::buildLayoutStyle($k, $v);
//			}
			$iMax = count(self::$arSections);
			if($iMax > 0) {
				for ($i = 0; $i < $iMax; $i++) {
					$arStyle [] = array(
						'file' => $_SERVER['DOCUMENT_ROOT']
							.DIRECTORY_SEPARATOR.'sections'
							.DIRECTORY_SEPARATOR.'style'
							.DIRECTORY_SEPARATOR.self::$arSections[$i].'.css',
						'location' => 'relative');
				}
				if(isset($aAttributes['style']['screen'])) {
					$aAttributes['style']['screen'] = array_merge_recursive($aAttributes['style']['screen'], $arStyle);
				}
				else {
					$aAttributes['style']['screen'] = $arStyle;
				}
			}
		}

		if(count($aAttributes['style']) > 0) {
			if(isset($aAttributes['style']['screen'])) {
				$_SESSION['A_TMP_PAGE_STYLE_SCREEN'] = $aAttributes['style']['screen'];
				echo('<link type="text/css" href="/includes/aGatePhp/style.php?media=screen" rel="stylesheet" media="screen"/>');
			}

			if(isset($aAttributes['style']['print'])) {
				$_SESSION['A_TMP_PAGE_STYLE_PRINT'] = $aAttributes['style']['print'];
				echo('<link type="text/css" href="/includes/aGatePhp/style.php?media=print" rel="stylesheet" media="print"/>');
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
		if (isset(self::$arSiteMap[self::$page]['class'])) {
			echo('<body class="'.self::$arSiteMap[self::$page]['class'].'">');
		}
		else {
			echo('<body>');
		}

		switch(self::$page) {//special sections logic
			case 'section': //one page only with one section used for section preview
				if(isset($_GET['id'])) {
					self::section($_GET['id'], $_GET);
				}
				else {
					echo('<h1>Missing parameter: GET[id]!</h1>');
				}
				break;
			default:
				if(count(self::$arLayout) > 0) {
					echo($sBodyContent);
				}
				else {
					self::section($aAttributes['body']);
				}
				break;
		}

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
			echo('/*Modules*/');
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
			echo('/*Sections*/');
			for($i=0; $i<$iMax; $i++) {
				include_once($_SERVER['DOCUMENT_ROOT']
				.DIRECTORY_SEPARATOR.'sections'
				.DIRECTORY_SEPARATOR.self::$arJavaScriptsFromSections[$i]);
			}
		}
		echo('</script>');

		echo('</body>'."\n");
		echo('</html>');
	}


	/**
	 * Enter description here ...
	 * @param string $sPageName
	 * @param string $sLabel
	 * @param string $sClass
	 * @param string $sGetVar
	 */
	public static function pageMenuItem($sPageName, $sLabel, $sClass='', $sPageCategory = '', $bIsDefaultMenu = false, $sGetVar='page') {
		self::$arDebug['calls'][] = 'pageMenuItem';
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
	 * Enter description here ...
	 * @param unknown_type $iGridSize
	 * @param unknown_type $iGridPrefix
	 * @param unknown_type $iGridSuffix
	 * @param unknown_type $iGridPush
	 */
	public static function gs($iGridSize = 0, $iGridPrefix = 0, $iGridSuffix = 0, $iGridPush = 0)
	{
		self::$arDebug['calls'][] = 'gs';
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
	 * Enter description here ...
	 */
	public static function gs_x()
	{
		self::$arDebug['calls'][] = 'gs_x';
		echo('</div>');
	}

	/**
	 * Enter description here ...
	 */
	public static function gs_clear()
	{
		self::$arDebug['calls'][] = 'gs_clear';
		self::gs();
	}


	public static function outputDebugBox($sDebugValue, $sTitle = FALSE)
	{
		self::$arDebug['calls'][] = 'outputDebugBox';
		echo('<div class="debug">');
		echo('<a href="javascript:;" onclick="debug.utils.toggleContent(this)">[+] Show</a>');
		if($sTitle)
		{
			echo('<h1>'.$sTitle.'</h1>');
		}
		echo('<pre class="content">'.$sDebugValue.'</pre>');
		echo('</div>');
	}


	public static function treeToString($array, $attributes = array())
	{
		self::$arDebug['calls'][] = 'treeToString';
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

		foreach ($array as $k => &$v) {
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
					$sLastParentKey = $array[$sLastParentKey]['parent'];
				}
			}

			$sReturn .= $attributes['itemStartTag'].sprintf($attributes['pattern'], $k, $v['type'], $v['name']);

			$sLastKey = $k;
			$sLastParentKey = $v['parent'];
		}

		$sReturn .= $attributes['itemEndTag'];
		while($sLastParentKey !== '') {
			$sReturn .= $attributes['groupEndTag'].$attributes['itemEndTag'];
			$sLastParentKey = $array[$sLastParentKey]['parent'];
		}
		$sReturn .= $attributes['groupEndTag'];
		return($sReturn);
	}


	public static function treeItemHasParent($array, $index, $parent) {
		self::$arDebug['calls'][] = 'treeItemHasParent';
		while(isset($array[$index]['parent']) && $array[$index]['parent'] !== '') {
			if($array[$index]['parent'] === $parent) {
				return true;
			}
			$index = $array[$index]['parent'];
		}
		return false;
	}


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
 * Used to insert an item into an ORDONED tree, which have the structure based on parent attribute;
 * @param (Array) $tree = the tree;
 * @param (Array) $item = the element to be inserted;
 * @param (Array) $position = specify where to be positioned:
 * 	- (String) $position['key'] = refference key;
 * 	- (Integer) $position['pos'] = refference position can have values:
 * 		INSERT_BEFORE = 1 = isert before the element;
 * 		INSERT_AFTER = 2 = insert after the element;
 * 		INSERT_CHILD_FIRST = 3 = insert as first child;
 * 		INSERT_CHILD_LAST = 4 = insert as last child;
 *
 */
	public static function treeInsertItem(&$tree, $item, $position) {
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

		$newArray = array_merge_recursive($newArray, array('x' => $item));
		if($position['pos'] === self::INSERT_BEFORE) {
			 $newArray[$k] = $tree[$k];
			unset($tree[$k]);
		}
		$newArray = array_merge_recursive($newArray, $tree);

		$tree = $newArray;
		//return($newArray);


//the logic for insert new element at the same level before the refference element:
/*
		if(isset($array[$position]['parent'])) {
			$arItem['parent'] = $array[$position]['parent'];
		}
		else {
			$arItem['parent'] = '';
		}
		$newArray = array();

		foreach ($array as $k => &$v) {
			if($k === $position) {
				break;
			}
			else {
				$newArray[$k] = $array[$k];
				unset($array[$k]);
			}
		}
		$newArray = array_merge_recursive($newArray, $arItem);
		$newArray = array_merge_recursive($newArray, $array);

		return($newArray);
*/

//the logic for insert new element at the same level after the refference element:
/*
		if(isset($array[$position]['parent'])) {
			$arItem['parent'] = $array[$position]['parent'];
		}
		else {
			$arItem['parent'] = '';
		}

		$newArray = array();

		foreach ($array as $k => &$v) {
			$newArray[$k] = $array[$k];
			unset($array[$k]);
			if($k === $position) {
				break;
			}
		}
		foreach ($array as $k => &$v) {
			$newArray[$k] = $array[$k];
			if(self::treeItemHasParent($newArray, $k, $position)) {
				unset($array[$k]);
			}
			else {
				unset($newArray[$k]);
				break;
			}
		}
		$newArray = array_merge_recursive($newArray, $arItem);
		$newArray = array_merge_recursive($newArray, $array);

		return($newArray);

*/
//the logic for insert new element as first child:
/*
 		$arItem['parent'] = $position;
		$newArray = array();

		foreach ($array as $k => &$v) {
			$newArray[$k] = $array[$k];
			unset($array[$k]);
			if($k === $position) {
				break;
			}
		}
		$newArray = array_merge_recursive($newArray, $arItem);
		$newArray = array_merge_recursive($newArray, $array);

		return($newArray);
*/



//the logic for insert new element as last child:
/*
		$arItem['parent'] = $position;
		$newArray = array();

		foreach ($array as $k => &$v) {
			$newArray[$k] = $array[$k];
			unset($array[$k]);
			if($k === $position) {
				break;
			}
		}
		foreach ($array as $k => &$v) {
			$newArray[$k] = $array[$k];
			if(self::treeItemHasParent($newArray, $k, $position)) {
				unset($array[$k]);
			}
			else {
				unset($newArray[$k]);
				break;
			}
		}
		$newArray = array_merge_recursive($newArray, $arItem);
		$newArray = array_merge_recursive($newArray, $array);

		return($newArray);
*/
	}
}
?>