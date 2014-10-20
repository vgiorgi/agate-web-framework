<?php
echo(
	'<h1>Sign in</h1>'
//	.'<p>Sign in using your registered account:</p>'
	.'<form method="post" action="javascript:onyx.on.submit.login()" id="formLogin">'
		.'<p><input type="hidden" name="get" value="login" /></p>'
		.'<p><label for="txtLoginEmail">Email:</label><input type="email" name="email" id="txtLoginEmail" tabindex="1" required="required"/></p>'
		.'<p><label for="pwdLoginPassword">Password:</label><input type="password" name="password" id="pwdLoginPassword" tabindex="2" required/></p>'
//		.'<p><input type="checkbox"  name="chkPersist" id="chkPersist" value="1" tabindex="4"/><label for="chkPersist" class="lblCheckbox">Stay logged in</label></p>'
		.'<p><input type="submit" name="btnLogin" id="btnLogin" class="button" value="Log In" tabindex="3"/></p>'
	.'</form>'
	.'<div class="message"></div>'
	.'<div class="links">'
		.'<a href="/">Back to home</a>&nbsp;|&nbsp;<a href="javascript:;" onclick="$(\'#form-login\').hide();$(\'#form-register\').show()">Register</a>'
		.'&nbsp;|&nbsp;'
		.'<a href="javascript:;" onclick="$(\'#form-login\').hide();$(\'#form-recover\').show()">Forgot your password?</a>'
	.'</div>');
?>
