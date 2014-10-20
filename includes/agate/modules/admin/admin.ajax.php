<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');

a::module('db/mysql');
a::module('ajax/response');

class adminAjax extends ajax
{
	public function call()
	{
		switch ($this -> sAction)
		{
		case 'deleteSection':
			$this -> deleteSection();
			break;
		case 'deletePage':
			$this -> deletePage();
			break;
		case 'deletePost':
			$this -> deletePost();
			break;
		case 'grid':
			switch(@$_POST['key']) {
				case 'postsList':
					$this -> getPostsList();
					break;
				case 'posts':
					$this -> getPosts(@$_POST['index']);
					break;
			}
			break;
		case 'insertPage':
			$this -> insertPage();
			break;
		case 'insertPost':
			$this -> insertPost(@$_POST['section']);
			break;
		case 'insertSection':
			$this -> insertSection();
			break;
		case 'loadPageForm':
			$this -> getPageForm();
			break;
		case 'loadSectionForm':
			$this -> getSectionForm();
			break;
		case 'loadPostForm':
			$this -> getPostForm();
			break;
//		case 'loadSectionsTypesList':
//			$this -> getSectionsTypesList();
//			break;
		case 'lov':
			switch(@$_POST['key']) {
			case 'pages':
				$this -> getLovPagesList(true);
				break;
			}
			break;
		case 'sectionsList':
			$this -> getSectionsList();
			break;
		case 'tree':
			switch(@$_POST['key']) {
			case 'sections':
				$this -> getSectionsList();
				break;
			case 'pages':
				$this -> getPagesList();
				break;
			}
			break;
		case 'updatePage':
			$this -> updatePage();
			break;
		case 'updatePost':
			$this -> updatePost();
			break;
		case 'updateSection':
			$this -> updateSection();
			break;
		default:
			$this -> sMessage = 'Warrning: Undefined action:'.$sAction;
			break;
		}
	}


	private function deletePage() {
		a::cacheLoad('pages');
		unset(a::$arSiteMap[$_POST['key']]);
		a::cacheSave('pages');
		$this -> bSuccess = true;
	}

	private function deletePost() {
		$sQuery = "DELETE FROM `posts` WHERE `id` = ".$_POST['id'];
		a::dbExecute($sQuery);
		$this -> bSuccess = true;
	}


	private function deleteSection() {
		//		$sQuery = "DELETE FROM `sections` WHERE `id` = ".a::dbFormat(@$_POST['id'], 'integer', 'NULL');
		//		$oResult = a::dbExecute($sQuery, DB_RETURN_NOTHING);
		//		$this -> setData('query:', $sQuery);
		//		$this -> setData('result:', $oResult);
		a::cacheLoad('sections');
		unset(a::$arLayout[$_POST['key']]);
		a::cacheSave('sections');
		$this -> bSuccess = true;
	}


	private function getLovPagesList($bIncludeSubPages) {
		a::cacheLoad('pages');
		$sReturn = '<ul>';
		foreach(a::$arSiteMap as $k => $v) {
			$sReturn .= '<li><div id="'.$k.'" class="page"><span class="name">'.$v['name'].'</span></div></li>';
			if($bIncludeSubPages) {
				if(isset($v['subpage'])) {
					foreach($v['subpage'] as $ks => $vs) {
						$sReturn .= '<li><div id="'.$k.'" class="page"><span class="name">'.$v['name'].'/'.$vs.'</span></div></li>';
					}
				}
			}
		}
		$sReturn .= '</ul>';
		$this -> setData('tree', $sReturn);
	}


	private function getPagesList() {
		a::cacheLoad('pages');
		$this -> setData('tree', a::treeToString(a::$arSiteMap, array('defaultType' => 'page')));
	}


