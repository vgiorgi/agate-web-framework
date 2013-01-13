<?php
a::$config['pages'] = array (
	'i1' => array (
		'name' => 'admin', //onyx?
		'parent' => '',
		'default' => true,
//		'title' => 'Agate Web Framework - ONYX -',
		'security' => 'BC747117-7BB4-498A-BB3F-8EE7D25CE65D',
		'javascript' => array(
			'web' => array(
				'0' => '/includes/agate/modules/admin/3rd/jQuery/jquery-1.7.2.min.js',
				'1' => '/includes/agate/modules/admin/3rd/jQuery/jquery-ui-1.8.20.custom.min.js',
				'2' => '/includes/agate/modules/admin/3rd/jQuery/jquery-ui-i18n.js',
				'3' => '/includes/agate/agate.js',
				'4' => '/includes/agate/modules/admin/onyx.js',
				'5' => '/includes/agate/modules/admin/3rd/CodeMirror/codemirror.js'
			)
		)
	),
	'i2' => array (
		'name' => 'login', //including register and password recovery forms
		'parent' => '',
		'title' => 'Agate Login',
		'javascript' => array(
			'web' => array(
//			'0' => '/includes/jQuery/1.7.1/jquery.min.js',
			'1' => '/includes/agate/agate.js',
			'2' => '/includes/agate/modules/admin/onyx.js',
			'3' => '/includes/agate/modules/admin/3rd/CodeMirror/codemirror.js'
			)
		)
	),
	'i3' => array (
		'name' => 'post',
		'parent' => '',
	),
	'i4' => array (
		'name' => 'section',
		'parent' => '',
	),
	'i5' => array(
		'name' => 'api',
		'parent' => ''
	),
	'i6' => array(
		'name' => 'page404',
		'parent' => ''
	)
);
?>