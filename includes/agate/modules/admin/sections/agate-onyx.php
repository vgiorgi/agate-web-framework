<?php
echo('<h1>Agate-web</h1>');
echo('<div class="main">');
if (isset($_GET['page'])) {
	if ($_GET['page'] === 'admin') {
		echo('<a href="/">Home</a>');
	}
	else {
		echo('<a href="/admin.html">Admin</a>');
	}
}
echo('</div>');
echo('<div class="user">');
if (isset($_SESSION['agate']['user']['name'])) {
	echo('<span>'.$_SESSION['agate']['user']['name'].'</span>');
}
echo('<a href="api.html?get=logout&amp;response=redirect">Log out</a>');
echo('</div>');