	private function getPostForm() {
		a::cacheLoad('sections');
		$aReturn = a::dbLookupRow(
			"DATE_FORMAT(`posts`.`date`, '%Y-%m-%d %H:%i') AS `date`, "
			."`posts`.`excerpt` AS `excerpt`, "
			."`posts`.`permalink` AS `permalink`, "
			."`posts`.`title` AS `title`, "
			."`posts`.`key` AS `key`, "
			."`users`.`display_name` AS `author`",
			"`posts` LEFT JOIN `users` ON `users`.`id` = `posts`.`author`",
			"`posts`.`id` = ".@$_POST['post'],
			MYSQLI_ASSOC);
//metaList/metaPost
		$aMetaList = array();
		foreach (a::$arLayout as $k => $v) {
			if(isset($v['type']) && $v['type'] === 'post' && isset($v['key']) && $v['key'] === $aReturn['key']) {
				if(isset($v['metaList'])) {
					$aMetaList = $v['metaList'];
				}
				if(isset($v['metaPost'])) {
					$aMetaPost = $v['metaPost'];
				}
				break;
			}
		}
		$aPostMeta = a::dbLookupTable(
				"`key`, `value`",
				"`postmeta`",
				"`post` = ".@$_POST['post'],
				MYSQLI_ASSOC);
//		$aReturn['postmeta'] = $aPostMeta;
		$iMax = count($aPostMeta);

		if(count($aMetaList) > 1) {
			$sLookupFields = "";
			$aReturn['metaList'] = array();
			foreach ($aMetaList as $k => $v) {
				$aReturn['metaList'][$v] = '';
				for($i = 0; $i < $iMax; $i++) {
					if($v === $aPostMeta[$i]['key']) {
						$aReturn['metaList'][$v] = $aPostMeta[$i]['value'];
						break;
					}
				}
			}
		}
		if(count($aMetaPost) > 1) {
			$sLookupFields = "";
			$aReturn['metaPost'] = array();
			foreach ($aMetaPost as $k => $v) {
				$aReturn['metaPost'][$v] = '';
				for($i = 0; $i < $iMax; $i++) {
					if($v === $aPostMeta[$i]['key']) {
						$aReturn['metaPost'][$v] = $aPostMeta[$i]['value'];
						break;
					}
				}
			}
		}

		unset($aReturn['key']);

		$this -> setData('post', $aReturn);
	}


	private function getPostsList() {
		a::cacheLoad('sections');
		$aReturn = array(
			'data' => array(),
			'fields' => array('key', 'name'));
		foreach (a::$arLayout as $k => $v) {
			if(isset($v['type']) && $v['type'] === 'post') {
				$aReturn['data'][] = array($k, $v['name']);
			}
		}
		$this -> setData('grid', $aReturn);
	}


	private function getPosts($index) {
		a::cacheLoad('sections');

//lookup for all posts:
		$this -> setData('grid', array( 'fields' => array('id', 'title', 'author', 'date'),
			'data' => a::dbLookupTable(
				"`posts`.`id` AS `id`, "
				."`posts`.`title` AS `title`, "
				."IFNULL(`users`.`display_name`, 'guest') AS `author`, "
				."`posts`.`date` AS `date` ",
				"`posts` LEFT JOIN `users` ON `users`.`id` = `posts`.`author`",
				"`posts`.`key` = '".a::$arLayout[$index]['key']."' "
				."ORDER BY `posts`.`date` DESC")));
	}


	private function getSectionsList() {
		a::cacheLoad('sections');
		$this -> setData('tree', a::treeToString(a::$arLayout));
	}


	private function getPageForm() {
		a::cacheLoad('pages');
		$this -> aData['page'] = a::$arSiteMap[$_POST['key']];
	}


	private function getSectionForm() {
		a::cacheLoad('sections');
		$this -> aData['section'] = a::$arLayout[$_POST['id']];

		if(isset($this -> aData['section']['style']) && $this -> aData['section']['style'] === true) {
			ob_start();
			include($_SERVER['DOCUMENT_ROOT']
				.DIRECTORY_SEPARATOR.'sections'
				.DIRECTORY_SEPARATOR.'style'
				.DIRECTORY_SEPARATOR.$this -> aData['section']['name'].'.css');
			$this -> aData['section']['styleContent'] = ob_get_clean();
		}

		if(isset($this -> aData['section']['type'])) {
			switch($this -> aData['section']['type'])
			{
			case 'html':
				$this -> aData['section']['content'] = 'loading...';
				break;
			case 'item':
				if(a::$arLayout[$this -> aData['section']['parent']]['type'] === 'menu') {
					$this -> aData['section']['pattern'] = a::$arLayout[$this -> aData['section']['parent']]['pattern'];
				}
				break;
			}
		}
		if(isset(a::$arLayout[$_POST['id']]['class'])) {
			$this -> aData['section']['class'] = a::$arLayout[$_POST['id']]['class'];
		}
		else {
			$this -> aData['section']['class'] = '';
		}
	}


