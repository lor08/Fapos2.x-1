<?php
/*---------------------------------------------\
|											   |
| @Author:       Alexandr Danilow (modos189)   |
| @Email:        modos189@ya.ru                |
| @Package       CMS Fapos                     |
| @Subpackege    Blog Module                   |
|											   |
\---------------------------------------------*/




Class BlogModule extends Module {

	/**
	* @module_title  title of module
	*/
	var $module_title = 'Блог';
	/**
	* @template  layout for module
	*/
	var $template = 'blog';
	/**
	* @module module indentifier
	*/
	var $module = 'blog';


	
	/**
	* default action ( show main page )
	*/
	function index($tag = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));
		
		
		//формируем блок со списком  разделов
		$this->_getCatsTree();
		
		
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}
	
		// we need to know whether to show hidden
		$query_params = array('cond' => array());
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) {
			$query_params['cond']['available'] = 1;
		}
		if (!empty($tag)) $query_params['cond'][] = "`tags` LIKE '%{$tag}%'";
		

		$total = $this->Model->getTotal($query_params);
		list ($pages, $page) = pagination( $total, Config::read('per_page', $this->module), $this->getModuleURL());
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
			'limit' => Config::read('per_page', $this->module),
			'order' => getOrderParam(__CLASS__),
		);
		$where = array();
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) $where['available'] = '1';
		if (!empty($tag)) $where[] = "`tags` LIKE '%{$tag}%'";


		$this->Model->bindModel('attaches');
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);



		if (is_object($this->AddFields) && count($records) > 0) {
			$records = $this->AddFields->mergeRecords($records);
		}


		// create markers
		$addParams = array();
		foreach ($records as $result) {
			$this->Register['current_vars'] = $result;
			$_addParams = array();
			
			
			$_addParams['moder_panel'] = $this->_getAdminBar($result);
			$entry_url = get_url(entryUrl($result, $this->module));
			$_addParams['entry_url'] = $entry_url;
			
			
			$announce = $result->getMain();
			
			
			$announce = $this->Textarier->getAnnounce($announce
				, $entry_url
				, 0 
				, Config::read('announce_lenght', $this->module)
				, $result
			);
			
			
			// replace image tags in text
			$attaches = $result->getAttaches();
			if (!empty($attaches) && count($attaches) > 0) {
				foreach ($attaches as $attach) {
					if ($attach->getIs_image() == '1') {
						$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
					}
				}
			}

			$_addParams['announce'] = $announce;
			
			
			$_addParams['category_url'] = get_url($this->getModuleURL('category/' . $result->getCategory_id()));
			$_addParams['profile_url'] = getProfileUrl($result->getAuthor()->getId());
			$result->setTags(explode(',', $result->getTags()));


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


	

	 
	function category($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_list'));
		$id = intval($id);
		if (empty($id) || $id < 1) $this->showInfoMessage(__('Unknown error'), '/', 2);

		
		$catsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$category = $catsModel->getById($id);
		if (!$category)
			return $this->showInfoMessageFull(__('Can not find category'), $this->getModuleURL());
		if (!$this->ACL->checkCategoryAccess($category->getNo_access())) 
			return $this->showInfoMessageFull(__('Permission denied'), $this->getModuleURL());
		
		
		$this->page_title = h($category->getTitle()) . ' - ' . $this->page_title;
		
		
		//формируем блок со списком  разделов
		$this->_getCatsTree($id);
		
		
		if ($this->cached && $this->Cache->check($this->cacheKey)) {
			$source = $this->Cache->read($this->cacheKey);
			return $this->_view($source);
		}
	
		// we need to know whether to show hidden
		$childCats = $catsModel->getOneField('id', array('parent_id' => $id));
		$childCats[] = $id;
		$childCats = implode(', ', $childCats);
		$query_params = array('cond' => array(
			'`category_id` IN (' . $childCats . ')'
		));
		
		
		
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) {
			$query_params['cond']['available'] = 1;
		}
		

		$total = $this->Model->getTotal($query_params);
		list ($pages, $page) = pagination( $total, Config::read('per_page', $this->module), $this->getModuleURL());
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
			'limit' => Config::read('per_page', $this->module),
			'order' => getOrderParam(__CLASS__),
		);
		$where = $query_params['cond'];
		if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) $where['available'] = '1';


		$this->Model->bindModel('attaches');
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$records = $this->Model->getCollection($where, $params);


		if (is_object($this->AddFields) && count($records) > 0) {
			$records = $this->AddFields->mergeRecords($records);
		}


		// create markers
		$addParams = array();
		foreach ($records as $result) {
			$this->Register['current_vars'] = $result;
			$_addParams = array();
			
			
			$_addParams['moder_panel'] = $this->_getAdminBar($result);
			$entry_url = get_url(entryUrl($result, $this->module));
			$_addParams['entry_url'] = $entry_url;
			
			
			$announce = $result->getMain();
			
			
			$announce = $this->Textarier->getAnnounce($announce
				, $entry_url
				, 0 
				, Config::read('announce_lenght', $this->module)
				, $result
			);
			
			
			// replace image tags in text
			$attaches = $result->getAttaches();
			if (!empty($attaches) && count($attaches) > 0) {
				foreach ($attaches as $attach) {
					if ($attach->getIs_image() == '1') {
						$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
					}
				}
			}

			$_addParams['announce'] = $announce;
			
			
			$_addParams['category_url'] = get_url($this->getModuleURL('category/' . $result->getCategory_id()));
			$_addParams['profile_url'] = getProfileUrl($result->getAuthor()->getId());
			$result->setTags(explode(',', $result->getTags()));


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
	  
	  


	function view ($id = null) {
		//turn access
		$this->ACL->turn(array($this->module, 'view_materials'));
		$id = intval($id);
		if (empty($id) || $id < 1) $this->showInfoMessage(__('Unknown error'), '/', 2);

		
		$this->Model->bindModel('attaches');
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$entity = $this->Model->getById($id);
		
		
		if (empty($entity)) $this->showInfoMessage(__('Material not found'), '/', 2);
		if ($entity->getAvailable() == 0 && !$this->ACL->turn(array('other', 'can_see_hidden'), false)) 
			return $this->showInfoMessageFull(__('Permission denied'), $this->getModuleURL());
		if (!$this->ACL->checkCategoryAccess($entity->getCategory()->getNo_access())) 
			return $this->showInfoMessageFull(__('Permission denied'), $this->getModuleURL());
			
		
		// Some gemor with add fields
		if (is_object($this->AddFields)) {
			$entities = $this->AddFields->mergeRecords(array($entity));
			$entity = $entities[0];
		}
		
		
		$max_attaches = Config::read('max_attaches', $this->module);
		if (empty($max_attaches) || !is_numeric($max_attaches)) $max_attaches = 5;
		
		
		//category block
		$this->_getCatsTree($entity->getCategory()->getId());
		/* COMMENT BLOCK */
		if (Config::read('comment_active', $this->module) == 1 
		&& $this->ACL->turn(array($this->module, 'view_comments'), false) 
		&& $entity->getCommented() == 1) {
			if ($this->ACL->turn(array($this->module, 'add_comments'), false)) 
				$this->comments_form = $this->_add_comment_form($id);
			$this->comments = $this->_get_comments($entity);
		}
		$this->Register['current_vars'] = $entity;
		
		
		//производим замену соответствующих участков в html шаблоне нужной информацией
		$this->page_title = h($entity->getTitle()) . ' - ' . $this->page_title;
		$tags = $entity->getTags();
		$description = $entity->getDescription();
		if (!empty($tags)) $this->page_meta_keywords = h($tags);
		if (!empty($description)) $this->page_meta_description = h($description);
		
		$navi = array();
		$navi['module_url'] = get_url($this->getModuleURL());
		$navi['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory()->getId()));
		$navi['category_name'] = h($entity->getCategory()->getTitle());
		$navi['navigation'] = $this->_buildBreadCrumbs($entity->getCategory()->getId());
		$this->_globalize($navi);
		
		
		$markers = array();
		$markers['moder_panel'] = $this->_getAdminBar($entity);
		$markers['profile_url'] = getProfileUrl($entity->getAuthor()->getId());
		
		
		$entry_url = get_url(entryUrl($entity, $this->module));
		$markers['entry_url'] = $entry_url;
		
		
		$announce = $entity->getMain();
		$announce = $this->Textarier->print_page($announce, $entity->getAuthor()->getStatus(), $entity->getTitle());
		
		// replace image tags in text
		$attaches = $entity->getAttaches();
		if (!empty($attaches) && count($attaches) > 0) {
			foreach ($attaches as $attach) {
				if ($attach->getIs_image() == '1') {
					$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
				}
			}
		}

		$markers['mainText'] = $announce;
		$entity->setAdd_markers($markers);
		$entity->setTags(atrim(explode(',', $entity->getTags())));
		
		
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
	 */
	public function add_form () {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;
		
		
		// categories block
		$this->_getCatsTree();
		
		
		// Additional fields
		$markers = array();
		if (is_object($this->AddFields)) {
			$_addFields = $this->AddFields->getInputs(array(), true, $this->module);
			foreach($_addFields as $k => $field) {
				$markers[strtolower($k)] = $field;
			}
		}
		
		
        // Check for preview or errors
        $data = array('title' => null, 'mainText' => null, 'in_cat' => null, 'description' => null, 'tags' => null, 'commented' => null, 'available' => null);
		$data = array_merge($data, $markers);
        $data = Validate::getCurrentInputsValues($data);
        
		
		
        $data['errors'] = $this->Parser->getErrors();
        if (isset($_SESSION['FpsForm'])) unset($_SESSION['FpsForm']);
		
		
		$catsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
		$sql = $catsModel->getCollection();
		$data['cats_selector'] = $this->_buildSelector($sql, ((!empty($data['in_cat'])) ? $data['in_cat'] : false));
		
		
		//comments and hide
		$data['commented'] = (!empty($commented) || !isset($_POST['submitForm'])) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $data['commented'] .= ' disabled="disabled"';
		$data['available'] = (!empty($available) || !isset($_POST['submitForm'])) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false)) $data['available'] .= ' disabled="disabled"';
		
		
		$data['action'] = get_url($this->getModuleURL('add/'));
		$data['max_attaches'] = Config::read('max_attaches', $this->module);
		if (empty($data['max_attaches']) || !is_numeric($data['max_attaches'])) $data['max_attaches'] = 5;
			
			
		//navigation panel
		$navi = array();
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
		if (!isset($_POST['title']) 
		|| !isset($_POST['mainText']) 
		|| !isset($_POST['cats_selector'])) {
			$this->showInfoMessage(__('Unknown error'), '/', 2);
		}
		if (!is_numeric($_POST['cats_selector'])) $this->showInfoMessage(__('Unknown error'), '/', 2);
		$error  = '';
		
		
		// Check additional fields if an exists.
		// This must be doing after define $error variable.
		if (is_object($this->AddFields)) {
			$_addFields = $this->AddFields->checkFields();
			if (is_string($_addFields)) $error .= $_addFields; 
		}
		
		
		$fields = array('description', 'tags');
		$fields_settings = Config::read('fields', $this->module);
		foreach ($fields as $field) {
			if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
				$error = $error.'<li>' . __('Empty field') . ' "' . $field . '"</li>'."\n";
				$$field = null;
			} else {
				$$field = trim($_POST[$field]);
			}
		}
		
		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title  = trim(mb_substr($_POST['title'], 0, 128));
		$add 	= trim($_POST['mainText']);
		$in_cat = intval($_POST['cats_selector']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		$available = (!empty($_POST['available'])) ? 1 : 0;

		// Check fields
		$valobj = new Validate;
		if (empty($in_cat))                     	
			$error = $error . '<li>' . __('Category not selected') . '</li>'."\n";
		if (empty($title))                       	
			$error = $error.'<li>' . __('Empty field "title"') . '</li>'."\n";
		elseif (!$valobj->cha_val($title, V_TITLE))  
			$error = $error.'<li>' . __('Wrong chars in "title"') . '</li>'."\n";
		if (empty($add))                    		 
			$error = $error.'<li>' . __('Empty field "material"') . '</li>'."\n";
		else if (mb_strlen($add) > Config::read('max_lenght', $this->module))
			$error = $error .'<li>'. sprintf(__('Wery big "material"'), Config::read('max_lenght', $this->module)) .'</li>'."\n";
		if (!empty($tags) && !$valobj->cha_val($tags, V_TITLE)) 
			$error = $error.'<li>' . __('Wrong chars in "tags"') . '</li>'."\n";

			
		// Check attaches size and format
		$max_attach = Config::read('max_attaches', $this->module);
		if (empty($max_attach) || !is_numeric($max_attach)) $max_attach = 5;
		$max_attach_size = $this->getMaxSize('max_attaches_size');
		if (empty($max_attach_size) || !is_numeric($max_attach_size)) $max_attach_size = 1048576;
		for ($i = 1; $i <= $max_attach; $i++) {
			$attach_name = 'attach' . $i;
			if (!empty($_FILES[$attach_name]['name'])) {
			
				$ext = strrchr($_FILES[$attach_name]['name'], ".");
				
				if ($_FILES[$attach_name]['size'] > $max_attach_size) {
					$error .= '<li>' . sprintf(__('Wery big file'), $i, round(($max_attach_size / 1024), 2)) . '</li>'."\n";
				}
				if (!isImageFile($_FILES[$attach_name]['type'], $ext)) {
					$error .= '<li>' . __('Wrong file format') . '</li>'."\n";
				}
			}
		}
			
		
		$categoryModel = ucfirst($this->module) . 'SectionsModel';
		$categoryModel = new $categoryModel;
		$cat = $categoryModel->getCollection(array('id' => $in_cat));
		if (empty($cat)) $error .= '<li>' . __('Can not find category') . '</li>'."\n";
			
			
		// Errors
		if (!empty($error)) {
			$_SESSION['FpsForm'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat,
				'description' => null, 'tags' => null, 'commented' => null, 'available' => null), $_POST);
			$_SESSION['FpsForm']['error']   = '<p class="errorMsg">' . __('Some error in form') . '</p>'.
				"\n".'<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$this->showInfoMessage($_SESSION['FpsForm']['error'], $this->getModuleURL('add_form/' . $id));
		}

		
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented = '1';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false)) $available = '1';

		// Защита от того, чтобы один пользователь не добавил
		// 100 материалов за одну минуту
		if ( isset( $_SESSION['unix_last_post'] ) and ( time()-$_SESSION['unix_last_post'] < 10 ) ) {
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL());
		}
		

		
		// Auto tags generation
		if (empty($tags)) {
			$TagGen = new MetaTags;
			$tags = $TagGen->getTags($add);
			$tags = (!empty($tags) && is_array($tags)) ? implode(',', array_keys($tags)) : '';
		}		
	
		
		//remove cache
		$this->Register['Cache']->clean(CACHE_MATCHING_ANY_TAG, array('module_' . $this->module));
		$this->Register['DB']->cleanSqlCache();
		// Формируем SQL-запрос на добавление темы	
		$add = mb_substr($add, 0, Config::read('max_lenght', $this->module));
		$res = array(
			'title'        => $title,
			'main'         => $add,
			'date'         => new Expr('NOW()'),
			'author_id'    => $_SESSION['user']['id'],
			'category_id'  => $in_cat,
			'description'  => $description,
			'tags'         => $tags,
			'commented'    => $commented,
			'available'    => $available,
			'view_on_home' => '1',
		);
		$className = ucfirst($this->module) . 'Entity';
		$new = new $className($res);
		
		$new->save();
		
		// Get last insert ID and save additional fields if an exists and activated.
		// This must be doing only after save main(parent) material
		$last_id = mysql_insert_id();

		if (is_object($this->AddFields)) {
			$this->AddFields->save($last_id, $_addFields);
		}
		
		downloadAttaches($this->module, $last_id);
		
		
		//clean cache
		$this->Register['Cache']->clean(CACHE_MATCHING_TAG, array('module_' . $this->module));
		$this->Register['DB']->cleanSqlCache();
		if ($this->Log) $this->Log->write('adding blog', 'ent. id(' . $last_id . ')');
		return $this->showInfoMessage(__('Material successful added'), $this->getModuleURL('view/' . $last_id));				  
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
	public function edit_form($id = null)
    {
		$id = (int)$id;
		if ( $id < 1 ) $this->showInfoMessage(__('Unknown error'), '/', 2);
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;

		
		$this->Model->bindModel('attaches');
		$this->Model->bindModel('author');
		$this->Model->bindModel('category');
		$entity = $this->Model->getById($id);
		
		if (count($entity) == 0) $this->showInfoMessage(__('Unknown error'), '/', 2);
		
		
		if (is_object($this->AddFields) && count($entity) > 0) {
			$entities = $this->AddFields->mergeRecords(array($entity), true);
			$entity = $entities[0];
		}
		
		
		//turn access
		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false) 
		&& (!empty($_SESSION['user']['id']) && $entity->getAuthor()->getId() == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false)) === false) {
			return $this->showInfoMessageFull(__('Permission denied'), $this->getModuleURL());
		}
		
		
		$attaches = $entity->getAttaches();
		
		$this->Register['current_vars'] = $entity;
		
		//forming categories list
		$this->_getCatsTree($entity->getCategory()->getId());
		

        $data = array(
			'title' 		=> '', 
			'mainText' 		=> $entity->getMain(), 
			'in_cat' 		=> $entity->getCategory_id(), 
			'description' 	=> '', 
			'tags' 			=> '', 
			'commented' 	=> '', 
			'available' 	=> '',
		);
		$data = Validate::getCurrentInputsValues($entity, $data);

		
        $errors = $this->Parser->getErrors();
        if (isset($_SESSION['FpsForm'])) unset($_SESSION['FpsForm']);
		
		$html = '';
        if (!empty($errors)) $html .= $errors;

		
		$className = $this->Register['ModManager']->getModelNameFromModule($this->module . 'Sections');
		$sectionModel = new $className;
		$cats = $sectionModel->getCollection();
		$selectedCatId = ($data->getIn_cat()) ? $data->getIn_cat() : $data->getCategory_id();
		$cats_change = $this->_buildSelector($cats, $selectedCatId);
		
		
		//comments and hide
		$commented = ($data->getCommented()) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented .= ' disabled="disabled"';
		$available = ($data->getAvailable()) ? 'checked="checked"' : '';
		$action = get_url($this->getModuleURL('update/' . $data->getId()));
		
		
		$data->setCommented($commented);
		$data->setAvailable($available);
		
		
		$attaches = $data->getAttaches();
		$attDelButtons = '';
        if (count($attaches)) {
            foreach ($attaches as $key => $attach) {
                $attDelButtons .= '<input type="checkbox" name="' . $attach->getAttach_number()
                . 'dattach"> ' . $attach->getAttach_number() . '. (' . $attach->getFilename() . ')' . "<br />\n";
            }
        }
		
		
		$markers = $data;
		$markers->setMain_text($data->getMaintext());
		$markers->setAction($action);
		$markers->setCats_selector($cats_change);
		$markers->setAttaches_delete($attDelButtons);
		$markers->setMax_attaches(Config::read('max_attaches', $this->module));


		//navigation panel
		$navi = array();
		$navi['navigation']  = $this->_buildBreadCrumbs($entity->getCategory_id());
		$this->_globalize($navi);


		setReferer();
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
		|| !isset($_POST['mainText']) 
		|| !isset($_POST['cats_selector'])) {
			$this->showInfoMessage(__('Unknown error'), '/', 2);
		}
		$id = (int)$id;
		if ($id < 1) $this->showInfoMessage(__('Unknown error'), '/', 2);
		$error = '';
		

		$target = $this->Model->getbyId($id);
		if (!$target) $this->showInfoMessage(__('Unknown error'), '/', 2);
		
		
		//turn access
		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false) 
		&& (!empty($_SESSION['user']['id']) && $target->getAuthor_id() == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}
		
		
		// Check additional fields if an exists.
		// This must be doing after define $error variable.
		if (is_object($this->AddFields)) {
			$_addFields = $this->AddFields->checkFields();
			if (is_string($_addFields)) $error .= $_addFields; 
		}
		
		
		$valobj = $this->Register['Validate'];
		$fields = array('description', 'tags');
		$fields_settings = Config::read('fields', $this->module);
		foreach ($fields as $field) {
			if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
				$error = $error.'<li>' . __('Empty field') . '"' . $field . '"</li>'."\n";
				$$field = '';
			} else {
				$$field = trim($_POST[$field]);
			}
		}
		
		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title  = trim(mb_substr($_POST['title'], 0, 128));
		$edit   = trim($_POST['mainText']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		$available = (!empty($_POST['available'])) ? 1 : 0;
        $in_cat = intval($_POST['cats_selector']);

		
		
		// Check fields
		if (empty($title))                   	
			$error = $error.'<li>' . __('Empty field "title"') . '</li>'."\n";
		if (!$valobj->cha_val($title, V_TITLE))  	
			$error = $error.'<li>' . __('Wrong chars in "title"') . '</li>'."\n";
		if (empty($edit))                 		
			$error = $error.'<li>' . __('Empty field "material"') . '</li>'."\n";
		else if (mb_strlen($edit) > Config::read('max_lenght', $this->module))
			$error = $error . '<li>' . sprintf(__('Wery big "material"'), Config::read('max_lenght', $this->module)) .'</li>'."\n";
		if (!empty($tags) && !$valobj->cha_val($tags, V_TITLE)) 
			$error = $error.'<li>' . __('Wrong chars in "tags"') . '</li>'."\n";
		
		
		
		$className = $this->Register['ModManager']->getModelNameFromModule($this->module . 'Sections');
		$catModel = new $className;
		$category = $catModel->getById($in_cat);
		if (!$category) $error = $error.'<li>' . __('Can not find category') . '</li>'."\n";
		

        // Check attaches size and format
		$max_attach = Config::read('max_attaches', $this->module);
        if (empty($max_attach) || !is_numeric($max_attach)) $max_attach = 5;
		$max_attach_size = $this->getMaxSize('max_attaches_size');
		if (empty($max_attach_size) || !is_numeric($max_attach_size)) $max_attach_size = 1048576;
        for ($i = 1; $i <= $max_attach; $i++) {
            // Delete attaches. If need
            $dattach = $i . 'dattach';
			$attach_name = 'attach' . $i;
			if (array_key_exists($dattach, $_POST) || !empty($_FILES[$attach_name]['name'])) {
                deleteAttach($this->module, $id, $i);
            }

            if (!empty($_FILES[$attach_name]['name'])) {

                $ext = strrchr($_FILES[$attach_name]['name'], ".");

                if ($_FILES[$attach_name]['size'] > $max_attach_size) {
					$error .= '<li>' . sprintf(__('Wery big file'), $i, round(($max_attach_size / 1024), 2)) . '</li>'."\n";
                }
				if (!isImageFile($_FILES[$attach_name]['type'], $ext)) {
                    $error .= '<li>' . __('Wrong file format') . '</li>'."\n";
                }
            }
        }
        downloadAttaches($this->module, $id);
		

		// Errors
		if (!empty( $error )) {
			$_SESSION['FpsForm'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat, 
				'description' => null, 'tags' => null, 'commented' => null, 'available' => null), $_POST);
			$_SESSION['FpsForm']['error']   = '<p class="errorMsg">' . __('Some error in form') . '</p>'
				."\n".'<ul class="errorMsg">'."\n".$error.'</ul>'."\n";
			$this->showInfoMessage($_SESSION['FpsForm']['error'], $this->getModuleURL('edit_form/' . $id));
		}
		

		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented = '1';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false)) $available = '1';
		
		
		
		// Auto tags generation
		if (empty($tags)) {
			$TagGen = new MetaTags;
			$tags = $TagGen->getTags($edit);
			$tags = (!empty($tags) && is_array($tags)) ? implode(',', array_keys($tags)) : '';
		}
		
		
		//remove cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_stat', 'record_id_' . $id));
		$this->Register['DB']->cleanSqlCache();
		
		$edit = mb_substr($edit, 0, Config::read('max_lenght', $this->module));
		$data = array(
			'title' 	   => $title,
			'main' 		   => $edit,
			'category_id'  => $in_cat,
			'description'  => $description,
			'tags'         => $tags,
			'commented'    => $commented,
			'available'    => $available,
		);
		$target->__construct($data);
		$target->save();
		if (is_object($this->AddFields)) {
			$this->AddFields->save($id, $_addFields);
		}
		
		
		if ($this->Log) $this->Log->write('editing blog', 'ent. id(' . $id . ')');
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
		$id = (int)$id;
		if ($id < 1) $this->showInfoMessage(__('Unknown error'), '/', 2);


		$target = $this->Model->getById($id);
		if (!$target) $this->showInfoMessage(__('Unknown error'), '/', 2);
		
		
		//turn access
		if (!$this->ACL->turn(array($this->module, 'delete_materials'), false) 
		&& (!empty($_SESSION['user']['id']) && $target->getAuthor_id() == $_SESSION['user']['id'] 
		&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
		}
		
		
		//remove cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('module_stat', 'record_id_' . $id));
		$this->Register['DB']->cleanSqlCache();

		$target->delete();
		
		$user_id = (!empty($_SESSION['user']['id'])) ? intval($_SESSION['user']['id']) : 0;
		if ($this->Log) $this->Log->write('delete stat', 'ent. id(' . $id . ') user id('.$user_id.')');
		return $this->showInfoMessage(__('Operation is successful'), getReferer());
	}


	
	/**
	* add comment to stat
	*
	* @id (int)    stat ID
	* @return      info message
	*/
	public function add_comment($id = null) {
		include_once(ROOT . '/sys/inc/includes/add_comment.php');
	}
	
	
	/**
	* add comment form to stat
	*
	* @id (int)    stat ID
	* @return      html form
	*/
	private function _add_comment_form($id = null) {
		include_once(ROOT . '/sys/inc/includes/_add_comment_form.php');
		return $html;
	}
	
	
	
	/**
	* edit comment form to stat
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
	* get comments for stat
	*
	* @id (int)    stat ID
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
		$id = (int)$id;
		if ($id < 1) $this->showInfoMessage(__('Unknown error'), '/', 2);

		
		$entity = $this->Model->getById($id);
		if (!$entity) $this->showInfoMessage(__('Unknown error'), '/', 2);
		
		$entity->setDate(date("Y-m-d H-i-s"));
		$entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}

	
	
	/**
	* @param int $id - record ID
	*
	* allow record be on home page
	*/
	public function on_home($id) {
		//turn access
		$this->ACL->turn(array($this->module, 'on_home'));
		$id = (int)$id;
		if ($id < 1) $this->showInfoMessage(__('Unknown error'), '/', 2);

		
		$entity = $this->Model->getById($id);
		if (!$entity) $this->showInfoMessage(__('Unknown error'), '/', 2);
		
		$entity->setView_on_home('1');
		$entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}


	
	/**
	* @param int $id - record ID
	*
	* denied record be on home page
	*/
	public function off_home($id) {
		//turn access
		$this->ACL->turn(array($this->module, 'on_home'));
		$id = (int)$id;
		if ($id < 1) $this->showInfoMessage(__('Unknown error'), '/', 2);

		
		$entity = $this->Model->getById($id);
		if (!$entity) $this->showInfoMessage(__('Unknown error'), '/', 2);
		
		$entity->setView_on_home('0');
		$entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
	}
	
	
	
	/**
	* @param int $id - record ID
	*
	* fix or unfix record on top on home page
	*/
	public function fix_on_top($id) {
		$this->ACL->turn(array($this->module, 'on_home'));
		$id = (int)$id;
		if ($id < 1) $this->showInfoMessage(__('Unknown error'), '/', 2);

		$target = $this->Model->getById($id);
		if (!$target) $this->showInfoMessage(__('Unknown error'), '/', 2);
		
		$curr_state = $target->getOn_home_top();
		$dest = ($curr_state) ? '0' : '1';
		$target->setOn_home_top($dest);
		$target->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
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
			$moder_panel .= get_link('', $this->getModuleURL('fix_on_top/' . $id), array('id' => 'ffm'.$record->getId(), 'class' => 'fps-star', 'onClick' => "if (confirm('" . __('Are you sure') . "')) {sendu('ffm".$record->getId()."')}; return false")) . '&nbsp;';
			$moder_panel .= get_link('', $this->getModuleURL('upper/' . $id), array('id' => 'fum'.$record->getId(),'class' => 'fps-up', 'onClick' => "if (confirm('" . __('Are you sure') . "')) {sendu('fum".$record->getId()."')}; return false")) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'on_home'), false)) {
			if ($record->getView_on_home() == 1) {
				$moder_panel .= get_link('', $this->getModuleURL('off_home/' . $id), array('id' => 'fofm'.$record->getId(), 'class' => 'fps-on', 'onClick' => "if (confirm('" . __('Are you sure') . "')) {sendu('fofm".$record->getId()."')}; return false")) . '&nbsp;';
			} else {
				$moder_panel .= get_link('', $this->getModuleURL('on_home/' . $id), array('id' => 'fonm'.$record->getId(), 'class' => 'fps-off', 'onClick' => "if (confirm('" . __('Are you sure') . "')) {sendu('fonm".$record->getId()."')}; return false")) . '&nbsp;';
			}
		}

		if ($this->ACL->turn(array($this->module, 'delete_materials'), false)
				|| (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false))) {
			$moder_panel .= get_link('', $this->getModuleURL('delete/' . $id), array('id' => 'fdm'.$record->getId(), 'class' => 'fps-delete', 'onClick' => "if (confirm('" . __('Are you sure') . "')) {sendu('fdm".$record->getId()."'}; return false")) . '&nbsp;';
		}
		return $moder_panel;
	}
	
	
	

    /**
     * RSS 
	 *
     */
    function rss() {
		include_once ROOT . '/sys/inc/includes/rss.php';
    }	
	
}
