<?php
echo('
	<h1>Create a new account</h1>'
	.'<form method="post" action="javascript:onyx.on.submit.register()" id="formRegister">'
		.'<p><input type="hidden" name="get" value="register" /></p>'
		.'<p><label for="txtRegisterEmail">Email:</label><input type="email" name="email" id="txtRegisterEmail" tabindex="1" required="required"/></p>'
		.'<p><label for="pwdRegisterPassword">Password:</label><input type="password" name="password" id="pwdRegisterPassword" tabindex="2" required="required"/></p>'
		.'<p><label for="pwdRegisterPasswordConfirm">Confirm Password:</label><input type="password" name="passwordConfirm" id="pwdRegisterPasswordConfirm" tabindex="3" required="required"/></p>'
		.'<p><label for="txtRegisterFirstName">First Name:</label><input type="text" name="firstName" id="txtRegisterFirstName" tabindex="4" required="required"/></p>'
		.'<p><label for="txtLastName">Last Name:</label><input type="text" name="lastName" id="txtLastName" tabindex="5" required="required"/></p>'
		.'<p><label for="txtDisplayName">Display Name:</label><input type="text" name="displayName" id="txtDisplayName" tabindex="6" required="required"/></p>'
		.'<p><input type="submit" name="btnRegister" id="btnRegister" value="Sign Up" class="button" tabindex="7"/></p>'
	.'</form>'
	.'<div class="message"></div>'
	.'<div class="links">'
		.'<a href="/">Back to home</a>'
		.'&nbsp;|&nbsp;'
		.'<a href="javascript:;" onclick="$(\'#form-register\').hide();$(\'#form-login\').show()">Log In</a>'
		.'&nbsp;|&nbsp;'
		.'<a href="javascript:;" onclick="$(\'#form-register\').hide();$(\'#form-recover\').show()">Forgot your password?</a>'
	.'</div>');
?>