	private function insertPage() {
		a::cacheLoad('pages');
		$index = a::treeInsertItem(
			a::$arSiteMap,
			array('name' => $_REQUEST['name']),
			array('key' => $_REQUEST['ref'], 'pos' => (int) $_REQUEST['pos']));
//add custom properties for specific pages:
		switch($_REQUEST['name']) {
		case 'admin'://reserved page
			a::$arSiteMap[$index]['security'] = a::guid();
			break;
		}

		a::treeReIndex(a::$arSiteMap);
		a::cacheSave('pages');
		$this -> setData('tree', a::treeToString(a::$arSiteMap, array('defaultType' => 'page')));
	}


	private function insertPost($index) {
		a::cacheLoad('sections');
		$iPostId = a::dbExecute(
			"INSERT INTO `posts` (`author`, `key`, `title`) VALUES("
				.$_SESSION['agate']['user']['id'].", "
				.a::dbFormat(a::$arLayout[$index]['key'], 'str').", "
				.a::dbFormat($_POST['title'], 'str')
			.")",
			DB_RETURN_LAST_ID);
		$this -> getPosts($index);
		a::dbLog(
			'insert',
			print_r(array(
				'author' => $_SESSION['agate']['user']['id'],
				'key' => a::$arLayout[$index]['key'],
				'title' => $_POST['title']), true),
			'posts',
			$iPostId);

		$aPostMeta = array();
		if(isset(a::$arLayout[$index]['metaList'])) {
			$aPostMeta = array_values(a::$arLayout[$index]['metaList']);
		}
		if(isset(a::$arLayout[$index]['metaPost'])) {
			$aPostMeta = array_merge($aPostMeta, array_values(a::$arLayout[$index]['metaPost']));
		}

		if(count($aPostMeta) > 1) {
			foreach ($aPostMeta as $k => $v) {
				a::dbExecute(
					"INSERT INTO `postmeta` (`key`, `value`, `post`) VALUES("
						.a::dbFormat($v, 'string').','
						."'',"
						.$iPostId.")");
			}
		}
	}


	private function insertSection() {
		a::cacheLoad('sections');
		$index = a::treeInsertItem(
			a::$arLayout,
			array('name' => $_REQUEST['name'], 'type' => $_REQUEST['type']),
			array('key' => $_REQUEST['ref'], 'pos' => (int) $_REQUEST['pos']));
//inheritance of showin from parent section
//		if(isset(a::$arLayout[a::$arLayout[$index]['parent']]['showin'])) {
//			a::$arLayout[$index]['showin'] = a::$arLayout[a::$arLayout[$index]['parent']]['showin'];
//		}
//inheritance of hidein from parent section
//		if(isset(a::$arLayout[a::$arLayout[$index]['parent']]['hidein'])) {
//			a::$arLayout[$index]['hidein'] = a::$arLayout[a::$arLayout[$index]['parent']]['hidein'];
//		}
//add custom properties for specific types:
		switch($_REQUEST['type'])
		{
		case 'html':
			file_put_contents(a::$config['root'].'/sections/'.$_REQUEST['name'].'.html', 'New section', FILE_APPEND);
			break;
		case 'post':
			a::$arLayout[$index]['guid'] = a::guid();
			break;
		}
		a::treeReIndex(a::$arLayout);
		a::cacheSave('sections');
		a::cacheLoad('sections');

		$this -> setData('tree', a::treeToString(a::$arLayout));
	}


