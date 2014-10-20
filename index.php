<?php
require_once('config.php');

//include modules:
a::module('admin/onyx');
a::module('db/mysql');

a::cacheLoad('pages');
a::cacheLoad('sections');

$page = new a();

$page -> gate( array(
	'lang' => 'en-EN',
	'title' => 'Agate-Web Framework',
	'metaDescription' => 'meta description for website',
	'metaRobots' => 'description for robots',
//	'metaGoogleSiteVerification' => ''),
	'style' => array(
		'screen' => array(
			array(
				'file' => 'reset.css',
				'location' => 'static'),
			array(
				'file' => $_SERVER['DOCUMENT_ROOT']
					.DIRECTORY_SEPARATOR.'includes'
					.DIRECTORY_SEPARATOR.'gs960'
					.DIRECTORY_SEPARATOR.'gs960.css',
				'location' => 'relative'),
			array(
				'file' => 'website.css',
				'location' => 'static')
			),
//		'print' => array(
//			array(
//				'file' => 'print.css',
//				'location' => 'static')
//			),
		'web' => array(
			'http://fonts.googleapis.com/css?family=Open+Sans+Condensed:300&amp;subset=latin,latin-ext',
//			'/includes/jQueryUi/themes/onyx/jquery-ui-1.8.16.css',
//			'/includes/aGatePhp/modules/edit/onyx.css'
			)
		),
	'javascript' => array(
		'web' => array(
			'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'//alternative: http://code.jquery.com/jquery.min.js
//			'/includes/galleria/galleria-1.2.6.min.js'
//			'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js')
//		'google' => array(
//			'key' => '')
		))
	)
);
?>
