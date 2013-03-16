<?php
//turn access
$this->ACL->turn(array($this->module, 'edit_comments'));
$id = (!empty($id)) ? (int)$id : 0;
if ($id < 1) redirect($this->getModuleURL());


$commentsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Comments');
if (!$commentsModel) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
$comment = $commentsModel->getById($id);
if (!$comment) return $this->showInfoMessage(__('Comment not found'), $this->getModuleURL());


// Categories tree
$entity = $this->Model->getById($comment->getEntity_id());
if ($entity && $entity->getCategory_id()) {
	$this->categories = $this->_getCatsTree($entity->getCategory_id());
} else {
	$this->categories = $this->_getCatsTree();
}


$markers = array();
$markers['disabled'] = ($comment->getUser_id()) ? ' disabled="disabled"' : '';


// Если при заполнении формы были допущены ошибки
if (isset($_SESSION['editCommentForm'])) {
	$errors   = $_SESSION['editCommentForm']['error'];
	$message  = $_SESSION['editCommentForm']['message'];
	$name     = $_SESSION['editCommentForm']['name'];
	unset($_SESSION['editCommentForm']);
} else {
	$errors = '';
	$message = $comment->getMessage();
	$name    = $comment->getName();
}


$markers['action'] = get_url($this->getModuleURL('/update_comment/' . $id));
$markers['errors'] = $errors;
$markers['name'] = h($name);
$markers['message'] = h($message);
$source = $this->render('editcommentform.html', array(
	'form' => $markers,
	'comment' => $comment,
));
$this->comments = '';

return $this->_view($source);
