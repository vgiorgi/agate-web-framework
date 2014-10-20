<?php
define ('LANG_REQ', 'lang');

class aexLocale extends a
{
	static private $lang = false;
	static private $langDef = 'en';
	static private $arLangs = array();

	public static function langDomain($domain)
	{
		if (!isset(self::$lang)) {
			self::$lang = self::$langDef;
		}

		if (isset($_REQUEST[LANG_REQ])) {
			self::$lang = $_REQUEST[LANG_REQ];
		}

		require_once __DIR__.'/'.langs.php;
		self::$arLangs = $a__arLangs;
		self::langSet(self::$lang);
		bindtextdomain($domain, $_SERVER['DOCUMENT_ROOT'].'/locale');
		bind_textdomain_codeset($domain, 'UTF-8');
		textdomain($domain);
	}


	public static function langSet($lang = false)
	{
		if ($lang === false) {
			$lang = self::$lang;
		}
		else {
			self::$lang = $lang;
		}

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			putenv('LC_ALL='.$lang); //needed on some systems(window$) - to check!
			putenv('LANGUAGE='.$lang); //needed on some systems(window$) - to check!
			setlocale(LC_ALL, $lang);
		}
		else {
			putenv('LC_ALL='.self::langFormat($lang, 'unix'));
			putenv('LANGUAGE='.self::langFormat($lang, 'unix'));
			setlocale(LC_ALL, self::langFormat($lang, 'unix'));
		}
		setlocale(LC_NUMERIC, 'C');
	}


	public static function langFormat($lang, $format)
	{
		switch ($format) {
		case 'unix':
			$sReturn = self::$arLangs['regional'].'.utf8';
			break;
		case 'uRegional':
			$sReturn = str_replace('-', '_', self::$arLangs['regional']);
			break;
		default:
			$sReturn = self::$arLangs[$format];
			break;
		}
		return ($sReturn);
	}


	public static function langParse($lang, $format = '')
	{
		foreach (self::$arLangs as $k => $v) {
			if ($v[$format] === $lang) {
				return ($k);
			}
		}
	}


	public static function langGet()
	{
		return (self::$lang);
	}
}


aexLocale::addStaticMethod('langDomain', 'aexLocale::langDomain');
aexLocale::addStaticMethod('langFormat', 'aexLocale::langFormat');
aexLocale::addStaticMethod('langGet', 'aexLocale::langGet');
aexLocale::addStaticMethod('langParse', 'aexLocale::langParse');
aexLocale::addStaticMethod('langSet', 'aexLocale::langSet');
?>