	private function updatePage() {
		a::cacheLoad('pages');
		$arExcludeKeysForUpdate = array('get', 'key', 'subpage');
		$arTest = array();
		foreach($_POST as $k => $v ){
			if(!in_array($k, $arExcludeKeysForUpdate)) {
				a::$arSiteMap[$_POST['key']][$k] = $v;
			}
			else {
				$arTest[$k] = $v;
			}
		}

		if(isset($_POST['subpage']['add'])) {
			foreach($_POST['subpage']['add'] as $k => $v) {
				a::$arSiteMap[$_POST['key']]['subpage'][] = $v;
			}
		}

		if(isset($_POST['subpage']['del'])) {
			a::$arSiteMap[$_POST['key']]['subpage'] = array_values(array_diff(a::$arSiteMap[$_POST['key']]['subpage'], $_POST['subpage']['del']));
			if(count(a::$arSiteMap[$_POST['key']]['subpage']) === 0) {
				unset(a::$arSiteMap[$_POST['key']]['subpage']);
			}
		}

		a::cacheSave('pages');
	}


	private function updatePost() {
		$this -> bSuccess = false;
		if(!isset($_POST['id'])) {
			return;
		}
		$arPostToData = array(
			'title' => array('field' => 'title', 'type' => 'string'),
			'permalink' => array('field' => 'permalink', 'type' => 'string'),
			'content' => array('field' => 'content', 'type' => 'string'),
			'date' => array('field' => 'date', 'type' => 'date', 'format' => 'Y-m-d H:i'),
			'excerpt' => array('field' => 'excerpt', 'type' => 'string'));
		$sqlUpdate = '';
		foreach ($_POST AS $k => $v) {
			if (key_exists($k, $arPostToData)) {
				if (isset($arPostToData[$k]['format'])) {
					$sqlUpdate .= "`".$arPostToData[$k]['field']."` = ".a::dbFormat($v, $arPostToData[$k]['type'], 'NULL', $arPostToData[$k]['format']).',';
				}
				else {
					$sqlUpdate .= "`".$arPostToData[$k]['field']."` = ".a::dbFormat($v, $arPostToData[$k]['type']).',';
				}
			}
		}
		$iSetLen = strlen($sqlUpdate);
		if ($iSetLen > 0) {
			$sqlUpdate =
				"UPDATE `posts` "
				."SET ".substr($sqlUpdate, 0, $iSetLen - 1).' '
				."WHERE `id` = ".@$_POST['id'];
			a::dbExecute($sqlUpdate);
			$this -> bSuccess = true;
			a::dbLog('update', $sqlUpdate, 'posts', $_POST['id']);
		}

		if(isset($_POST['metaList'])) {
			foreach($_POST['metaList'] AS $k => $v) {
				a::dbExecute("UPDATE `postmeta` SET `value` = ".a::dbFormat($v, 'string')." WHERE `key` = '".$k."' AND `post` = ".$_POST['id']);
				a::dbLog('update', $k.'='.$v, 'postsmeta', $_POST['id']);
			}
			$this -> bSuccess = true;
		}
		if(isset($_POST['metaPost'])) {
			foreach($_POST['metaPost'] AS $k => $v) {
				a::dbExecute("UPDATE `postmeta` SET `value` = ".a::dbFormat($v, 'string')." WHERE `key` = '".$k."' AND `post` = ".$_POST['id']);
				a::dbLog('update', $k.'='.$v, 'postsmeta', $_POST['id']);
			}
			$this -> bSuccess = true;
		}
		/*
		else {
			$this -> bSuccess = false;
		}*/
	}


