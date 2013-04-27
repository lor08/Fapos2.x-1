<?php
/*-----------------------------------------------\
| 												 |
|  @Author:       Andrey Brykin (Drunya)         |
|  @Version:      1.6.70                         |
|  @Project:      CMS                            |
|  @package       CMS Fapos                      |
|  @subpackege    Forum Module                   |
|  @copyright     ©Andrey Brykin 2010-2013       |
|  @last mod.     2013/03/30                     |
\-----------------------------------------------*/

/*-----------------------------------------------\
| 												 |
|  any partial or not partial extension          |
|  CMS Fapos,without the consent of the          |
|  author, is illegal                            |
|------------------------------------------------|
|  Любое распространение                         |
|  CMS Fapos или ее частей,                      |
|  без согласия автора, является не законным     |
\-----------------------------------------------*/


/**
 * forum functionaly
 *
 * @author      Andrey Brykin
 * @package     CMS Fapos
 * @subpackage  Forum module
 * @link        http://cms.develdo.com
 */
Class ForumModule extends Module {

	/**
	 * @module_title  title of module
	 */
	public $module_title = 'Форум';

	/**
	 * @template  layout for module
	 */
	public $template = 'forum';

	/**
	 * @module module indentifier
	 */
	public $module = 'forum';

	/**
	 * @return main forum page content
	 */
	public function index($cat_id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_forums_list'));
		$this->page_title = h($this->Register['Config']->read('title', $this->module)) . __('Forums list');


		// navigation block
		$markers = array();
		$markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), '/forum/') . "\n";
		$markers['pagination'] = '';
		$markers['add_link'] = '';
		$markers['meta'] = '';
		$this->_globalize($markers);


		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$html = $this->Cache->read($this->cacheKey) . $this->_get_stat();
			return $this->_view($html);
		}



		$conditions = array();
		if (!empty($cat_id) && is_numeric($cat_id)) {
			$cat_id = intval($cat_id);
			if ($cat_id > 0) {
				$conditions['id'] = $cat_id;
			}
		}

		//get forums categories records
		$catsModel = $this->Register['ModManager']->getModelInstance('ForumCat');
		$cats = $catsModel->getCollection(array($conditions), array('order' => 'previev_id'));
		if (empty($cats)) {
			$html = __('No categories') . "\n" . $this->_get_stat();
			return $this->_view($html);
		}
		//pr($cats); die();





		$conditions = (!empty($conditions)) ? array('in_cat' => $cat_id) : array();
		$conditions[] = array("`parent_forum_id` IS NULL OR `parent_forum_id` = '0'");
		$this->Model->bindModel('last_theme');
		$this->Model->bindModel('subforums');
		$_forums = $this->Model->getCollection($conditions, array(
			'order' => 'pos',
				));
		$_forums = $this->Model->addLastAuthors($_forums);



		//pr($_forums); die();
		//sort forums and subforums
		//after this we will be have $categories array with all cats, forum and subforums
		$forums = array();
		$categories = array();
		if (count($_forums) > 0) {
			foreach ($_forums as $forum) {
				$forums[$forum->getIn_cat()][] = $forum;
			}
		}


		foreach ($cats as $category) {
			$categories[$category->getId()] = $category;
			$categories[$category->getId()]->setForums(array());
			if (array_key_exists($category->getId(), $forums)) {
				$categories[$category->getId()]->setForums($forums[$category->getId()]);
				unset($forums[$category->getId()]); //clean memory
			} else {
				unset($categories[$category->getId()]); //we needen't empty categories
			}
		}



		foreach ($categories as $cat) {
			$cat->setCat_url(get_url($this->getModuleURL('index/' . $cat->getId())));
			$forums = $cat->getForums();
			if ($forums && !empty($forums)) {
				foreach ($forums as $forum) {
					if ($forum) {
						$forum = $this->_parseForumTable($forum);
					}
				}
			}
		}


		//write to cache ( only if records detected )
		if ($this->cached)
			$this->Cache->write($html, $this->cacheKey, $this->cacheTags);



		$source = $this->render('catlist.html', array('forum_cats' => $categories));
		$source .= $this->_get_stat();
		return $this->_view($source);
	}

	/**
	 * @param array $forum
	 * @retrun string HTML forum table wiht replaced markers
	 */
	private function _parseForumTable($forum) {
		// Summ posts and themes
		if ($forum->getSubforums() && count($forum->getSubforums()) > 0) {
			foreach ($forum->getSubforums() as $subforum) {
				$forum->setPosts($forum->getPosts() + $subforum->getPosts());
				$forum->setThemes($forum->getThemes() + $subforum->getThemes());
			}
		}


		$forum->setForum_url(get_url($this->getModuleURL('view_forum/' . $forum->getId())));



		//выводим название темы в которой было добавлено последнее сообщение и дату его добавления
		if ($forum->getLast_theme_id() < 1) {
			$last_post = __('No posts');
		} else {
			if (!$forum->getLast_theme() || !$forum->getLast_author()) {
				$themesClass = $this->Register['ModManager']->getModelInstance('Themes');
				$themesClass->bindModel('last_author');
				$theme = $themesClass->getById($forum->getLast_theme_id());
				if ($theme) {
					$forum->setLast_theme($theme);
					$forum->setLast_author($theme->getLast_author());
				}
			}
			if (!$forum->getLast_theme() || !$forum->getLast_author()) {
				$last_post = __('No posts');
			} else {
				$last_post_title = (mb_strlen($forum->getLast_theme()->getTitle()) > 30) ? mb_substr($forum->getLast_theme()->getTitle(), 0, 30) . '...' : $forum->getLast_theme()->getTitle();


				$last_theme_author = __('Guest');
				if ($forum->getLast_author()) {
					$last_theme_author = get_link(h($forum->getLast_author()->getName()), getProfileUrl($forum->getLast_author()->getId()), array('title' => __('To profile')));
				}


				$id_last_post = $forum->getLast_theme()->getId_last_post();
				$last_post = $forum->getLast_theme()->getLast_post() . '<br>' . get_link(h($last_post_title), $this->getModuleURL($id_last_post ? 'view_post/' . $id_last_post :
												'view_theme/' . $forum->getLast_theme()->getId() . '?page=999'), array('title' => __('To last post')))
						. __('Post author') . $last_theme_author;
			}
		}
		$forum->setLast_post($last_post);



		// Ссылка "Править форум"
		$admin_bar = '';
		if ($this->ACL->turn(array($this->module, 'replace_forums'), false)) {
			$admin_bar .= get_link('', $this->getModuleURL('forum_up/' . $forum->getId()), array('class' => 'fps-up'))
					. '&nbsp;' . get_link('', $this->getModuleURL('forum_down/' . $forum->getId()), array('class' => 'fps-down')) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'edit_forums'), false)) {
			$admin_bar .= get_link('', $this->getModuleURL('edit_forum_form/' . $forum->getId()), array('class' => 'fps-edit')) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'delete_forums'), false)) {
			$admin_bar .= get_link('', $this->getModuleURL('delete_forum/' . $forum->getId()), array('class' => 'fps-delete', 'onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		$forum->setAdmin_bar($admin_bar);


		/* forum icon */
		$forum_icon = get_url('/template/' . getTemplateName() . '/img/guest.png');
		if (file_exists(ROOT . '/sys/img/forum_icon_' . $forum->getId() . '.jpg')) {
			$forum_icon = get_url('/sys/img/forum_icon_' . $forum->getId() . '.jpg');
		}
		$forum->setIcon_url($forum_icon);
		return $forum;
	}

	/**
	 * View threads list (forum)
	 */
	public function view_forum($id_forum = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_forums'));
		$id_forum = intval($id_forum);
		if ($id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());



		//who is here
		$who = array();
		$dir = ROOT . '/sys/logs/forum/';
		$forumFile = $dir . $id_forum . '.dat';
		if (!file_exists($dir))
			mkdir($dir, 0777, true);
		if (file_exists($forumFile)) {
			$who = unserialize(file_get_contents($forumFile));
		}


		if (isset($_SESSION['user'])) {
			if (!isset($who[$_SESSION['user']['id']])) {
				$who[$_SESSION['user']['id']]['profile_link'] = get_link(h($_SESSION['user']['name']), getProfileUrl($_SESSION['user']['id']));
				$who[$_SESSION['user']['id']]['expire'] = time() + 1000;
			}
		}


		$who_is_here = '';
		foreach ($who as $key => $val) {
			if ($val['expire'] < time()) {
				unset($who[$key]);
				continue;
			}
			$who_is_here .= $val['profile_link'] . ', ';
		}
		file_put_contents($forumFile, serialize($who));
		//$context = array('who_is_here', substr($who_is_here, 0, -2));
		//are we have cache?
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$html = $this->Cache->read($this->cacheKey);
		} else {


			// Получаем информацию о форуме
			$this->Model->bindModel('subforums');
			$this->Model->bindModel('category');
			$this->Model->bindModel('last_theme');
			$forum = $this->Model->getById($id_forum);
			if (!$forum) {
				return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
			}


			// Check access to this forum. May be locked by pass or posts count
			$this->__checkForumAccess($forum);
			$this->page_title = h($forum->getTitle()) . ' - ' . $this->page_title;



			// reply link
			$addLink = ($this->ACL->turn(array($this->module, 'add_themes', $id_forum), false)) ? get_link(get_img('/template/' . getTemplateName() . '/img/add_theme_button.png', array('alt' => __('New topic'))), '/forum/add_theme_form/' . $id_forum) : '';


			// count themes for page nav
			$themesClass = $this->Register['ModManager']->getModelInstance('Themes');
			$themesClass->bindModel('author');
			$themesClass->bindModel('last_author');
			$total = $themesClass->getTotal(array('cond' => array('id_forum' => $id_forum)));


			$perPage = intval($this->Register['Config']->read('themes_per_page', $this->module));
			if ($perPage < 1)
				$perPage = 10;
			list($pages, $page) = pagination($total, $perPage, $this->getModuleURL('view_forum/' . $id_forum));
			$this->page_title .= ' (' . $page . ')';


			$themes = $themesClass->getCollection(
					array(
				'id_forum' => $id_forum
					), array(
				'page' => $page,
				'limit' => $perPage,
				'order' => 'important DESC, last_post DESC, id DESC',
					)
			);


			// Nav block
			$markers = array();
			$markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
					. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator')
					. get_link(h($forum->getTitle()), $this->getModuleURL('view_forum/' . $id_forum));

			$cntPages = ceil($total / $perPage);
			$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
			$firstOnPage = ($page - 1) * $perPage + 1;
			$lastOnPage = $firstOnPage + $recOnPage - 1;

			$markers['pagination'] = $pages;
			$markers['add_link'] = $addLink;
			$markers['meta'] = __('Count all topics') . ' ' . $total . '. ' . __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage;
			$this->_globalize($markers);


			$subforums = $forum->getSubforums();
			if (count($subforums) > 0) {
				foreach ($subforums as $subforum) {
					$subforum = $this->_parseForumTable($subforum);
				}

				$forum->setCat_name(__('Subforums title'));
			}




			$cnt_themes_here = count($themes);
			if ($cnt_themes_here > 0 && is_array($themes)) {
				foreach ($themes as $theme) {

					$theme = $this->__parseThemeTable($theme);

					//set cache tags
					$this->setCacheTag(array(
						'theme_id_' . $theme->getId(),
					));
				}
				$this->setCacheTag(array(
					'forum_id_' . $id_forum,
				));
			}


			$forum->setCount_themes_here($cnt_themes_here);
			$forum->setWho_is_here(substr($who_is_here, 0, -2));
			//$forum->setCount_themes(count($themes));
			//write cache
			if ($this->cached)
				$this->Cache->write($html, $this->cacheKey, $this->cacheTags);
		}



		$source = $this->render('themes_list.html', array(
			'themes' => $themes,
			'forum' => $forum,
				));
		return $this->_view($source);
	}

	/**
	 * Check access to this forum.
	 * May be locked by pass or posts count
	 *
	 * @param array $forum
	 */
	private function __checkForumAccess($forum) {
		if (!$forum->getLock_passwd() && !$forum->getLock_posts())
			return;


		if ($forum->getLock_passwd()) {
			if (isset($_SESSION['access_forum_' . $forum->getId()])) {
				return;

				// if we have two generally meet one of them
			} else if ($forum->getLock_posts() &&
					(isset($_SESSION['user']['posts']) && $_SESSION['user']['posts'] >= $forum->getLock_posts())) {
				return;

				// Check sended password
			} else if (isset($_POST['forum_lock_pass'])) {
				if ($_POST['forum_lock_pass'] == $forum->getLock_passwd()) {
					$_SESSION['access_forum_' . $forum->getId()] = true;
					return;
				}
				$this->showInfoMessage(__('Wrong pass for forum'), $this->getModuleURL());
			} else {
				echo $this->render('forum_passwd_form.html', array());
				die();
			}


			// For lock by posts count
		} else if ($forum->getLock_posts()) {
			if (isset($_SESSION['user']['posts']) && $_SESSION['user']['posts'] >= $forum->getLock_posts()) {
				return;
			}
			$this->showInfoMessage(sprintf(__('locked forum by posts'), $forum->getLock_posts()), $this->getModuleURL());
		}
	}

	/**
	 * @param array $theme
	 * @retrun string HTML theme table with replaced markers
	 */
	private function __parseThemeTable($theme) {
		//ICONS
		$themeicon = $this->__getThemeIcon($theme);

		$theme->setTheme_url(get_url($this->getModuleURL('view_theme/' . $theme->getId())));


		//ADMINBAR
		$adminbar = '';
		if ($this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()), false)
				|| (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_themes', $theme->getId_forum()), false))) {
			$adminbar .= get_link('', $this->getModuleURL('edit_theme_form/' . $theme->getId()), array('class' => 'fps-edit'));
		}
		if ($this->ACL->turn(array($this->module, 'close_themes', $theme->getId_forum()), false)) {
			if ($theme->getLocked() == 0) { // заблокировать тему
				$adminbar .= get_link('', $this->getModuleURL('lock_theme/' . $theme->getId()), array('class' => 'fps-close'));
			} else { // разблокировать тему
				$adminbar .= get_link('', $this->getModuleURL('unlock_theme/' . $theme->getId()), array('class' => 'fps-open'));
			}
		}


		if ($this->ACL->turn(array($this->module, 'important_themes'), false)) {
			if ($theme->getImportant() == 1) {
				$adminbar .= get_link('', $this->getModuleURL('unimportant/' . $theme->getId()), array('class' => 'fps-unfix'));
			} else {
				$adminbar .= get_link('', $this->getModuleURL('important/' . $theme->getId()), array('class' => 'fps-fix'));
			}
		}


		if ($this->ACL->turn(array($this->module, 'delete_themes', $theme->getId_forum()), false)
				|| (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'delete_mine_themes', $theme->getId_forum()), false))) {
			$adminbar .= get_link('', $this->getModuleURL('delete_theme/' . $theme->getId()), array('class' => 'fps-delete', 'onClick' => "return confirm('" . __('Are you sure') . "')"));
		}
		$theme->setAdminbar($adminbar);


		//USER PROFILE
		$author_url = __('Guest');
		if ($theme->getId_author() && $theme->getAuthor()) {
			$author_url = get_link(h($theme->getAuthor()->getName()), getProfileUrl($theme->getId_author()));
		}
		$theme->setAuthorUrl($author_url);


		// Last post author
		$last_user = __('Guest');
		if ($theme->getId_last_author() && $theme->getLast_author()) {
			$last_user = get_link(h($theme->getLast_author()->getName()), getProfileUrl($theme->getId_last_author()));
		}
		$id_last_page = $theme->getId_last_post();
		$last_page = get_link(__('To last'), $this->getModuleURL($id_last_page ? 'view_post/' . $id_last_page :
								'view_theme/' . $theme->getId() . '&page=99999'));


		//NEAR PAGES
		$near_pages = '';
		if (($theme->getPosts() + 1) > $this->Register['Config']->read('posts_per_page', $this->module)) {
			$cnt_near_pages = ceil(($theme->getPosts() + 1) / $this->Register['Config']->read('posts_per_page', $this->module));
			if ($cnt_near_pages > 1) {
				$near_pages .= '&nbsp;(';
				for ($n = 1; $n < ($cnt_near_pages + 1); $n++) {
					if ($cnt_near_pages > 5 && $n > 3) {
						$near_pages .= '&nbsp;...&nbsp;' . get_link(($cnt_near_pages - 1), $this->getModuleURL('view_theme/' . $theme->getId() . '?page='
												. ($cnt_near_pages - 1))) . '&nbsp;' . get_link($cnt_near_pages, $this->getModuleURL('view_theme/'
												. $theme->getId() . '?page=' . $cnt_near_pages));
						break;
					} else {
						if ($n > 5)
							break;
						$near_pages .= ($n > 1 ? '&nbsp;' : '') . get_link($n, $this->getModuleURL('view_theme/' . $theme->getId() . '?page=' . $n));
					}
				}
				$near_pages .= ')';
			}
		}


		$theme->setLast_page($last_page);
		$theme->setLast_user($last_user);
		$theme->setThemeicon($themeicon);
		$theme->setFps_css_class(($theme->getImportant()) ? 'fps-theme-important' : '');
		$theme->setNear_pages($near_pages);
		$theme->setImportantly(($theme->getImportant() == 1) ? __('Important2') : '');


		return $theme;
	}

	/**
	 * Return theme icon
	 *
	 * @param array $theme
	 * @return string img HTML tag with URL to needed icon
	 */
	private function __getThemeIcon($theme) {
		$hot_theme_limit = 20;

		if (isset($_SESSION['user'])) { // это для зарегистрированного пользователя
			// Если есть новые сообщения (посты) - только для зарегистрированных пользователей
			if (isset($_SESSION['newThemes']) and in_array($theme->getiId(), $_SESSION['newThemes'])) {
				if ($theme->getLocked() == 0) // тема открыта
					if ($theme->getPosts() > $hot_theme_limit)
						$themeicon = get_img('/template/' . getTemplateName() . '/img/folder_hot_new.gif'
								, array('class' => 'themeicon', 'alt' => __('New posts'), 'title' => __('New posts')));
					else
						$themeicon = get_img('/template/' . getTemplateName() . '/img/folder_new.gif'
								, array('class' => 'themeicon', 'alt' => __('New posts'), 'title' => __('New posts')));
				else // тема закрыта
					$themeicon = get_img('/template/' . getTemplateName() . '/img/folder_lock_new.gif'
							, array('class' => 'themeicon', 'alt' => __('New posts'), 'title' => __('New posts')));
			} else {
				if ($theme->getLocked() == 0) // тема открыта
					if ($theme->getPosts() > $hot_theme_limit)
						$themeicon = get_img('/template/' . getTemplateName() . '/img/folder_hot.gif'
								, array('class' => 'themeicon', 'alt' => __('No new posts'), 'title' => __('No new posts')));
					else
						$themeicon = get_img('/template/' . getTemplateName() . '/img/folder.gif'
								, array('class' => 'themeicon', 'alt' => __('No new posts'), 'title' => __('No new posts')));
				else // тема закрыта
					$themeicon = get_img('/template/' . getTemplateName() . '/img/folder_lock.gif'
							, array('class' => 'themeicon', 'alt' => __('No new posts'), 'title' => __('No new posts')));
			}
		} else { // это для не зарегистрированного пользователя
			if ($theme->getLocked() == 0) // тема открыта
				if ($theme->getPosts() > $hot_theme_limit)
					$themeicon = get_img('/template/' . getTemplateName() . '/img/folder_hot.gif'
							, array('class' => 'themeicon'));
				else
					$themeicon = get_img('/template/' . getTemplateName() . '/img/folder.gif'
							, array('class' => 'themeicon'));
			else // тема закрыта
				$themeicon = get_img('/template/' . getTemplateName() . '/img/folder_lock.gif'
						, array('class' => 'themeicon'));
		}

		return $themeicon;
	}

	/**
	 * Return posts list
	 */
	public function view_theme($id_theme = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_themes'));
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());



		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themeModel->bindModel('forum');
		$themeModel->bindModel('poll');
		$theme = $themeModel->getById($id_theme);
		if (!$theme || !$theme->getForum())
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		//turn access
		$this->ACL->turn(array($this->module, 'view_themes', $theme->getId_forum()));


		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($theme->getForum());
		$id_forum = $theme->getId_forum();

		$this->__checkThemeAccess($theme);



		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
		} else {


			// Если запрошенной темы не существует - возвращаемся на форум
			if (empty($theme))
				return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


			// Заголовок страницы (содержимое тега title)
			$this->page_title = h($theme->getTitle()) . ' - ' . $this->page_title;



			$markers = array();
			$markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
					. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator') . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme));
			$description = h($theme->getDescription());
			if (!empty($description)) {
				$markers['navigation'] .= ' (' . $description . ')';
			}


			// Page nav
			$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
			$total = $postsModel->getTotal(array('cond' => array('id_theme' => $id_theme)));

			if ($total === 0) {
				$this->__delete_theme($theme);
				if ($this->Log)
					$this->Log->write('delete theme (because error uccured)', 'theme id(' . $id_theme . ')');
				return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL('view_forum/' . $id_forum));
			}
			list($pages, $page) = pagination($total, $this->Register['Config']->read('posts_per_page', $this->module), $this->getModuleURL('view_theme/' . $id_theme));
			$markers['pagination'] = $pages;
			$this->page_title .= ' (' . $page . ')';



			// SELECT posts
			$postsModel->bindModel('author');
			$postsModel->bindModel('editor');
			$postsModel->bindModel('attacheslist');
			$posts = $postsModel->getCollection(array(
				'id_theme' => $id_theme,
					), array(
				'order' => 'time ASC, id ASC',
				'page' => $page,
				'limit' => $this->Register['Config']->read('posts_per_page', $this->module),
					));



			// Ссылка "Ответить" (если тема закрыта - выводим сообщение "Тема закрыта")
			if ($theme->getLocked() == 0) {
				$markers['add_link'] = get_link(get_img('/template/'
								. getTemplateName() . '/img/reply.png', array('alt' => __('Answer'),
							'title' => __('Answer'))), $this->getModuleURL('view_theme/' . $id_theme . '#sendForm'));
			} else {
				$markers['add_link'] = get_img('/template/'
						. getTemplateName() . '/img/reply_locked.png', array('alt' => __('Theme is locked'), 'title' => __('Theme is locked')));
			}
			$admin_bar = array();
			if ($this->ACL->turn(array($this->module, 'edit_themes', $id_forum), false)) {
				$admin_bar[] = array('url' => get_url($this->getModuleURL('move_posts_form/' . $id_theme)), 'title' => __('Move posts'));
				if ($this->ACL->turn(array($this->module, 'add_themes', $id_forum), false)) {
					$admin_bar[] = array('url' => get_url($this->getModuleURL('split_theme_form/' . $id_theme)), 'title' => __('Split theme'));
				}
				$admin_bar[] = array('url' => get_url($this->getModuleURL('edit_theme_form/' . $id_theme)), 'title' => __('Edit theme'));
				$admin_bar[] = array('url' => get_url($this->getModuleURL('unite_themes_form/' . $id_theme)), 'title' => __('Unite themes'));
			}
			if ($this->ACL->turn(array($this->module, 'close_themes', $theme->getId_forum()), false)) {
				if ($theme->getLocked() == 0) {
					$admin_bar[] = array('url' => get_url($this->getModuleURL('lock_theme/' . $theme->getId())), 'title' => __('Lock theme'));
				} else {
					$admin_bar[] = array('url' => get_url($this->getModuleURL('unlock_theme/' . $theme->getId())), 'title' => __('Unlock theme'));
				}
			}
			if ($this->ACL->turn(array($this->module, 'important_themes'), false)) {
				if ($theme->getImportant() == 1) {
					$admin_bar[] = array('url' => get_url($this->getModuleURL('unimportant/' . $theme->getId())), 'title' => __('Unimportant theme'));
				} else {
					$admin_bar[] = array('url' => get_url($this->getModuleURL('important/' . $theme->getId())), 'title' => __('Important theme'));
				}
			}
			/*
			  // Необходимо добавить подтверждение удаления темы
			  if ($this->ACL->turn(array($this->module, 'delete_themes', $theme->getId_forum()), false)
			  || (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id']
			  && $this->ACL->turn(array($this->module, 'delete_mine_themes', $theme->getId_forum()), false))) {
			  $admin_bar[] = array('url' => get_url($this->getModuleURL('delete_theme/' . $theme->getId())), 'title' => __('Delete theme'));
			  }
			 */
			if ($admin_bar && is_array($admin_bar) && count($admin_bar) > 0) {
				$markers['admin_bar'] = '<form name="admin_bar"><table class="admin_bar"><tr><td><select id="admin_bar_select"><option value="">' . __('Theme admin panel') . ':</option>';
				foreach ($admin_bar as $index => $command) {
					$markers['admin_bar'] .= '<option value="' . $command['url'] . '">- ' . $command['title'] . '</option>';
				}
				$markers['admin_bar'] .= '</select></td><td><input type="button" value="OK" onclick="if (document.admin_bar.admin_bar_select.selectedIndex>0)location.href=document.admin_bar.admin_bar_select.value" /></td></tr></table></form>';
			} else {
				$markers['admin_bar'] = '';
			}


			if (!$this->ACL->turn(array($this->module, 'add_posts', $theme->getId_forum()), false))
				$markers['add_link'] = '';
			$markers['meta'] = '';
			$this->_globalize($markers);


			$first_top = false;
			if ($page > 1 && $theme->getFirst_top() == '1') {
				$post = $postsModel->getFirst(array(
					'id_theme' => $id_theme,
						), array(
					'order' => 'time ASC, id ASC',
						));
				if ($post) {
					array_unshift($posts, $post);
					$first_top = true;
				}
			}
			$this->setCacheTag('theme_id_' . $id_theme);


			// Polls render
			$polls = $theme->getPoll();
			if (is_array($polls) && count($polls) && !empty($polls[0])) {
				$theme->setPoll($this->_renderPoll($polls[0]));
			} else {
				$theme->setPoll('');
			}



			$markers = array(
				'reply_form' => $this->add_post_form($theme),
			);
			$this->_globalize($markers);


			$source = $this->render('posts_list.html', array(
				'posts' => $this->__parsePostsTable($posts, $page, $first_top, $theme),
				'theme' => $theme,
					));


			//write into cache
			if ($this->cached)
				$this->Cache->write($source, $this->cacheKey, $this->cacheTags);
		}


		// Если страницу темы запросил зарегистрированный пользователь, значит он ее просмотрит
		if (isset($_SESSION['user']) and isset($_SESSION['newThemes'])) {
			if (count($_SESSION['newThemes']) > 0) {
				if (in_array($id_theme, $_SESSION['newThemes'])) {
					unset($_SESSION['newThemes'][$id_theme]);
				}
			} else {
				unset($_SESSION['newThemes']);
			}
		}

		if (empty($_SESSION['VIEW_PAGE']) || $_SESSION['VIEW_PAGE'] != 'theme' . $id_theme) {
			$theme->setViews($theme->getViews() + 1);
			$theme->save();
			$_SESSION['VIEW_PAGE'] = 'theme' . $id_theme;
		}

		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('action_viev_forum', 'theme_id_' . $id_theme));
		return $this->_view($source);
	}

	/**
	 * Parse posts table
	 */
	private function __parsePostsTable($posts, $page, $first_top = false, $one_theme = null) {
		if ($posts) {
			$post_num = (($page - 1) * $this->Register['Config']->read('posts_per_page', $this->module));

			//serialize rating settings
			$settingsModel = $this->Register['ModManager']->getModelInstance('UsersSettings');
			$rating_settings = $settingsModel->getFirst(array('type' => 'rating'));
			$rating_settings = $rating_settings ? $rating_settings->getValues() : '';

			foreach ($posts as $post) {
				// Если автор сообщения (поста) - зарегистрированный пользователь
				$postAuthor = $post->getAuthor();
				$author_status = ($postAuthor && $postAuthor->getStatus()) ? $postAuthor->getStatus() : 0;
				if ($postAuthor) {
					if (!property_exists($postAuthor, 'processComplete')) {
						// Аватар
						$postAuthor->setAvatar(getAvatar($post->getId_author()));


						// Статус пользователя
						$status = $this->ACL->get_group_info();
						$user_status = $status[$author_status];
						$postAuthor->setStatus_title($user_status['title']);


						// Рейтинг пользователя (по количеству сообщений)
						$rating = $postAuthor->getPosts();
						$rank_star = getUserRating($rating, $rating_settings);
						$postAuthor->setRank($rank_star['rank']);
						if ($postAuthor->getState())
							$postAuthor->setRank($postAuthor->getState());
						$postAuthor->setUser_rank(get_img('/template/' . getTemplateName() . '/img/' . $rank_star['img']));


						// Если автор сообщения сейчас "на сайте"
						$users_on_line = getOnlineUsers();
						if (isset($users_on_line) && isset($users_on_line[$post->getId_author()])) {
							$postAuthor->setStatus_on(__('Online'));
							$postAuthor->setStatus_line('Online');
						} else {
							$postAuthor->setStatus_on(__('Offline'));
							$postAuthor->setStatus_line('Offline');
						}


						// Если пользователь заблокирован
						if ($postAuthor->getBlocked()) {
							$postAuthor->setStatus_on('<span class="statusBlock">' . __('Banned') . '</span>');
							$postAuthor->setStatus_line('');
						}


						$signature = ($postAuthor->getSignature()) ? $this->Textarier->getSignature($postAuthor->getSignature(), $author_status) : '';
						$postAuthor->setSignature($signature);


						// If author is authorized user.
						$email = '';
						$privat_message = '';
						$author_site = '';
						$user_profile = '';
						$icon_params = array('class' => 'user-details');


						if ($post->getId_author()) {
							$user_profile = '&nbsp;' . get_link(get_img('/template/' . getTemplateName() . '/img/icon_profile.png', array('alt' => __('View profile'), 'title' => __('View profile'))), getProfileUrl($post->getId_author()), $icon_params);


							if (isset($_SESSION['user'])) {
								$email = '&nbsp;' . get_link(get_img('/template/' . getTemplateName() . '/img/icon_email.png', array('alt' => __('Send mail'), 'title' => __('Send mail'))), '/users/send_mail_form/' . $post->getId_author(), $icon_params);
								$privat_message = '&nbsp;' . get_link(get_img('/template/' . getTemplateName() . '/img/icon_pm.png', array('alt' => __('PM'), 'title' => __('PM'))), '/users/send_msg_form/' . $post->getId_author(), $icon_params);
							}


							$author_site = ($postAuthor->getUrl()) ? '&nbsp;' . get_link(get_img('/template/' . getTemplateName() . '/img/icon_www.png', array('alt' => __('Author site'), 'title' => __('Author site'))), h($postAuthor->getUrl()), array_merge($icon_params, array('target' => '_blank')), true) : '';
						}
						$postAuthor->setAuthor_site($author_site);
						$postAuthor->setProfile_url($user_profile);
						$postAuthor->setEmail_url($email);
						$postAuthor->setPm_url($privat_message);

						$postAuthor->processComplete = true;
					}


					// Если автор сообщения - незарегистрированный пользователь
				} else {
					$postAuthor = new FpsEntity();
					$postAuthor->setAvatar(getAvatar());
					$postAuthor->setName(__('Guest'));
				}

				$message = $this->Textarier->print_page($post->getMessage(), $author_status);

				$attachment = null;
				$attach_list = $post->getAttacheslist();
				if (is_array($attach_list)) {
					$collizion = true;
					sort($attach_list);
					foreach ($attach_list as $attach) {
						$step = false;
						if (file_exists(ROOT . $this->getFilesPath($attach->getFilename()))) {
							$attachment .= __('Attachment') . ' ' . $attach->getAttach_number()
									. ': ' . get_link(getSimpleFileSize($attach->getSize()), $this->getModuleURL('download_file/'
													. $attach->getFilename()), array('target' => '_blank')) . '<br />';


							//if attach is image and isset markers for this image
							if ($attach->getIs_image() == '1') {
								$message = $this->insertImageAttach($message, $attach->getFilename(), $attach->getAttach_number());
							}
							$step = true;
						}
						$collizion = $collizion && $step;
					}
					/* may be collizion (paranoya mode) */
					if (!$collizion)
						$this->deleteCollizions($post);
				} else {
					$this->deleteCollizions($post);
				}

				if ($attachment != null) {
					$post->setAttachment($attachment);
				}

				$post->setMessage($message);


				$post->setAuthor($postAuthor);


				// Если сообщение редактировалось...
				if ($post->getId_editor() && $post->getEditor()) {
					if ($post->getId_author() && $post->getId_author() == $post->getId_editor()) {
						$editor = __('Edit by author') . ' ' . $post->getEdittime();
					} else {
						$status_info = $this->ACL->get_user_group($post->getEditor()->getStatus());
						$editor = __('Edited') . ' ' . $post->getEditor()->getName() . '('
								. $status_info['title'] . ') ' . $post->getEdittime();
					}
				} else {
					$editor = '';
				}
				$post->setEditor_info($editor);


				//edit and delete links
				$edit_link = '';
				$delete_link = '';
				$theme = $one_theme;
				if (!$theme && $post->getTheme())
					$theme = $post->getTheme();
				if (!empty($_SESSION['user']) && $theme && $theme->getId_forum()) {
					if ($this->ACL->turn(array($this->module, 'edit_posts', $theme->getId_forum()), false)
							|| (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id']
							&& $this->ACL->turn(array($this->module, 'edit_mine_posts', $theme->getId_forum()), false))) {
						$edit_link = get_link(__('Edit'), $this->getModuleURL('edit_post_form/' . $post->getId()));
					}
					if ($this->ACL->turn(array($this->module, 'delete_posts', $theme->getId_forum()), false)
							|| (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id']
							&& $this->ACL->turn(array($this->module, 'delete_mine_posts', $theme->getId_forum()), false))) {
						$delete_link = get_link(__('Delete'), $this->getModuleURL('delete_post/' . $post->getId()), array('onClick' => "return confirm('" . __('Are you sure') . "')"));
					}
				}
				$on_top = get_link(__('To top'), '#top', array(), true);
				$post->setOn_top_link($on_top);
				$post->setEdit_link($edit_link);
				$post->setDelete_link($delete_link);



				//message number
				if ($first_top) {
					$post->setPost_number(1);
					$first_top = false;
				} else {
					$post_num++;
					$post->setPost_number($post_num);
				}
				$post_number_url = 'http://' . $_SERVER['HTTP_HOST'] . get_url($this->getModuleURL('view_post/' . $post->getId()), true);
				$post->setPost_number_url($post_number_url);



				//set tags for cache
				$this->setCacheTag(array(
					'post_id_' . $post->getId(),
					'user_id_' . $post->getId_author(),
				));
			}
		}
		return $posts;
	}

	/**
	 * View post for users
	 *
	 * @param ind $user_id
	 * @return none
	 */
	public function user_posts($user_id = null) {
		$this->page_title .= ' - ' . __('User messages');

		//turn access
		$this->ACL->turn(array($this->module, 'view_themes'));
		$user_id = intval($user_id);
		if ($user_id < 1)
			return $this->showInfoMessage(__('Can not find user'), $this->getModuleURL());

		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
		} else {
			$usersModel = $this->Register['ModManager']->getModelInstance('Users');
			$user = $usersModel->getById($user_id);
			if (!$user)
				return $this->showInfoMessage(__('Can not find user'), $this->getModuleURL());


			// Заголовок страницы (содержимое тега title)
			$this->page_title .= ' "' . h($user->getName()) . '"';


			$markers = array();
			$markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
					. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('User messages') . ' "' . h($user->getName()) . '"';


			// Page nav
			$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
			$total = $postsModel->getTotal(array('cond' => array('id_author' => $user_id)));
			if ($total > 0) {
				list($pages, $page) = pagination($total, $this->Register['Config']->read('posts_per_page', $this->module), $this->getModuleURL('user_posts/' . $user_id));
				$markers['pagination'] = $pages;
				$this->page_title .= ' (' . $page . ')';



				// SELECT posts
				$postsModel->bindModel('theme');
				$postsModel->bindModel('author');
				$postsModel->bindModel('editor');
				$postsModel->bindModel('attacheslist');
				$posts = $postsModel->getCollection(array(
					'id_author' => $user_id,
						), array(
					'order' => 'time DESC, id DESC',
					'page' => $page,
					'limit' => $this->Register['Config']->read('posts_per_page', $this->module),
						));
			} else {
				$markers['pagination'] = null;
				$posts = array();
				$page = 1;
			}
			// Ссылка "Ответить" (если тема закрыта - выводим сообщение "Тема закрыта")
			$markers['add_link'] = '';
			$markers['admin_bar'] = '';
			$markers['meta'] = '';
			$markers['reply_form'] = '';
			$this->_globalize($markers);


			$source = $this->render('posts_list.html', array(
				'posts' => $this->__parsePostsTable($posts, $page),
				'theme' => array('poll' => null, 'title' => __('User messages') . ' "' . h($user->getName()) . '"'),
					));


			//write into cache
			if ($this->cached)
				$this->Cache->write($source, $this->cacheKey, $this->cacheTags);
		}

		return $this->_view($source);
	}

	private function __savePoll($theme) {
		$poll = isset($_POST['poll']) ? '1' : '0';
		$poll_question = isset($_POST['poll_question']) ? h(trim(mb_substr($_POST['poll_question'], 0, 250))) : '';
		$poll_ansvers = isset($_POST['poll_ansvers']) ? h(trim(mb_substr($_POST['poll_ansvers'], 0, 1000))) : '';

		if ($poll && $poll_ansvers) {

			$ansvers = explode("\n", $poll_ansvers);

			$variants = array();
			if (count($ansvers) && is_array($ansvers)) {
				foreach ($ansvers as $ansver) {
					$variants[] = array(
						'ansver' => $ansver,
						'votes' => 0,
					);
				}
			}

			$data = array(
				'variants' => json_encode($variants),
				'question' => $poll_question,
				'theme_id' => $theme->getId(),
				'voted_users' => '',
			);


			$poll = new PollsEntity($data);
			$poll->save();
			return true;
		}
		return false;
	}

	protected function _renderPoll($poll) {
		if (!$poll) {
			
		}


		$questions = json_decode($poll->getVariants(), true);
		if (!$questions && !is_array($questions)) {
			
		}


		$all_votes_summ = 0;
		foreach ($questions as $case) {
			$all_votes_summ += $case['votes'];
		}

		// Find 1% value
		$percent = round($all_votes_summ / 100, 2);


		// Show percentage graph for each variant
		foreach ($questions as $k => $case) {
			$questions[$k] = array(
				'ansver' => h($case['ansver']),
				'votes' => $case['votes'],
				'percentage' => ($case['votes'] > 0) ? round($case['votes'] / $percent) : 0,
				'ansver_id' => $k + 1,
			);

			//$poll->setPercentage(round($case / $percent));
		}

		$poll->setVariants($questions);


		// Did user voted
		if (!empty($_SESSION['user'])) {
			$voted_users = explode(',', $poll->getVoted_users());
			if ($voted_users && is_array($voted_users)) {


				if (!in_array($_SESSION['user']['id'], $voted_users)) {
					$poll->setCan_voted(1);
				}
			}
		}


		return $this->render('polls.html', array('poll' => $poll));
	}

	/**
	 *
	 */
	public function vote_poll($id) {
		if (empty($_SESSION['user']))
			die('ERROR: permission denied');

		$id = intval($id);
		if ($id < 1)
			die('ERROR: empty ID');


		$ansver_id = (!empty($_GET['ansver'])) ? intval($_GET['ansver']) : 0;
		if ($ansver_id < 1)
			die('ERROR: empty ANSVER_ID');


		$pollsModel = new PollsModel;
		$poll = $pollsModel->getById($id);

		if (empty($poll))
			die('ERROR: poll not found');

		$variants = json_decode($poll->getVariants(), true);
		if ($variants && is_array($variants)) {


			if (!array_key_exists($ansver_id - 1, $variants))
				die('ERROR: wrong ansver');


			// Check user ability
			$voted_users = explode(',', $poll->getVoted_users());
			if (!empty($voted_users)) {
				if (in_array($_SESSION['user']['id'], $voted_users)) {
					die('ERROR: you already voted');
				} else {
					$voted_users[] = $_SESSION['user']['id'];
				}
			} else {
				$voted_users = array($_SESSION['user']['id']);
			}

			$poll->setVoted_users(implode(',', $voted_users));


			$variants[$ansver_id - 1]['votes']++;

			$poll->setVariants(json_encode($variants));
			$poll->save();




			// Create response data for AJAX request
			$all_votes_summ = 0;
			foreach ($variants as $case) {
				$all_votes_summ += $case['votes'];
			}

			// Find 1% value
			$percent = round($all_votes_summ / 100, 2);


			// Show percentage graph for each variant
			foreach ($variants as $k => $case) {
				$variants[$k] = array(
					'ansver' => h($case['ansver']),
					'votes' => $case['votes'],
					'percentage' => ($case['votes'] > 0) ? round($case['votes'] / $percent) : 0,
					'ansver_id' => $k + 1,
				);
			}

			die(json_encode($variants));
		}

		die('ERROR');
	}

	private function __checkThemeAccess($theme) {
		$fid = $theme->getForum() ? $theme->getForum()->getId() : 0;
		$rules = $theme->getGroup_access();
		$id = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;

		foreach ($rules as $k => $v)
			if ('' === $v)
				unset($rules[$k]);

		if (in_array($id, $rules)) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $fid));
		}
	}

	/**
	 * View last posts and last themes
	 * Build list with themes ordered by add date
	 *
	 * @return string html content
	 */
	public function last_posts() {

		$this->page_title .= ' - ' . __('Last update');


		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$html = $this->Cache->read($this->cacheKey);
			return $this->_view($html);
		}


		// Page nav
		$nav = array();
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$total = $themesModel->getTotal();
		$perPage = intval($this->Register['Config']->read('themes_per_page', $this->module));
		if ($perPage < 1)
			$perPage = 10;
		list($pages, $page) = pagination($total, $perPage, $this->getModuleURL('last_posts/'));
		$nav['pagination'] = $pages;
		$this->page_title .= ' (' . $page . ')';


		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$firstOnPage = ($page - 1) * $perPage + 1;
		$lastOnPage = $firstOnPage + $recOnPage - 1;

		$nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('Last update');
		$nav['meta'] = __('Count all topics') . ' ' . $total . '. ' . __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage;
		$this->_globalize($nav);

		if ($total < 1)
			return $this->_view(__('No topics'));



		//get records
		$themesModel->bindModel('forum');
		$themesModel->bindModel('author');
		$themesModel->bindModel('last_author');
		$themes = $themesModel->getCollection(array(), array(
			'order' => 'last_post DESC',
			'page' => $page,
			'limit' => $perPage,
				));


		foreach ($themes as $theme) {
			if ($theme) {
				$theme_pf = $theme->getForum() ? get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $theme->getId_forum())) : '';
				$theme->setParent_forum($theme_pf);
				$theme = $this->__parseThemeTable($theme);

				//set cache tags
				$this->setCacheTag(array(
					'theme_id_' . $theme->getId(),
				));
			}
		}


		// write into cache
		if ($this->cached) {
			$this->Cache->write($html, $this->cacheKey, $this->cacheTags);
		}

		//pr($themes); die();
		$source = $this->render('lastposts_list.html', array(
			'context' => array(
				'forum_name' => __('Last update'),
			),
			'themes' => $themes
				));
		$this->_view($source);
	}

	/**
	 * Create HTML form for edit forum and paste current values into inputs
	 */
	public function edit_forum_form($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'edit_forums'));
		if (!isset($_SESSION['user']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_forum = intval($id_forum);
		if ($id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());



		$html = '';
		$action = get_url($this->getModuleURL('update_forum/' . $id_forum));


		// Если при заполнении формы были допущены ошибки
		if (isset($_SESSION['editForumForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editForumForm']['error'],
				),
					));
			$html = $html . $info . "\n";
			$title = h($_SESSION['editForumForm']['title']);
			$description = h($_SESSION['editForumForm']['description']);
			unset($_SESSION['editForumForm']);
		} else {
			// Получаем из БД информацию о форуме
			$forum = $this->Model->getById($id_forum);
			$title = $forum ? h($forum->getTitle()) : '';
			$description = $forum ? h($forum->getDescription()) : '';
		}


		// Считываем в переменную содержимое файла,
		// содержащего форму для редактирования форума
		$source = $this->render('editforumform.html', array(
			'context' => array(
				'action' => $action,
				'title' => $title,
				'description' => $description,
			),
				));


		// nav block
		$navi = array(
			'navigation' => get_link(__('Home'), '/') . __('Separator')
			. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('Edit forum'),
		);
		$this->_globalize($navi);


		$html = $html . $source;
		return $this->_view($html);
	}

	/**
	 * Get request and work for it. Validate data and update record
	 */
	public function update_forum($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'edit_forums'));
		if (!isset($_SESSION['user']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_forum = intval($id_forum);
		if ($id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());


		$forum = $this->Model->getById($id_forum);
		if (!$forum)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());


		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title = trim(mb_substr($_POST['title'], 0, 120));
		$description = trim(mb_substr($_POST['description'], 0, 250));


		// Check fields fo empty values and valid chars
		$error = '';
		$valobj = $this->Register['Validate'];
		if (empty($title))
			$error = $error . '<li>' . __('Empty field "forum name"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($title, V_TITLE))
			$error = $error . '<li>' . __('Wrong chars in "forum name"') . '</li>' . "\n";


		// if an errors
		if (!empty($error)) {
			$_SESSION['editForumForm'] = array();
			$_SESSION['editForumForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
					"\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['editForumForm']['title'] = $title;
			$_SESSION['editForumForm']['description'] = $description;

			redirect($this->getModuleURL('edit_forum_form/' . $id_forum));
		}


		$forum->setTitle($title);
		$forum->setDescription($description);
		$forum->save();

		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('editing forum', 'forum id(' . $id_forum . ')');
		return $this->showInfoMessage(__('Forum update is successful'), $this->getModuleURL());
	}

	/**
	 * raise forum
	 *
	 * @id_forum (int)    forum ID
	 * @return           info message
	 */
	public function forum_up($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'replace_forums'));
		if (!isset($_SESSION['user']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_forum = intval($id_forum);
		if ($id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());



		// upper forum
		$id_forum_up = intval($id_forum);
		$forum = $this->Model->getById($id_forum_up);
		if (!$forum)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
		// upper position
		$order_up = $forum->getPos();



		$dforum = $this->Model->getFirst(array(
			'pos < ' . $order_up,
			'in_cat' => $forum->getIn_cat(),
			'parent_forum_id' => $forum->getParent_forum_id(),
				), array(
			'order' => 'pos DESC',
				));
		if (!$dforum)
			return $this->showInfoMessage(__('Forum is above all'), $this->getModuleURL());


		// Порядок следования и ID форума, который находится выше и будет "опущен" вниз
		// ( поменявшись местами с форумом, который "поднимается" вверх )
		$order_down = $dforum->getPos();

		// replace forums
		$dforum->setPos($order_up);
		$res1 = $dforum->save();

		$forum->setPos($order_down);
		$res2 = $forum->save();


		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
		$this->DB->cleanSqlCache();

		if ($this->Log)
			$this->Log->write('uping forum', 'forum id(' . $id_forum . ')');
		if ($res1 && $res2)
			return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
		else
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
	}

	/**
	 * down forum
	 *
	 * @id_forum (int)    forum ID
	 * @return           info message
	 */
	public function forum_down($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'replace_forums'));
		if (!isset($_SESSION['user']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_forum = intval($id_forum);
		if ($id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());



		// downing forum
		$id_forum_down = $id_forum;
		$forum = $this->Model->getById($id_forum_down);
		if (!$forum)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
		// upper position
		$order_down = $forum->getPos();


		$dforum = $this->Model->getFirst(array(
			'pos > ' . $order_down,
			'in_cat' => $forum->getIn_cat(),
			'parent_forum_id' => $forum->getParent_forum_id(),
				), array(
			'order' => 'pos ASC',
				));
		if (!$dforum)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());


		// Порядок следования и ID форума, который находится ниже и будет "поднят" вверх
		// ( поменявшись местами с форумом, который "опускается" вниз )
		$order_up = $dforum->getPos();

		// replace forums
		$dforum->setPos($order_down);
		$res1 = $dforum->save();

		$forum->setPos($order_up);
		$res2 = $forum->save();


		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
		$this->DB->cleanSqlCache();

		if ($this->Log)
			$this->Log->write('down forum', 'forum id(' . $id_forum . ')');
		if ($res1 && $res2)
			return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
		else
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
	}

	/**
	 * delete forum
	 *
	 * @id_forum (int)    forum ID
	 * @return            info message
	 */
	public function delete_forum($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'delete_forums'));
		$id_forum = intval($id_forum);
		if ($id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());


		$forum = $this->Model->getById($id_forum);
		if (!$forum)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());


		// Можно удалить только форум, который не содержит тем (в целях безопасности)
		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themes = $themeModel->getTotal(array('cond' => array('id_forum' => $id_forum)));
		if ($themes > 0) {
			return $this->showInfoMessage(__('Can not delete forum with themes'), $this->getModuleURL());
		} else {
			$forum->delete();
		}

		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('forum_id_' . $id_forum));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('delete forum', 'forum id(' . $id_forum . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}

	/**
	 * form per add theme into forum
	 *
	 * @id_forum (int)    forum ID
	 * @return            html content
	 */
	public function add_theme_form($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'add_themes', $id_forum));
		$id_forum = intval($id_forum);
		if ($id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;



		$forum = $this->Model->getById($id_forum);
		if (!$forum)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());



		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($forum);


		$html = '';

		// preview
		if (isset($_SESSION['viewMessage']) and !empty($_SESSION['viewMessage']['message'])) {
			$view = $this->render('previewmessage.html', array(
				'context' => array(
					'message' => $this->Textarier->print_page($_SESSION['viewMessage']['message'], $writer_status),
				),
					));
			$html = $html . $view . "\n";
			$theme = h($_SESSION['viewMessage']['theme']);
			$description = h($_SESSION['viewMessage']['description']);
			$message = $_SESSION['viewMessage']['message'];
			$gr_access = $_SESSION['viewMessage']['gr_access'];
			$first_top = $_SESSION['viewMessage']['first_top'];
			$poll = $_SESSION['viewMessage']['poll'];
			$poll_question = h($_SESSION['viewMessage']['poll_question']);
			$poll_ansvers = h($_SESSION['viewMessage']['poll_ansvers']);
			unset($_SESSION['viewMessage']);
		}

		// errors
		if (isset($_SESSION['addThemeForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['addThemeForm']['error'],
				),
					));
			$html = $html . $info . "\n";
			$theme = h($_SESSION['addThemeForm']['theme']);
			$description = h($_SESSION['addThemeForm']['description']);
			$message = $_SESSION['addThemeForm']['message'];
			$gr_access = $_SESSION['addThemeForm']['gr_access'];
			$first_top = $_SESSION['addThemeForm']['first_top'];
			$poll = $_SESSION['addThemeForm']['poll'];
			$poll_question = h($_SESSION['addThemeForm']['poll_question']);
			$poll_ansvers = h($_SESSION['addThemeForm']['poll_ansvers']);
			unset($_SESSION['addThemeForm']);
		}


		$markers = array(
			'action' => get_url($this->getModuleURL('add_theme/' . $id_forum)),
			'theme' => (!empty($theme)) ? $theme : '',
			'description' => (!empty($description)) ? $description : '',
			'main_text' => (!empty($message)) ? $message : '',
			'gr_access' => (!empty($gr_access)) ? $gr_access : array(),
			'first_top' => (!empty($first_top)) ? $first_top : '0',
			'poll' => (!empty($poll)) ? $poll : '0',
			'poll_question' => (!empty($poll_question)) ? $poll_question : '',
			'poll_ansvers' => (!empty($poll_ansvers)) ? $poll_ansvers : '',
		);

		// nav block
		$navi = array();
		$navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator')
				. get_link(h($forum->getTitle()), $this->getModuleURL('view_forum/' . $id_forum));
		$this->_globalize($navi);


		$source = $this->render('addthemeform.html', array(
			'context' => $markers,
				));
		$html = $html . $source;
		return $this->_view($html);
	}

	/**
	 * add theme into forum
	 *
	 * @id_forum (int)    forum ID
	 * @return            info message
	 */
	public function add_theme($id_forum = null) {
		//check access
		$this->ACL->turn(array($this->module, 'add_themes', $id_forum));
		if (!isset($id_forum) || !isset($_POST['theme']) || !isset($_POST['mainText']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_forum = intval($id_forum);
		if ($id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());



		$forum = $this->Model->getById($id_forum);
		if (!$forum)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());



		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($forum);


		// cut lenght
		$name = trim(mb_substr($_POST['theme'], 0, 55));
		$description = trim(mb_substr($_POST['description'], 0, 128));
		$message = trim($_POST['mainText']);
		$first_top = isset($_POST['first_top']) ? '1' : '0';
		$poll = isset($_POST['poll']) ? '1' : '0';
		$poll_question = isset($_POST['poll_question']) ? h(trim(mb_substr($_POST['poll_question'], 0, 250))) : '';
		$poll_ansvers = isset($_POST['poll_ansvers']) ? h(trim(mb_substr($_POST['poll_ansvers'], 0, 1000))) : '';

		$gr_access = array();
		$groups = $this->ACL->getGroups();
		foreach ($groups as $grid => $grval) {
			if (isset($_POST['gr_access_' . $grid]))
				$gr_access[] = $grid;
		}


		// preview
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage']['theme'] = $name;
			$_SESSION['viewMessage']['description'] = $description;
			$_SESSION['viewMessage']['message'] = $message;
			$_SESSION['viewMessage']['gr_access'] = $gr_access;
			$_SESSION['viewMessage']['first_top'] = $first_top;
			$_SESSION['viewMessage']['poll'] = $poll;
			$_SESSION['viewMessage']['poll_question'] = $poll_question;
			$_SESSION['viewMessage']['poll_ansvers'] = $poll_ansvers;
			redirect($this->getModuleURL('add_theme_form/' . $id_forum));
		}

		// Check fields of empty values and valid chars
		$error = '';
		$valobj = $this->Register['Validate'];
		if (empty($name))
			$error = $error . '<li>' . __('Empty field "theme"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($name, V_TITLE))
			$error = $error . '<li>' . __('Wrong chars in "theme"') . '</li>' . "\n";
		if (empty($message))
			$error = $error . '<li>' . __('Empty field "message"') . '</li>' . "\n";
		if (mb_strlen($message) > $this->Register['Config']->read('max_post_lenght', $this->module))
			$error = $error . '<li>' . sprintf(__('Field "message" contains more symbols')
							, $this->Register['Config']->read('max_post_lenght', $this->module)) . '</li>' . "\n";

		if ($poll) {
			if (empty($poll_question))
				$error = $error . '<li>' . __('Empty field "poll_question"') . '</li>' . "\n";
			elseif (mb_strlen($poll_question) < 5)
				$error = $error . '<li>' . __('Very small "poll_question"') . '</li>' . "\n";
			if (empty($poll_ansvers))
				$error = $error . '<li>' . __('Empty field "poll_ansvers"') . '</li>' . "\n";
			else {
				$answers = explode("\n", $poll_ansvers);
				if (!$answers || !is_array($answers) || count($answers) < 2)
					$error = $error . '<li>' . __('Few "poll_question"') . '</li>' . "\n";
				elseif (count($answers) > 20)
					$error = $error . '<li>' . __('Many "poll_question"') . '</li>' . "\n";
			}
		}

		for ($i = 1; $i < 6; $i++) {
			if (!empty($_FILES['attach' . $i]['name'])) {
				if ($_FILES['attach' . $i]['size'] > $this->getMaxSize()) {
					$error = $error . '<li>' . sprintf(__('Very big file'), $i, round($this->getMaxSize() / 1024, 2)) . '</li>' . "\n";
				}
			}
		}
		// errors
		if (!empty($error)) {
			$_SESSION['addThemeForm'] = array();
			$_SESSION['addThemeForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
					"\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['addThemeForm']['theme'] = $name;
			$_SESSION['addThemeForm']['description'] = $description;
			$_SESSION['addThemeForm']['message'] = $message;
			$_SESSION['addThemeForm']['gr_access'] = $gr_access;
			$_SESSION['addThemeForm']['first_top'] = $first_top;
			$_SESSION['addThemeForm']['poll'] = $poll;
			$_SESSION['addThemeForm']['poll_question'] = $poll_question;
			$_SESSION['addThemeForm']['poll_ansvers'] = $poll_ansvers;
			redirect($this->getModuleURL('add_theme_form/' . $id_forum));
		}

		$message = mb_substr($message, 0, $this->Register['Config']->read('max_post_lenght', $this->module));


		$user_id = (!empty($_SESSION['user'])) ? $_SESSION['user']['id'] : 0;

		$data = array(
			'title' => $name,
			'description' => $description,
			'id_author' => $user_id,
			'time' => new Expr('NOW()'),
			'id_last_author' => $user_id,
			'last_post' => new Expr('NOW()'),
			'id_forum' => $id_forum,
			'group_access' => $gr_access,
			'first_top' => $first_top,
		);
		$theme = new ThemesEntity($data);
		$id_theme = $theme->save();
		if (!is_int($id_theme)) {
			$id_theme = mysql_insert_id();
		}
		$theme->setId($id_theme);



		// Check poll
		$this->__savePoll($theme);



		// add first post
		$postData = array(
			'message' => $message,
			'id_author' => $user_id,
			'time' => new Expr('NOW()'),
			'edittime' => new Expr('NOW()'),
			'id_theme' => $id_theme
		);
		$post = new PostsEntity($postData);
		$post_id = $post->save();
		if (!is_int($post_id)) {
			$post_id = mysql_insert_id();
		}


		/*		 * *** END ATTACH **** */
		$attaches_exists = 0;
		/* delete collizions if exists */
		$this->deleteCollizions($post, true);
		for ($i = 1; $i < 6; $i++) {
			$attach_name = 'attach' . $i;
			if (!empty($_FILES[$attach_name]['name'])) {
				// Извлекаем из имени файла расширение
				$ext = strtolower(strrchr($_FILES[$attach_name]['name'], "."));
				// Формируем путь к файлу
				if (!isPermittedFile($ext))
					$file = $post_id . '-' . $i . '-' . date("YmdHi") . '.txt';
				else
					$file = $post_id . '-' . $i . '-' . date("YmdHi") . $ext;
				$is_image = (isImageFile($_FILES[$attach_name]['type'], $ext) ? '1' : '0');
				// Перемещаем файл из временной директории сервера в директорию files

				if (move_uploaded_file($_FILES[$attach_name]['tmp_name'], ROOT . $this->getFilesPath($file))) {
					if ($is_image == '1') {
						$watermark_path = ROOT . '/sys/img/' . ($this->Register['Config']->read('watermark_type') == '1' ? 'watermark_text.png' : $this->Register['Config']->read('watermark_img'));
						if ($this->Register['Config']->read('use_watermarks') && !empty($watermark_path) && file_exists($watermark_path)) {
							$waterObj = new FpsImg;
							$save_path = ROOT . $this->getFilesPath($file);
							$waterObj->createWaterMark($save_path, $watermark_path);
						}
					}
					chmod(ROOT . $this->getFilesPath($file), 0644);
					$attach_file_data = array(
						'post_id' => $post_id,
						'theme_id' => $id_theme,
						'user_id' => $user_id,
						'attach_number' => $i,
						'filename' => $file,
						'size' => $_FILES[$attach_name]['size'],
						'date' => new Expr('NOW()'),
						'is_image' => $is_image,
					);

					$attach = new ForumAttachesEntity($attach_file_data);
					if ($attach->save() != NULL) {
						$attaches_exists = 1;
					}
				}
			}
		}
		if ($attaches_exists == 1) {
			$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
			$post = $postsModel->getById($post_id);
			if ($post) {
				$post->setAttaches('1');
				$post->save();
			}
		}
		/*		 * *** END ATTACH **** */


		// Обновляем число оставленных сообщений и созданных тем
		if (!empty($_SESSION['user'])) {
			$usersModel = $this->Register['ModManager']->getModelInstance('Users');
			$user = $usersModel->getById($_SESSION['user']['id']);
			if ($user) {
				$user->setThemes($user->getThemes() + 1);
				$user->setPosts($user->getPosts() + 1);
				$user->save();
			}
		}


		$forum->setThemes($forum->getThemes() + 1);
		$forum->setPosts($forum->getPosts() + 1);
		$forum->setLast_theme_id($id_theme);
		$forum->save();


		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array(
			'user_id_' . $user_id,
			'forum_id_' . $id_forum,
		));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('adding theme', 'theme id(' . $id_theme . '), post id(' . $post_id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $id_forum));
	}

	/**
	 * form per edit theme
	 *
	 * @id_forum (int)    theme ID
	 * @return            html content
	 */
	public function edit_theme_form($id_theme = null) {
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		// Получаем из БД информацию о редактируемой теме
		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themeModel->bindModel('author');
		$theme = $themeModel->getById($id_theme);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		$id_forum = $theme->getId_forum();
		$html = '';


		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()), false)
				&& (empty($_SESSION['user']['id']) || $theme->getId_author() != $_SESSION['user']['id']
				|| !$this->ACL->turn(array($this->module, 'edit_mine_themes', $theme->getId_forum()), false))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $id_forum));
		}


		// Если при заполнении формы были допущены ошибки
		if (isset($_SESSION['editThemeForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editThemeForm']['error'],
				),
					));
			$html = $info . $html . "\n";
			$name = h($_SESSION['editThemeForm']['theme']);
			$description = h($_SESSION['editThemeForm']['description']);
			$gr_access = $_SESSION['editThemeForm']['gr_access'];
			$first_top = $_SESSION['editThemeForm']['first_top'];
			unset($_SESSION['editThemeForm']);
		} else {
			$name = h($theme->getTitle());
			$description = h($theme->getDescription());
			$gr_access = $theme->getGroup_access();
			$first_top = $theme->getFirst_top();
		}


		// Формируем список форумов, чтобы можно было переместить тему в другой форум
		$forums = $this->Model->getCollection(array(), array('order' => 'pos'));
		if (!$forums)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());


		$options = '';
		foreach ($forums as $forum) {
			if ($forum->getId() == $theme->getId_forum())
				$options = $options . '<option value="' . $forum->getId() . '" selected>' . h($forum->getTitle()) . '</option>' . "\n";
			else
				$options = $options . '<option value="' . $forum->getId() . '">' . h($forum->getTitle()) . '</option>' . "\n";
		}


		$author_name = ($theme->getId_author() && $theme->getAuthor()) ? h($theme->getAuthor()->getName()) : __('Guest');
		$data = array(
			'action' => get_url($this->getModuleURL('update_theme/' . $id_theme)),
			'theme' => $name,
			'description' => $description,
			'author' => $author_name,
			'options' => $options,
			'gr_access' => (!empty($gr_access)) ? $gr_access : array(),
			'first_top' => (!empty($first_top)) ? $first_top : '0',
		);

		// nav block
		$navi = array();
		$navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('Edit theme');
		$this->_globalize($navi);


		$source = $this->render('editthemeform.html', array(
			'context' => $data,
				));
		$html = $html . $source;
		return $this->_view($html);
	}

	/**
	 * update theme
	 *
	 * @id_forum (int)    theme ID
	 * @return            info message
	 */
	public function update_theme($id_theme = null) {

		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($id_theme) || !isset($_POST['id_forum']) || !isset($_POST['theme']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_theme = intval($id_theme);
		$id_forum = intval($_POST['id_forum']);
		if ($id_theme < 1 || $id_forum < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themeModel->getById($id_theme);
		if (!$theme)
			return $this->showInfoMessage(__('Theme does not exists'), $this->getModuleURL());


		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$id_from_forum = $theme->getId_forum();
		$name = trim(mb_substr($_POST['theme'], 0, 55));
		$description = trim(mb_substr($_POST['description'], 0, 128));
		$first_top = isset($_POST['first_top']) ? '1' : '0';

		$gr_access = array();
		$groups = $this->ACL->getGroups();
		foreach ($groups as $grid => $grval) {
			if (isset($_POST['gr_access_' . $grid]))
				$gr_access[] = $grid;
		}


		// validate ...
		$error = '';
		$valobj = $this->Register['Validate'];
		if (empty($name))
			$error = $error . '<li>' . __('Empty field "theme"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($name, V_TITLE))
			$error = $error . '<li>' . __('Wrong chars in "theme"') . '</li>' . "\n";

		// errors
		if (!empty($error)) {
			$_SESSION['editThemeForm'] = array();
			$_SESSION['editThemeForm']['error'] = '<p class="errorMsg">' . __('Some error in form')
					. '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['editThemeForm']['theme'] = $name;
			$_SESSION['editThemeForm']['description'] = $description;
			$_SESSION['editThemeForm']['gr_access'] = $gr_access;
			$_SESSION['editThemeForm']['first_top'] = $first_top;
			redirect($this->getModuleURL('edit_theme_form/' . $id_theme));
		}



		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()), false)
				&& (empty($_SESSION['user']['id']) || $theme->getId_author() != $_SESSION['user']['id']
				|| !$this->ACL->turn(array($this->module, 'edit_mine_themes', $theme->getId_forum()), false))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $id_forum));
		}


		// update theme
		$theme->setTitle($name);
		$theme->setDescription($description);
		$theme->setId_forum($id_forum);
		$theme->setGroup_access($gr_access);
		$theme->setFirst_top($first_top);
		$theme->save();


		//update forums info
		if ($id_from_forum != $id_forum) {
			$new_forum = $this->Model->getById($id_forum);
			if (!$new_forum)
				return $this->showInfoMessage(__('No forum for moving'), $this->getModuleURL());


			$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
			$posts_cnt = $postsModel->getTotal(array('cond' => array('id_theme' => $id_theme)));

			$from_forum = $this->Model->getById($id_from_forum);
			if ($from_forum) {
				$from_forum->setPosts($from_forum->getPosts() - $posts_cnt);
				$from_forum->setThemes($from_forum->getThemes() - 1);
				$from_forum->save();
			}

			$new_forum->setPosts($new_forum->getPosts() + $posts_cnt);
			$new_forum->setThemes($new_forum->getThemes() + 1);
			$new_forum->save();


			$this->Model->upLastPost($id_from_forum, $id_forum);
		}


		//clean cahce
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('editing theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $id_forum));
	}

	/**
	 * Deleting theme
	 */
	public function delete_theme($id_theme = null) {
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		//check access
		if (!$this->ACL->turn(array($this->module, 'delete_themes', $theme->getId_forum()), false)
				|| (!empty($_SESSION['user']['id']) && $theme->getId_author() == $_SESSION['user']['id']
				&& !$this->ACL->turn(array($this->module, 'delete_mine_themes', $theme->getId_forum()), false))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		$this->__delete_theme($theme);
		if ($this->Log)
			$this->Log->write('delete theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Theme is deleted'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}

	/**
	 * Close Theme
	 */
	public function lock_theme($id_theme = null) {
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());

		$this->ACL->turn(array($this->module, 'close_themes', $theme->getId_forum()));

		// Сначала заблокируем сообщения (посты) темы
		$posts = $postsModel->getCollection(array('id_theme' => $id_theme));
		if ($posts) {
			foreach ($posts as $post) {
				$post->setLocked('1');
				$post->save();
			}
		}

		// Теперь заблокируем тему
		$theme->setLocked('1');
		$theme->save();


		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('lock theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Theme is locked'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}

	/**
	 * Unlocking Theme
	 */
	public function unlock_theme($id_theme = null) {
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());

		$this->ACL->turn(array($this->module, 'close_themes', $theme->getId_forum()));

		// Сначала заблокируем сообщения (посты) темы
		$posts = $postsModel->getCollection(array('id_theme' => $id_theme));
		if ($posts) {
			foreach ($posts as $post) {
				$post->setLocked('0');
				$post->save();
			}
		}

		// Теперь заблокируем тему
		$theme->setLocked('0');
		$theme->save();


		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('unlock theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Theme is open'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}

	/**
	 * Create reply form
	 *
	 * @param array $theme Theme info
	 * @return string HTML reply form
	 */
	private function add_post_form($theme = null) {
		if (empty($theme))
			return null;
		$id_theme = intval($theme->getId());
		if ($id_theme < 1)
			return null;
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;


		if ($this->ACL->turn(array($this->module, 'add_posts', $theme->getId_forum()), false)) {
			if ($theme->getLocked() == 1 && !$this->ACL->turn(array($this->module, 'close_themes', $theme->getId_forum()), false)) {
				$html = '<div class="not-auth-mess">' . __('Theme is locked') . '</div>';
			} else {


				$message = '';
				$html = '';
				if (isset($_SESSION['viewMessage']) and !empty($_SESSION['viewMessage'])) {
					$view = $this->render('previewmessage.html', array(
						'context' => array(
							'message' => $this->Textarier->print_page($_SESSION['viewMessage'], $writer_status),
						),
							));
					$html = $html . $view . "<script>window.location.href=\"#preview\";</script>\n";
					$message = h($_SESSION['viewMessage']);
					unset($_SESSION['viewMessage']);
				}


				// Если при заполнении формы были допущены ошибки
				if (isset($_SESSION['addPostForm'])) {
					$info = $this->render('infomessage.html', array(
						'context' => array(
							'info_message' => $_SESSION['addPostForm']['error'],
						),
							));
					$html = $html . $info . "\n";
					$message = h($_SESSION['addPostForm']['message']);
					unset($_SESSION['addPostForm']);
				}


				$source = $this->render('replyform.html', array(
					'context' => array(
						'action' => get_url($this->getModuleURL('add_post/' . $id_theme)),
						'main_text' => $message,
					),
						));
				$html = $html . $source;
			}
		} else {
			if (isset($_SESSION['user']))
				$html = '<div class="not-auth-mess">' . __('Dont have permission to write post') . '</div>';
			else
				$html = '<div class="not-auth-mess">' . __('Guests cant write posts') . '</div>';
		}

		return $html;
	}

	/**
	 * Adding new record into posts table
	 *
	 * @param int $id_theme
	 */
	public function add_post($id_theme = null) {
		if (empty($id_theme) || !isset($_POST['mainText']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());



		// Проверяем, не заблокирована ли тема?
		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());

		$this->ACL->turn(array($this->module, 'add_posts', $theme->getId_forum()));

		if ($theme->getLocked() == 1 && !$this->ACL->turn(array($this->module, 'close_themes', $theme->getId_forum()), false))
			return $this->showInfoMessage(__('Can not write in a closed theme'), $this->getModuleURL('view_theme/' . $id_theme));



		// Check access to this forum. May be locked by pass or posts count
		$forum = $this->Model->getById($theme->getId_forum());
		if (!$forum)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());
		$this->__checkForumAccess($forum);


		// Обрезаем сообщение (пост) до длины $set['forum']['max_post_lenght']
		$message = trim($_POST['mainText']);
		// Если пользователь хочет посмотреть на сообщение перед отправкой
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage'] = $message;
			redirect($this->getModuleURL('view_theme/' . $id_theme));
		}



		// Проверяем, правильно ли заполнены поля формы
		$error = '';
		if (empty($message))
			$error = $error . '<li>' . __('Empty field "message"') . '</li>' . "\n";
		if (strlen($message) > $this->Register['Config']->read('max_post_lenght', $this->module))
			$error = $error . '<li>' . sprintf(__('Field "message" contains more symbols')
							, $this->Register['Config']->read('max_post_lenght', $this->module)) . '</li>' . "\n";


		$gluing = true;
		for ($i = 1; $i < 6; $i++) {
			if (!empty($_FILES['attach' . $i]['name'])) {
				if ($_FILES['attach' . $i]['size'] > $this->getMaxSize()) {
					$error = $error . '<li>' . sprintf(__('Very big file'), $i, round($this->getMaxSize() / 1024, 2)) . '</li>' . "\n";
				}
				//if exists attach files we do not gluing posts
				$gluing = false;
			}
		}


		// errors
		if (!empty($error)) {
			$_SESSION['addPostForm'] = array();
			$_SESSION['addPostForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' . "\n" .
					'<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['addPostForm']['message'] = $message;
			redirect($this->getModuleURL('view_theme/' . $id_theme));
		}


		$message = mb_substr($message, 0, $this->Register['Config']->read('max_post_lenght', $this->module));
		$id_user = (!empty($_SESSION['user'])) ? $_SESSION['user']['id'] : 0;
		// Защита от того, чтобы один пользователь не добавил
		// 100 сообщений за одну минуту
		if (isset($_SESSION['unix_last_post']) && (time() - $_SESSION['unix_last_post'] < 10)) {
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL('view_theme/' . $id_theme));
		}


		//gluing posts
		if ($gluing === true) {
			$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
			$prev_post = $postsModel->getFirst(array(
				'id_theme' => $id_theme,
					), array(
				'order' => 'time DESC, id DESC',
					));
			if ($prev_post) {
				$prev_post_author = $prev_post->getId_author();
				if (empty($prev_post_author))
					$gluing = false;
				if ((mb_strlen($prev_post->getMessage() . $message)) > $this->Register['Config']->read('max_post_lenght', $this->module))
					$gluing = false;
				if ($prev_post_author != $id_user || empty($id_user))
					$gluing = false;
			} else {
				$gluing = false;
			}
		}



		if ($gluing === true) {
			$message = $prev_post->getMessage() . "\n\n" . '[color=939494]'
					. __('Added') . " " . date("Y.m.d H:i") . "[/color]\n\n" . $message;

			$prev_post->setMessage($message);
			$prev_post->setTime(new Expr('NOW()'));
			$prev_post->save();

			$theme->setId_last_author($id_user);
			$theme->setLast_post(new Expr('NOW()'));
			$theme->save();

			$forum->setLast_theme_id($id_theme);
			$forum->save();
		} else {   // NOT GLUING MESSAGE
			// Все поля заполнены правильно - выполняем запрос к БД
			$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
			$post_data = array(
				'message' => $message,
				'id_author' => $id_user,
				'time' => new Expr('NOW()'),
				'edittime' => new Expr('NOW()'),
				'id_theme' => $id_theme
			);
			$post = new PostsEntity($post_data);
			$post_id = $post->save();
			if (!is_int($post_id)) {
				$post_id = mysql_insert_id();
			}


			$attaches_exists = 0;
			/* delete collizions if exists */
			$this->deleteCollizions($post, true);
			for ($i = 1; $i < 6; $i++) {
				$attach_name = 'attach' . $i;
				if (!empty($_FILES[$attach_name]['name'])) {
					// Извлекаем из имени файла расширение
					$ext = strtolower(strrchr($_FILES[$attach_name]['name'], "."));
					// Формируем путь к файлу
					if (!isPermittedFile($ext))
						$file = $post_id . '-' . $i . '-' . date("YmdHi") . '.txt';
					else
						$file = $post_id . '-' . $i . '-' . date("YmdHi") . $ext;
					$is_image = (isImageFile($_FILES[$attach_name]['type'], $ext) ? '1' : '0');

					// Перемещаем файл из временной директории сервера в директорию files
					if (move_uploaded_file($_FILES[$attach_name]['tmp_name'], ROOT . $this->getFilesPath($file))) {
						if ($is_image == '1') {
							$watermark_path = ROOT . '/sys/img/' . ($this->Register['Config']->read('watermark_type') == '1' ? 'watermark_text.png' : $this->Register['Config']->read('watermark_img'));
							if ($this->Register['Config']->read('use_watermarks') && !empty($watermark_path) && file_exists($watermark_path)) {
								$waterObj = new FpsImg;
								$save_path = ROOT . $this->getFilesPath($file);
								$waterObj->createWaterMark($save_path, $watermark_path);
							}
						}
						chmod(ROOT . $this->getFilesPath($file), 0644);
						$attach_file_data = array(
							'post_id' => $post_id,
							'theme_id' => $id_theme,
							'user_id' => $id_user,
							'attach_number' => $i,
							'filename' => $file,
							'size' => $_FILES[$attach_name]['size'],
							'date' => new Expr('NOW()'),
						);
						if ($is_image)
							$attach_file_data['is_image'] = $is_image;

						$attach = new ForumAttachesEntity($attach_file_data);
						if ($attach->save() != NULL) {
							$attaches_exists = 1;
						}
					}
				}
			}


			if ($attaches_exists == 1) {
				$post = $postsModel->getById($post_id);
				if ($post) {
					$post->setAttaches('1');
					$post->save();
				}
			}


			$cnt_posts_from_theme = $postsModel->getTotal(array('cond' => array('id_theme' => $id_theme)));
			$theme->setPosts(($cnt_posts_from_theme - 1));
			$theme->setId_last_author($id_user);
			$theme->setLast_post(new Expr('NOW()'));
			$theme->save();


			// speed spam protected
			$_SESSION['unix_last_post'] = time();


			// Обновляем количество сообщений для зарегистрированного пользователя
			if (isset($_SESSION['user'])) {
				$usersModel = $this->Register['ModManager']->getModelInstance('Users');
				$user = $usersModel->getById($id_user);
				if ($user) {
					$user->setPosts($user->getPosts() + 1);
					$user->save();
				}
			}


			//update forum info
			$forum->setPosts($forum->getPosts() + 1);
			$forum->setLast_theme_id($id_theme);
			$forum->save();
		}



		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme, 'user_id_' . $id_user));
		$this->DB->cleanSqlCache();


		if ($gluing === false) {
			if ($this->Log)
				$this->Log->write('adding post', 'post id(' . $post_id . '), theme id(' . $id_theme . ')');
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL(
									$post_id ? 'view_post/' . $post_id :
											'view_theme/' . $id_theme . '?page=999#post' . $cnt_posts_from_theme));
		} else {
			if ($this->Log)
				$this->Log->write('adding post', 'post id(*gluing), theme id(' . $id_theme . ')');
			$id_last_post = $prev_post->getId_last_post();
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL(
									$id_last_post ? 'view_post/' . $id_last_post :
											'view_theme/' . $id_theme . '?page=999#post' . $prev_post->getPosts()));
		}
	}

	/**
	 * Create Edit post form
	 *
	 * @param int $id Post ID
	 */
	public function edit_post_form($id = null) {
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;


		// Получаем из БД сообщение
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$post = $postsModel->getById($id);
		if (!$post)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());

		$id_theme = $post->getId_theme();

		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());

		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_posts', $theme->getId_forum()), false)
				&& (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_posts', $theme->getId_forum()), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		$message = $post->getMessage();
		$html = '';
		$markers = array();

		//if user vant preview message
		if (isset($_SESSION['viewMessage']) and !empty($_SESSION['viewMessage'])) {
			$view = $this->render('previewmessage.html', array(
				'context' => array(
					'message' => $this->Textarier->print_page($_SESSION['viewMessage'], $writer_status),
				),
					));
			$html = $html . $view . "\n";
			$message = $_SESSION['viewMessage'];
			unset($_SESSION['viewMessage']);
		}

		// errors
		if (isset($_SESSION['editPostForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editPostForm']['error'],
				),
					));
			$html = $info . $html . "\n";
			$message = $_SESSION['editPostForm']['message'];
			unset($_SESSION['editPostForm']);
		}



		$markers = array(
			'action' => get_url($this->getModuleURL('update_post/' . $id)),
			'main_text' => h($message),
		);


		/*		 * **  ATTACH  *** */
		$unlinkfiles = array('att1' => '', 'att2' => '', 'att3' => '', 'att4' => '', 'att5' => '',);
		if ($post->getAttaches()) {
			$attachModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
			$attach_files = $attachModel->getCollection(array('post_id' => $post->getId()));
			if ($attach_files) {
				foreach ($attach_files as $attach_file) {
					if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
						$unlinkfiles['att' . $attach_file->getAttach_number()] = '<input type="checkbox" name="unlink' . $attach_file->getAttach_number()
								. '" value="1" />&nbsp;' . __('Delete') . "\n";
					}
				}
			}
		}
		$markers['unlinkfiles'] = $unlinkfiles;
		/*		 * **  END  ATTACH  *** */


		// nav block
		$navi = array();
		$navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator')
				. get_link('Просмотр темы', $this->getModuleURL('view_theme/' . $id_theme)) . __('Separator') . __('Edit message');
		$this->_globalize($navi);


		setReferer();
		$source = $this->render('editpostform.html', array('context' => $markers));
		$html = $html . $source;
		return $this->_view($html);
	}

	/**
	 * Update Post record
	 *
	 * @param int $id Post ID
	 */
	public function update_post($id = null) {
		// Если не переданы данные формы - значит функция была вызвана по ошибке
		if (empty($id) || !isset($_POST['mainText']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		// Проверяем, имеет ли пользователь право редактировать это сообщение (пост)
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$post = $postsModel->getById($id);
		if (!$post)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_theme = $post->getId_theme();

		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id_theme);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_posts', $theme->getId_forum()), false)
				&& (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_posts', $theme->getId_forum()), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}

		// Обрезаем сообщение до длины $set['forum']['max_post_lenght']
		$message = trim($_POST['mainText']);

		// Preview
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage'] = $message;
			redirect($this->getModuleURL('edit_post_form/' . $id));
		}



		// check fields...
		$error = '';
		if (empty($message))
			$error = $error . '<li>' . __('Empty field "message"') . '</li>' . "\n";
		if (mb_strlen($message) > $this->Register['Config']->read('max_post_lenght', $this->module))
			$error = $error . '<li>' . sprintf(__('Very big message'), $this->Register['Config']->read('max_post_lenght', $this->module)) . '</li>' . "\n";
		// check attach
		for ($i = 1; $i <= 5; $i++) {
			if (!empty($_FILES['attach' . $i]['name'])) {
				if ($_FILES['attach' . $i]['size'] > $this->getMaxSize()) {
					$error = $error . '<li>' . sprintf(__('Very big file'), $i, round($this->getMaxSize() / 1024, 2)) . '</li>' . "\n";
				}
			}
		}


		/* if an error */
		if (!empty($error)) {
			$_SESSION['editPostForm'] = array();
			$_SESSION['editPostForm']['error'] = '<p class="errorMsg">' . __('Some error in form')
					. '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['editPostForm']['message'] = $message;
			redirect($this->getModuleURL('edit_post_form/' . $id));
		}


		$user_id = (!empty($_SESSION['user'])) ? $_SESSION['user']['id'] : 0;



		/*		 * ***   ATTACH   **** */
		$attachModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		for ($i = 1; $i <= 5; $i++) {
			if (!empty($_POST['unlink' . $i]) || !empty($_FILES['attach' . $i]['name'])) {
				$unlink_files = $attachModel->getCollection(array(
					'post_id' => $id,
					'attach_number' => $i,
						));
				/* may be collizions */
				if (count($unlink_files) > 1)
					$this->deleteCollizions($post, true);
				elseif (!empty($unlink_files)) {
					foreach ($unlink_files as $unlink_file) {
						if (!empty($unlink_file)) {
							if (file_exists(ROOT . $this->getFilesPath($unlink_file->getFilename()))) {
								@unlink(ROOT . $this->getFilesPath($unlink_file->getFilename()));
							}
							$unlink_file->delete();
						}
					}
				}
			}

			$attach_name = 'attach' . $i;
			if (!empty($_FILES[$attach_name]['name'])) {

				// Извлекаем из имени файла расширение
				$ext = strtolower(strrchr($_FILES[$attach_name]['name'], "."));
				// Формируем путь к файлу
				if (!isPermittedFile($ext))
					$file = $id . '-' . $i . '-' . date("YmdHi") . '.txt';
				else
					$file = $id . '-' . $i . '-' . date("YmdHi") . $ext;
				$is_image = (isImageFile($_FILES[$attach_name]['type'], $ext) ? '1' : '0');


				// Перемещаем файл из временной директории сервера в директорию files
				if (move_uploaded_file($_FILES[$attach_name]['tmp_name'], ROOT . $this->getFilesPath($file))) {
					if ($is_image == '1') {
						$watermark_path = ROOT . '/sys/img/' . ($this->Register['Config']->read('watermark_type') == '1' ? 'watermark_text.png' : $this->Register['Config']->read('watermark_img'));
						if ($this->Register['Config']->read('use_watermarks') && !empty($watermark_path) && file_exists($watermark_path)) {
							$waterObj = new FpsImg;
							$save_path = ROOT . $this->getFilesPath($file);
							$waterObj->createWaterMark($save_path, $watermark_path);
						}
					}
					chmod(ROOT . $this->getFilesPath($file), 0644);
					$attach_file_data = array(
						'post_id' => $id,
						'theme_id' => $id_theme,
						'user_id' => $user_id,
						'attach_number' => $i,
						'filename' => $file,
						'size' => $_FILES[$attach_name]['size'],
						'date' => new Expr('NOW()'),
						'is_image' => $is_image,
					);
					$newattach = new ForumAttachesEntity($attach_file_data);
					$newattach->save();
				}
			}
		}
		$attach_exists = $attachModel->getCollection(array('post_id' => $id));
		$attach_exists = ($attach_exists > 0) ? '1' : '0';
		/*		 * ***  END ATTACH   **** */



		// Все поля заполнены правильно - выполняем запрос к БД
		$message = mb_substr($message, 0, $this->Register['Config']->read('max_post_lenght', $this->module));
		$post->setMessage($message);
		$post->setAttaches($attach_exists);
		$post->setId_editor($user_id);
		$post->setEdittime(new Expr('NOW()'));
		$post->save();


		//clean cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('post_id_' . $id));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('editing post', 'post id(' . $id . '), theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Operation is successful'), getReferer());
	}

	/**
	 * deleting post from forum
	 * @id     post ID
	 * @return none
	 */
	public function delete_post($id = null) {
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		// Получаем из БД информацию об удаляемом сообщении - это нужно,
		// чтобы узнать, имеет ли право пользователь удалить это сообщение
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$post = $postsModel->getById($id);
		if (!$post)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());


		if ($post->getId_author()) {
			$usersModel = $this->Register['ModManager']->getModelInstance('Users');
			$user = $usersModel->getById($post->getId_author());
		}


		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($post->getId_theme());

		//check access
		if (!$this->ACL->turn(array($this->module, 'delete_posts', $theme->getId_forum()), false)
				&& (!empty($_SESSION['user']['id']) && $post->getId_author() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'delete_mine_posts', $theme->getId_forum()), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		// Удаляем файл, если он есть
		$attachModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		if ($post->getAttaches()) {
			$attach_files = $attachModel->getCollection(array('post_id' => $id));
			if (count($attach_files) && is_array($attach_files)) {
				foreach ($attach_files as $attach_file) {
					if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
						if (@unlink(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
							$attach_file->delete();
						}
					}
				}
			}
		}
		$post->delete();

		// Если это - единственное сообщение темы, то надо удалить и тему
		$postscnt = $postsModel->getTotal(array('cond' => array('id_theme' => $post->getId_theme())));


		$deleteTheme = false;
		if ($postscnt == 0) {
			if ($user) {
				// Прежде чем удалять тему, надо обновить таблицу TABLE_USERS
				$user->setThemes($user->getThemes() - 1);
				$user->save();
			}

			if ($theme) {
				$theme->delete();
			}
			// Если мы удалили тему, то мы не можем в нее вернуться;
			// поэтому редирект будет на страницу форума, а не страницу темы
			$deleteTheme = true;
		}


		if ($user) {
			// Обновляем количество сообщений, оставленных автором сообщения ...
			$user->setPosts($user->getPosts() - 1);
			$user->save();
		}


		// ... и таблицу .themes
		if (!$deleteTheme) {
			$last_post = $postsModel->getFirst(array(
				'id_theme' => $post->getId_theme(),
					), array(
				'order' => 'id DESC'
					));

			if ($theme) {
				if ($last_post) {
					$theme->setId_last_author($last_post->getId_author());
					$theme->setLast_post($last_post->getTime());
				}
				$theme->setPosts($postscnt - 1);
				$theme->save();
			}
		}

		//clean cache
		$cahceKey = array('post_id_' . $id);
		if (isset($deleteTheme))
			$cahceKey[] = 'theme_id_' . $post->getId_theme();
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, $cahceKey);
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('delete post', 'post id(' . $id . '), theme id(' . $post->getId_theme() . ')');


		//update forum info
		$last_theme = $themesModel->getFirst(array(
			'id_forum' => $theme->getId_forum(),
				), array(
			'order' => '`last_post` DESC',
				));

		$forum = $this->Model->getById($theme->getId_forum());
		if ($deleteTheme) {
			if ($forum) {
				$forum->setThemes($forum->getThemes() - 1);
				$forum->setPosts($forum->getPosts() - 1);
				$forum->setLast_theme_id($last_theme ? $last_theme->getId() : '0');
				$forum->save();
			}
			return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
		} else {
			if ($forum) {
				$forum->setPosts($forum->getPosts() - 1);
				$forum->setLast_theme_id($last_theme ? $last_theme->getId() : '0');
				$forum->save();
			}
			return $this->showInfoMessage(__('Operation is successful'), getReferer());
		}
	}

	/**
	 * View themes for users
	 *
	 * @param ind $user_id
	 * @return none
	 */
	public function user_themes($user_id = null) {
		$user_id = intval($user_id);
		if ($user_id < 1)
			return $this->showInfoMessage(__('Can not find user'), $this->getModuleURL());

		$this->page_title .= ' - ' . __('User themes');
		$html = '';

		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$html = $this->Cache->read($this->cacheKey);
			return $this->_view($html);
		}

		$usersModel = $this->Register['ModManager']->getModelInstance('Users');
		$user = $usersModel->getById($user_id);
		if (!$user)
			return $this->showInfoMessage(__('Can not find user'), $this->getModuleURL());


		// Заголовок страницы (содержимое тега title)
		$this->page_title .= ' "' . h($user->getName()) . '"';


		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$total = $themesModel->getTotal(array('cond' => array('id_author' => $user_id)));
		$perPage = intval($this->Register['Config']->read('themes_per_page', $this->module));
		if ($perPage < 1)
			$perPage = 10;
		list($pages, $page) = pagination($total, $perPage, $this->getModuleURL('user_themes/' . $user_id));


		// Page nav
		$nav = array();
		$nav['pagination'] = $pages;
		$this->page_title .= ' (' . $page . ')';


		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$firstOnPage = ($page - 1) * $perPage + 1;
		$lastOnPage = $firstOnPage + $recOnPage - 1;

		$nav['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . __('User themes') . ' "' . h($user->getName()) . '"';
		$nav['meta'] = __('Count all topics') . ' ' . $total . '. ' . __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage;
		$this->_globalize($nav);

		if ($total < 1)
			return $this->_view(__('No topics'));



		//get records
		$themesModel->bindModel('author');
		$themesModel->bindModel('last_author');
		$themesModel->bindModel('postslist');
		$themesModel->bindModel('forum');
		$themes = $themesModel->getCollection(array(
			'id_author' => $user_id,
				), array(
			'order' => 'time DESC',
			'group' => 'id',
			'page' => $page,
			'limit' => $perPage,
				));


		foreach ($themes as $theme) {
			if ($theme) {
				$parent_forum = $theme->getForum() ? get_link($theme->getForum()->getTitle()
								, $this->getModuleURL('view_forum/' . $theme->getId_forum())) : '';
				$theme->setParent_forum($parent_forum);
				$theme = $this->__parseThemeTable($theme);


				//set cache tags
				$this->setCacheTag(array(
					'theme_id_' . $theme->getId(),
				));
			}
		}


		// write into cache
		if ($this->cached) {
			$this->Cache->write($html, $this->cacheKey, $this->cacheTags);
		}

		//pr($themes); die();
		$source = $this->render('lastposts_list.html', array(
			'context' => array(
				'forum_name' => __('User themes') . ' "' . h($user->getName()) . '"',
			),
			'themes' => $themes
				));
		$this->_view($source);
	}

	/**
	 * @return forum statistic block
	 */
	protected function _get_stat() {
		$markers = array();
		$result = $this->Model->getStats();


		if (!empty($result[0]['last_user_id']) && !empty($result[0]['last_user_name'])) {
			$markers['new_user'] = get_link(h($result[0]['last_user_name']), getProfileUrl($result[0]['last_user_id']));
		}
		$markers['count_users'] = getAllUsersCount();
		$markers['count_posts'] = (!empty($result[0]['posts_cnt'])) ? $result[0]['posts_cnt'] : 0;
		$markers['count_themes'] = (!empty($result[0]['themes_cnt'])) ? $result[0]['themes_cnt'] : 0;


		$html = $this->render('get_stat.html', $markers);
		return $html;
	}

	public function download_file($file = null, $mimetype = 'application/octet-stream') {
		if (empty($file))
			return $this->showInfoMessage(__('File not found'), $this->getModuleURL());

		$path = ROOT . $this->getFilesPath($file);
		if (!file_exists($path))
			return $this->showInfoMessage(__('File not found'), $this->getModuleURL());
		$from = 0;
		$size = filesize($path);
		$to = $size;
		if (isset($_SERVER['HTTP_RANGE'])) {
			if (preg_match('#bytes=-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {// если указан отрезок от конца файла
				$from = $size - $range[1];
				$to = $size;
			} elseif (preg_match('#bytes=([0-9]*)-#', $_SERVER['HTTP_RANGE'], $range)) {// если указана только начальная метка
				$from = $range[1];
				$to = $size;
			} elseif (preg_match('#bytes=([0-9]*)-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {// если указан отрезок файла
				$from = $range[1];
				$to = $range[2];
			}
			header('HTTP/1.1 206 Partial Content');

			$cr = 'Content-Range: bytes ' . $from . '-' . $to . '/' . $size;
		} else
			header('HTTP/1.1 200 Ok');

		$etag = md5($path);
		$etag = substr($etag, 0, 8) . '-' . substr($etag, 8, 7) . '-' . substr($etag, 15, 8);
		header('ETag: "' . $etag . '"');
		header('Accept-Ranges: bytes');
		header('Content-Length: ' . ($to - $from));
		if (isset($cr))
			header($cr);
		header('Connection: close');
		header('Content-Type: ' . $mimetype);
		header('Last-Modified: ' . gmdate('r', filemtime($path)));
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($path)) . " GMT");
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3600) . " GMT");
		$f = fopen($path, 'rb');


		if (preg_match('#^image/#', $mimetype))
			header('Content-Disposition: filename="' . $file . '";');
		else
			header('Content-Disposition: attachment; filename="' . $file . '";');

		fseek($f, $from, SEEK_SET);
		$size = $to;
		$downloaded = 0;
		while (!feof($f) and ($downloaded < $size)) {
			$block = min(1024 * 8, $size - $downloaded);
			echo fread($f, $block);
			$downloaded += $block;
			flush();
		}
		fclose($f);
	}

	public function important($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'important_themes'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());

		$theme->setImportant('1');
		$theme->save();

		/* clean cache DB */
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('important post', 'theme id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}

	public function unimportant($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'important_themes'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$themesModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themesModel->getById($id);
		if (!$theme)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());

		$theme->setImportant('0');
		$theme->save();

		/* clean cache DB */
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('unimportant post', 'theme id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_forum/' . $theme->getId_forum()));
	}

	/**
	 * deleting attaches  collizion
	 *
	 * @post (array)   reply data
	 * @clean(boolean) clean all or only collizions
	 * @return         none
	 */
	private function deleteCollizions($post, $clean = false) {
		/* DB has file */
		$attachModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		$attachments = $attachModel->getCollection(array('post_id' => $post->getId()));
		if ($clean === true) {
			if (count($attachments) && is_array($attachments))
				foreach ($attachments as $attach)
					$attach->delete();
		} else {
			if (count($attachments) && is_array($attachments)) {
				foreach ($attachments as $key => $attach) {
					if (file_exists(ROOT . $this->getFilesPath($attach->getFilename()))) {
						clearstatcache();
						continue;
					}
					$attach->delete();
					unset($attachments[$key]);
				}
			}
		}


		/* File has DB record */
		$attach_files = glob(ROOT . $this->getFilesPath($post->getId() . '-*'));
		if (!empty($attach_files)) {
			foreach ($attach_files as $_key => $attach_file) {
				if ($clean === true) {
					@unlink($attach_file);
					continue;
				}
				$record_exists = false;
				if (count($attachments) && is_array($attachments)) {
					foreach ($attachments as $attach) {
						if (strrchr($attach_file, '/') == $attach->getFilename()) {
							$record_exists = true;
							break;
						}
					}
				}
				if ($record_exists === false) {
					unset($attach_files[$_key]);
					@unlink($attach_file);
				}
			}
		}
		if ($clean === true)
			return;
		/* posts.attaches flag */
		$flag = (!empty($attach_files) && !empty($attachments)) ? '1' : '0';
		if ($flag != $post->getAttaches()) {
			$post->setAttaches($flag);
			$post->save();
		}
		return;
	}

	//delete theme
	private function __delete_theme($theme) {
		$id_theme = $theme->getId();

		// Step 1: Deleting attached files
		$attachesModel = $this->Register['ModManager']->getModelInstance('ForumAttaches');
		$attach_files = $attachesModel->getCollection(array('theme_id' => $id_theme));
		if (count($attach_files) && is_array($attach_files)) {
			foreach ($attach_files as $attach_file) {
				if (file_exists(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
					if (@unlink(ROOT . $this->getFilesPath($attach_file->getFilename()))) {
						$attach_file->delete();
					}
				}
			}
		}

		// Step 2: Selecting authors and deleting posts
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$users = $postsModel->getCollection(array('id_theme' => $id_theme), array('fields' => array('DISTINCT id_author')));
		$postsModel->deleteByTheme($id_theme);

		// Step 3: Deleting poll
		$pollsModel = $this->Register['ModManager']->getModelInstance('Polls');
		$pollsModel->deleteByTheme($id_theme);

		// Step 4: Deleting theme
		$theme->delete();

		// Step 5: Deleting collision
		$this->Model->deleteCollisions();

		// Step 6: Updating counters for forum
		$this->Model->updateForumCounters($theme->getId_forum());

		// Step 7: Updating counters for users
		if ($users && is_array($users)) {
			foreach ($users as $user) {
				if ($user) {
					$this->Model->updateUserCounters($user->getId_author());
				}
			}
		}

		// Step 8: Cleaning cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme,));
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_forum', 'action_index'));
		$this->DB->cleanSqlCache();
	}

	public function view_post($id_post = null) {
		$id_post = intval($id_post);
		if ($id_post < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());

		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$post = $postsModel->getById($id_post);
		if (!$post)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());

		$id_theme = $post->getId_theme();
		$post_num = $postsModel->getTotal(
				array(
					'order' => 'id ASC',
					'cond' => array(
						'id_theme' => $id_theme,
						'((time = \'' . $post->getTime() . '\' AND id < ' . $id_post . ') OR time < \'' . $post->getTime() . '\')',
					),
				)
		);

		$page = floor($post_num / $this->Register['Config']->read('posts_per_page', $this->module)) + 1;
		$post_num++;

		redirect($this->getModuleURL('view_theme/' . $id_theme . '?page=' . $page . '#post' . $post_num));
	}

	public function split_theme_form($id_theme = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'add_themes'));
		$this->ACL->turn(array($this->module, 'edit_themes'));
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());



		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themeModel->bindModel('forum');
		$theme = $themeModel->getById($id_theme);
		if (empty($theme) || !$theme->getForum())
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		//turn access
		$this->ACL->turn(array($this->module, 'add_themes', $theme->getId_forum()));
		$this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()));


		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($theme->getForum());
		$id_forum = $theme->getId_forum();

		$this->__checkThemeAccess($theme);


		$html = '';
		// Если при заполнении формы были допущены ошибки
		if (isset($_SESSION['editThemeForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editThemeForm']['error'],
				),
					));
			$html = $info . $html . "\n";
			$name = h($_SESSION['editThemeForm']['theme']);
			$desc = h($_SESSION['editThemeForm']['description']);
			$gr_access = $_SESSION['editThemeForm']['gr_access'];
			$posts_select = $_SESSION['editThemeForm']['posts_select'];
			$first_top = $_SESSION['editThemeForm']['first_top'];
			unset($_SESSION['editThemeForm']);
		} else {
			$name = '';
			$desc = '';
			$gr_access = array();
			$posts_select = array();
			$first_top = '';
		}


		// Формируем список форумов, чтобы можно было переместить тему в другой форум
		$forums = $this->Model->getCollection(array(), array('order' => 'pos'));
		if (!$forums)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());


		$options = '';
		foreach ($forums as $forum) {
			if ($forum->getId() == $theme->getId_forum())
				$options = $options . '<option value="' . $forum->getId() . '" selected>' . h($forum->getTitle()) . '</option>' . "\n";
			else
				$options = $options . '<option value="' . $forum->getId() . '">' . h($forum->getTitle()) . '</option>' . "\n";
		}


		// Заголовок страницы (содержимое тега title)
		$this->page_title = __('Split theme') . ' - ' . h($theme->getTitle()) . ' - ' . $this->page_title;


		$markers = array();
		$markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator') . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme));
		$description = h($theme->getDescription());
		if (!empty($description)) {
			$markers['navigation'] .= ' (' . $description . ')';
		}


		// Page nav
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');

		$where = array('id_theme' => $id_theme);
		$first_post = $postsModel->getFirst(array(
			'id_theme' => $id_theme,
				), array(
			'order' => 'time ASC, id ASC',
				));
		if ($first_post) {
			$where[] = 'id != ' . $first_post->getId();
		}

		$posts_per_page = 100; // $this->Register['Config']->read('posts_per_page', $this->module);
		$total = $postsModel->getTotal(array('cond' => $where));
		if ($total < 1)
			return $this->showInfoMessage(__('Not enough posts'), $this->getModuleURL('view_theme/' . $id_theme));
		list($pages, $page) = pagination($total, $posts_per_page, $this->getModuleURL('split_theme_form/' . $id_theme));
		$markers['pagination'] = $pages;
		$this->page_title .= ' (' . $page . ')';

		// SELECT posts
		$posts = $postsModel->getCollection($where, array(
			'order' => 'time ASC, id ASC',
			'page' => $page,
			'limit' => $posts_per_page,
				));


		$markers['meta'] = '';
		$this->_globalize($markers);


		$usersModel = $this->Register['ModManager']->getModelInstance('Users');
		foreach ($posts as $post) {
			$postAuthor = $usersModel->getById($post->getId_author());
			$post->setAuthor_name($postAuthor ? $postAuthor->getName() : __('Guest'));
			$author_status = ($postAuthor) ? $postAuthor->getStatus() : 0;
			$message = $this->Textarier->print_page($post->getMessage(), $author_status);
			$post->setMessage($message);
		}


		$data = array(
			'action' => get_url($this->getModuleURL('split_theme/' . $id_theme . '?page=' . $page)),
			'theme' => $name,
			'description' => $desc,
			'options' => $options,
			'gr_access' => (!empty($gr_access)) ? $gr_access : array(),
			'posts_select' => (!empty($posts_select)) ? $posts_select : array(),
			'first_top' => (!empty($first_top)) ? $first_top : '0',
		);


		$source = $this->render('splitthemeform.html', array(
			'posts' => $posts,
			'theme' => $theme,
			'context' => $data,
				));

		return $this->_view($html . $source);
	}

	public function split_theme($id_theme = null) {
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($id_theme) || !isset($_POST['id_forum']) || !isset($_POST['theme']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_theme = intval($id_theme);
		$id_forum = intval($_POST['id_forum']);
		if ($id_theme < 1 || $id_forum < 1)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());


		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themeModel->getById($id_theme);
		if (empty($theme))
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$id_from_forum = $theme->getId_forum();
		$name = trim(mb_substr($_POST['theme'], 0, 55));
		$description = trim(mb_substr($_POST['description'], 0, 128));
		$first_top = isset($_POST['first_top']) ? '1' : '0';

		$gr_access = array();
		$groups = $this->ACL->getGroups();
		foreach ($groups as $grid => $grval) {
			if (isset($_POST['gr_access_' . $grid]))
				$gr_access[] = $grid;
		}
		$posts_select = array();
		foreach ($_POST as $key => $value) {
			if (strpos($key, 'post_') === 0) {
				$number = substr($key, strlen('post_'));
				if (!empty($number))
					$posts_select[] = intval($number);
			}
		}
		$posts_select = array_unique($posts_select, SORT_NUMERIC);


		// validate ...
		$error = '';
		$valobj = $this->Register['Validate'];
		if (empty($name))
			$error = $error . '<li>' . __('Empty field "theme"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($name, V_TITLE))
			$error = $error . '<li>' . __('Wrong chars in "theme"') . '</li>' . "\n";
		if (empty($posts_select))
			$error = $error . '<li>' . __('Empty "posts_select"') . '</li>' . "\n";

		// errors
		if (!empty($error)) {
			$_SESSION['editThemeForm'] = array();
			$_SESSION['editThemeForm']['error'] = '<p class="errorMsg">' . __('Some error in form')
					. '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['editThemeForm']['theme'] = $name;
			$_SESSION['editThemeForm']['description'] = $description;
			$_SESSION['editThemeForm']['gr_access'] = $gr_access;
			$_SESSION['editThemeForm']['posts_select'] = $posts_select;
			$_SESSION['editThemeForm']['first_top'] = $first_top;
			redirect($this->getModuleURL('split_theme_form/' . $id_theme . (isset($_GET['page']) ? '?page=' . $_GET['page'] : '')));
		}



		//check access
		if (!$this->ACL->turn(array($this->module, 'add_themes'), false) ||
				!$this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()), false)) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $id_forum));
		}

		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$first_post = $postsModel->getById(min($posts_select));
		if (!$first_post)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL('view_theme/' . $id_theme));
		$last_post = $postsModel->getById(max($posts_select));
		if (!$last_post)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL('view_theme/' . $id_theme));


		// new theme
		$data = array(
			'title' => $name,
			'description' => $description,
			'id_author' => $first_post->getId_author(),
			'time' => $first_post->getTime(),
			'id_last_author' => $last_post->getId_author(),
			'last_post' => $last_post->getTime(),
			'id_forum' => $id_forum,
			'posts' => count($posts_select) > 0 ? count($posts_select) - 1 : 0,
			'group_access' => $gr_access,
			'first_top' => $first_top,
		);
		$new_theme = new ThemesEntity($data);
		$id_new_theme = $new_theme->save();
		if (!is_int($id_new_theme)) {
			$id_new_theme = mysql_insert_id();
		}
		if ($id_new_theme == 0)
			$this->showInfoMessage(__('Some error occurred'), $this->getModuleURL('view_theme/' . $id_theme));

		$postsModel->moveToTheme($id_new_theme, $posts_select);


		$new_last_post = $postsModel->getFirst(array(
			'id_theme' => $theme->getId(),
				), array(
			'order' => 'id DESC'
				));

		// update theme
		if ($new_last_post) {
			$theme->setId_last_author($new_last_post->getId_author());
			$theme->setLast_post($new_last_post->getTime());
		}
		$theme->setPosts($theme->getPosts() > count($posts_select) ? $theme->getPosts() - count($posts_select) : 0);
		$theme->save();

		//update forums info
		$new_forum = $this->Model->getById($id_forum);
		if (!$new_forum)
			return $this->showInfoMessage(__('No forum for moving'), $this->getModuleURL('view_theme/' . $id_theme));

		if ($id_from_forum != $id_forum) {
			$from_forum = $this->Model->getById($id_from_forum);
			if ($from_forum) {
				$from_forum->setPosts($from_forum->getPosts() > count($posts_select) ? $from_forum->getPosts() - count($posts_select) : 0);
				$from_forum->save();
			}

			$new_forum->setPosts($new_forum->getPosts() + count($posts_select));
			$new_forum->setThemes($new_forum->getThemes() + 1);
			$new_forum->save();
		} else {
			$new_forum->setThemes($new_forum->getThemes() + 1);
			$new_forum->save();
		}

		$this->Model->upLastPost($id_from_forum, $id_forum);


		//clean cahce
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('split theme', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $id_new_theme));
	}

	public function move_posts_form($id_theme = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'edit_themes'));
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());



		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themeModel->bindModel('forum');
		$theme = $themeModel->getById($id_theme);
		if (empty($theme) || !$theme->getForum())
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		//turn access
		$this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()));


		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($theme->getForum());
		$id_forum = $theme->getId_forum();

		$this->__checkThemeAccess($theme);


		$html = '';
		// Если при заполнении формы были допущены ошибки
		if (isset($_SESSION['editThemeForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editThemeForm']['error'],
				),
					));
			$html = $info . $html . "\n";
			$name = h($_SESSION['editThemeForm']['theme']);
			$posts_select = $_SESSION['editThemeForm']['posts_select'];
			unset($_SESSION['editThemeForm']);
		} else {
			$name = '';
			$posts_select = array();
		}


		// Формируем список форумов, чтобы можно было переместить тему в другой форум
		$forums = $this->Model->getCollection(array(), array('order' => 'pos'));
		if (!$forums)
			return $this->showInfoMessage(__('Can not find forum'), $this->getModuleURL());


		// Заголовок страницы (содержимое тега title)
		$this->page_title = __('Move posts') . ' - ' . h($theme->getTitle()) . ' - ' . $this->page_title;


		$markers = array();
		$markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator') . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme));
		$description = h($theme->getDescription());
		if (!empty($description)) {
			$markers['navigation'] .= ' (' . $description . ')';
		}


		// Page nav
		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');

		$where = array('id_theme' => $id_theme);
		$first_post = $postsModel->getFirst(array(
			'id_theme' => $id_theme,
				), array(
			'order' => 'time ASC, id ASC',
				));
		if ($first_post) {
			$where[] = 'id != ' . $first_post->getId();
		}

		$posts_per_page = 100; // $this->Register['Config']->read('posts_per_page', $this->module);
		$total = $postsModel->getTotal(array('cond' => $where));
		if ($total < 1)
			return $this->showInfoMessage(__('Not enough posts'), $this->getModuleURL('view_theme/' . $id_theme));
		list($pages, $page) = pagination($total, $posts_per_page, $this->getModuleURL('move_posts_form/' . $id_theme));
		$markers['pagination'] = $pages;
		$this->page_title .= ' (' . $page . ')';

		// SELECT posts
		$posts = $postsModel->getCollection($where, array(
			'order' => 'time ASC, id ASC',
			'page' => $page,
			'limit' => $posts_per_page,
				));


		$markers['meta'] = '';
		$this->_globalize($markers);


		$usersModel = $this->Register['ModManager']->getModelInstance('Users');
		foreach ($posts as $post) {
			$postAuthor = $usersModel->getById($post->getId_author());
			$post->setAuthor_name($postAuthor ? $postAuthor->getName() : __('Guest'));
			$author_status = ($postAuthor) ? $postAuthor->getStatus() : 0;
			$message = $this->Textarier->print_page($post->getMessage(), $author_status);
			$post->setMessage($message);
		}


		$data = array(
			'action' => get_url($this->getModuleURL('move_posts/' . $id_theme . '?page=' . $page)),
			'theme' => $name,
			'posts_select' => (!empty($posts_select)) ? $posts_select : array(),
		);


		$source = $this->render('movepostsform.html', array(
			'posts' => $posts,
			'theme' => $theme,
			'context' => $data,
				));

		return $this->_view($html . $source);
	}

	public function move_posts($id_theme = null) {
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($id_theme) || !isset($_POST['theme']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$theme = $themeModel->getById($id_theme);
		if (empty($theme))
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());
		$id_forum = $theme->getId_forum();


		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$id_from_forum = $theme->getId_forum();
		$id_new_theme = trim($_POST['theme']);

		$posts_select = array();
		foreach ($_POST as $key => $value) {
			if (strpos($key, 'post_') === 0) {
				$number = substr($key, strlen('post_'));
				if (!empty($number))
					$posts_select[] = intval($number);
			}
		}
		$posts_select = array_unique($posts_select, SORT_NUMERIC);


		// validate ...
		$error = '';
		if (empty($id_new_theme))
			$error = $error . '<li>' . __('Empty field "theme"') . '</li>' . "\n";
		elseif ($id_new_theme == $id_theme)
			$error = $error . '<li>' . __('Moving into same topic') . '</li>' . "\n";
		else {
			$new_theme = $themeModel->getById($id_new_theme);
			if (empty($new_theme))
				$error = $error . '<li>' . __('Theme does not exists') . '</li>' . "\n";
		}
		if (empty($posts_select))
			$error = $error . '<li>' . __('Empty "posts_select"') . '</li>' . "\n";

		// errors
		if (!empty($error)) {
			$_SESSION['editThemeForm'] = array();
			$_SESSION['editThemeForm']['error'] = '<p class="errorMsg">' . __('Some error in form')
					. '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['editThemeForm']['theme'] = $id_new_theme;
			$_SESSION['editThemeForm']['posts_select'] = $posts_select;
			redirect($this->getModuleURL('move_posts_form/' . $id_theme . (isset($_GET['page']) ? '?page=' . $_GET['page'] : '')));
		}



		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()), false)) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $id_forum));
		}

		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$first_post = $postsModel->getById(min($posts_select));
		if (!$first_post)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL('view_theme/' . $id_theme));
		$last_post = $postsModel->getById(max($posts_select));
		if (!$last_post)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL('view_theme/' . $id_theme));


		$postsModel->moveToTheme($id_new_theme, $posts_select);


		$new_last_post = $postsModel->getFirst(array(
			'id_theme' => $theme->getId(),
				), array(
			'order' => 'id DESC'
				));

		// update theme
		if ($new_last_post) {
			$theme->setId_last_author($new_last_post->getId_author());
			$theme->setLast_post($new_last_post->getTime());
		}
		$theme->setPosts($theme->getPosts() > count($posts_select) ? $theme->getPosts() - count($posts_select) : 0);
		$theme->save();

		$new_theme->setPosts($new_theme->getPosts() + count($posts_select));
		$new_theme->save();

		//update forums info
		$new_forum = $this->Model->getById($id_forum);
		if (!$new_forum)
			return $this->showInfoMessage(__('No forum for moving'), $this->getModuleURL('view_theme/' . $id_theme));

		if ($id_from_forum != $id_forum) {
			$from_forum = $this->Model->getById($id_from_forum);
			if ($from_forum) {
				$from_forum->setPosts($from_forum->getPosts() > count($posts_select) ? $from_forum->getPosts() - count($posts_select) : 0);
				$from_forum->save();
			}

			$new_forum->setPosts($new_forum->getPosts() + count($posts_select));
			$new_forum->save();
		}

		$this->Model->upLastPost($id_from_forum, $id_forum);


		//clean cahce
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('move posts', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $id_new_theme));
	}

	public function unite_themes_form($id_theme = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'edit_themes'));
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());



		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themeModel->bindModel('forum');
		$theme = $themeModel->getById($id_theme);
		if (empty($theme) || !$theme->getForum())
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		//turn access
		$this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()));


		// Check access to this forum. May be locked by pass or posts count
		$this->__checkForumAccess($theme->getForum());
		$id_forum = $theme->getId_forum();

		$this->__checkThemeAccess($theme);


		$html = '';
		// Если при заполнении формы были допущены ошибки
		if (isset($_SESSION['editThemeForm'])) {
			$info = $this->render('infomessage.html', array(
				'context' => array(
					'info_message' => $_SESSION['editThemeForm']['error'],
				),
					));
			$html = $info . $html . "\n";
			$name = h($_SESSION['editThemeForm']['theme']);
			unset($_SESSION['editThemeForm']);
		} else {
			$name = '';
		}


		// Формируем список форумов, чтобы можно было переместить тему в другой форум
		$forums = $this->Model->getCollection(array(), array('order' => 'pos'));
		if (!$forums)
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());


		// Заголовок страницы (содержимое тега title)
		$this->page_title = __('Unite themes') . ' - ' . h($theme->getTitle()) . ' - ' . $this->page_title;


		$markers = array();
		$markers['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(__('Forums list'), $this->getModuleURL()) . __('Separator') . get_link($theme->getForum()->getTitle(), $this->getModuleURL('view_forum/' . $id_forum)) . __('Separator') . get_link($theme->getTitle(), $this->getModuleURL('view_theme/' . $id_theme));
		$description = h($theme->getDescription());
		if (!empty($description)) {
			$markers['navigation'] .= ' (' . $description . ')';
		}


		$markers['pagination'] = '';
		$markers['meta'] = '';
		$this->_globalize($markers);


		$data = array(
			'action' => get_url($this->getModuleURL('unite_themes/' . $id_theme)),
			'theme' => $name,
		);


		$source = $this->render('unitethemesform.html', array(
			'theme' => $theme,
			'context' => $data,
				));

		return $this->_view($html . $source);
	}

	public function unite_themes($id_theme = null) {
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($id_theme) || !isset($_POST['theme']))
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		$id_theme = intval($id_theme);
		if ($id_theme < 1)
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());


		$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
		$themeModel->bindModel('poll');
		$theme = $themeModel->getById($id_theme);
		if (empty($theme))
			return $this->showInfoMessage(__('Topic not found'), $this->getModuleURL());
		$id_forum = $theme->getId_forum();


		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$id_from_forum = $theme->getId_forum();
		$id_new_theme = trim($_POST['theme']);

		// validate ...
		$error = '';
		if (empty($id_new_theme))
			$error = $error . '<li>' . __('Empty field "theme"') . '</li>' . "\n";
		elseif ($id_new_theme == $id_theme)
			$error = $error . '<li>' . __('Moving into same topic') . '</li>' . "\n";
		else {
			$new_theme = $themeModel->getById($id_new_theme);
			if (empty($new_theme))
				$error = $error . '<li>' . __('Theme does not exists') . '</li>' . "\n";
		}

		// errors
		if (!empty($error)) {
			$_SESSION['editThemeForm'] = array();
			$_SESSION['editThemeForm']['error'] = '<p class="errorMsg">' . __('Some error in form')
					. '</p>' . "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['editThemeForm']['theme'] = $id_new_theme;
			redirect($this->getModuleURL('unite_themes_form/' . $id_theme));
		}


		//check access
		if (!$this->ACL->turn(array($this->module, 'edit_themes', $theme->getId_forum()), false)) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL('view_forum/' . $id_forum));
		}

		$postsModel = $this->Register['ModManager']->getModelInstance('Posts');
		$posts = $postsModel->getCollection(array('id_theme' => $id_theme), array('fields' => array('id')));
		$posts_select = array();
		if ($posts && is_array($posts)) {
			foreach ($posts as $post) {
				$posts_select[] = intval($post->getId());
			}
		}

		$postsModel->moveToTheme($id_new_theme, $posts_select);

		$polls = $theme->getPoll();
		if ($polls) {
			if ($new_theme->getPoll()) {
				if (is_array($polls)) {
					foreach ($polls as $poll) {
						$poll->delete();
					}
				}
			} else {
				if (is_array($polls)) {
					$first = true;
					foreach ($polls as $poll) {
						if ($first) {
							$poll->setTheme_id($id_new_theme);
							$poll->save();
							$first = false;
						} else {
							$poll->delete();
						}
					}
				}
			}
		}

		$theme->delete();

		$new_theme->setPosts($new_theme->getPosts() + count($posts_select));
		$new_theme->save();

		//update forums info
		$from_forum = $this->Model->getById($id_from_forum);
		if (!$from_forum)
			return $this->showInfoMessage(__('No forum for moving'), $this->getModuleURL('view_theme/' . $id_theme));

		if ($id_from_forum != $id_forum) {
			$from_forum->setPosts($from_forum->getPosts() > count($posts_select) ? $from_forum->getPosts() - count($posts_select) : 0);
			$from_forum->setThemes($from_forum->getThemes() - 1);
			$from_forum->save();

			$new_forum = $this->Model->getById($id_forum);
			if ($new_forum) {
				$new_forum->setPosts($new_forum->getPosts() + count($posts_select));
				$new_forum->save();
			}
		} else {
			$from_forum->setThemes($from_forum->getThemes() - 1);
			$from_forum->save();
		}

		$this->Model->upLastPost($id_from_forum, $id_forum);


		//clean cahce
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('theme_id_' . $id_theme));
		$this->DB->cleanSqlCache();
		if ($this->Log)
			$this->Log->write('move posts', 'theme id(' . $id_theme . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL('view_theme/' . $id_new_theme));
	}

	/**
	 * RSS
	 *
	 */
	function rss() {
		include_once ROOT . '/sys/inc/includes/rss.php';
	}

}
