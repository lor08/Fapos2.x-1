<?php
$id = (int)$entity->getId();
if (empty($id) || $id < 1) $html = true;

$commentsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Comments');

if (empty($html) && $commentsModel) {
	$commentsModel->bindModel('Users');

	/* pages nav */
	$total = $commentsModel->getTotal(array('cond' => array('entity_id' => $id)));
	$per_page = intval(Config::read('comment_per_page', $this->module));
	if ($per_page < 1) $per_page = 10;
    list($pages, $page) = pagination($total, $per_page,  $this->getModuleURL('view/' . $id));
	$this->_globalize(array('comments_pagination' => $pages));


	$order_way = (Config::read('comments_order', $this->module)) ? 'DESC' : 'ASC';
	$params = array(
		'page'  => $page,
		'limit' => $per_page,
		'order' => 'date ' . $order_way,
	);
	$comments = $commentsModel->getCollection(array('entity_id' => $id), $params);
	if ($comments) {
		foreach ($comments as $comment) {
			$markers = array();


			// COMMENT ADMIN BAR
			$ip = ($comment->getIp()) ? $comment->getIp() : 'Unknown';
			$moder_panel = '';
			if ($this->ACL->turn(array($this->module, 'edit_comments'), false)) {
				$moder_panel .= get_link('', $this->getModuleURL('/edit_comment_form/' . $comment->getId()), array('class' => 'fps-edit', 'title' => __('Edit')));
			}

			if ($this->ACL->turn(array($this->module, 'delete_comments'), false)) {
				$moder_panel .= get_link('', $this->getModuleURL('/delete_comment/' . $comment->getId()), array('class' => 'fps-delete', 'title' => __('Delete'), 'onClick' => "return confirm('" . __('Are you sure') . "')"));
			}

			if (!empty($moder_panel)) {
				$moder_panel .= '<a target="_blank" href="https://apps.db.ripe.net/search/query.html?searchtext=' . h($ip) . '" class="fps-ip" title="IP: ' . h($ip) . '"></a>';
			}

			$img = array(
				'alt' => 'User avatar',
				'title' => h($comment->getName()),
				'class' => 'ava',
			);
			$markers['avatar'] = '<img class="ava" src="' . getAvatar($comment->getUser_id()) . '" alt="User avatar" />';


			if ($comment->getUser_id()) {
				$markers['name_a'] = get_link(h($comment->getName()), getProfileUrl((int)$comment->getUser_id()));
				$markers['user_url'] = get_url(getProfileUrl((int)$comment->getUser_id()));
				$markers['avatar'] = get_link($markers['avatar'], $markers['user_url']);
			} else {
				$markers['name_a'] = h($comment->getName());
			}
			$markers['name'] = h($comment->getName());


			$markers['moder_panel'] = $moder_panel;
			$markers['message'] = $this->Textarier->print_page($comment->getMessage());

			if ($comment->getEditdate()!='0000-00-00 00:00:00') {
				$markers['editdate'] = 'Комментарий был изменён '.$comment->getEditdate();
			} else {
				$markers['editdate'] = '';
			}

			$comment->setAdd_markers($markers);
		}
	}
	$html = $this->render('viewcomment.html', array('commentsr' => $comments));


} else {
	$html = '';
}
