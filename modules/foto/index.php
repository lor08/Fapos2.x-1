<?php
/*-----------------------------------------------\
| 												 |
| @Author:       Andrey Brykin (Drunya)          |
| @Email:        drunyacoder@gmail.com           |
| @Site:         http://fapos.net                |
| @Version:      1.5.3                           |
| @Project:      CMS                             |
| @package       CMS Fapos                       |
| @subpackege    Foto Module  			 		 |
| @copyright     ©Andrey Brykin 2010-2013        |
| @last  mod     2013/01/22                      |
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
		
		
		//check content cache
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}
		
		
		
		//Узнаем кол-во материалов в БД
		$total = $this->Model->getTotal(array());
		list ($pages, $page) = pagination( $total, $this->Register['Config']->read('per_page', $this->module), $this->getModuleURL());
		$this->Register['pages'] = $pages;
		$this->Register['page'] = $page;
		$this->page_title .= ' (' . $page . ')';

		
		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) 
			? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs();
		$navi['pagination'] = $pages;
		$navi['meta'] = __('Count all material') . $total;
		$this->_globalize($navi);


		if($total <= 0) {
			$html = __('Materials not found');
			return $this->_view($html);
		}
		
		
		$params = array(
			'page' => $page,
			'limit' => $this->Register['Config']->read('per_page', $this->module),
			'order' => getOrderParam(__CLASS__),
		);
		$where = array();


		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);
		
		
		// create markers
		foreach ($records as $result) {
			$this->Register['current_vars'] = $result;
			$_addParams = array();
			
			
			$_addParams['moder_panel'] = $this->_getAdminBar($result);
			$entry_url = get_url(entryUrl($result, $this->module));
			$_addParams['entry_url'] = $entry_url;
			$_addParams['preview_foto'] = get_url($this->getFilesPath('preview/' . $result->getFilename()));
			$_addParams['foto_alt'] = h(preg_replace('#[^\w\d ]+#ui', ' ', $result->getTitle()));
			
			
			
			$_addParams['category_url'] = get_url($this->getModuleURL('category/' . $result->getCategory_id()));
			$_addParams['profile_url'] = getProfileUrl($result->getAuthor()->getId());


			//set users_id that are on this page
			$this->setCacheTag(array(
				'user_id_' . $result->getAuthor()->getId(),
				'record_id_' . $result->getId(),
			));
		

			$result->setAdd_markers($_addParams);
		}
		
		
		$source = $this->render('list.html', array('entities' => $records));
		
		
		//write int cache
		if ($this->cached)
			$this->Cache->write($source, $this->cacheKey, $this->cacheTags);
		
	
		return $this->_view($source);
	}


	

	 
	public function category($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));
		$id = intval($id);
		if (empty($id) || $id < 1) redirect('/');

		
		$catsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$category = $catsModel->getById($id);
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
		$childCats = $catsModel->getOneField('id', array('parent_id' => $id));
		$query_params = array('cond' => array(
			'`category_id` = ' . $id
		));
		if ($childCats) $query_params['cond'] .= ' OR `category_id` IN (' . implode(', ', $childCats) . ')';
		

		$total = $this->Model->getTotal($query_params);
		list ($pages, $page) = pagination( $total, $this->Register['Config']->read('per_page', $this->module), $this->getModuleURL());
		$this->Register['pages'] = $pages;
		$this->Register['page'] = $page;
		$this->page_title .= ' (' . $page . ')';


		
		$navi = array();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) 
			? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$navi['navigation'] = $this->_buildBreadCrumbs($id);
		$navi['pagination'] = $pages;
		$navi['meta'] = __('Count material in cat') . $total;
		$navi['category_name'] = h($category->getTitle());
		$this->_globalize($navi);


		if($total <= 0) {
			$html = __('Materials not found');
			return $this->_view($html);
		}
	  
	  
		$params = array(
			'page' => $page,
			'limit' => $this->Register['Config']->read('per_page', $this->module),
			'order' => getOrderParam(__CLASS__),
		);
		$where = $query_params['cond'];


		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);


		// create markers
		foreach ($records as $result) {
			$this->Register['current_vars'] = $result;
			$_addParams = array();
			
			
			$_addParams['moder_panel'] = $this->_getAdminBar($result);
			$entry_url = get_url(entryUrl($result, $this->module));
			$_addParams['entry_url'] = $entry_url;
			//$_addParams['entry_url'] = get_url($this->getModuleURL('view/' . $result->getId()));
			
			$_addParams['preview_foto'] = get_url($this->getFilesPath('preview/' . $result->getFilename()));
			$_addParams['foto_alt'] = h(preg_replace('#[^\w\d ]+#ui', ' ', $result->getTitle()));
			
			
			$_addParams['category_url'] = get_url($this->getModuleURL('category/' . $result->getCategory_id()));
			$_addParams['profile_url'] = getProfileUrl($result->getAuthor()->getId());


			//set users_id that are on this page
			$this->setCacheTag(array(
				'user_id_' . $result->getAuthor()->getId(),
				'record_id_' . $result->getId(),
				'category_id_' . $id,
			));
		

			$result->setAdd_markers($_addParams);
		}
		
		
		$source = $this->render('list.html', array('entities' => $records));
		
		
		//write int cache
		if ($this->cached)
			$this->Cache->write($source, $this->cacheKey, $this->cacheTags);
		
	
		return $this->_view($source);
	}
	  
	  
	  


	/**
	 *
	 */
	public function view ($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_materials'));
		$id = intval($id);
		if (empty($id) || $id < 1) redirect('/');

		

		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$entity = $this->Model->getById($id);
		
		
		if (!$entity) redirect('/error.php?ac=404');
		if (!$this->ACL->checkCategoryAccess($entity->getCategory()->getNo_access())) 
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		
		
		//category block
		$this->_getCatsTree($entity->getCategory()->getId());

		/* COMMENT BLOCK */
		if ($this->Register['Config']->read('comment_active', $this->module) == 1 
		&& $this->ACL->turn(array($this->module, 'view_comments'), false) 
		&& $entity->getCommented() == 1) {
			if ($this->ACL->turn(array($this->module, 'add_comments'), false)) 
				$this->comments_form = $this->_add_comment_form($id);
			$this->comments  = $this->_get_comments($entity);
		}
		$this->Register['current_vars'] = $entity;
		
		
		//производим замену соответствующих участков в html шаблоне нужной информацией
		$this->page_title = h($entity->getTitle()) . ' - ' . $this->page_title;

		
		$navi = array();
		$navi['module_url'] = get_url($this->getModuleURL());
		$navi['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory()->getId()));
		$navi['category_name'] = h($entity->getCategory()->getTitle());
		$navi['navigation'] = $this->_buildBreadCrumbs($entity->getCategory()->getId());
		$this->_globalize($navi);
		
		
		$next_prev = $this->Model->getNextPrev($id);
		
		
		$markers = array();
		$markers['profile_url'] = getProfileUrl($entity->getAuthor()->getId());
		
		$markers['moder_panel'] = $this->_getAdminBar($entity);
		$markers['main'] = get_url($this->getFilesPath('full/' . $entity->getFilename()));
		$markers['foto_alt'] = h(preg_replace('#[^\w\d ]+#ui', ' ', $entity->getTitle()));
		$markers['description'] = $this->Textarier->print_page($entity->getDescription(), $entity->getAuthor()->geteStatus());
		

		$prev_id = (!empty($next_prev['prev'])) ? $next_prev['prev']->getId() : $id;
		$next_id = (!empty($next_prev['next'])) ? $next_prev['next']->getId() : $id;
		
		$markers['previous_url'] = get_url($this->getModuleURL('view/' . $prev_id));
		$markers['next_url'] = get_url($this->getModuleURL('view/' . $next_id));

		$entry_url = get_url(entryUrl($entity, $this->module));
		$markers['entry_url'] = $entry_url;
		
		
		$entity->setAdd_markers($markers);
		
		
		$this->setCacheTag(array(
			'user_id_' . $entity->getAuthor()->getId(),
			'record_id_' . $entity->getId(),
			(!empty($_SESSION['user']['status'])) ? 'user_group_' . $_SESSION['user']['status'] : 'user_group_' . 'guest',
		));
		
		
		$source = $this->render('material.html', array('entity' => $entity));
		
		
		$entity->setViews($entity->getViews() + 1);
		$entity->save();
		$this->Register['DB']->cleanSqlCache();
		
		return $this->_view($source);
	}





	/**
	 * 
	 * Create form and fill his data from SESSION['FpsForm']
	 * or SESSION['previewMessage'] if an exists. 
	 * Show errors if an exists after unsuccessful attempt.
	 *
	 */
	public function add_form () {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));

		
		//формируем блок со списком  разделов
		$this->_getCatsTree();
		

		// Check for preview or errors
		$data = Validate::getCurrentInputsValues(array(
			'title' => null, 
			'in_cat' => null, 
			'description' => null, 
			'commented' => '1'
		));
		$in_cat = $data['in_cat'];
		$commented = $data['commented'];
		$title = $data['title'];
		$description = $data['description'];
		
		

		$html = '';
		$errors = $this->Parser->getErrors();
		if (isset($_SESSION['FpsForm'])) unset($_SESSION['FpsForm']);
		if (!empty($errors)) $html = $errors;

		
		//categories list
		$catsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$cats = $catsModel->getCollection();
		$cats_selector = $this->_buildSelector($cats, (!empty($in_cat) ? $in_cat : false));
		

		$markers = array();
		$markers['action'] = get_url($this->getModuleURL('add/'));
		$markers['cats_selector'] = $cats_selector;

		//comments and hide
		$markers['commented'] = !empty($commented) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $markers['commented'] .= ' disabled="disabled"';

		$markers['title'] = (!empty($title)) ? $title : '';
		$markers['main_text'] = (!empty($description)) ? $description : '';
		
		
		// Navigation Panel
		$navi['navigation'] = $this->_buildBreadCrumbs();
		$navi['add_link'] = ($this->ACL->turn(array($this->module, 'add_materials'), false)) 
			? get_link(__('Add material'), $this->getModuleURL('add_form/')) : '';
		$this->_globalize($navi);
		
		$source = $this->render('addform.html', array('data' => $markers));
		
		$html = $html . $source;
		return $this->_view($html);
	}





	// Функция добавляет новое изображение (новую запись в таблицу БД FOTO)
	public function add() {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));
		
		if (!isset($_POST['title']) 
		|| !isset($_FILES['foto']) 
		|| !isset($_POST['cats_selector'])
		|| !is_numeric($_POST['cats_selector'])) {
			redirect('/');
		}


		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title = trim(mb_substr( $_POST['title'], 0, 128 ));
		$description = trim($_POST['mainText']);
		$in_cat = intval($_POST['cats_selector']);
		$commented = (!empty($_POST['commented'])) ? '1' : '0';


		// Check fields
		$errors = '';
		$valobj = $this->Register['Validate'];
		if (empty($in_cat))
			$errors = $errors.'<li>'.__('Category not selected').'</li>'."\n";
		if (empty($title))
			$errors = $errors.'<li>'.__('Empty field "title"').'</li>'."\n";
		elseif (!$valobj->cha_val($title, V_TITLE))  
			$errors = $errors.'<li>'.__('Wrong chars in "title"').'</li>'."\n";
		$foto_fields = $this->Register['Config']->read('fields', $this->module);
		if (empty($description) && !empty($foto_fields['description'])) 
			$errors = $errors.'<li>'.__('Empty field "description"').'</li>'."\n";
		if (mb_strlen($description) > $this->Register['Config']->read('description_lenght', $this->module))
			$errors = $errors .'<li>'.sprintf(__('Wery big "description"'), $this->Register['Config']->read('description_lenght', $this->module)).'</li>'."\n";
		
		
		
		/* check file */
		if (empty($_FILES['foto']['name']))	{
			$errors = $errors .'<li>'.__('Not attaches').'</li>'. "\n";
		} else {
			if ($_FILES['foto']['size'] > $this->getMaxSize()) 
				$errors = $errors .'<li>'. sprintf(__('Wery big file2'), round($this->getMaxSize() / 1024, 2)) .'</li>'."\n";
			$ext = strrchr($_FILES['foto']['name'], ".");
			if (!isImageFile($_FILES['foto']['type'], $ext)) 
				$errors = $errors .'<li>'.__('Wrong file format').'</li>'."\n";
		}
		
		
		//categories list
		$catsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$cat = $catsModel->getById($in_cat);

		if (empty($cat)) $errors = $errors . '<li>' . __('Can not find category') . '</li>' . "\n";
		

		// errors
		if (!empty($errors)) {
			$data = array(
				'title' => $title,
				'description' => $description,
				'in_cat' => $in_cat,
				'commented' => $commented,
			);
			$data['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'.
				"\n".'<ul class="errorMsg">'."\n" . $errors . '</ul>'."\n";
			$_SESSION['FpsForm'] = $data;
			redirect($this->getModuleURL('add_form/'));
		}

		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented = '1';

		// spam protected
		if ( isset( $_SESSION['unix_last_post'] ) and ( time()-$_SESSION['unix_last_post'] < 10 ) ) {
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL());
		}
		
		

		
		
		// Формируем SQL-запрос на добавление темы	
		$res = array(
			'title'        => $title,
			'description'  => mb_substr($description, 0, $this->Register['Config']->read('description_lenght', $this->module)),
			'date'         => new Expr('NOW()'),
			'author_id'    => $_SESSION['user']['id'],
			'category_id'  => $in_cat,
			'filename'     => '',
			'commented'    => $commented,
		);
		
		$entity = new FotoEntity($res);
		$id = $entity->save();
		$entity->setId($id);
 
 
		/* save full and resample images */
		$ext = strtolower(strchr($_FILES['foto']['name'], '.'));
		$save_path = ROOT . $this->getFilesPath('full/' . $id . $ext);
		$save_sempl_path = ROOT . $this->getFilesPath('preview/' . $id . $ext);
		
		if (!move_uploaded_file($_FILES['foto']['tmp_name'], $save_path)) $error_flag = true;
		elseif (!chmod($save_path, 0644)) $error_flag = true; 
		
		/* if an error when coping */
		if (!empty($error_flag) && $error_flag) {
			$entity->delete();
			$data = array(
				'title' => $title,
				'description' => $description,
				'in_cat' => $in_cat,
				'commented' => $commented,
			);
			$data['error'] = '<p class="errorMsg">Произошла ошибка:</p>'
				. "\n" . '<ul class="errorMsg">'."\n".'Неизвесная ошибка. Попробуйте начать заново.</ul>'."\n";
			$_SESSION['FpsForm'] = $data;
			redirect($this->getModuleURL('add_form/'));
		} else {
			$entity->setFilename($id . $ext);
			$entity->save();
		}
		
		
		// Create watermark and resample image
		$watermark_path = ROOT . '/sys/img/' . ($this->Register['Config']->read('watermark_type') == '1' ? 'watermark_text.png' : $this->Register['Config']->read('watermark_img'));
		if ($this->Register['Config']->read('use_watermarks') && !empty($watermark_path) && file_exists($watermark_path)) {
			$waterObj = new FpsImg;
			$waterObj->createWaterMark($save_path, $watermark_path);
		}

		
		$resample = resampleImage($save_path, $save_sempl_path, 150, 150);
		if ($resample) chmod($save_sempl_path, 0644);
		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_foto'));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('adding foto', 'foto id(' . $id . ')');
		return $this->showInfoMessage(__('Material successful added'), $this->getModuleURL() );		  
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
		if ($id < 1) redirect('/');

		
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$entity = $this->Model->getById($id);
		
		if (!$entity) return redirect($this->getModuleURL());
		
		
		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false) 
		&& (empty($_SESSION['user']['id']) || $entity->getAuthor_id() != $_SESSION['user']['id'] 
		|| !$this->ACL->turn(array($this->module, 'edit_mine_materials'), false))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL() );
		}
		
		
		$this->Register['current_vars'] = $entity;
		$html = '';
		
		//формируем блок со списком  разделов
		$this->_getCatsTree($entity->getCategory_id());
		
		
		//navigation panel
		$navi = array();
		$navi['navigation']  = $this->_buildBreadCrumbs($entity->getCategory_id());
		$this->_globalize($navi);
		
		
		$in_cat = $entity->getCategory_id();
		// Check for preview or errors
		$data = Validate::getCurrentInputsValues($entity, array(
			'title' => null,
			'description' => null,
			'in_cat' => $in_cat,
			'commented' => null,
		));
		$commented = $data->getCommented();
		$commented = !empty($commented) ? ' checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented .= ' disabled="disabled"';
		$data->setCommented($commented);
	
		$errors = $this->Parser->getErrors();
		if (isset($_SESSION['FpsForm'])) unset($_SESSION['FpsForm']);
		if (!empty($errors)) $html = $errors . $html;
	
	
		//categories list
		$catsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$cats = $catsModel->getCollection();
		$cats_selector = $this->_buildSelector($cats, (!empty($in_cat)) ? $in_cat : $entity->getCategory_id());
		
		
		$data->setAction(get_url($this->getModuleURL('update/' . $id)));
		$data->setCats_selector($cats_selector);
		$data->setMain_text($this->Textarier->print_page($data->getDescription(), $data->getAuthor()->geteStatus()));
		
		
		$source = $this->render('editform.html', array('data' => $data));
		
		return $this->_view($html . $source);
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
		$id = (int)$id;
		if (empty($id) 
		|| !isset($_POST['title']) 
		|| !isset($_POST['cats_selector']) 
		|| !is_numeric($_POST['cats_selector'])) {
			redirect('/');
		}


		$entity = $this->Model->getById($id);
		if (!$entity) return $this->_view(__('Some error occurred'));


		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false) 
		&& (empty($_SESSION['user']['id']) || $entity->getAuthor_id() !== $_SESSION['user']['id'] 
		|| !$this->ACL->turn(array($this->module, 'edit_mine_materials'), false))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL() );
		}
		
		
		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title = trim(mb_substr($_POST['title'], 0, 128));
		$description = trim($_POST['mainText']);
		$in_cat = intval($_POST['cats_selector']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		if (empty($in_cat)) $in_cat = $entity['category_id'];
		
		
		// Check fields
		$Validate = $this->Register['Validate'];
		$errors = '';
		if (empty($title))
			$errors = $errors.'<li>'.__('Empty field "title"').'</li>'."\n";
		if (!$Validate->cha_val($title, V_TITLE))  
			$errors = $errors.'<li>'.__('Wrong chars in "title"').'</li>'."\n";
		$foto_fields = $this->Register['Config']->read('fields', $this->module);
		if (empty($description) && !empty($foto_fields['description'])) 
			$errors = $errors.'<li>'.__('Empty field "description"').'</li>'."\n";
		if (mb_strlen($description) > $this->Register['Config']->read('description_lenght', $this->module))
			$errors = $errors.'<li>'.sprintf(__('Wery big "description"'), $this->Register['Config']->read('description_lenght', $this->module)).'</li>'."\n";
			
			
		$catsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$cat = $catsModel->getById($in_cat);

		if (empty($cat)) $errors = $errors . '<li>' . __('Can not find category') . '</li>' . "\n";

		
		// errors
		if (!empty( $errors )) {
			$data = array(
				'title' => $title,
				'description' => $description,
				'in_cat' => $in_cat,
				'commented' => $commented
			);
			$data['error'] = '<p class="errorMsg">' . __('Some error in form') 
			. '</p>'."\n".'<ul class="errorMsg">'."\n".$errors.'</ul>'."\n";
			$_SESSION['FpsForm'] = $data;
			redirect($this->getModuleURL('edit_form/' . $id ));
		}
		
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented = '1';

		$entity->setTitle($title);
		$entity->setDescription(mb_substr($description, 0, $this->Register['Config']->read('description_lenght', $this->module)));
		$entity->setCategory_id($in_cat);
		$entity->setCommented($commented);
		$entity->save();

		
		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_foto', 'record_id_' . $id));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('editing foto', 'foto id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL() );
	}





	/**
	 * Check user access and if all right
	 * delete record with geting ID.
	 *
	 * @param int $id
	 */
	public function delete($id = null) {		
		$id = intval($id);
		if ($id < 1) redirect('/');
		
		
		$entity = $this->Model->getById($id);
		if (!$entity) return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL() );


		if (!$this->ACL->turn(array($this->module, 'delete_materials'), false) 
		&& (empty($_SESSION['user']['id']) || $entity->getAuthor_id() != $_SESSION['user']['id'] 
		|| !$this->ACL->turn(array($this->module, 'delete_mine_materials'), false))) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL() );
		}
		
		
		$entity->delete();

		//clean cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_foto'));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('delete foto', 'foto id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL() );
	}


	/**
	* @param int $id - record ID
	*
	* update date by record also up record in recods list
	*/
	public function upper($id) {
		$this->ACL->turn(array($this->module, 'up_materials'));
		$entity = $this->Model->getById($id);
		$entity->setDate(date("Y-m-d H:i:s"));
		$entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}
	


	/**
	* add comment to stat
	*
	* @id (int) stat ID
	* @return info message
	*/
	public function add_comment($id = null)
	{
		include_once(ROOT . '/sys/inc/includes/add_comment.php');
	}


	/**
	* add comment form to stat
	*
	* @id (int) stat ID
	* @return html form
	*/
	private function _add_comment_form($id = null)
	{
		include_once(ROOT . '/sys/inc/includes/_add_comment_form.php');
		return $html;
	}



	/**
	* edit comment form to stat
	*
	* @id (int) comment ID
	* @return html form
	*/
	public function edit_comment_form($id = null)
	{
		include_once(ROOT . '/sys/inc/includes/edit_comment_form.php');
	}



	/**
	* update comment
	*
	* @id (int) comment ID
	* @return info message
	*/
	public function update_comment($id = null)
	{
		include_once(ROOT . '/sys/inc/includes/update_comment.php');
	}



	/**
	* get comments for stat
	*
	* @id (int) stat ID
	* @return html comments list
	*/
	private function _get_comments($entity = null)
	{
		include_once(ROOT . '/sys/inc/includes/_get_comments.php');
		return $html;
	}



	/**
	* delete comment
	*
	* @id (int) comment ID
	* @return info message
	*/
	public function delete_comment($id = null)
	{
		include_once(ROOT . 'sys/inc/includes/delete_comment.php');
	}
	
	
	
	/**
	* @param array $record - record from database
	* @return string - admin buttons
	*
	* create and return admin bar
	*/
	protected function _getAdminBar($record) {
		$moder_panel = '';
		$id = $record->getId();
		$author_id = $record->getAuthor_id();
		
		if ($this->ACL->turn(array($this->module, 'edit_materials'), false) 
		|| (!empty($_SESSION['user']['id']) && $author_id == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false))) {
			$moder_panel .= get_link(get_img('/sys/img/edit_16x16.png'), $this->getModuleURL('edit_form/' . $id)) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'up_materials'), false)) {
			$moder_panel .= get_link(get_img('/sys/img/up_arrow_16x16.png'), $this->getModuleURL('upper/' . $id), 
			array('onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'delete_materials'), false) 
		|| (!empty($_SESSION['user']['id']) && $author_id == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false))) {
			$moder_panel .= get_link(get_img('/sys/img/delete_16x16.png'), $this->getModuleURL('delete/' . $id), 
			array('onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		return $moder_panel;
	}	
	
}
