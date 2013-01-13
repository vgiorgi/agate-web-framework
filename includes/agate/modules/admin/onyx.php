<?php
/**
* This file is a part of AGATE WEB framework
* http://agateweb.org/
*
* Onyx is 1st GUI for AGATE WEB framework
*
* Copyright (C) 2012 Vasile Giorgi
*
* Date: Mon May 7 16:18:21 2012
*
* dependencies:
* - modules:
*  |- core
*  |- auth
*  |- ajax/response
*  +- db/mysql
* - external libraries
*  |- jquery
*  +- codeMirror
**/

class aAdmin extends a
{
	public static function isOnyx() {
		if(isset($_GET['page']) && $_GET['page'] === 'admin') {
			self::$config['root'] =
				$_SERVER['DOCUMENT_ROOT']
				.DIRECTORY_SEPARATOR.'includes'
				.DIRECTORY_SEPARATOR.'agate'
				.DIRECTORY_SEPARATOR.'modules'
				.DIRECTORY_SEPARATOR.'admin';
//			self::$arSiteMap = array(
//					'admin' => array('default' => true));
			return true;
		}
		return false;

	}
}

if(aAdmin::isOnyx()) {
	$onyx = new aAdmin();
	$onyx -> gate( array(
		'lang' => 'ro-RO',
		'title' => 'Agate-website administration',
		'style' => array(
			'screen' => array(
				array(
					'file' => self::$config['root'].DIRECTORY_SEPARATOR.'style.css',
					'location' => 'relative'),
				array(
					'file' => $_SERVER['DOCUMENT_ROOT']
						.DIRECTORY_SEPARATOR.'includes'
						.DIRECTORY_SEPARATOR.'agate'
						.DIRECTORY_SEPARATOR.'modules'
						.DIRECTORY_SEPARATOR.'admin'
						.DIRECTORY_SEPARATOR.'3rd'
						.DIRECTORY_SEPARATOR.'CodeMirror'
						.DIRECTORY_SEPARATOR.'codemirror.css',
					'location' => 'relative'),
				array(
					'file' => $_SERVER['DOCUMENT_ROOT']
						.DIRECTORY_SEPARATOR.'includes'
						.DIRECTORY_SEPARATOR.'agate'
						.DIRECTORY_SEPARATOR.'modules'
						.DIRECTORY_SEPARATOR.'admin'
						.DIRECTORY_SEPARATOR.'3rd'
						.DIRECTORY_SEPARATOR.'jQuery'
						.DIRECTORY_SEPARATOR.'themes'
						.DIRECTORY_SEPARATOR.'simple'
						.DIRECTORY_SEPARATOR.'jquery-ui-1.8.20.custom.css',
					'location' => 'relative'))
//			'web' => array(
//				'/includes/aGatePhp/modules/admin/style.css',
//				'/includes/CodeMirror/lib/codemirror.css')
		),
		'javascript' => array(
			'web' => array(
//				'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js',//alternative: http://code.jquery.com/jquery.min.js
				'/includes/jQuery/1.7.1/jquery.min.js',
				'/includes/agate/agate.js',
				'/includes/agate/modules/admin/onyx.js',
				'/includes/agate/modules/admin/3rd/CodeMirror/codemirror.js'
			)
		)
	));
	die();
}
?>