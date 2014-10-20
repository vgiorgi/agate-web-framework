<?php
/**
* dependencies:
* - modules:
*   - core
*   - ajax/response
*   - db/mysql
* - external libraries
*	- jquery
*	- codeMirror
**/

class aAdmin extends a
{
	public static function isAdmin() {
		if(isset($_GET['page']) && $_GET['page'] === 'admin') {
			self::$config['root'] =
				$_SERVER['DOCUMENT_ROOT']
				.DIRECTORY_SEPARATOR.'includes'
				.DIRECTORY_SEPARATOR.'aGatePhp'
				.DIRECTORY_SEPARATOR.'modules'
				.DIRECTORY_SEPARATOR.'admin';
//			self::$arSiteMap = array(
//					'admin' => array('default' => true));

			return true;
		}
		return false;

	}
}

if(aAdmin::isAdmin()) {
	$pageAdmin = new aAdmin();
	$pageAdmin -> page( array(
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
						.DIRECTORY_SEPARATOR.'CodeMirror'
						.DIRECTORY_SEPARATOR.'lib'
						.DIRECTORY_SEPARATOR.'codemirror.css',
					'location' => 'relative'))
//			'web' => array(
//				'/includes/aGatePhp/modules/admin/style.css',
//				'/includes/CodeMirror/lib/codemirror.css')
		),
		'javascript' => array(
			'web' => array(
				'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js',//alternative: http://code.jquery.com/jquery.min.js
				'/includes/aGatePhp/modules/agate.js',
				'/includes/aGatePhp/modules/admin/admin.js',
				'/includes/CodeMirror/lib/codemirror.js',
				'/includes/CodeMirror/mode/htmlembedded/htmlembedded.js',
				'/includes/CodeMirror/mode/xml/xml.js',
				'/includes/CodeMirror/mode/javascript/javascript.js',
				'/includes/CodeMirror/mode/css/css.js',
				'/includes/CodeMirror/mode/htmlmixed/htmlmixed.js'
			)
		)
	));
	die();
}

/*
if(a::isAdmin()) {
	$pageAdmin = new a();
	$pageAdmin -> page( array(
		'lang' => 'ro-RO',
		'title' => 'Agate-website administration',
		'style' => array(
			'web' => array(
				'/includes/aGatePhp/modules/admin/style.css'
			)
		)
	));
	die();
}
//die(7);
/*
if(isset($_GET['page']) && $_GET['page'] === 'admin') {
	a::$arSiteMap = array(
		'admin' => array('default' => true));
	$page = new a();

	$page -> page( array(
		'lang' => 'ro-RO',
		'title' => 'Agate-website administration',
		'metaDescription' => 'Agate admin',
		'metaRobots' => 'nofollow',
	//	'metaGoogleSiteVerification' => 'Ykb4hMvHuy5gaUly1IwZT6j0CF4_QtPS-ECFXo54y2o'),
		'style' => array(
//			'screen' => array(
//				array(
//					'file' => $_SERVER['DOCUMENT_ROOT']
//						.DIRECTORY_SEPARATOR.'modules'
//						.DIRECTORY_SEPARATOR.'admin'
//						.DIRECTORY_SEPARATOR.'style.css',
//					'location' => 'relative'),
//			)
//		)
//	),
	//		'print' => array(
	//			array(
	//				'file' => 'print.css',
	//				'location' => 'static')
	//			),
			'web' => array(
//				'http://fonts.googleapis.com/css?family=Open+Sans+Condensed:300&subset=latin,latin-ext',
	//			'/includes/jQueryUi/themes/onyx/jquery-ui-1.8.16.css',
				'/includes/aGatePhp/modules/admin/style.css'
			)
		),
		'javascript' => array(
			'web' => array(
				'https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js',//alternative: http://code.jquery.com/jquery.min.js
				'/includes/aGatePhp/modules/admin/admin.js'
//				'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js')
	//		'google' => array(
	//			'key' => 'ABQIAAAAQocjDTSRtcsijgpKgxOXrRRoOgi7g2w6yQXm7H3p8rUmVaxYRxRXL5I5Ve5aE-k5mqffvG5BLXTOvw')
		))
	)
	);
/*
	echo('<h1>Admin page</h1>');
	echo('<h2>page:'.a::$page.'</h2>');
	exit();
*/

?>