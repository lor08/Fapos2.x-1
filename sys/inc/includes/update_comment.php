<?php
//turn access
$this->ACL->turn(array($this->module, 'edit_comments'));
$id = (!empty($id)) ? (int)$id : 0;
if ($id < 1) return $this->showInfoMessage(__('Unknown error'), $this->getModuleURL(), 1);


$commentsModel = $this->Register['ModManager']->getModelInstance('Comments');
if (!$commentsModel) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL(), 1);
$comment = $commentsModel->getById($id);
if (!$comment) return $this->showInfoMessage(__('Comment not found'), $this->getModuleURL(), 1);


/* cut and trim values */
if ($comment->getUser_id() > 0) {
	$name = $comment->getName();
} else {
	$name = mb_substr($_POST['login'], 0, 70);
	$name = trim($name);
}


$mail = '';
$message = (!empty($_POST['message'])) ? $_POST['message'] : '';
$message = mb_substr($message, 0, Config::read('comment_lenght', $this->module));
$message = trim($message);


$error = '';
$valobj = $this->Register['Validate'];
if (empty($name)) {
	$error .= '<li>' . __('Empty field "login"') . '</li>' . "\n";
} elseif (!$valobj->cha_val($name, V_TITLE)) {
	$error .= '<li>' . __('Wrong chars in field "login"') . '</li>' . "\n";
}
if (empty($message)) $error .= '<li>' . __('Empty field "text"') . '</li>' . "\n";

	
/* if an error */
if (!empty($error)) {
	$_SESSION['editCommentForm'] = array();
	$_SESSION['editCommentForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'
		. "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
	$_SESSION['editCommentForm']['message'] = $message;
	$_SESSION['editCommentForm']['name'] = $name;
	return $this->showInfoMessage($_SESSION['editCommentForm']['error'], $this->getModuleURL('/edit_comment_form/' . $id), 1);
}


//remove cache
$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $comment->getEntity_id()));
$this->DB->cleanSqlCache();


// Update comment
$comment->setMessage($message);
$comment->setEditdate(new Expr('NOW()'));
if ($name) $comment->setName($name);
$comment->save();


if ($this->Log) $this->Log->write('editing comment for ' . $this->module, $this->module . ' id(' . $comment->getEntity_id() . '), comment id(' . $id . ')');
return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('/view/' . $comment->getEntity_id()));
?>