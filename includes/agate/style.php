<?php
/**
 *Style compressor - part of <b>agate-web library</b>
 *@author Vasile Giorgi
 *@license lgpl
 *@copyright 2010 (c) Vasile Giorgi
 *@version 0.12.0511
 **/
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
header('Content-type: text/css');
header("Pragma: public");
//header("Cache-Control: maxage=8640000");
//header('Expires: ' . gmdate('D, d M Y H:i:s', time()+8640000) . ' GMT');
ob_start("compress");

echo('@CHARSET "UTF-8";'."\n");


function compress($buffer) {
//	$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer); //remove comments
//	$buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer); //remove tabs, spaces, newlines, etc.
	return $buffer;
}


function includeStyle($file, $type)
{
//debug{
	echo("\n".'/*STYLE:'.$file.'*/');
//}

	switch($type)
	{
		case 'static':
			include(
				$_SERVER['DOCUMENT_ROOT']
				.DIRECTORY_SEPARATOR.'static'
				.DIRECTORY_SEPARATOR.'css'
				.DIRECTORY_SEPARATOR.$file);
			break;
		case 'relative':
			include($file);
			break;
	}
}

if(isset($_SESSION['A_TMP_PAGE_STYLE_'.strtoupper($_GET['media'])])) {
	$aStyle = $_SESSION['A_TMP_PAGE_STYLE_'.strtoupper($_GET['media'])];
	if (is_array($aStyle)) {
		$iMax = count($aStyle);
		for($i=0; $i<$iMax; $i++) {
			includeStyle($aStyle[$i]['file'], $aStyle[$i]['location']);
		}
	}
	unset($_SESSION['A_TMP_PAGE_STYLE_'.strtoupper($_GET['media'])]);
}

if (isset($_SESSION['agate']['user']['id'])) {
	includeStyle($_SERVER['DOCUMENT_ROOT'].'/includes/agate/modules/admin/sections/style/agate-onyx.css', 'relative');
}

ob_end_flush();
?>