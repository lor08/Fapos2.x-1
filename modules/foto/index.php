<?php
/*-----------------------------------------------\
| 												 |
| @Author:       Andrey Brykin (Drunya)          |
| @Email:        drunyacoder@gmail.com           |
| @Site:         http://fapos.net                |
| @Version:      1.5.5                           |
| @Project:      CMS                             |
| @package       CMS Fapos                       |
| @subpackege    Foto Module  			 		 |
| @copyright     ©Andrey Brykin 2010-2013        |
| @last  mod     2013/03/31                      |
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
 *
 */
Class FotoModule extends Module {

	/**
	 * @module_title  title of module
	 */
	public $module_title = 'Фото';

	/**
	 * @template  layout for module
	 */
	public $template = 'foto';

	/**
	 * @module module indentifier
	 */
	public $module = 'foto';

	/**
	 * default action ( show main page )
	 */
	public function index() {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));


		//формируем блок со списком  разделов
		$this->_getCatsTree();


		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}

		$where = array();


		$total = $this->Model->getTotal(array('cond' => $where));
		$perPage = intval($this->Register['Config']->read('per_page', $this->module));
		if ($perPage < 1)
			$perPage = 10;
		list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL());
		$this->Register['pages'] = $pages;
		$this->Register['page'] = $page;
		$this->page_title .= ' (' . $page . ')';



		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs();
		$navi['pagination'] = $pages;

		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$firstOnPage = ($page - 1) * $perPage + 1;
		$lastOnPage = $firstOnPage + $recOnPage - 1;

		$navi['meta'] = __('Count all material') . ' ' . $total . '. ' . __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage;
		$this->_globalize($navi);


		if ($total <= 0) {
			$html = __('Materials not found');
			return $this->_view($html);
		}


		$params = array(
			'page' => $page,
			'limit' => $perPage,
			'order' => getOrderParam(__CLASS__),
		);


		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);


		// create markers
		foreach ($records as $entity) {
			$this->Register['current_vars'] = $entity;
			$markers = array();


			$markers['moder_panel'] = $this->_getAdminBar($entity);
			$entry_url = get_url(entryUrl($entity, $this->module));
			$markers['entry_url'] = $entry_url;
			$markers['preview_foto'] = get_url($this->getFilesPath('preview/' . $entity->getFilename()));
			$markers['foto_alt'] = h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle()));



			$markers['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
			$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());


			//set users_id that are on this page
			$this->setCacheTag(array(
				'user_id_' . $entity->getAuthor_id(),
				'record_id_' . $entity->getId(),
			));


			$entity->setAdd_markers($markers);
		}


		$source = $this->render('list.html', array('entities' => $records));


		//write int cache
		if ($this->cached)
			$this->Cache->write($source, $this->cacheKey, $this->cacheTags);


		return $this->_view($source);
	}

	/**
	 * Show materials in category. Category ID must be integer and not null.
	 */
	public function category($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Can not find category'), $this->getModuleURL());


		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$category = $sectionsModel->getById($id);
		if (!$category)
			return $this->showInfoMessage(__('Can not find category'), $this->getModuleURL());
		if (!$this->ACL->checkCategoryAccess($category->getNo_access()))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());


		$this->page_title = h($category->getTitle()) . ' - ' . $this->page_title;


		//формируем блок со списком  разделов
		$this->_getCatsTree($id);


		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}

		// we need to know whether to show hidden
		$childCats = $sectionsModel->getOneField('id', array('parent_id' => $id));
		$ids = '`category_id` = ' . $id;
		if ($childCats && is_array($childCats) && count($childCats) > 0)
			$ids .= ' OR `category_id` IN (' . implode(', ', array_unique($childCats)) . ')';
		$where = array($ids);
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) {
			$where['available'] = 1;
		}


		$total = $this->Model->getTotal(array('cond' => $where));
		$perPage = intval($this->Register['Config']->read('per_page', $this->module));
		if ($perPage < 1)
			$perPage = 10;
		list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL('category/' . $id));
		$this->Register['pages'] = $pages;
		$this->Register['page'] = $page;
		$this->page_title .= ' (' . $page . ')';



		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs($id);
		$navi['pagination'] = $pages;

		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$firstOnPage = ($page - 1) * $perPage + 1;
		$lastOnPage = $firstOnPage + $recOnPage - 1;

		$navi['meta'] = __('Count material in cat') . ' ' . $total . '. ' . __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage;
		$navi['category_name'] = h($category->getTitle());
		$this->_globalize($navi);


		if ($total <= 0) {
			$html = __('Materials not found');
			return $this->_view($html);
		}


		$params = array(
			'page' => $page,
			'limit' => $perPage,
			'order' => getOrderParam(__CLASS__),
		);


		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);


		// create markers
		foreach ($records as $entity) {
			$this->Register['current_vars'] = $entity;
			$markers = array();


			$markers['moder_panel'] = $this->_getAdminBar($entity);
			$entry_url = get_url(entryUrl($entity, $this->module));
			$markers['entry_url'] = $entry_url;

			$markers['preview_foto'] = get_url($this->getFilesPath('preview/' . $entity->getFilename()));
			$markers['foto_alt'] = h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle()));


			$markers['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
			$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());


			//set users_id that are on this page
			$this->setCacheTag(array(
				'user_id_' . $entity->getAuthor_id(),
				'record_id_' . $entity->getId(),
				'category_id_' . $id,
			));


			$entity->setAdd_markers($markers);
		}


		$source = $this->render('list.html', array('entities' => $records));


		//write int cache
		if ($this->cached)
			$this->Cache->write($source, $this->cacheKey, $this->cacheTags);


		return $this->_view($source);
	}

	/**
	 * View entity. Entity ID must be integer and not null.
	 */
	public function view($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_materials'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());



		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$entity = $this->Model->getById($id);


		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());
		if (!$this->ACL->checkCategoryAccess($entity->getCategory()->getNo_access()))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());


		//category block
		$this->_getCatsTree($entity->getCategory_id());
		/* COMMENT BLOCK */
		if ($this->Register['Config']->read('comment_active', $this->module) == 1
				&& $this->ACL->turn(array($this->module, 'view_comments'), false)
				&& $entity->getCommented() == 1) {
			if ($this->ACL->turn(array($this->module, 'add_comments'), false))
				$this->comments_form = $this->_add_comment_form($id);
			$this->comments = $this->_get_comments($entity);
		}
		$this->Register['current_vars'] = $entity;


		//производим замену соответствующих участков в html шаблоне нужной информацией
		$this->page_title = h($entity->getTitle()) . ' - ' . $this->page_title;

		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['module_url'] = get_url($this->getModuleURL());
		$navi['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
		$navi['category_name'] = h($entity->getCategory()->getTitle());
		$navi['navigation'] = $this->_buildBreadCrumbs($entity->getCategory_id());
		$this->_globalize($navi);


		$markers = array();
		$markers['moder_panel'] = $this->_getAdminBar($entity);
		$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());

		$markers['main'] = get_url($this->getFilesPath('full/' . $entity->getFilename()));
		$markers['foto_alt'] = h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle()));
		$markers['description'] = $this->Textarier->print_page($entity->getDescription(), $entity->getAuthor() ? $entity->getAuthor()->geteStatus() : 0);

		$entry_url = get_url(entryUrl($entity, $this->module));
		$markers['entry_url'] = $entry_url;

		$next_prev = $this->Model->getNextPrev($id);
		$prev_id = (!empty($next_prev['prev'])) ? $next_prev['prev']->getId() : $id;
		$next_id = (!empty($next_prev['next'])) ? $next_prev['next']->getId() : $id;

		$markers['previous_url'] = get_url($this->getModuleURL('view/' . $prev_id));
		$markers['next_url'] = get_url($this->getModuleURL('view/' . $next_id));



		$entity->setAdd_markers($markers);


		$this->setCacheTag(array(
			'user_id_' . $entity->getAuthor_id(),
			'record_id_' . $entity->getId(),
			(!empty($_SESSION['user']['status'])) ? 'user_group_' . $_SESSION['user']['status'] : 'user_group_' . 'guest',
		));


		$source = $this->render('material.html', array('entity' => $entity));


		$entity->setViews($entity->getViews() + 1);
		$entity->save();
		$this->DB->cleanSqlCache();

		return $this->_view($source);
	}

	/**
	 * Show materials by user. User ID must be integer and not null.
	 */
	public function user($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Can not find user'), $this->getModuleURL());


		$usersModel = $this->Register['ModManager']->getModelInstance('Users');
		$user = $usersModel->getById($id);
		if (!$user)
			return $this->showInfoMessage(__('Can not find user'), $this->getModuleURL());
		if (!$this->ACL->checkCategoryAccess($user->getNo_access()))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());


		$this->page_title = __('User materials') . ' "' . h($user->getName()) . '" - ' . $this->page_title;


		//формируем блок со списком  разделов
		$this->_getCatsTree();


		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}

		// we need to know whether to show hidden
		$where = array('author_id' => $id);
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) {
			$where['available'] = 1;
		}


		$total = $this->Model->getTotal(array('cond' => $where));
		$perPage = intval($this->Register['Config']->read('per_page', $this->module));
		if ($perPage < 1)
			$perPage = 10;
		list ($pages, $page) = pagination($total, $perPage, $this->getModuleURL('user/' . $id));
		$this->Register['pages'] = $pages;
		$this->Register['page'] = $page;
		$this->page_title .= ' (' . $page . ')';



		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = get_link(__('Home'), '/') . __('Separator')
				. get_link(h($this->module_title), $this->getModuleURL()) . __('Separator') . __('User materials') . ' "' . h($user->getName()) . '"';
		$navi['pagination'] = $pages;

		$cntPages = ceil($total / $perPage);
		$recOnPage = ($page == $cntPages) ? ($total % $perPage) : $perPage;
		$firstOnPage = ($page - 1) * $perPage + 1;
		$lastOnPage = $firstOnPage + $recOnPage - 1;

		$navi['meta'] = __('Count all material') . ' ' . $total . '. ' . __('Count visible') . ' ' . $firstOnPage . '-' . $lastOnPage;
		$navi['category_name'] = __('User materials') . ' "' . h($user->getName()) . '"';
		$this->_globalize($navi);


		if ($total <= 0) {
			$html = __('Materials not found');
			return $this->_view($html);
		}


		$params = array(
			'page' => $page,
			'limit' => $perPage,
			'order' => getOrderParam(__CLASS__),
		);


		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);


		// create markers
		foreach ($records as $entity) {
			$this->Register['current_vars'] = $entity;
			$markers = array();


			$markers['moder_panel'] = $this->_getAdminBar($entity);
			$entry_url = get_url(entryUrl($entity, $this->module));
			$markers['entry_url'] = $entry_url;

			$markers['preview_foto'] = get_url($this->getFilesPath('preview/' . $entity->getFilename()));
			$markers['foto_alt'] = h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle()));


			$markers['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
			$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());


			//set users_id that are on this page
			$this->setCacheTag(array(
				'user_id_' . $entity->getAuthor_id(),
				'record_id_' . $entity->getId(),
				'category_id_' . $id,
			));


			$entity->setAdd_markers($markers);
		}


		$source = $this->render('list.html', array('entities' => $records));


		//write int cache
		if ($this->cached)
			$this->Cache->write($source, $this->cacheKey, $this->cacheTags);


		return $this->_view($source);
	}

	/**
	 * return form to add
	 */
	public function add_form() {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));


		// categories block
		$this->_getCatsTree();


		// Check for preview or errors
		$data = array('title' => null, 'in_cat' => null, 'description' => null, 'commented' => '1');
		$data = Validate::getCurrentInputsValues($data);
		$data['main_text'] = $data['description'];


		$data['errors'] = $this->Parser->getErrors();
		if (isset($_SESSION['FpsForm']))
			unset($_SESSION['FpsForm']);


		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$categories = $sectionsModel->getCollection();
		$data['cats_selector'] = $this->_buildSelector($categories, ((!empty($data['in_cat'])) ? $data['in_cat'] : false));


		//comments and hide
		$data['commented'] = (!empty($data['commented']) || !isset($_POST['submitForm'])) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false))
			$data['commented'] .= ' disabled="disabled"';


		$data['action'] = get_url($this->getModuleURL('add/'));


		// Navigation panel
		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs();
		$this->_globalize($navi);


		$source = $this->render('addform.html', array('context' => $data));
		return $this->_view($source);
	}

	/**
	 *
	 * Validate data and create a new record into
	 * Data Base. If an errors, redirect user to add form
	 * and show error message where speaks as not to admit
	 * errors in the future
	 *
	 */
	public function add() {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($_FILES['foto'])
				|| !isset($_POST['title'])
				|| !isset($_POST['cats_selector'])
				|| !is_numeric($_POST['cats_selector'])) {
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		}
		$error = '';


		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title = trim(mb_substr($_POST['title'], 0, 128));
		$description = trim($_POST['mainText']);
		$in_cat = intval($_POST['cats_selector']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false))
			$commented = 1;


		// Проверяем, заполнены ли обязательные поля
		$valobj = $this->Register['Validate'];  //validation data class
		if (empty($in_cat))
			$error .= '<li>' . __('Category not selected') . '</li>' . "\n";
		if (empty($title))
			$error .= '<li>' . __('Empty field "title"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($title, V_TITLE))
			$error .= '<li>' . __('Wrong chars in "title"') . '</li>' . "\n";
		$max_lenght = $this->Register['Config']->read('description_lenght', $this->module);
		if ($max_lenght <= 0)
			$max_lenght = 1000;
		if (mb_strlen($description) > $max_lenght)
			$error .= '<li>' . sprintf(__('Very big "description"'), $max_lenght) . '</li>' . "\n";



		/* check file */
		if (empty($_FILES['foto']['name'])) {
			$error .= '<li>' . __('No attachment') . '</li>' . "\n";
		} else {
			if ($_FILES['foto']['size'] > $this->getMaxSize())
				$error .= '<li>' . sprintf(__('Very big file2'), round($this->getMaxSize() / 1024, 2)) . '</li>' . "\n";
			$ext = strrchr($_FILES['foto']['name'], ".");
			if (!isImageFile($_FILES['foto']['type'], $ext))
				$error .= '<li>' . __('Wrong file format') . '</li>' . "\n";
		}


		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$category = $sectionsModel->getById($in_cat);
		if (empty($category))
			$error .= '<li>' . __('Can not find category') . '</li>' . "\n";


		// Errors
		if (!empty($error)) {
			$_SESSION['FpsForm'] = array_merge(array('title' => null, 'in_cat' => $in_cat,
				'description' => null, 'commented' => null), $_POST);
			$_SESSION['FpsForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'
					. "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			redirect($this->getModuleURL('add_form/'));
		}


		// Защита от того, чтобы один пользователь не добавил
		// 100 материалов за одну минуту
		if (isset($_SESSION['unix_last_post']) and ( time() - $_SESSION['unix_last_post'] < 10 )) {
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL());
		}




		//remove cache
		$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $this->module));
		$this->DB->cleanSqlCache();
		// Формируем SQL-запрос на добавление темы
		$data = array(
			'title' => $title,
			'description' => mb_substr($description, 0, $max_lenght),
			'date' => new Expr('NOW()'),
			'author_id' => $_SESSION['user']['id'],
			'category_id' => $in_cat,
			'filename' => '',
			'commented' => $commented,
		);
		$className = $this->Register['ModManager']->getEntityName($this->module);
		$entity = new $className($data);
		if ($entity) {
			$last_id = $entity->save();
			$entity->setId($last_id);


			/* save full and resample images */
			$ext = strtolower(strchr($_FILES['foto']['name'], '.'));
			$save_path = ROOT . $this->getFilesPath('full/' . $last_id . $ext);
			$save_sempl_path = ROOT . $this->getFilesPath('preview/' . $last_id . $ext);

			if (!move_uploaded_file($_FILES['foto']['tmp_name'], $save_path))
				$error_flag = true;
			elseif (!chmod($save_path, 0644))
				$error_flag = true;

			/* if an error when coping */
			if (!empty($error_flag) && $error_flag) {
				$entity->delete();
				$_SESSION['FpsForm'] = array_merge(array('title' => null, 'in_cat' => $in_cat,
					'description' => null, 'commented' => null), $_POST);
				$_SESSION['FpsForm']['error'] = '<p class="errorMsg">' . __('Some error occurred') . '</p>'
						. "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
				redirect($this->getModuleURL('add_form/'));
			} else {
				$entity->setFilename($last_id . $ext);
				$entity->save();
			}


			// Create watermark and resample image
			$watermark_path = ROOT . '/sys/img/' . ($this->Register['Config']->read('watermark_type') == '1' ? 'watermark_text.png' : $this->Register['Config']->read('watermark_img'));
			if ($this->Register['Config']->read('use_watermarks') && !empty($watermark_path) && file_exists($watermark_path)) {
				$waterObj = new FpsImg;
				$waterObj->createWaterMark($save_path, $watermark_path);
			}


			$resample = resampleImage($save_path, $save_sempl_path, 150, 150);
			if ($resample)
				chmod($save_sempl_path, 0644);

			//clean cache
			$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module));
			$this->DB->cleanSqlCache();
			if ($this->Log)
				$this->Log->write('adding ' . $this->module, $this->module . ' id(' . $last_id . ')');
			return $this->showInfoMessage(__('Material successfully added'), $this->getModuleURL('view/' . $last_id));
		} else {
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		}
	}

	/**
	 *
	 * Create form and fill his data from record which ID
	 * transfered into function. Show errors if an exists
	 * after unsuccessful attempt. Also can get data for filling
	 * from SESSION if user try preview message or create error.
	 *
	 * @param int $id material then to be edit
	 */
	public function edit_form($id = null) {
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$this->Model->bindModel('author');
		// $this->Model->bindModel('category');
		$entity = $this->Model->getById($id);

		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false)
				&& (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		$this->Register['current_vars'] = $entity;

		//forming categories list
		$this->_getCatsTree($entity->getCategory_id());


		$data = array(
			'title' => '',
			'in_cat' => $entity->getCategory_id(),
			'description' => '',
			'commented' => '',
		);
		$markers = Validate::getCurrentInputsValues($entity, $data);
		$markers->setMain_text($this->Textarier->print_page($markers->getDescription(), $entity->getAuthor() ? $markers->getAuthor()->geteStatus() : 0));


		$markers->setErrors($this->Parser->getErrors());
		if (isset($_SESSION['FpsForm']))
			unset($_SESSION['FpsForm']);


		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$categories = $sectionsModel->getCollection();
		$selectedCatId = ($markers->getIn_cat()) ? $markers->getIn_cat() : $markers->getCategory_id();
		$cats_change = $this->_buildSelector($categories, $selectedCatId);


		//comments and hide
		$commented = ($markers->getCommented()) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false))
			$commented .= ' disabled="disabled"';
		$markers->setAction(get_url($this->getModuleURL('update/' . $markers->getId())));
		$markers->setCommented($commented);




		$markers->setCats_selector($cats_change);


		// Navigation panel
		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) ? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs($entity->getCategory_id());
		$this->_globalize($navi);


		$source = $this->render('editform.html', array('context' => $markers));
		return $this->_view($source);
	}

	/**
	 *
	 * Validate data and update record into
	 * Data Base. If an errors, redirect user to add form
	 * and show error message where speaks as not to admit
	 * errors in the future
	 *
	 */
	public function update($id = null) {
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($id)
				|| !isset($_POST['title'])
				|| !isset($_POST['cats_selector'])) {
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		}
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());
		$error = '';


		$entity = $this->Model->getById($id);
		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		//turn access
		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false)
				&& (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		$valobj = $this->Register['Validate'];

		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title = trim(mb_substr($_POST['title'], 0, 128));
		$description = trim($_POST['mainText']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		$in_cat = intval($_POST['cats_selector']);
		if (empty($in_cat))
			$in_cat = $entity['category_id'];
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false))
			$commented = 1;


		// Проверяем, заполнены ли обязательные поля
		if (empty($title))
			$error .= '<li>' . __('Empty field "title"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($title, V_TITLE))
			$error .= '<li>' . __('Wrong chars in "title"') . '</li>' . "\n";
		$max_lenght = $this->Register['Config']->read('description_lenght', $this->module);
		if ($max_lenght <= 0)
			$max_lenght = 1000;
		if (mb_strlen($description) > $max_lenght)
			$error .= '<li>' . sprintf(__('Very big "description"'), $max_lenght) . '</li>' . "\n";



		$sectionsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$category = $sectionsModel->getById($in_cat);
		if (!$category)
			$error .= '<li>' . __('Can not find category') . '</li>' . "\n";


		// Errors
		if (!empty($error)) {
			$_SESSION['FpsForm'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat,
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null,
				'sourse_site' => null, 'commented' => null, 'available' => null), $_POST);
			$_SESSION['FpsForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'
					. "\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			redirect($this->getModuleURL('edit_form/' . $id));
		}


		//remove cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $id));
		$this->DB->cleanSqlCache();


		$data = array(
			'title' => $title,
			'category_id' => $in_cat,
			'description' => mb_substr($description, 0, $max_lenght),
			'commented' => $commented,
		);
		$entity->set($data);
		$entity->save();

		if ($this->Log)
			$this->Log->write('editing ' . $this->module, $this->module . ' id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), getReferer());
	}

	/**
	 * Check user access and if all right
	 * delete record with geting ID.
	 *
	 * @param int $id
	 */
	public function delete($id = null) {
		$this->cached = false;
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$entity = $this->Model->getById($id);
		if (!$entity)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		//turn access
		if (!$this->ACL->turn(array($this->module, 'delete_materials'), false)
				&& (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}


		//remove cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_' . $this->module, 'record_id_' . $id));
		$this->DB->cleanSqlCache();

		$entity->delete();

		$user_id = (!empty($_SESSION['user']['id'])) ? intval($_SESSION['user']['id']) : 0;
		if ($this->Log)
			$this->Log->write('delete ' . $this->module, $this->module . ' id(' . $id . ') user id(' . $user_id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}

	/**
	 * add comment
	 *
	 * @id (int)    entity ID
	 * @return      info message
	 */
	public function add_comment($id = null) {
		include_once(ROOT . '/sys/inc/includes/add_comment.php');
	}

	/**
	 * add comment form
	 *
	 * @id (int)    entity ID
	 * @return      html form
	 */
	private function _add_comment_form($id = null) {
		include_once(ROOT . '/sys/inc/includes/_add_comment_form.php');
		return $html;
	}

	/**
	 * edit comment form
	 *
	 * @id (int)    comment ID
	 * @return      html form
	 */
	public function edit_comment_form($id = null) {
		include_once(ROOT . '/sys/inc/includes/edit_comment_form.php');
	}

	/**
	 * update comment
	 *
	 * @id (int)    comment ID
	 * @return      info message
	 */
	public function update_comment($id = null) {
		include_once(ROOT . '/sys/inc/includes/update_comment.php');
	}

	/**
	 * get comments
	 *
	 * @id (int)    entity ID
	 * @return      html comments list
	 */
	private function _get_comments($entity = null) {
		include_once(ROOT . '/sys/inc/includes/_get_comments.php');
		return $html;
	}

	/**
	 * delete comment
	 *
	 * @id (int)    comment ID
	 * @return      info message
	 */
	public function delete_comment($id = null) {
		include_once(ROOT . '/sys/inc/includes/delete_comment.php');
	}

	/**
	 * @param int $id - record ID
	 *
	 * update date by record also up record in recods list
	 */
	public function upper($id) {
		//turn access
		$this->ACL->turn(array($this->module, 'up_materials'));
		$id = intval($id);
		if ($id < 1)
			return $this->showInfoMessage(__('Material not found'), $this->getModuleURL());


		$entity = $this->Model->getById($id);
		if ($entity) {
			$entity->setDate(date("Y-m-d H:i:s"));
			$entity->save();
			return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
		}
		return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
	}

	/**
	 * @param array $record - assoc record array
	 * @return string - admin buttons
	 *
	 * create and return admin bar
	 */
	protected function _getAdminBar($record) {
		$moder_panel = '';
		$id = $record->getId();
		$uid = $record->getAuthor_id();
		if (!$uid)
			$uid = 0;

		if ($this->ACL->turn(array($this->module, 'edit_materials'), false)
				|| (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false))) {
			$moder_panel .= get_link('', $this->getModuleURL('edit_form/' . $id), array('class' => 'fps-edit')) . '&nbsp;';
		}

		if ($this->ACL->turn(array($this->module, 'up_materials'), false)) {
			$moder_panel .= get_link('', $this->getModuleURL('upper/' . $id), array('class' => 'fps-up', 'onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}

		if ($this->ACL->turn(array($this->module, 'delete_materials'), false)
				|| (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false))) {
			$moder_panel .= get_link('', $this->getModuleURL('delete/' . $id), array('class' => 'fps-delete', 'onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		return $moder_panel;
	}

	/**
	 * RSS
	 *
	 */
	public function rss() {
		include_once ROOT . '/sys/inc/includes/rss.php';
	}

}
