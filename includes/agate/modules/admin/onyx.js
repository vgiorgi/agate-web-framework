var onyx = {
	c: {//(pseudo)constants
		'MENU_DASHBOARD': 1,
		'MENU_PAGES': 2,
		'MENU_SECTIONS': 3,
		'MENU_POSTS': 4,
		'MENU_FILES': 5,
		'MENU_USERS': 6,
		'MENU_SETTINGS': 7,
		'MSG_SAVE_KO': 'An error occurred on save!',
		'MSG_SAVE_NOTHING': 'Nothing to save!',
		'MSG_SAVE_OK': 'Saving done!',
		'MSG_DELETE_OK': 'Delete Okay!'
	},
	cache: {
		data: {
			sectionsType: ['Box', 'Html', 'Item', 'Menu', 'Php', 'Post', 'Widget'],
			lastSectionId: 0
		},
		form: {
			section: {}
		}
	},
	message: function (oMessage) {
		if (oMessage['parent']) {
			jqParent = $(oMessage['parent']);
		}
		else {
			jqParent = $('#website');
		}
		if ($('#onyxMessage').length === 0) {
			jqParent.append('<div id="onyxMessage"></div>');
		}
		sMessage = '<div ';
		if (oMessage['class']) {
			sMessage += 'class="' + oMessage['class'] + '" ';
		}
		sMessage += '>' + oMessage.text + '<a href="javascript:;" onclick="$(this).parent().remove()" class="button">Close</a></div>';
		$('#onyxMessage').append(sMessage);

		if(typeof(oMessage.time) === 'number') {
			$('#onyxMessage>div:last').fadeOut(oMessage.time, function() {
				$(this).remove();
			});
		}
	},
	on: {
		blur: {
			postListItem: function (oTrigger) {
				switch(oTrigger.id) {
				case 'txtPostTitle':
					if($('#txtPermalink').val() === '' && $('#txtPostTitle').val() !== '') {
						sPermalink = $('#txtPostTitle').val();
						sPermalink = sPermalink.replace(/[^a-zA-Z0-9\s]/g, '');
						sPermalink = sPermalink.replace(/[\s]/g, '-');
						sPermalink = sPermalink.toLowerCase();
						$('#txtPermalink').val(sPermalink);
					}
					break;
				}
			}
		},
		click: {
			menu: function (oTrigger, cMenu) {
				$('#agate-onyx-menu a').removeClass('selected');
				$(oTrigger).addClass('selected');
				onyx.load.content($(oTrigger).text(), cMenu);
			},
			search: function(oTrigger) {
				var sItemText = '', sKey = $(oTrigger).val();
				$('#agate-onyx-content .table li').each(function () {
					sItemText = $(this).find('div>span.name').text();
					if (sItemText.search(sKey) === -1) {
						$(this).hide();
					}
					else {
						$(this).show();
					}
				});
			},
			tree: {
/*				search: function(oTrigger) {
					var sItemText = '', sKey = $(oTrigger).val();
					$('#agate-onyx-content>.tree>.table li').each(function () {
						sItemText = $(this).find('div>span.name').text();
						if (sItemText.search(sKey) === -1) {
							$(this).hide();
						}
						else {
							$(this).show();
						}
//						console.info({
//							item: sItemText,
//							key: sKey,
//							result: sItemText.search(sKey)
//						});
					});
				}
*/
			},
			lov: {
				pages: function (oTrigger) {
					var oWrapper = $(oTrigger).parent().get(0);
					$('#lov').remove();
					$(oWrapper).append(
							'<div id="lov">' +
								'<div class="actions">' +
									'<a href="javascript:;" onclick="$(this).parent().parent().remove()">Close</a>' +
								'</div>' +
								'<div class="table hidden"></div>' +
							'</div>');
					$.ajax({
						type: 'POST',
						url: '/includes/agate/modules/admin/admin.ajax.php',
						dataType: 'json',
						data: {
							get: 'lov',
							key: 'pages'
						},
						success: function (response) {
							$('#lov>.table').append(response.data.tree);
							$(oWrapper).children('div').children('span').each(function() {
								text = $(this).text();
								$('#lov>.table li>div>span.name').each(function(){
									if($(this).text() === text) {
										$(this).parent().remove();
									}
								});
							});
							$('#lov>.table li>div').click(function() {
								$(oWrapper).append(
									'<div>' +
										'<span class="new">' + $(this).children('span.name').text() + '</span>' +
										'<a href="javascript:;" onclick="$(this).parent().hide()">Delete</a>' +
									'</div>');
								$('#lov').remove();
							});
							$(oWrapper).find('#lov>.table').removeClass('hidden');
						}
					});
				}
			},
			postListItem: function (oTrigger, iPostAction) {
				if($('body>#mask').length === 0) {
					$('body').append('<div id="mask"></div>');
					$('#mask').height($('html').get(0).scrollHeight);
				}

				var oPost = {};
				if($(oTrigger).attr('id') !== undefined) {
					oPost['id'] = $(oTrigger).attr('id').substr(4);
				}
//				var oPost = {
//					'id': $(oTrigger).parent().attr('id'),
//					'name': $(oTrigger).siblings('.name').text(),
//					'type':  $(oTrigger).parent().attr('class')
//				};

				if(oTrigger.id === 'btnNewPost') { //new post
					var d = new Date(),
						sToday = $.datepicker.formatDate('yy-mm-dd', d);
						sToday += ' ' + d.getHours().toString().padLeft(6, '0') + ':00';
					$('#mask').append(
						'<form id="frmPostEdit" class="dialog new" action="javascript:onyx.on.click.postListItem(\'\', \'actionNew\');" >' +
						'<p class="title">Add new post:' +
							'<input type="text" name="txtPostTitle" id="txtPostTitle" required onblur="onyx.on.blur.postListItem(this)" />' +
						'</p>' +
/*						'<p>' +
							'<label for="txtPermalink">Permalink:</label>' +
							'<input type="text" name="txtPermalink" id="txtPermalink" pattern="[a-z][A-Za-z0-9-]+" />' +
							'<span>.html</span>' +
						'</p>' +
						'<div class="wrapperContent">' +
							'<div class="framePostPreviewWrapper">' +
								'<iframe id="framePostPreview" ' +
									'src="/post.html?id=' + oPost['id'] + '" ' +
									'onload="editDialog.load(this);">' +
								'</iframe>' +
							'</div>' +
							'<div class="codeEditor">' +
								'<span class="content">Content (HTML)</span>' +
								'<textarea id="txtPostContent" name="txtPostContent">loading...</textarea>' +
							'</div>' +
						'</div>' +
						'<p>' +
							'<label for="txtExcerpt">Excerpt:</label>' +
							'<textarea name="txtExcerpt" id="txtExcerpt"></textarea>' +
							'<span class="info">Small infromation from description used in list preview</span>' +
						'</p>' +
						'<p>' +
							'<label for="txtDate">Date:</label>' +
							'<input type="datetime-local" name="txtDate" id="txtDate" class="datepicker" value="' + sToday + '" />' +
							'<span class="info">format: yyyy-mm-dd hh:mm</span>' +
						'</p>' +
						//author, published: y/n, visibility: public/group, tags, picture(gallery)*/
						'<p class="message"></p>' +
						'<p class="actions">' +
							'<input type="submit" value="Save" />' +
							'<input type="button" onclick="onyx.on.click.postListItem(this, \'dialogCancel\')" value="Cancel" />' +
						'</p>' +
						'</form>');
					scroll(0, 0);

					editDialog = {
						load: function (o) { //the trigger is preview frame
							var docPreview = o.contentDocument || o.contentWindow.document;
							$(docPreview).find('body').attr('contenteditable', 'true');
							$(docPreview).find('body').blur(function () {
								$(this).children().removeAttr('style');
								$('#txtPostContent').next('.CodeMirror').get(0).CodeMirror.setValue($(this).html());
							});
							$('#txtPostContent').val($(docPreview).find('body').html());
//autoformat the code:
							cmHtmlEditor = CodeMirror.fromTextArea(document.getElementById('txtPostContent'), {
								mode: 'htmlmixed',
								tabMode: 'indent',
								lineNumbers: true,
								lineWrapping: true,
								onChange: function (cmEditor) {
									var elPreviewFrame = $('#framePostPreview').get(0);
									if(elPreviewFrame !== undefined) {
										var docPreview = elPreviewFrame.contentDocument || elPreviewFrame.contentWindow.document;
										$(docPreview).find('body').html(cmEditor.getValue());
									};
								}
							});
						}
					};
				}
				else {
					switch(iPostAction) {
					case 'actionDelete':
						var data = {
							'get': 'deletePost',
							'id': $('#hPostId').val()
						};
						$.ajax({
							type: 'POST',
							url: '/includes/agate/modules/admin/admin.ajax.php',
							dataType: 'json',
							async: false,
							data: data,
							success: function (response) {
								//console.info(response);
								$('#mask').remove();
							}
						});
						break;

					case 'actionEdit':
						var data = {};
//Title
						if($('#txtPostTitle').val() !== $('#txtPostTitle').get(0).defaultValue) {
							data['title'] = $('#txtPostTitle').val();
						}
//Permalink
						if($('#txtPermalink').val() !== $('#txtPermalink').get(0).defaultValue) {
							data['permalink'] = $('#txtPermalink').val();
						}
//Content
						if($('#txtPostContent').next('.CodeMirror').get(0) !== undefined && $('#txtPostContent').next('.CodeMirror').get(0).CodeMirror.getValue() !== $('#txtPostContent').next('.CodeMirror').get(0).CodeMirror.getOption('value')) {
							data['content'] = $('#txtPostContent').next('.CodeMirror').get(0).CodeMirror.getValue();
						}
//Date
						if($('#txtDate').val() !== $('#txtDate').get(0).defaultValue) {
							data['date'] = $('#txtDate').val();
						}
//Excerpt
						if($('#txtExcerpt').val() !== $('#txtExcerpt').get(0).defaultValue) {
							data['excerpt'] = $('#txtExcerpt').val();
						}
//metaList
						$('#flsMetaList>p>input').each(function (){
							if($(this).val() !== this.defaultValue) {
								data['metaList[' + this.id.substr(11) + ']'] = $(this).val();
							}
						});
//metaPost
						$('#flsMetaPost>p>input').each(function (){
							if($(this).val() !== this.defaultValue) {
								data['metaPost[' + this.id.substr(11) + ']'] = $(this).val();
							}
						});

						if ($.isEmptyObject(data)) {
							onyx.message({
								'class': 'warning',
								'parent': '#frmPostEdit>.message',
								'text': onyx.c.MSG_SAVE_KO
							});
						}
						else {
							data['get'] = 'updatePost';
							data['id'] = $('#hPostId').val();
							$.ajax({
								type: 'POST',
								url: '/includes/agate/modules/admin/admin.ajax.php',
								dataType: 'json',
								async: false,
								data: data,
								success: function (response) {
									if(data['title'] !== undefined) {
										$('#post_' + data['id'] + '>span:first').text(data['title']);
									}
									onyx.message({
										'class': 'info',
										'parent': '#frmPostEdit>.message',
										'text': onyx.c.MSG_SAVE_OK,
										'time': 4000
									});
									$('#mask').remove();
								},
								error: function() {
									onyx.message({
										text: onyx.c.MSG_SAVE_KO,
										'class': 'error',
										'parent': '#frmPostEdit>.message'});
								}
							});
						}
						break;

					case 'actionNew':
						var data = {
							'get': 'insertPost',
							'section': $('#hPostGroup').val(),
							'title': $('#txtPostTitle').val(),
							'permalink': $('#txtPermalink').val()
						};
						$.ajax({
							type: 'POST',
							url: '/includes/agate/modules/admin/admin.ajax.php',
							dataType: 'json',
							async: false,
							data: data,
							success: function (response) {
								//console.info(response);
								$('#mask').remove();
							}
						});
						break;

					case 'dialogCancel':
						$('#mask').remove();
						break;

					case 'dialogEdit':
						//console.info(this);
						iPostId = oTrigger.id.substr(5);
						$('#mask').append(
							'<form id="frmPostEdit" class="dialog edit" action="javascript:onyx.on.click.postListItem(\'\', \'actionEdit\');" >' +
								'<p class="title">Edit post:' +
									'<input type="text" name="txtPostTitle" id="txtPostTitle" required onblur="onyx.on.blur.postListItem(this)" />' +
								'</p>' +
								'<p>' +
									'<label for="txtPermalink">Permalink:</label>' +
									'<span class="url">' +
										window.location.protocol + '//'
										+ window.location.host + '/'
										+ $('#agate-onyx-content>.title>h2>.child').text() + '/' +
									'</span>' +
									'<input type="text" name="txtPermalink" id="txtPermalink" pattern="[a-z][A-Za-z0-9-]+" class="w3" />' +
									'<span class="url">.html</span>' +
								'</p>' +
								'<div class="wrapperContent">' +
									'<div class="framePostPreviewWrapper">' +
										'<iframe id="framePostPreview" ' +
											'src="/post.html?id=' + iPostId + '" ' +
											'onload="editDialog.load(this);">' +
										'</iframe>' +
									'</div>' +
									'<div class="codeEditor">' +
										'<span class="content">Content (HTML)</span>' +
										'<textarea id="txtPostContent" name="txtPostContent">loading...</textarea>' +
									'</div>' +
								'</div>' +
								'<p>' +
									'<label for="txtDate">Date:</label>' +
									'<input type="datetime-local" name="txtDate" id="txtDate" class="datepicker" value="" />' +
									'<span class="info">format: yyyy-mm-dd hh:mm</span>' +
								'</p>' +
								'<p>' +
									'<label for="txtExcerpt">Excerpt:</label>' +
									'<textarea name="txtExcerpt" id="txtExcerpt"></textarea>' +
								'</p>' +
//custom fields
								'<fieldset id="flsMetaList">' +
									'<legend>List items</legend>' +
								'</fieldset>' +
								'<fieldset id="flsMetaPost">' +
									'<legend>Post items</legend>' +
								'</fieldset>' +
								'<p>' +
									'<label class="w3">Author: <span class="author"></span></label>' +
									'<label class="w3">Last editor: <span class="lastEditor"></span></label>' +
									'<label class="w3">Last edit: <span class="lastEdit"></span></label>' +
								'</p>' +
								'<p class="message"></p>' +
								'<p class="actions">' +
									'<input type="text" name="hPostId" id="hPostId" value="' + iPostId + '"/>' +
									'<input type="submit" value="Update" />' +
									'<input type="button" onclick="onyx.on.click.postListItem(this, \'actionDelete\')" value="Delete" />' +
									'<input type="button" onclick="onyx.on.click.postListItem(this, \'dialogCancel\')" value="Cancel" />' +
								'</p>' +
								'</form>');
						editDialog = {
							load: function (o) { //the trigger is preview frame
								var docPreview = o.contentDocument || o.contentWindow.document;
								$(docPreview).find('#post' + iPostId).attr('contenteditable', 'true');
								$(docPreview).find('#post' + iPostId).blur(function () {
									$(this).children().removeAttr('style');
									$('#txtPostContent').next('.CodeMirror').get(0).CodeMirror.setValue($(this).html());
								});
								$('#txtPostContent').val($(docPreview).find('#post' + iPostId).html());
		//autoformat the code:
								cmHtmlEditor = CodeMirror.fromTextArea(document.getElementById('txtPostContent'), {
									mode: 'htmlmixed',
									tabMode: 'indent',
									lineNumbers: true,
									lineWrapping: true,
									onChange: function (cmEditor) {
										var elPreviewFrame = $('#framePostPreview').get(0);
										if(elPreviewFrame !== undefined) {
											var docPreview = elPreviewFrame.contentDocument || elPreviewFrame.contentWindow.document;
											$(docPreview).find('#post' + iPostId).html(cmEditor.getValue());
										};
									}
								});
							}
						};

//load data:
						$.ajax({
							type: 'POST',
							url: '/includes/agate/modules/admin/admin.ajax.php',
							dataType: 'json',
							async: false,
							data: {
								get: 'loadPostForm',
								post: iPostId
							},
							success: function (response) {
								$('#txtPostTitle').val(response.data.post.title);
								$('#txtPostTitle').get(0).defaultValue = response.data.post.title;
								$('#txtPermalink').val(response.data.post.permalink);
								$('#txtPermalink').get(0).defaultValue = response.data.post.permalink;
								if(response.data.post.date === null) {
									var d = new Date(),
										sToday = $.datepicker.formatDate('yy-mm-dd', d) + ' ' +
											d.getHours().toString().padLeft(6, '0') + ':00';
									$('#txtDate').val(sToday);
								}
								else {
									$('#txtDate').val(response.data.post.date);
									$('#txtDate').get(0).defaultValue = response.data.post.date;
								}
								$('#txtExcerpt').val(response.data.post.excerpt);
								$('#txtExcerpt').get(0).defaultValue = response.data.post.excerpt;
								$('#frmPostEdit .author').text(response.data.post.author);
								$('#frmPostEdit .lastEditor').text(response.data.post.lastEditor);
								$('#frmPostEdit .lastEdit').text(response.data.post.lastEdit);

								if(response.data.post.metaList !== undefined) {
									for(k in response.data.post.metaList) {
										$('#flsMetaList').append(
											'<p>' +
											'<label for="txtMetaList' + k + '">' + k + ':</label>' +
											'<input ' +
												'type="text" ' +
												'id="txtMetaList' + k + '" ' +
												'name="txtMetaList' + k + '" ' +
												'value="' + response.data.post.metaList[k] + '" />' +
											'</p>');
									}
								}

								if(response.data.post.metaPost !== undefined) {
									for(k in response.data.post.metaPost) {
										$('#flsMetaPost').append(
											'<p>' +
											'<label for="txtMetaPost' + k + '">' + k + ':</label>' +
											'<input ' +
												'type="text" ' +
												'id="txtMetaPost' + k + '" ' +
												'name="txtMetaPost' + k + '" ' +
												'value="' + response.data.post.metaPost[k] + '" />' +
											'</p>');
									}
								}
								//console.info(response);
								//$('#mask').remove();
							}
						});

						break;
					}
				}
//				var oPage = {
//					'id': $(oTrigger).parent().attr('id'),
//					'name': $(oTrigger).siblings('.name').text(),
//					'type':  $(oTrigger).parent().attr('class')
//				};
			},
			pagesListItem: function (oTrigger, iActionId, oTrigger2) {
				if($('body>#mask').length === 0) {
					$('body').append('<div id="mask"></div>');
					$('#mask').height($('html').get(0).scrollHeight);
				}
				var oPage = {
					'id': $(oTrigger).parent().attr('id'),
					'name': $(oTrigger).siblings('.name').text(),
					'type':  $(oTrigger).parent().attr('class')
				};

				switch(iActionId) {
				case 'actionDelete':
					$.ajax({
						type: 'POST',
						url: '/includes/agate/modules/admin/admin.ajax.php',
						dataType: 'json',
						async: false,
						data: {
							'get': 'deletePage',
							'key':  oPage.id
						},
						success: function (response) {
							$(oTrigger).parent().remove();
							$('#mask').remove();
						}
					});
					break;

				case 'actionDeleteSubpage':
					sSubpage = $(oTrigger2).parent().children('.name').text();
					if($('#flsSectionSubpages input[name=subpage][value=' + sSubpage + ']').length === 1) {
						$('#flsSectionSubpages input[name=subpage][value=' + sSubpage + ']').remove();
					}
					else {
						$('#flsSectionSubpages').append('<input type="hidden" name="delSubpage" value="' + sSubpage + '" />');
					}
					$(oTrigger2).parent().remove();
					break;

				case 'actionEdit':
					//check for dirty:
					data = {};
//name:
					if($('#txtPageName').val() !== $('#' + oPage.id + '>.name').text()) {
						data['name'] = $('#txtPageName').val();
					}

//title:
					if($('#txtPageTitle').val() !== $('#txtPageTitle').get(0).defaultValue) {
						data['title'] = $('#txtPageTitle').val();
					}

//class:
					if($('#txtPageClass').val() !== $('#txtPageClass').get(0).defaultValue) {
						data['class'] = $('#txtPageClass').val();
					}

//subpages:
					if($('#flsSectionSubpages>input[name=subpage]').length > 0) {
						i = 0;
						$('#flsSectionSubpages>input[name=subpage]').each(function(){
							data['subpage[add][' + i + ']'] = $(this).val();
							i++;
						});
					}
					if($('#flsSectionSubpages>input[name=delSubpage]').length > 0) {
						i = 0;
						$('#flsSectionSubpages>input[name=delSubpage]').each(function(){
							data['subpage[del][' + i + ']'] = $(this).val();
							i++;
						});
					}

//meta description:
					if($('#txtPageMetaDescription').val() !== $('#txtPageMetaDescription').get(0).defaultValue) {
						data['metaDescription'] = $('#txtPageMetaDescription').val();
					}

//meta robots:
					if($('#txtPageMetaRobots').val() !== $('#txtPageMetaRobots').get(0).defaultValue) {
						data['metaRobots'] = $('#txtPageMetaRobots').val();
					}

					if ($.isEmptyObject(data)) {
						onyx.message({
							text: onyx.c.MSG_SAVE_NOTHING,
							'class': 'warning'});
					}
					else {
						data['get'] = 'updatePage';
						data['key'] = oPage.id;
						$.ajax({
							type: 'POST',
							url: '/includes/agate/modules/admin/admin.ajax.php',
							dataType: 'json',
							async: false,
							data: data,
							success: function (response) {
								$('#' + oPage.id + '>.name').text($('#txtPageName').val());
								onyx.message({
									text: onyx.c.MSG_SAVE_OK,
									'class': 'info',
									time: 4000});
								$('#mask').remove();
							},
							error: function() {
								onyx.message({
									text: onyx.c.MSG_SAVE_NOTHING,
									'class': 'error'});
							}
						});
					}
					break;

				case 'actionNew':
					$.ajax({
						type: 'POST',
						url: '/includes/agate/modules/admin/admin.ajax.php',
						dataType: 'json',
						async: false,
						data: {
							'get': 'insertPage',
							'name': $('#txtPageName').val(),
							'pos': $('input[name=rdPagePosition]:checked').val(),
							'ref': oPage.id
						},
						success: function (response) {
							$('#agate-onyx-content .table').html(response.data.tree);
							$('#agate-onyx-content li>div').hover(onyx.on.hover.tree.pages.on, onyx.on.hover.tree.pages.out);
							$('#mask').remove();
						},
					});
					break;

				case 'actionNewSubpage':
					var sNewSubpage = $('#txtNewSubpage').val().replace(/ /g, '-');
					$('#txtNewSubpage').val(sNewSubpage);
					if($('#flsSectionSubpages>input[name=subpage][value=' + sNewSubpage + ']').length > 0) {
						onyx.message({
							'text': 'This subpage already exist!',
							'class': 'error',
							'parent': '#frmPageEdit>.message',
							'time': 3000
						});
						return;
					}
					if($("#flsSectionSubpages>ul>li>.name:contains('" + sNewSubpage + "')").length > 0) {
						onyx.message({
							'text': 'This subpage already exist!',
							'class': 'error',
							'parent': '#frmPageEdit>.message',
							'time': 3000
						});
						return;
					}

					$('#flsSectionSubpages>ul').append(
						'<li>' +
							'<span class="name">' + sNewSubpage + '</span>' +
							'<a href="javascript:;" onclick="onyx.on.click.pagesListItem($(\'#' + oPage.id + '>.name\').get(0), \'actionDeleteSubpage\', this)">Remove</a>' +
						'</li>');
					$('#flsSectionSubpages').append('<input type="hidden" name="subpage" value="' + sNewSubpage + '"/>');
					$('#txtNewSubpage').val('');
					break;

				case 'dialogCancel':
					$('#mask').remove();
					break;

				case 'dialogDelete':
					$('#mask').append(
						'<form class="dialog delete" action="javascript:onyx.on.click.pagesListItem($(\'#' + oPage.id + '>.name\').get(0), \'actionDelete\');" >' +
							'<p class="title">Remove page: ' + oPage.name + '.html</p>' +
							'<p>Are you sure?</p>' +
							'<p class="actions">' +
								'<input type="submit" value="Okay" />' +
								//'<input type="button" onclick="onyx.on.click.sectionListItem($(\'#' + oPage.id + '>.name\').get(0), \'deleteOk\')" value="Okay" />' +
								'<input type="button" onclick="onyx.on.click.sectionListItem(this, \'dialogCancel\')" value="Cancel" />' +
							'</p>' +
						'</form>');
					scroll(0, 0);
					break;

				case 'dialogEdit':
					$('#mask').append(
					'<form id="frmPageEdit" class="dialog edit" action="javascript:onyx.on.click.pagesListItem($(\'#' + oPage.id + '>.name\').get(0), \'actionEdit\');" >' +
					'<p class="title">Edit page' +
						'<span>.html</span>' +
						'<input type="text" name="txtPageName" id="txtPageName" pattern="[a-z][A-Za-z0-9\-_]+" required value="' + oPage.name + '"/>' +
					'</p>' +
					'<p>' +
						'<label for="txtPageClass">Class</label>' +
						'<input type="text" name="txtPageClass" id="txtPageClass" pattern="[a-z][A-Za-z0-9\-_]+" class="w3"/>' +
					'</p>' +
					'<p>' +
						'<label for="txtPageTitle">Title</label>' +
						'<input type="text" name="txtPageTitle" id="txtPageTitle" class="w3"/>' +
						'<span class="info">this is not mandatory, if is not set, the website title is used</span>' +
					'</p>' +
					'<fieldset id="flsSectionSubpages">' +
						'<legend>Sub pages</legend>' +
						'<p>' +
							'<input type="text" name="txtNewSubpage" id="txtNewSubpage" class="w4" />' +
							'<a href="javascript:;" onclick="onyx.on.click.pagesListItem($(\'#' + oPage.id + '>.name\').get(0), \'actionNewSubpage\')">Add subpage</a>' +
						'</p>' +
						'<ul></ul>' +
					'</fieldset>' +
					'<p>' +
						'<label for="txtPageMetaDescription">Meta description</label>' +
						'<textarea name="txtPageMetaDescription" id="txtPageMetaDescription" class="w3"></textarea>' +
						'<span class="info">this is not mandatory, if is not set, the website meta description is used</span>' +
					'</p>' +
					'<p>' +
						'<label for="txtPageMetaRobots">Meta robots</label>' +
						'<textarea name="txtPageMetaRobots" id="txtPageMetaRobots" class="w3"></textarea>' +
						'<span class="info">this is not mandatory, if is not set, the website meta robots is used</span>' +
					'</p>' +
					'<p class="message"></p>' +
					'<p class="actions">' +
						'<input type="submit" value="Save" />' +
						'<input type="button" onclick="onyx.on.click.sectionListItem(this, \'dialogCancel\')" value="Cancel" />' +
					'</p>' +
					'</form>');
					scroll(0, 0);
					$.ajax({
						type: 'POST',
						url: '/includes/agate/modules/admin/admin.ajax.php',
						dataType: 'json',
						data: {
							get: 'loadPageForm',
							key: oPage.id
						},
						success: function (response) {
							if(response.data.page['class'] !== undefined) {
								$('#txtPageClass').get(0).defaultValue = response.data.page['class'];
							}
							if(response.data.page.title !== undefined) {
								$('#txtPageTitle').get(0).defaultValue = response.data.page.title;
							}
							if(response.data.page.metaDescription !== undefined) {
								$('#txtPageMetaDescription').get(0).defaultValue = response.data.page.metaDescription;
							}
							if(response.data.page.metaRobots !== undefined) {
								$('#txtPageMetaRobots').get(0).defaultValue = response.data.page.metaRobots;
							}
							if(response.data.page.subpage !== undefined) {
								for(k in response.data.page.subpage) {
									$('#flsSectionSubpages>ul').append(
										'<li>' +
											'<span class="name">' + response.data.page.subpage[k] + '</span>' +
											'<a href="javascript:;" onclick="onyx.on.click.pagesListItem($(\'#' + oPage.id + '>.name\').get(0), \'actionDeleteSubpage\', this)">Remove</a>' +
										'</li>');
								}
							}
						}
					});
					break;

				case 'dialogNew':
					$('#mask').append(
						'<form class="dialog new" action="javascript:onyx.on.click.pagesListItem($(\'#' + oPage.id + '>.name\').get(0), \'actionNew\');" >' +
						'<p class="title">Add new page' +
							'<span>.html</span>' +
							'<input type="text" name="txtPageName" id="txtPageName" pattern="[a-z][A-Za-z0-9\-]+" required/>' +
						'</p>' +
						'<fieldset>' +
							'<legend>Position</legend>' +
							'<p>' +
								'<input type="radio" name="rdPagePosition" id="rdPagePositionBefore" value="1" required/>' +
								'<label for="rdPagePositionBefore">Before</label>' +
								'<input type="radio" name="rdPagePosition" id="rrdPagePositionAfter" value="2" required/>' +
								'<label for="rrdPagePositionAfter">After</label>' +
								'<input type="radio" name="rdPagePosition" id="rdPagePositionChildFirst" value="3" required/>' +
								'<label for="rdPagePositionChildFirst">First Child</label>' +
								'<input type="radio" name="rdPagePosition" id="rdPagePositionChildLast" value="4" required/>' +
								'<label for="rdPagePositionChildLast">Last Child</label>' +
							'</p>' +
						'</fieldset>' +
						'<p class="message"></p>' +
						'<p class="actions">' +
							'<input type="submit" value="Okay" />' +
							'<input type="button" onclick="onyx.on.click.pagesListItem(this, \'dialogCancel\')" value="Cancel" />' +
						'</p>' +
						'</form>');
					break;
				}
			},
			sectionListItem: function(oTrigger, iActionId) {
				if($('body>#mask').length === 0) {
					$('body').append('<div id="mask"></div>');
					$('#mask').height($('html').get(0).scrollHeight);
				}
//				console.info({debug: {'trigger': oTrigger, 'actionId':iActionId}});
				var oSection = {
					'id': $(oTrigger).parent().attr('id'),
					'name': $(oTrigger).siblings('.name').text(),
					'type':  $(oTrigger).parent().attr('class')
				};
				switch(iActionId) {
				case 'actionDelete':
					$.ajax({
						type: 'POST',
						url: '/includes/agate/modules/admin/admin.ajax.php',
						dataType: 'json',
						async: false,
						data: {
							'get': 'deleteSection',
							'key':  oSection.id
						},
						success: function (response) {
							$(oTrigger).parent().remove();
						}
					});
					$('#mask').remove();
					break;

				case 'actionEdit':
					data = {};
//name:
					if($('#txtSectionName').val() !== $('#' + oSection.id + '>.name').text()) {
						data['name'] = $('#txtSectionName').val();
					}
//content(html):
					if($('#txtSectionContent').next('.CodeMirror').get(0) !== undefined && $('#txtSectionContent').next('.CodeMirror').get(0).CodeMirror.getValue() !== $('#txtSectionContent').next('.CodeMirror').get(0).CodeMirror.getOption('value')) {
						data['html'] = $('#txtSectionContent').next('.CodeMirror').get(0).CodeMirror.getValue();
					}
//style:
					if($('#txtSectionStyle').next('.CodeMirror').get(0) !== undefined && $('#txtSectionStyle').next('.CodeMirror').get(0).CodeMirror.getValue() !== $('#txtSectionStyle').next('.CodeMirror').get(0).CodeMirror.getOption('value')) {
						data['style'] = $('#txtSectionStyle').next('.CodeMirror').get(0).CodeMirror.getValue();
					}
//class:
					if($('#txtSectionClass').val() !== $('#txtSectionClass').get(0).defaultValue) {
						data['class'] = $('#txtSectionClass').val();
					}

//pattern:
					if(oSection.type !== 'item' && $('#txtSectionPattern').next('.CodeMirror').get(0) !== undefined && $('#txtSectionPattern').next('.CodeMirror').get(0).CodeMirror.getValue() !== $('#txtSectionPattern').next('.CodeMirror').get(0).CodeMirror.getOption('value')) {
						data['pattern'] = $('#txtSectionPattern').val();
					}

//items:
					var i, iMax = $('#items input[type=text]').length;
					if (iMax > 0) {
						for (i = 0; i < iMax; i++) {
							if($('#txtItem' + i).val() !== $('#txtItem' + i).get(0).defaultValue) {
								data['items[i' + i + ']'] = $('#txtItem' + i).val();
							}
						}
					}

//subpage:
					if(oSection.type === 'item' && $('#chkSubpage').get(0).checked !== $('#chkSubpage').get(0).defaultChecked) {
						data['subpage'] = $('#chkSubpage').get(0).checked;
					}

//show in:
					i = 0;
					$('#flsSectionShowIn>div>span.new').each(function() {
						data['add_showin[' + i + ']'] = $(this).text();
						i++;
					});
					i = 0;
					$('#flsSectionShowIn>div:hidden>span').each(function() {
						data['del_showin[' + i + ']'] = $(this).text();
						i++;
					});

//hide in:
					i = 0;
					$('#flsSectionHideIn>div>span.new').each(function() {
						data['add_hidein[' + i + ']'] = $(this).text();
						i++;
					});
					i = 0;
					$('#flsSectionHideIn>div:hidden>span').each(function() {
						data['del_hidein[' + i + ']'] = $(this).text();
						i++;
					});

					if ($.isEmptyObject(data)) {
						onyx.message({
							text: onyx.c.MSG_SAVE_NOTHING,
							'class': 'warning'});
					}
					else {
//						console.info('save...' + oSection.id);
//						console.info({'save':data});
						data['get'] = 'updateSection';
						data['key'] = oSection.id;
						$.ajax({
							type: 'POST',
							url: '/includes/agate/modules/admin/admin.ajax.php',
							dataType: 'json',
							async: false,
							data: data,
							success: function (response) {
								$('#' + oSection.id + '>.name').text($('#txtSectionName').val());
								onyx.message({
									text: onyx.c.MSG_SAVE_OK,
									'class': 'info',
									time: 4000});
								$('#mask').remove();
							},
							error: function() {
								onyx.message({
									text: onyx.c.MSG_SAVE_KO,
									'class': 'error'});
							}
						});
					}
					break;

				case 'actionNew':
					$.ajax({
						type: 'POST',
						url: '/includes/agate/modules/admin/admin.ajax.php',
						dataType: 'json',
						async: false,
						data: {
							'get': 'insertSection',
							'name': $('#txtSectionName').val(),
							'type': $('input[name=rdSectionType]:checked').val(),
							'pos': $('input[name=rdSectionPosition]:checked').val(),
							'ref': oSection.id
						},
						success: function (response) {
							$('#agate-onyx-content .table').html(response.data.tree);
							$('#agate-onyx-content li>div').hover(onyx.on.hover.tree.sections.on, onyx.on.hover.tree.sections.out);
						}
					});
					$('#mask').remove();
					break;

				case 'dialogCancel':
					$('#mask').remove();
					break;

				case 'dialogDelete':
					$('#mask').append(
						'<form class="dialog delete" action="javascript:onyx.on.click.sectionListItem($(\'#' + oSection.id + '>.name\').get(0), \'actionDelete\');" >' +
							'<p class="title">Remove section: ' + oSection.name + '</p>' +
							'<p>Attention, removing section will delete also the content, and children.</p>' +
							'<p>Are you sure?</p>' +
							'<p class="actions">' +
								'<input type="submit" value="Okay" />' +//submit not working?
								//'<input type="button" onclick="onyx.on.click.sectionListItem($(\'#' + oSection.id + '>.name\').get(0), \'deleteOk\')" value="Okay" />' +
								'<input type="button" onclick="onyx.on.click.sectionListItem(this, \'dialogCancel\')" value="Cancel" />' +
							'</p>' +
						'</form>');
					scroll(0, 0);
					break;

				case 'dialogEdit':
					var sForm = '<form id="frmSectionEdit" class="dialog edit" action="javascript:onyx.on.click.sectionListItem($(\'#' + oSection.id + '>.name\').get(0), \'actionEdit\');" >' +
						'<p class="title">Edit section' +
							'<input type="text" name="txtSectionName" id="txtSectionName" pattern="[A-Za-z][A-Za-z0-9\-_]+" autofocus required value="' + oSection.name + '"/>' +
						'</p>';
					switch(oSection.type) {
					case 'html':
						sForm +=
							'<div class="wrapperContent">' +
								'<div class="frameSectionPreviewWrapper">' +
									'<iframe id="frameSectionPreview" ' +
										'src="/section/' + oSection.name + '.html?type=html" ' +
										'onload="editDialog.load(this);"></iframe>' +
								'</div>' +
								'<div class="codeEditor">' +
									'<div class="half">' +
										'<span class="content">Content (HTML)</span>' +
										'<textarea id="txtSectionContent" name="txtSectionContent">loading...</textarea>' +
									'</div>' +
									'<div class="half">' +
										'<span class="style">Style (CSS)</span>' +
										'<textarea id="txtSectionStyle" name="txtSectionStyle">loading...</textarea>' +
									'</div>' +
								'</div>' +
							'</div>';
						break;
					case 'menu':
						sForm +=
							'<p>' +
								'<label for="txtSectionPattern">Pattern</label>' +
								'<textarea id="txtSectionPattern" name="txtSectionPattern"></textarea>' +
							'</p>' +
							'<p>' +
								'<label for="txtSectionStyle">Style (CSS)</label>' +
								'<textarea id="txtSectionStyle" name="txtSectionStyle">loading...</textarea>' +
							'</p>';
						break;
					case 'post':
						sForm +=
							'<p>' +
								'<label for="txtSectionPattern">Pattern</label>' +
								'<textarea id="txtSectionPattern" name="txtSectionPattern"></textarea>' +
							'</p>' +
							'<p>' +
								'<label for="txtSectionStyle">Style (CSS)</label>' +
								'<textarea id="txtSectionStyle" name="txtSectionStyle">loading...</textarea>' +
							'</p>' +
							'<fieldset id="flsMetaList">' +
								'<legend>Meta list</legend>' +
								'<p>' +
									'<input type="text" name="txtAddMetaList" id="txtAddMetaList" />' +
									'<a href="javascript:;" onclick="alert("work in progress")>Add</a>' +
								'</p>' +
							'</fieldset>' +
							'<fieldset id="flsMetaPost">' +
								'<legend>Meta post</legend>' +
								'<p>' +
									'<input type="text" name="txtAddMetaPost" id="txtAddMetaPost" />' +
									'<a href="javascript:;" onclick="alert("work in progress")>Add</a>' +
								'</p>' +
							'</fieldset>';
						break;
					case 'item':
						sForm +=
							'<p>' +
								'<label for="txtSectionPattern">Pattern</label>' +
								'<textarea id="txtSectionPattern" name="txtSectionPattern" readonly="readonly"></textarea>' +
							'</p>' +
							'<fieldset id="items">' +
								'<legend>Items</legend>' +
							'</fieldset>' +
							'<p>' +
								'<label for="chkSubpage">Subpage</label>' +
								'<input type="checkbox" name="chkSubpage" id="chkSubpage" />' +
							'</p>';
						break;
					}
					sForm +=
						'<p><label for="txtSectionClass">Class</label>' +
							'<input type="text" name="txtSectionClass" id="txtSectionClass" pattern="[a-z][A-Za-z0-9\-_\ ]+"/>' +
						'</p>' +
						'<fieldset id="flsSectionShowIn">' +
							'<legend>Show in</legend>' +
							'<a href="javascript:;" onclick="onyx.on.click.lov.pages(this)">Add</a>' +
						'</fieldset>' +
						'<fieldset id="flsSectionHideIn">' +
							'<legend>Hide in</legend>' +
							'<a href="javascript:;" onclick="onyx.on.click.lov.pages(this)">Add</a>' +
						'</fieldset>' +
						'<p class="message"></p>' +
						'<p class="actions">' +
							'<input type="submit" value="Save" />' +
							'<input type="button" onclick="onyx.on.click.sectionListItem(this, \'dialogCancel\')" value="Cancel" />' +
						'</p>' +
						'</form>';
					$('#mask').append(sForm);
					scroll(0, 0);
					if (oSection.type === 'html') {
						editDialog = {
							load: function (o) { //the trigger is preview frame
								var docPreview = o.contentDocument || o.contentWindow.document;
								$(docPreview).find('#' + oSection.name).attr('contenteditable', 'true');
								$(docPreview).find('#' + oSection.name).blur(function () {
									$(this).children().removeAttr('style');
									$('#txtSectionContent').next('.CodeMirror').get(0).CodeMirror.setValue($(this).html());
								});
								$('#txtSectionContent').val($(docPreview).find('#' + oSection.name).html());
//autoformat the code:
								cmHtmlEditor = CodeMirror.fromTextArea(document.getElementById('txtSectionContent'), {
									mode: 'htmlmixed',
									tabMode: 'indent',
									lineNumbers: true,
									lineWrapping: true,
									onChange: function (cmEditor) {
										var elPreviewFrame = $('#frameSectionPreview').get(0);
										if(elPreviewFrame !== undefined) {
											var docPreview = elPreviewFrame.contentDocument || elPreviewFrame.contentWindow.document;
											$(docPreview).find('#' + oSection.name).html(cmEditor.getValue());
										};
									}
								});
							}
						};
					}

//load form values:
					$.ajax({
						type: 'POST',
						url: '/includes/agate/modules/admin/admin.ajax.php',
						dataType: 'json',
						data: {
							get: 'loadSectionForm',
							id: oSection.id
						},
						success: function (response) {
							var i, iMax;
							if(response.data.section.metaList !== undefined) {
								iMax = response.data.section.metaList.length;
								for(i = 0; i < iMax; i++) {
									$('#flsMetaList').append(
										'<div>' +
											'<span>' + response.data.section.metaList[i] + '</span>' +
											'<a href="javascript:;" onclick="$(this).parent().hide()">Delete</a>' +
										'</div>');
								}
							}
							if(response.data.section['class'] !== undefined) {
								$('#txtSectionClass').get(0).defaultValue = response.data.section['class'];
							}
							if(response.data.section.showin !== undefined) {
								iMax = response.data.section.showin.length;
								for(i = 0; i < iMax; i++) {
									$('#flsSectionShowIn').append(
										'<div>' +
											'<span>' + response.data.section.showin[i] + '</span>' +
											'<a href="javascript:;" onclick="$(this).parent().hide()">Delete</a>' +
										'</div>');
								}
							}
							if(response.data.section.hidein !== undefined) {
								var i, iMax = response.data.section.hidein.length;
								for(i = 0; i < iMax; i++) {
									$('#flsSectionHideIn').append(
										'<div>' +
											'<span>' + response.data.section.hidein[i] + '</span>' +
											'<a href="javascript:;" onclick="$(this).parent().hide()">Delete</a>' +
										'</div>');
								}
							}
							if(response.data.section.styleContent !== undefined) {
								$('#txtSectionStyle').val(response.data.section.styleContent);
							}
							else {
								$('#txtSectionStyle').val('');
							}
							switch(oSection.type) {
							case 'html':
								cmStyleEditor = CodeMirror.fromTextArea(document.getElementById('txtSectionStyle'), {
									mode: 'css',
									tabMode: 'indent',
									lineNumbers: true,
									lineWrapping: true,
//									onCursorActivity: function() {
//										editDialog.style.setLineClass(editDialog.hLine, null);
//										editDialog.hLine = editDialog.style.setLineClass(editDialog.style.getCursor().line, 'activeline');
//									},
									onChange: function (cmEditor) {
										var elPreviewFrame = $('#frameSectionPreview').get(0);
										if(elPreviewFrame !== undefined) {
											var docPreview = elPreviewFrame.contentDocument || elPreviewFrame.contentWindow.document;
											if($(docPreview).find('head style[title=preview]').length === 0) {
												$(docPreview).find('head').append('<style type="text/css" title="preview"></style>');
											}
											$(docPreview).find('head style[title=preview]').text(cmEditor.getValue());
										}
									}
								});
//autoformat the code:
								CodeMirror.commands["selectAll"](cmStyleEditor);
								cmStyleEditor.autoFormatRange(
									cmStyleEditor.getCursor(true),
									cmStyleEditor.getCursor(false)
								);
								break;
							case 'menu':
							case 'post':
								if(response.data.section.pattern !== undefined) {
									$('#txtSectionPattern').val(response.data.section.pattern);
								}
								else {
									$('#txtSectionPattern').val('');
								}
								cmPatternEditor = CodeMirror.fromTextArea(document.getElementById('txtSectionPattern'), {
									mode: 'htmlmixed',
									tabMode: 'indent',
									lineNumbers: true,
									lineWrapping: true
								});
//autoformat the code:
								CodeMirror.commands["selectAll"](cmPatternEditor);
								cmPatternEditor.autoFormatRange(
									cmPatternEditor.getCursor(true),
									cmPatternEditor.getCursor(false));

								cmStyleEditor = CodeMirror.fromTextArea(document.getElementById('txtSectionStyle'), {
									mode: 'css',
									tabMode: 'indent',
									lineNumbers: true,
									lineWrapping: true
								});
//autoformat the code:
								CodeMirror.commands["selectAll"](cmStyleEditor);
								cmStyleEditor.autoFormatRange(
									cmStyleEditor.getCursor(true),
									cmStyleEditor.getCursor(false)
								);
								break;
							case 'item':
								var iItems = 0;
								if(response.data.section.pattern !== undefined) {
									$('#txtSectionPattern').val(response.data.section.pattern);
									iItems = response.data.section.pattern.split('%');
									iItems = iItems.length - 1;
								}
								else {
									$('#txtSectionPattern').val('');
								}
								if(response.data.section.subpage !== undefined && response.data.section.subpage) {
									$('#chkSubpage').attr('checked', true).get(0).defaultChecked = true;
								}
								cmPatternEditor = CodeMirror.fromTextArea(document.getElementById('txtSectionPattern'), {
									mode: 'htmlmixed',
									tabMode: 'indent',
									lineNumbers: true,
									lineWrapping: true,
									readOnly: true
								});
//autoformat the code:
								CodeMirror.commands["selectAll"](cmPatternEditor);
								cmPatternEditor.autoFormatRange(
									cmPatternEditor.getCursor(true),
									cmPatternEditor.getCursor(false));
								for (i = 0; i < iItems; i++) {
									$('#items').append(
										'<p>' +
											'<label for="txtItem' + i + '">Item #' + (i+1) + '</label>' +
											'<input type="text" name="txtItem' + i + '" id="txtItem' + i + '" />' +
										'</p>');
								}
								if(response.data.section.items !== undefined) {
									iMax = Math.min(response.data.section.items.length, iItems);
									for (i = 0; i < iMax; i++) {
										$('#txtItem' + i).get(0).defaultValue = response.data.section.items[i];
									}
								}
								break;
							}
						}
					});
					break;

//				case 'viewDialog'://view
//					$('#mask').show();
//					$('#mask').append(
//						'<form class="dialog" id="frmSectionPreview" action="javascript:onyx.on.click.sectionListItem($(\'.dialog\').get(0), \'dialogCancel\');" >' +
//							'<div class="frameSectionPreviewWrapper">' +
//								'<iframe id="frameSectionPreview" src="/section/' + oSection.name + '.html?type=html" ></iframe>' +
//							'</div>' +
//							'<p class="actions">' +
//								'<input type="button" onclick="$(\'#frmSectionPreview\').hide(0);onyx.on.click.sectionListItem($(\'#' + oSection.id + '>.name\').get(0), \'editDialog\')" value="Edit" />' +
//								'<input type="submit" value="Close" />' +
//							'</p>' +
//						'</form>');
//					break;

				case 'dialogNew':
					var i, iMax, sForm = '<form class="dialog new" action="javascript:onyx.on.click.sectionListItem($(\'#' + oSection.id + '>.name\').get(0), \'actionNew\');" >' +
						'<p class="title">Add new section' +
							'<input type="text" name="txtSectionName" id="txtSectionName" pattern="[A-Za-z][A-Za-z0-9\-_]+" autofocus required/>' +
						'</p>' +
						'<fieldset>' +
						'<legend>Type</legend>' +
						'<p>';
					iMax = onyx.cache.data.sectionsType.length;
					for (i = 0; i < iMax; i++) {
						sForm +=
							'<input type="radio" name="rdSectionType" id="rdSectionType' + onyx.cache.data.sectionsType[i] + '" value="' + onyx.cache.data.sectionsType[i].toLowerCase() + '" required/>' +
							'<label for="rdSectionType' + onyx.cache.data.sectionsType[i] + '">' + onyx.cache.data.sectionsType[i].toUpperCase() + '</label>';
					}
					sForm +=
						'</p>' +
						'</fieldset>' +
						'<fieldset>' +
							'<legend>Position</legend>' +
							'<p>' +
								'<input type="radio" name="rdSectionPosition" id="rdSectionPositionBefore" value="1" required/>' +
								'<label for="rdSectionPositionBefore">Before</label>' +
								'<input type="radio" name="rdSectionPosition" id="rdSectionPositionAfter" value="2" required/>' +
								'<label for="rdSectionPositionAfter">After</label>' +
								'<input type="radio" name="rdSectionPosition" id="rdSectionPositionChildFirst" value="3" required/>' +
								'<label for="rdSectionPositionChildFirst">First Child</label>' +
								'<input type="radio" name="rdSectionPosition" id="rdSectionPositionChildLast" value="4" required/>' +
								'<label for="rdSectionPositionChildLast">Last Child</label>' +
							'</p>' +
						'</fieldset>' +
						'<p class="message"></p>' +
						'<p class="actions">' +
							'<input type="submit" value="Ok" />' +
							'<input type="button" onclick="onyx.on.click.sectionListItem(this, \'dialogCancel\')" value="Cancel" />' +
						'</p>' +
						'</form>';
					$('#mask').append(sForm);
					scroll(0, 0);
					break;
				}
//				var iSectionId = parseInt($(oTrigger).parent().find('.h').text(), 10);
//				onyx.load.content('Section', iSectionId);
			},
			form: {
				section: {
					save: function (iSectionId) {
						if(iSectionId === undefined) {
							//insert new secton
							$.ajax({
								type: 'POST',
								url: '/includes/agate/modules/admin/admin.ajax.php',
								dataType: 'json',
								async: false,
								data: $('#agate-onyx-content form').serialize() + '&get=insertSection',
								success: function (response) {
									onyx.load.content('Sections');
									onyx.message({
										text: onyx.c.MSG_SAVE_OK,
										'class': 'ok',
										time: 2000});
								},
								error: function() {
									onyx.message({
										text: onyx.c.MSG_SAVE_KO,
										'class': 'error'});
								}
							});
						}
						else {

							//check for dirty:
							data = {};
							for(k in onyx.cache.form.section) {
								switch(onyx.cache.form.section[k].type) {
								case 'code': //do something special
									break;
								case 'checkbox':
									if($('#' + k).get(0).checked !== onyx.cache.form.section[k].value) {
										data[k] = ($('#' + k).get(0).checked)? 1 : 0;
									}
									break;
								default:
									if($('#' + k).val() !== onyx.cache.form.section[k].value) {
										data[k] = $('#' + k).val();
									}
									break;
								}
							}
							if ($.isEmptyObject(data)) {
								onyx.message({
									text: onyx.c.MSG_SAVE_NOTHING,
									'class': 'warning'});
							}
							else {
								//update section
//								console.info(data);
								data['get'] = 'updateSection';
								data['id'] = iSectionId;
								//insert new secton
								$.ajax({
									type: 'POST',
									url: '/includes/agate/modules/admin/admin.ajax.php',
									dataType: 'json',
									async: false,
									data: data,
									success: function (response) {
										onyx.load.content('Sections');
										onyx.message({
											text: onyx.c.MSG_SAVE_OK,
											'class': 'info',
											time: 4000});
									},
									error: function() {
										onyx.message({
											text: onyx.c.MSG_SAVE_KO,
											'class': 'error'});
									}
								});
							}
						};
					},
					'delete': function (iSectionId) {
						if(iSectionId !== undefined) {
							$.ajax({
								type: 'POST',
								url: '/includes/agate/modules/admin/admin.ajax.php',
								dataType: 'json',
								async: false,
								data: {
									'get': 'deleteSection',
									'id': iSectionId
								},
								success: function (response) {
									onyx.message({
										text: onyx.c.MSG_DELETE_OK,
										'class': 'ok',
										time: 2000});
									onyx.load.content('Sections');
								}
							});
						}
					}
				}
			}
		},
		hover: {
			tree: {
				sections: {
					on: function () {
						$(this).append(
//							'<span class="drag"></span>' +
							'<span class="button new" onclick="onyx.on.click.sectionListItem(this, \'dialogNew\')">New</span>' +
//							'<span class="button view" onclick="onyx.on.click.sectionListItem(this, \'viewDialog\')">View</span>' +
							'<span class="button edit" onclick="onyx.on.click.sectionListItem(this, \'dialogEdit\')">Edit</span>' +
							'<span class="button remove" onclick="onyx.on.click.sectionListItem(this, \'dialogDelete\')">Remove</span>'
						);
					},
					out: function () {
						$(this).children('span.button').remove();
					}
				},
				pages: {
					on: function () {
						$(this).append(
							'<span class="button new" onclick="onyx.on.click.pagesListItem(this, \'dialogNew\')">New</span>' +
							'<span class="button edit" onclick="onyx.on.click.pagesListItem(this, \'dialogEdit\')">Edit</span>' +
							'<span class="button remove" onclick="onyx.on.click.pagesListItem(this, \'dialogDelete\')">Remove</span>'
						);
					},
					out: function () {
						$(this).children('span.button').remove();
					}
				}
			}
		},
		submit: {
			register: function () {
				$.ajax({
					type: 'POST',
					url: 'api.html',
					dataType: 'json',
					data: $('#formRegister').serialize(),
					success: function (response) {
						if (response.success === true) {
							if(response.data.login.redirect !== undefined) {
								location = response.data.login.redirect;
							}
							else {
								location = '/admin.html';
							}
						}
						else {
							onyx.message({
								text: response.message,
								'class': 'error',
								'parent': '#form-register>.message',
								time: 10000});
						}
					}
				});
			},
			login : function () {
				$.ajax({
					type: 'POST',
					url: 'api.html',
					dataType: 'json',
					data: $('#formLogin').serialize(),
					success: function (response) {
						if (response.success === true) {
							if (response.data.redirect !== undefined) {
								location = response.data.redirect;
							}
							else {
								location = '/';
							}
						}
						else {
							onyx.message({
								text: response.message,
								'class': 'error',
								'parent': '#form-login>.message',
								time: 10000});
						}
					}
				});
			},
			logout: function () {
				$.ajax({
					type: 'POST',
					url: '/api.html',
					dataType: 'json',
					data: {'get': 'logout'},
					success: function (response) {
						if (response.success === true) {
							location = response.data.logout.redirect;
						}
						else {
							onyx.message({
								text: response.message,
								'class': 'error',
								time: 5000});
						}
					}
				});
			}
		}
	},
	load :{
		content: function (menuLabel, key, index, menuParent) {
			$('#agate-onyx-content>.wrapper').children().remove();
			var sMenuLabel = '';
			if(menuParent !== undefined) {
				sMenuLabel =
					'<span class="parent">' + menuParent + '</span>' +
					'<img src="/includes/agate/modules/admin/dirSeparator.png" alt="separator"/>' +
					'<span class="child">' + menuLabel + '</span>';
			}
			else {
				sMenuLabel += menuLabel;
			}
			$('#agate-onyx-content>.title>h2').html(sMenuLabel);

			switch (key) {
			case onyx.c.MENU_SECTIONS:
				onyx.load.tree('sections');
				break;
			case onyx.c.MENU_PAGES:
				onyx.load.tree('pages');
				break;
			case onyx.c.MENU_POSTS:
				if(index === undefined) {
					onyx.load.grid('postsList');
				}
				else {
					onyx.load.grid('posts', index);
				}
				break;
			}
		},
		tree: function(sTreeId) {
			var tree = {
				menu: '',
				rowPattern: ''
			};

//append the tree:
			$('#agate-onyx-content>.wrapper').append(
				'<div class="tree">' +
					'<div class="menu">' + tree.menu + '</div>' +
					'<div class="table"></div>' +
				'</div>');

//load tree data:
			$.ajax({
				type: 'POST',
				url: '/includes/agate/modules/admin/admin.ajax.php',
				dataType: 'json',
				data: {
					get: 'tree',
					key: sTreeId
				},
				success: function (response) {
					$('#agate-onyx-content .table').append(response.data.tree);
					$('#txtSearch').show(0);
					//append custom actions per items:
					switch(sTreeId) {
						case 'sections':
							$('#agate-onyx-content li>div').hover(onyx.on.hover.tree.sections.on, onyx.on.hover.tree.sections.out);
						break;
						case 'pages':
							$('#agate-onyx-content li>div').hover(onyx.on.hover.tree.pages.on, onyx.on.hover.tree.pages.out);
							break;
						break;
					}
				}
			});
		},

		grid: function (sGridId, index) {
//load crid configuration:
			var grid = {
				menu: '',
				rowPattern: ''
				};
			switch(sGridId) {
//			case 'sections':
//				grid.menu = '<a href="javascript:;" onclick="onyx.load.content(\'Section\')" class="button">Add</a>';
//				grid.rowPattern = '<tr><td class="h">{0}</td><td class="{2} t">{2}</td><td>{1}</td></tr>';
//				break;
			case 'postsList':
//				grid.menu = '<input type="search" id="txtGridSearch" name="txtGridSearch" onkeyup="onyx.on.click.grid.search(this)" placeholder="filter" />';
				grid.menu = '';
				grid.header = '<span>Post</span>';
				grid.rowPattern = '<div><a href="javascript:;" onclick="onyx.load.content(\'{name}\', ' + onyx.c.MENU_POSTS + ', \'{key}\', \'Posts\')"><span>{name}</span></a></div>';
				break;
			case 'posts':
				grid.menu =
					'<input type="button" name="btnNewPost" id="btnNewPost" value="New Post" onclick="onyx.on.click.postListItem(this)" />' +
					'<input type="hidden" name="hPostGroup" id="hPostGroup" value="' + index + '" />';
				grid.header =
					'<span class="w4">Title</span>' +
					'<span class="w2">Author</span>' +
					'<span class="w1">Date</span>';
				grid.rowPattern =
					'<div>' +
						'<a id="post_{id}" href="javascript:;" onclick="onyx.on.click.postListItem(this, \'dialogEdit\')">' +
							'<span class="w4">{title}</span>' +
							'<span class="w2">{author}</span>' +
							'<span class="w2">{date}</span>' +
						'</a>' +
					'</div>';
				break;
			}
//build the grid:
			$('#agate-onyx-content>.wrapper').append(
				'<div class="grid">' +
					'<div class="menu">' + grid.menu + '</div>' +
					'<div class="head">' + grid.header + '</div>' +
					'<div class="table"></div>' +
				'</div>');

//load grid data:
			var data = {
				get: 'grid',
				key: sGridId
			};
			if (index !== undefined) {
				data['index'] = index;
			}
			$.ajax({
				type: 'POST',
				url: '/includes/agate/modules/admin/admin.ajax.php',
				dataType: 'json',
				data: data,
				success: function (response) {
					$('#agate-onyx-content .table').append(atos($.fn.arrayMapArray(response.data.grid.fields, response.data.grid.data), grid.rowPattern));
					$('#txtSearch').show(0);
//					$('#agate-onyx-content table td').click(function (){
//						onyx.on.click.sectionListItem(this);
//					});
				}
			});
		},
		form: function(sFormId, iKey) {
			var form = {
				title: false,
				menu: false,
				controls: false,
				actions: false
			};

			switch(sFormId) {
			case 'section':
//form controls:
				form.title = 'Section';
				form.menu =
					'<a href="javascript:;" class="button" onclick="onyx.load.content(\'Sections\');">Back to sections</a>' +
//					'<a href="javascript:;" class="button-green" onclick="onyx.on.click.form.section.save(' + iKey + ')">Save</a>' +
					'<a href="javascript:;" class="button-blue" onclick="onyx.on.click.form.section.save(' + iKey + ')">Save</a>' +
					'<a href="javascript:;" class="button-red" onclick="onyx.on.click.form.section.delete(' + iKey + ')">Delete</a>';
				form.controls = {
					'name': {name: 'txtSectionName', type: 'input', label:'Name', maxlength:100},
					'type': {name: 'selSectionType', type: 'select', label:'Type', data: 'sectionsType'},
					'class': {name: 'txtSectionClass', type: 'input', label:'Class', maxlength:50},
					'content': {name: 'codeSectionContent', type: 'code', label:'Content', lang:'html', preview: true, mode:'text/html',
						onChange: function (cmEditor) {
							var elPreviewFrame = $('#codeSectionContentPreview').get(0);
							if(elPreviewFrame !== undefined) {
								var docPreview = elPreviewFrame.contentDocument || elPreviewFrame.contentWindow.document;
								$(docPreview).find('#' + $('#txtSectionName').val()).html(cmEditor.getValue());
							};
						}},
					'style': {name: 'chkSectionStyle', type:'checkbox', label: 'Style'},
					'styleContent': {name:'codeStyleContent', type: 'code', label:'CSS', lang:'css', preview: false, mode:'text/css',
						onChange: function(cmEditor) {
							var elPreviewFrame = $('#codeSectionContentPreview').get(0);
							if(elPreviewFrame !== undefined) {
								var docPreview = elPreviewFrame.contentDocument || elPreviewFrame.contentWindow.document;
								if($(docPreview).find('head style[title=preview]').length === 0) {
									$(docPreview).find('head').append('<style type="text/css" title="preview"></style>');
								}
								$(docPreview).find('head style[title=preview]').text(cmEditor.getValue());
							}
						}
					}
				};
				form.actions = {
					load: function() {
						delay = 300;
						switch($('#selSectionType').val()){
						case '2': //html
							$('#chkSectionStyle').change(function(){
								if($(this).get(0).checked){
									$('#codeStyleContent').parent('p').show();
								}
								else {
									$('#codeStyleContent').parent('p').hide();
								};
							});
							break;

						default:
							//cleanup: remove unused controls:
							$('#codeSectionContent').parent().remove();
							break;
						}
					}
				};
				break;

			}

//build form:
			$('#agate-onyx-content .grid').remove();
			if (form.title !== false) {
				$('#agate-onyx-content h2').text(form.title);
			}
			if (form.menu !== false) {
			}
			$('#agate-onyx-content').append(
				'<div class="form hidden">' +
					'<div class="menu">' + form.menu + '</div>' +
					'<form action="#"></form>' +
				'</div>');
			for(k in form.controls) {
				sFormLine = '<p>';
				if(form.controls[k].label !== undefined) {
					sFormLine += '<label for="' + form.controls[k].name + '">' + form.controls[k].label + '</label>';
				}
				switch(form.controls[k].type) {
				case 'select':
					sFormLine += '<select name="' + form.controls[k].name + '" id="' + form.controls[k].name + '"';
					if(form.controls[k]['class'] !== undefined){
						sFormLine += ' class="' + form.controls[k]['class'] + '"';
					}
					sFormLine += '><option value="">- select -</option>';
					if(onyx.cache.data[form.controls[k].data].length === 0) {
						$.ajax({
							type: 'POST',
							url: '/includes/agate/modules/admin/admin.ajax.php',
							dataType: 'json',
							async: false,
							data: {
								get: 'loadSectionsTypesList'
							},
							success: function (response) {
								onyx.cache.data[form.controls[k].data] = response.data.sectionsType;
								//console.info(response);
							}
						});
					}
					sFormLine += atos(onyx.cache.data[form.controls[k].data], '<option value="{0}">{1}</option>');
					sFormLine += '</select>';
					break;

				case 'input':
				case 'checkbox':
					sFormLine += '<input name="' + form.controls[k].name + '" id="' +form.controls[k].name + '"';
					if(form.controls[k]['class'] !== undefined){
						sFormLine += ' class="' + form.controls[k]['class'] + '"';
					}
					sFormLine += ' type="' + form.controls[k].type + '" />';
					break;

				case 'code':
					sFormLine += '<textarea name="' + form.controls[k].name + '" id="' + form.controls[k].name + '"';
					if(form.controls[k]['class'] !== undefined){
						sFormLine += ' class="' + form.controls[k]['class'] + '"';
					}
					sFormLine += '></textarea>';
					//add html preview:
					if(form.controls[k].preview !== undefined && form.controls[k].preview === true) {
						if(form.controls[k].previewLabel === undefined) {
							form.controls[k].previewLabel = 'Preview';
						}
						sFormLine += '<br />' +
							'<label>' + form.controls[k].previewLabel + '</label>' +
							'<iframe ' +
								'id="' + form.controls[k].name + 'Preview" >' +
							'</iframe>';
					}
					break;

				case 'textarea':
					sFormLine += '<textarea name="' + form.controls[k].name + '" id="' +form.controls[k].name + '"';
					if(form.controls[k]['class'] !== undefined){
						sFormLine += ' class="' + form.controls[k]['class'] + '"';
					}
					sFormLine += '></textarea>';
					break;
				}
				sFormLine += '</p>';

				$('#agate-onyx-content form').append(sFormLine);

//special controls configuration:
				switch(form.controls[k].type) {
				case 'code':
					form.controls[k].cmEditor = CodeMirror.fromTextArea(document.getElementById(form.controls[k].name), {
						mode: form.controls[k].mode,
						tabMode: 'indent',
						lineNumbers: true,
						lineWrapping: true,
						onCursorActivity: function() {
							form.controls[k].cmEditor.setLineClass(hlLine, null);
							hlLine = form.controls[k].cmEditor.setLineClass(form.controls[k].cmEditor.getCursor().line, 'activeline');
						},
						onChange: form.controls[k].onChange
					});

					hlLine = form.controls[k].cmEditor.setLineClass(0, 'activeline');
					break;
				}
			};
//load data values:
			if (iKey === undefined) {
				//new values nothing to load, just show the form:
				$('#agate-onyx-content .form').removeClass('hidden');
			}
			else {
				onyx.cache.form.section = {};
				$.ajax({
					type: 'POST',
					url: '/includes/agate/modules/admin/admin.ajax.php',
					dataType: 'json',
					data: {
						get: 'loadSectionForm',
						id: iKey
					},
					success: function (response) {
						$('#agate-onyx-content .form').removeClass('hidden');
						for(k in response.data.section) {

							if(form.controls[k] !== undefined) {
								switch(form.controls[k].type)
								{
								case 'input':
								case 'select':
//								case 'textarea':
									$('#' + form.controls[k].name).val(response.data.section[k]);
									onyx.cache.form.section[form.controls[k].name] = {
										type: form.controls[k].type,
										value: $('#' + form.controls[k].name).val()
									};
									break;
								case 'checkbox':
									if(response.data.section[k] === '1') {
										$('#' + form.controls[k].name).attr('checked', 'checked');
									}
//									else {
//										//$('#' + form.controls[k].name).removeAttr('checked');//do nothing because default checboxes are unchecked;
//									}

									onyx.cache.form.section[form.controls[k].name] = {
										type: form.controls[k].type,
										value: $('#' + form.controls[k].name).get(0).checked
									};
									break;
								case 'code':
									var sSectionName = $('#txtSectionName').val();
									if(form.controls[k].preview !== undefined && form.controls[k].preview === true) {
										form.controls[k].previewFrame = $('#' + form.controls[k].name + 'Preview').get(0);
										form.controls[k].previewFrame.src = '/section/' + $('#txtSectionName').val() + '.html?type=' + form.controls[k].lang;
										if (form.controls[k].previewFrame.attachEvent) {
											form.controls[k].previewFrame.attachEvent("onload", function () {
												$(this.parentNode).find('.CodeMirror').get(0).CodeMirror.setValue($(this.contentWindow.document).find('#' + sSectionName).html());
											});
										}
										else {
											form.controls[k].previewFrame.onload = function() {
												$(this.parentNode).find('.CodeMirror').get(0).CodeMirror.setValue($(this.contentWindow.document).find('#' + sSectionName).html());
											};
										}
									}
									else {
										form.controls[k].cmEditor.setValue(response.data.section[k]);
									}
									break;
								}
							};
						}
						//console.info(response);
						form.actions.load();
						$('#agate-onyx-content .form').removeClass('hidden');
					}
				});
			}
		}
	},
	test: {
		all: function () {
			console.info(this.countDomElements());
		},
		countDomElements: function (e, l) {
			if (e === undefined) {
				e = document;
			}
			var c = $(e).children().length;
			if (!l) {
				l = 999999;
			}
			if ($(e).children().length > 0) {
				$(e).children().each(function () {
					c += onyx.test.countDomElements(this, l);
				});
				if (c > l) {
					if (e.tagName === 'DIV' && (e.id.substr(0, 3) === 'sec' || e.id.substr(0, 3) === 'wdg')) {
					//	console.info('Div: ' + e.id + ':' + c);
					}
				}
				return (c);
			}
			return (0);
		}
	}
};