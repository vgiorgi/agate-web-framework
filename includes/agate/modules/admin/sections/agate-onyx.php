<?php
echo('<h1>Agate-web</h1>');
echo('<div class="main">');
if(@$_GET['page'] === 'admin') {
	echo('<a href="/">Home</a>');
}
else {
	echo('<a href="/admin.html">Admin</a>');
}
echo('</div>');
echo('<div class="user">');
echo('<span>Hi '.@$_SESSION['agate']['user']['name'].'</span>');
echo('<a href="api.html?get=logout&amp;response=redirect">Log out</a>');
echo('</div>');