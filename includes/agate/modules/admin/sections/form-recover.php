<?php
echo(
	'<h1>Password recovery</h1>'
	.'<form method="post" action="/includes/agate/api.php?call=register">'
		.'<p><label for="txtRecoverEmail">Email:</label><input type="email" name="email" id="txtRecoverEmail" tabindex="1"/></p>'
		.'<p><input type="submit" name="btnRecover" id="btnRecover" value="Recover password" class="button" tabindex="2"/></p>'
	.'</form>'
	.'<div class="links">'
		.'<a href="/">Back to home</a>'
		.'&nbsp;|&nbsp;'
		.'<a href="javascript:;" onclick="$(\'#form-recover\').hide();$(\'#form-login\').show()">Log in</a>'
		.'&nbsp;|&nbsp;'
		.'<a href="javascript:;" onclick="$(\'#form-recover\').hide();$(\'#form-register\').show()">Register</a>'
	.'</div>');
?>