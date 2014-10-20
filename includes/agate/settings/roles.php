<?php
//the users roles:
a::$config['roles'] = array (
	'i0' => array (
		'name' => 'Super user',
		'key' => '710C1C00-8217-4A4D-98B3-20A42A13BCF8'),
	'i1' => array (
		'name' => 'Administrator', //Somebody who has access to all the administration features
		'key' => '0638FC6F-67D1-438C-AA83-D38AEC41E5CD'),
	'i2' => array (
		'name' => 'Editor', //Somebody who can publish and manage posts and pages as well as manage other users' posts, etc.
		'key' => '4FC6FFFB-3C92-49BC-883D-400C587A6B9C'),
	'i3' => array (
		'name' => 'Author', //Somebody who can publish and manage their own posts
		'key' => '9FB63BBD-E598-4D2E-BDE0-7B203B62EE19'),
	'i4' => array (
		'name' => 'Contributor', //Somebody who can write and manage their posts but not publish them
		'key' => 'D3DB5712-7F25-4A08-8711-C2061862E7D6'),
	'i5' => array (
		'name' => 'Subscriber', //Somebody who can only manage their profile and receive news
		'key' => '49D3768C-1BD0-42B4-B6E5-63A2376BEB1F')
);
?>