<?php
a::$config['root'] = $_SERVER['DOCUMENT_ROOT']
	.DIRECTORY_SEPARATOR.'includes'
	.DIRECTORY_SEPARATOR.'agate'
	.DIRECTORY_SEPARATOR.'modules'
	.DIRECTORY_SEPARATOR.'auth';
/*
$login = new aAdmin();
$login -> gate( array(
	'lang' => 'en-US',
	'title' => 'Agate-website administration'));
*/
echo('test');
die();

?>