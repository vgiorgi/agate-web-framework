var agate = {
	auth: {
		on: {
			login: {
				submit: function (o) {
					$.ajax({
						type: 'POST',
						url: '/api.html',
						dataType: 'json',
						data: $('#form-login>form').serialize(),
						success: function (response) {
							if (response.success === true) {
								location = response.data.login.redirect;
							}
							else {
								$('#form-login>form .message').html(response.message);
							}
						}
					});
				}
			},
			logout: {
				submit: function (o) {

				}
			}
		}
	}
};