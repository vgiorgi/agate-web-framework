<?php
//debug{
require_once($_SERVER['DOCUMENT_ROOT'].'/includes/agate/modules/core/d.php');
a::$arDebug['time']['start'] = microtime(true);
a::$arDebug['time']['current'] = microtime(true);
//}

//production:
//require_once($_SERVER['DOCUMENT_ROOT'].'/includes/agate/modules/core/a.php');


//TODO: move following lines into /cache/settings.php
//WebSite general configuration
a::$config['name'] = 'Agate Web Framework';
a::$config['homepage'] = 'index.html'; //depricated - replaced by default from site map
a::$config['loginpage'] = 'login.html';
a::$config['404page'] = 'page404.html';
a::$config['showErrors'] = true;
a::$config['locale'] = 'ro-RO';
a::$config['protocol'] = 'http://';
a::$config['root'] = $_SERVER['DOCUMENT_ROOT'];

//modules configuration:
a::$config['db']['mysql']['server'] = 'dbServer';
a::$config['db']['mysql']['user'] = 'dbUser';
a::$config['db']['mysql']['password'] = 'dbPassword';
a::$config['db']['mysql']['database'] = 'dbDatabaseName';
?>