	private function updateSection() {
		a::cacheLoad('sections');
//name:
		switch(@a::$arLayout[$_POST['key']]['type']) {
		case 'html':
			$sExtension = '.html';
			break;
		case 'php':
			$sExtension = '.php';
			break;
		default:
			$sExtension = '.php';
			break;
		}
		if(isset($_POST['name'])) {
//rename files if there are associatedwith the new name:
			if (file_exists(a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.a::$arLayout[$_POST['key']]['name'].$sExtension)) {
				rename(
					a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.a::$arLayout[$_POST['key']]['name'].$sExtension,
					a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.$_POST['name'].$sExtension);
			}
//change the style acordingly to the name:#oldName => replaced with #newName;
			if (file_exists(a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.'style'.DIRECTORY_SEPARATOR.a::$arLayout[$_POST['key']]['name'].'.css')) {
				rename(
					a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.'style'.DIRECTORY_SEPARATOR.a::$arLayout[$_POST['key']]['name'].'.css',
					a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.'style'.DIRECTORY_SEPARATOR.$_POST['name'].'.css');
			}
			a::$arLayout[$_POST['key']]['name'] = $_POST['name'];
		}
//class:
		if(isset($_POST['class'])) {
			a::$arLayout[$_POST['key']]['class'] = $_POST['class'];
		}

//pattern:
		if(isset($_POST['pattern'])) {
			a::$arLayout[$_POST['key']]['pattern'] = $_POST['pattern'];
		}

//items:
		if(isset($_POST['items'])) {
			foreach($_POST['items'] as $k => $v) {
				a::$arLayout[$_POST['key']]['items'][substr($k, 1)] = $v;
			}
		}

//subpage:
		if(isset($_POST['subpage'])) {
			if($_POST['subpage'] === 'true') {
				a::$arLayout[$_POST['key']]['subpage'] = true;
			}
			else {
				unset(a::$arLayout[$_POST['key']]['subpage']);
			}
		}

//show in
		if(isset($_POST['add_showin'])) {
			foreach($_POST['add_showin'] as $k => $v) {
				a::$arLayout[$_POST['key']]['showin'][] = $v;
			}
		}

		if(isset($_POST['del_showin'])) {
			a::$arLayout[$_POST['key']]['showin'] = array_values(array_diff(a::$arLayout[$_POST['key']]['showin'], $_POST['del_showin']));
			if(count(a::$arLayout[$_POST['key']]['showin']) === 0) {
				unset(a::$arLayout[$_POST['key']]['showin']);
			}
		}

//hide in
		if(isset($_POST['add_hidein'])) {
			foreach($_POST['add_hidein'] as $k => $v) {
				a::$arLayout[$_POST['key']]['hidein'][] = $v;
			}
		}

		if(isset($_POST['del_hidein'])) {
			a::$arLayout[$_POST['key']]['hidein'] = array_values(array_diff(a::$arLayout[$_POST['key']]['hidein'], $_POST['del_hidein']));
			if(count(a::$arLayout[$_POST['key']]['hidein']) === 0) {
				unset(a::$arLayout[$_POST['key']]['hidein']);
			}
		}

//content:
		if(isset($_POST['html'])) {
			$sFileName = a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.a::$arLayout[$_POST['key']]['name'].$sExtension;
			$sNewContent = $_POST['html'];

			file_put_contents($sFileName, $_POST['html'], LOCK_EX);
		}

//style:
		if(isset($_POST['style'])) {
			$sNewStyle = $_POST['style'];
			$sFileName = a::$config['root'].DIRECTORY_SEPARATOR.'sections'.DIRECTORY_SEPARATOR.'style'.DIRECTORY_SEPARATOR.a::$arLayout[$_POST['key']]['name'].'.css';
			if(strlen($sNewStyle) === 0) {
				a::$arLayout[$_POST['key']]['style'] = false;
				if (file_exists($sFileName)) {
					unlink($sFileName);
				}
			}
			else {
				a::$arLayout[$_POST['key']]['style'] = true;
				$sNewStyle = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '',$sNewStyle); //remove comments
				$sNewStyle = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $sNewStyle); //remove tabs, spaces, newlines, etc.
				$sNewStyle = str_replace(array('}#', ';}', ':;'), array("}\r\n#", '}', ':'), $sNewStyle); //made the result readable
				file_put_contents($sFileName, $sNewStyle, LOCK_EX);
			}
		}

//apply changes:
		a::cacheSave('sections');
	}

}

$oAdminResponse = new adminAjax;
$oAdminResponse -> response();
?>