<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Email:        drunyacoder@gmail.com         |
| @Site:         http://fapos.net              |
| @Version:      1.8.02                        |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Loads Module                  |
| @copyright     В©Andrey Brykin 2010-2013      |
| @last mod.     2013/02/23                    |
|----------------------------------------------|
|											   |
| any partial or not partial extension         |
| CMS Fapos,without the consent of the         |
| author, is illegal                           |
|----------------------------------------------|
| Любое распространение                        |
| CMS Fapos или ее частей,                     |
| без согласия автора, является не законным    |
\---------------------------------------------*/




Class LoadsModule extends Module {
	/**
	* @template  layout for module
	*/
	public $template = 'loads';
	/**
	* @module_title  title of module
	*/
	public $module_title = 'Каталог файлов';
	/**
	* @module module indentifier
	*/
	public $module = 'loads';
	
	/**
	* @module module indentifier
	*/
	public $attached_files_path = 'loads';





    public function __construct($params)
    {
		parent::__construct($params);
		$this->attached_files_path = ROOT . $this->getFilesPath();
	}

	

	/**
     * @return none
     *
	 * default action ( show main page )
	 */
	function index($tag = null)
    {
		
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
        if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) $where['available'] = '1';
		if (!empty($tag)) $where[] = "`tags` LIKE '%{$tag}%'";
		

        $this->Model->bindModel('attaches');
        $this->Model->bindModel('author');
        $this->Model->bindModel('category');
        $records = $this->Model->getCollection($where, $params);

        if (is_object($this->AddFields) && count($records) > 0) {
            $records = $this->AddFields->mergeRecords($records);
        }


        foreach ($records as $entity) {
            $this->Register['current_vars'] = $entity;
            $_addParams = array();

            $markers['moder_panel'] = $this->_getAdminBar($entity);
            $entry_url = get_url(entryUrl($entity, $this->module));
            $markers['entry_url'] = $entry_url;


            $announce = $entity->getMain();
			
			
            $announce = $this->Textarier->getAnnounce($announce, $entry_url, 0,
                $this->Register['Config']->read('announce_lenght', $this->module), $entity);
			
			
            $rec_attaches = $entity->getAttaches();
            // replace image tags in text
			if (!empty($rec_attaches) && count($rec_attaches) > 0) {
                foreach ($rec_attaches as $attach) {
					if ($attach->getIs_image() == '1') {
						$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
                    }
                }
            }

            $markers['announce'] = $announce;


            $markers['loads'] = $entity->getDownloads();
            $markers['profile_url'] = getProfileUrl($entity->getAuthor_id());
			$markers['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
            $entity->setAdd_markers($markers);


            //prepear cache tags
            $this->setCacheTag(array(
                'user_id_' . $entity->getAuthor_id(),
                'record_id_' . $entity->getId(),
            ));
        }


        $source = $this->render('list.html', array('entities' => $records));


        //write int cache
        if ($this->cached)
            $this->Cache->write($source, $this->cacheKey, $this->cacheTags);


        return $this->_view($source);
	}


	

	/**
	* action view category of loads
	*/
	function category($id = null)
    {
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
        $childCats[] = $id;
        $childCats = implode(', ', $childCats);
        $query_params = array('cond' => array(
			'`category_id` IN (' . $childCats . ')'
        ));

        if (!$this->ACL->turn(array('other', 'can_see_hidden'), false)) {
            $query_params['cond']['available'] = 1;
        }


        $total = $this->Model->getTotal($query_params);
		list ($pages, $page) = pagination( $total, $this->Register['Config']->read('per_page', $this->module), $this->getModuleURL('category/' . $id));
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
                , $this->Register['Config']->read('announce_lenght', $this->module)
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
            $_addParams['profile_url'] = getProfileUrl($result->getAuthor_id());


            //set users_id that are on this page
            $this->setCacheTag(array(
                'user_id_' . $result->getAuthor_id(),
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
	  
	  


	/**
	 * show page with load info
     * s
	 * @param int $id
	 * @return none
	 */
	function view ($id = null)
    {
		//turn access
		$this->ACL->turn(array($this->module, 'view_materials'));
		$id = intval($id);
		if (empty($id) || $id < 1) redirect('/');



        $this->Model->bindModel('attaches');
        $this->Model->bindModel('author');
        $this->Model->bindModel('category');
        $entity = $this->Model->getById($id);


        if (empty($entity)) redirect('/error.php?ac=404');
        if ($entity->getAvailable() == 0 && !$this->ACL->turn(array('other', 'can_see_hidden'), false))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
        if (!$this->ACL->checkCategoryAccess($entity->getCategory()->getNo_access()))
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());


        // Some gemor with add fields
        if (is_object($this->AddFields)) {
            $entities = $this->AddFields->mergeRecords(array($entity));
            $entity = $entities[0];
        }


		$max_attaches = $this->Register['Config']->read('max_attaches', $this->module);
        if (empty($max_attaches) || !is_numeric($max_attaches)) $max_attaches = 5;


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
        $tags = $entity->getTags();
        $description = $entity->getDescription();
        if (!empty($tags)) $this->page_meta_keywords = h($tags);
        if (!empty($description)) $this->page_meta_description = h($description);

        $navi = array();
		$navi['module_url'] = get_url($this->getModuleURL());
		$navi['category_url'] = get_url($this->getModuleURL('category/' . $entity->getCategory_id()));
        $navi['category_name'] = h($entity->getCategory()->getTitle());
        $navi['navigation'] = $this->_buildBreadCrumbs($entity->getCategory_id());
        $this->_globalize($navi);


		$markers = array();
		$markers['moder_panel'] = $this->_getAdminBar($entity);
		

		if($entity->getDownload() && is_file(ROOT . $this->getFilesPath($entity->getDownload()))) {
		  $attach_serv = '<a target="_blank" href="' . get_url($this->getModuleURL('download_file/' 
		  . $entity->getId())) . '">' . __('Download from server') . ' ('.
          getSimpleFileSize(filesize(ROOT . $this->getFilesPath($entity->getDownload()))) . ')</a>';
		} else {
			$attach_serv  = '';
		}
		
		if($entity->getDownload_url_size()) {
			$attach_rem_size = ' (' . getSimpleFileSize($entity->getDownload_url_size()) . ')';
		} else {
			$attach_rem_size  = '';
		}

		if($entity->getDownload_url()) {
		  $attach_rem_url = '<a target="_blank" href="' . get_url($this->getModuleURL('download_file_url/' 
		  . $entity->getId())) . '">' . __('Download remotely') . $attach_rem_size . '</a>';
		} else {
			$attach_rem_url = '';
		}
		$markers['attachment'] = $attach_serv . ' | ' . $attach_rem_url;



        $announce = $entity->getMain();
		$announce = $this->Textarier->print_page($announce, $entity->getAuthor() ? $entity->getAuthor()->getStatus() : 0, $entity->getTitle());

        // replace image tags in text
        $attaches = $entity->getAttaches();
        if (!empty($attaches) && count($attaches) > 0) {
            foreach ($attaches as $attach) {
					if ($attach->getIs_image() == '1') {
						$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number());
                }
            }
        }

        $markers['main_text'] = $announce;

		
		$markers['profile_url'] = getProfileUrl($entity->getAuthor_id());
        $entity->setAdd_markers($markers);
		$entity->setTags(explode(',', $entity->getTags()));


        $source = $this->render('material.html', array('entity' => $entity));


        $entity->setViews($entity->getViews() + 1);
        $entity->save();
        $this->Register['DB']->cleanSqlCache();

        return $this->_view($source);
	}


	
	
	/*
	 * return form to add 
	 */
	function add_form () {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;
		
		
		//формируем блок со списком  разделов
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
        $data = array('title' => null, 'mainText' => null, 'in_cat' => null, 'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null, 'sourse_site' => null, 'commented' => null, 'available' => null, 'download_url' => null, 'download_url_size' => null);
        $data = array_merge($data, $markers);
        $data = Validate::getCurrentInputsValues($data);
        $data['main_text'] = $data['mainText'];


        $data['preview'] = $this->Parser->getPreview($data['main_text']);
        $data['errors'] = $this->Parser->getErrors();
        if (isset($_SESSION['viewMessage'])) unset($_SESSION['viewMessage']);
        if (isset($_SESSION['FpsForm'])) unset($_SESSION['FpsForm']);


        $catsModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
        $sql = $catsModel->getCollection();
        $data['cats_selector'] = $this->_buildSelector($sql, ((!empty($data['in_cat'])) ? $data['in_cat'] : false));


        //comments and hide
        $data['commented'] = (!empty($commented) || !isset($_POST['submitForm'])) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $data['commented'] .= ' disabled="disabled"';
        $data['available'] = (!empty($available) || !isset($_POST['submitForm'])) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false)) $data['available'] .= ' disabled="disabled"';


		$markers['action'] = get_url($this->getModuleURL('add/'));
		$markes['max_attaches'] = $this->Register['Config']->read('max_attaches', $this->module);
		if (empty($markers['max_attaches']) || !is_numeric($markers['max_attaches']))
			$markers['max_attaches'] = 5;
        $data = array_merge($data, $markers);
		$source = $this->render('addform.html', array('data' => $data));
		
		
		return $this->_view($source);
	}

	
	
	
	/**
	 * 
	 * Validate data and create a new record into 
	 * Data Base. If an errors, redirect user to add form
	 * and show error message where speaks as not to admit 
	 * errors in the future
	 *
     * @return none;
	 */
	function add()
    {
		//turn access
		$this->ACL->turn(array($this->module, 'add_materials'));
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($_POST['mainText'])
		|| !isset($_POST['title'])
		|| !isset($_POST['cats_selector'])
		|| !is_numeric($_POST['cats_selector'])) {
			redirect('/');
		}
		$error  = '';


        // Check additional fields if an exists.
        // This must be doing after define $error variable.
        if (is_object($this->AddFields)) {
            $_addFields = $this->AddFields->checkFields();
            if (is_string($_addFields)) $error .= $_addFields;
        }


		$fields = array('description', 'tags', 'sourse', 'sourse_email', 'sourse_site', 'download_url', 'download_url_size');
		$fields_settings = $this->Register['Config']->read('fields', $this->module);
		foreach ($fields as $field) {
			if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
				$error = $error.'<li>' . __('Empty field') . '"' . $field . '"</li>'."\n";
				$$field = null;
			} else {
				$$field = trim($_POST[$field]);

			}
		}
		
		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title     = mb_substr( $_POST['title'], 0, 128 );
		$addLoad   = trim($_POST['mainText']);
		$title     = trim( $title );
		$in_cat    = intval($_POST['cats_selector']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		$available = (!empty($_POST['available'])) ? 1 : 0;
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented = '1';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false)) $available = '1';
		
		// Preview
		if ( isset( $_POST['viewMessage'] ) ) {
			$_SESSION['viewMessage'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat, 
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null, 
				'sourse_site' => null, 'download_url' => null, 'download_url_size' => null, 'commented' => null, 'available' => null), $_POST);
			redirect($this->getModuleURL('add_form/'));
		}

		// Проверяем, заполнены ли обязательные поля
		$valobj = $this->Register['Validate'];  //validation data class
		if (empty($title))                      
			$error = $error . '<li>' . __('Empty field "title"') . '</li>' . "\n";
		elseif (!$valobj->cha_val($title, V_TITLE)) 
			$error = $error . '<li>' . __('Wrong chars in "title"') . '</li>' ."\n";	
		if (empty($addLoad))                    
			$error = $error . '<li>' . __('Empty field "material"') . '</li>' ."\n";
		
		if (mb_strlen($addLoad) > $this->Register['Config']->read('max_lenght', $this->module))
			$error = $error .'<li>'. sprintf(__('Wery big "material"')
			, $this->Register['Config']->read('max_lenght', $this->module)) .'</li>'."\n";
		if (mb_strlen($addLoad) < $this->Register['Config']->read('min_lenght', $this->module))
			$error = $error .'<li>'. sprintf(__('Wery small "material"')
			, $this->Register['Config']->read('min_lenght', $this->module)) .'</li>'."\n";
		
		if ($this->Register['Config']->read('require_file', $this->module) == 1) {
			if (empty($_FILES['attach']['name'])) 
				$error = $error . '<li>' . __('Not attaches') . '</li>' . "\n";	
		}
		if (isset($_FILES['attach']['name']) 
		&& $_FILES['attach']['size'] > $this->getMaxSize())
			$error = $error .'<li>'. sprintf(__('Wery big file2'), round($this->getMaxSize() / 1024, 2)) .'</li>'. "\n";
		
		if (!empty($tags) && !$valobj->cha_val($tags, V_TITLE)) 
			$error = $error. '<li>' . __('Wrong chars in "tags"') . '</li>' ."\n";
		if (!empty($sourse) && !$valobj->cha_val($sourse, V_TITLE)) 
			$error = $error. '<li>' . __('Wrong chars in "sourse"') . '</li>' ."\n";
		if (!empty($sourse_email) && !$valobj->cha_val($sourse_email, V_MAIL)) 
			$error = $error. '<li>' . __('Wrong chars in "email"') . '</li>' ."\n";
		if (!empty($sourse_site) && !$valobj->cha_val($sourse_site, V_URL)) 
			$error = $error. '<li>' . __('Wrong chars in "sourse site"') . '</li>' ."\n";
		if (!empty($download_url) && !$valobj->cha_val($download_url, V_TITLE)) 
			$error = $error. '<li>' . __('Wrong chars in "download_url"') . '</li>' ."\n";
		if (!empty($download_url_size) && !$valobj->cha_val($download_url_size, V_TITLE)) 
			$error = $error. '<li>' . __('Wrong chars in "download_url_size"') . '</li>' ."\n";



        $categoryModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
        $cat = $categoryModel->getById(array('id' => $in_cat));
        if (empty($cat)) $error .= '<li>' . __('Can not find category') . '</li>'."\n";


        // Check attaches size and format
		$max_attach = $this->Register['Config']->read('max_attaches', $this->module);
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
		
		
		
		// Errors
		if (!empty($error)) {
			$_SESSION['FpsForm'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat,
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null, 
				'sourse_site' => null, 'download_url' => null, 'download_url_size' => null, 'commented' => null, 'available' => null), $_POST);
			$_SESSION['FpsForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>'.
				"\n".'<ul class="errorMsg">'."\n".$error.'</ul>'."\n";
			redirect($this->getModuleURL('add_form/'));
		}


		//Проверяем прикрепленный файл...
		$file = '';
		$filename = '';
		if (!empty($_FILES['attach']['name'])) {
			$file = $this->__saveFile($_FILES['attach']);
		}

		if ($file!='') {
			$filename = $_FILES['attach']['name'];
		}

		// span protected
		if ( isset( $_SESSION['unix_last_post'] ) and ( time() - $_SESSION['unix_last_post'] < 30 ) ) {
			return $this->showInfoMessage(__('Your message has been added'), $this->getModuleURL());
		}
		
		
		// Auto tags generation
		if (empty($tags)) {
			$TagGen = new MetaTags;
			$tags = $TagGen->getTags($addLoad);
			$tags = (!empty($tags) && is_array($tags)) ? implode(',', array_keys($tags)) : '';
		}
		
		
		// Формируем SQL-запрос на добавление темы
		$addLoad = mb_substr($addLoad, 0, $this->Register['Config']->read('max_lenght', $this->module));
        $data = array(
			'title'             => $title,
			'main'              => $addLoad,
			'date'              => new Expr('NOW()'),
			'author_id'         => $_SESSION['user']['id'],
			'category_id'       => $in_cat,
			'description'       => $description,
			'tags'              => $tags,
			'sourse'            => $sourse,
			'sourse_email'      => $sourse_email,
			'sourse_site'       => $sourse_site,
			'download_url'      => $download_url,
			'download_url_size' => (int)$download_url_size,
			'commented'         => $commented,
			'available'         => $available,
			'view_on_home'      => $cat->getView_on_home(),
        );
		if (!empty($file)) {
			$data['download'] = $file;
			$data['filename'] = $filename;
		}
		$className = $this->Register['ModManager']->getEntityName($this->module);
		$entity = new $className($data);
		if ($entity) {
			$last_id = $entity->save();


			// Get last insert ID and save additional fields if an exists and activated.
			// This must be doing only after save main(parent) material
			if (is_object($this->AddFields)) {
				$this->AddFields->save($last_id, $_addFields);
			}

			downloadAttaches($this->module, $last_id);

			//clear cache
			$this->Cache->clean(CACHE_MATCHING_ANY_TAG, array('module_loads'));
			$this->DB->cleanSqlCache();
			if ($this->Log) $this->Log->write('adding load', 'load id(' . $last_id . ')');
			return $this->showInfoMessage(__('Material successful added'), $this->getModuleURL() );
		} else {
			return $this->showInfoMessage(__('Some error occurred'), $this->getModuleURL());
		}
	}




	/**
	 * 
	 * Create form and fill his data from record which ID
	 * transfered into function. Show errors if an exists
	 * after unsuccessful attempt.
	 * 
	 */
	function edit_form($id = null)
    {
		$id = (int)$id;
		if ($id < 1 || empty($id)) redirect('/');
		$writer_status = (!empty($_SESSION['user']['status'])) ? $_SESSION['user']['status'] : 0;


        $this->Model->bindModel('attaches');
        // $this->Model->bindModel('author');
        // $this->Model->bindModel('category');
        $entity = $this->Model->getById($id);

		if (!$entity) redirect($this->getModuleURL());


        if (is_object($this->AddFields) && count($entity) > 0) {
            $entities = $this->AddFields->mergeRecords(array($entity), true);
            $entity = $entities[0];
        }


        //turn access
		if (!$this->ACL->turn(array($this->module, 'edit_materials'), false)
            && (!empty($_SESSION['user']['id']) && $entity->getAuthor_id() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
        }


        $this->Register['current_vars'] = $entity;

        //forming categories list
        $this->_getCatsTree($entity->getCategory_id());

		
        // Check for preview or errors
        $data = array(
            'title' => '',
            'mainText' => $entity->getMain(),
            'in_cat' => '',
            'description' => '',
            'tags' => '',
            'sourse' => '',
            'sourse_email' => '',
            'sourse_site' => '',
            'commented' => '',
            'available' => '',
            'download_url' => '',
            'download_url_size' => '',
        );
        $data = Validate::getCurrentInputsValues($entity, $data);
        $data->setMain_text($data->getMaintext());


        $data->setPreview($this->Parser->getPreview($data->getMain()));
        $data->setErrors($this->Parser->getErrors());
        if (isset($_SESSION['viewMessage'])) unset($_SESSION['viewMessage']);
        if (isset($_SESSION['FpsForm'])) unset($_SESSION['FpsForm']);



        $sectionModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
        $cats = $sectionModel->getCollection();
        $selectedCatId = ($data->getIn_cat()) ? $data->getIn_cat() : $data->getCategory_id();
        $cats_change = $this->_buildSelector($cats, $selectedCatId);


        //comments and hide
        $commented = ($data->getCommented()) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented .= ' disabled="disabled"';
        $available = ($data->getAvailable()) ? 'checked="checked"' : '';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false)) $available .= ' disabled="disabled"';
		$data->setAction(get_url($this->getModuleURL('update/' . $data->getId())));
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


		$data->setCats_selector($cats_change);
        $data->setAttaches_delete($attDelButtons);
		$data->setMax_attaches($this->Register['Config']->read('max_attaches', $this->module));


		//navigation panel
		$navi = array();
		$navi['navigation'] = $this->_buildBreadCrumbs($entity->getCategory_id());
		$this->_globalize($navi);


        $source = $this->render('editform.html', array('data' => $data));
		setReferer();
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
	function update($id = null)
    {
		// Если не переданы данные формы - функция вызвана по ошибке
		if (!isset($id) or
		   !isset($_POST['title']) or
		   !isset($_POST['mainText']))
		{
			redirect('/');
		}
		$id = (int)$id;
		if ( $id < 1 ) redirect($this->getModuleURL());
        $error = '';



        $target = $this->Model->getbyId($id);
		if (!$target) redirect($this->getModuleURL());


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
		$fields = array('description', 'tags', 'sourse', 'sourse_email', 'sourse_site', 'download_url', 'download_url_size');
		$fields_settings = $this->Register['Config']->read('fields', $this->module);
		foreach ($fields as $field) {
			if (empty($_POST[$field]) && in_array($field, $fields_settings)) {
				$error = $error.'<li>' . __('Empty field') . ' "' . $field . '"</li>'."\n";
				$$field = null;
			} else {
				$$field = trim($_POST[$field]);
			}
		}
		
		
		// Обрезаем переменные до длины, указанной в параметре maxlength тега input
		$title  	= trim(mb_substr( $_POST['title'], 0, 128));
		$editLoad   = trim($_POST['mainText']);
		$in_cat 	= intval($_POST['cats_selector']);
		$commented = (!empty($_POST['commented'])) ? 1 : 0;
		$available = (!empty($_POST['available'])) ? 1 : 0;
		if (!$this->ACL->turn(array($this->module, 'record_comments_management'), false)) $commented = '1';
		if (!$this->ACL->turn(array($this->module, 'hide_material'), false)) $available = '1';


		// Preview
		if (isset($_POST['viewMessage'])) {
			$_SESSION['viewMessage'] = array_merge(array('title' => null, 'mainText' => null, 'in_cat' => $in_cat, 
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null, 
				'sourse_site' => null, 'download_url' => null, 'download_url_size' => null, 'commented' => null, 'available' => null), $_POST);
			redirect($this->getModuleURL('edit_form/' . $id));
		}


		if (mb_strlen($editLoad) > $this->Register['Config']->read('max_lenght', $this->module))
			$error = $error . '<li>' . sprintf(__('Wery big "material"')
			, $this->Register['Config']->read('max_lenght', $this->module)).'</li>'."\n";
		if (mb_strlen($editLoad) < $this->Register['Config']->read('min_lenght', $this->module))
			$error = $error .'<li>' . sprintf(__('Wery small "material"')
			, $this->Register['Config']->read('min_lenght', $this->module)).'</li>'."\n";

		if ($this->Register['Config']->read('require_file', $this->module) == 1) {
			if (empty($_FILES['attach']['name'])) 
				$error = $error . '<li>' . __('Not attaches') . '</li>' . "\n";	
		}
		if (isset($_FILES['attach']['name']) 
		&& $_FILES['attach']['size'] > $this->getMaxSize())
			$error = $error .'<li>'. sprintf(__('Wery big file2'), round($this->getMaxSize() / 1024, 2)) .'</li>'. "\n";

		// Проверяем, заполнены ли обязательные поля
		if ( empty( $title ) ) 
			$error = $error.'<li>' . __('Empty field "title"') .'</li>'."\n";
		elseif (!$valobj->cha_val($title, V_TITLE)) 
			$error = $error.'<li>' . __('Wrong chars in "title"') .'</li>'."\n";
		if ( empty( $editLoad ) ) 
			$error = $error.'<li>' . __('Empty field "material"') .'</li>'."\n";
		if (!empty($tags) && !$valobj->cha_val($tags, V_TITLE)) 
			$error = $error.'<li>' . __('Wrong chars in "tags"') .'</li>'."\n";
		if (!empty($sourse) && !$valobj->cha_val($sourse, V_TITLE)) 
			$error = $error.'<li>' . __('Wrong chars in "sourse"') .'</li>'."\n";
		if (!empty($sourse_email) && !$valobj->cha_val($sourse_email, V_MAIL)) 
			$error = $error.'<li>' . __('Wrong chars in "email"') .'</li>'."\n";
		if (!empty($sourse_site) && !$valobj->cha_val($sourse_site, V_URL)) 
			$error = $error.'<li>' . __('Wrong chars in "sourse site"') .'</li>'."\n";
		if (!empty($download_url) && !$valobj->cha_val($download_url, V_TITLE)) 
			$error = $error.'<li>' . __('Wrong chars in "download_url"') .'</li>'."\n";
		if (!empty($download_url_size) && !$valobj->cha_val($download_url_size, V_TITLE)) 
			$error = $error.'<li>' . __('Wrong chars in "download_url_size"') .'</li>'."\n";


        $catModel = $this->Register['ModManager']->getModelInstance($this->module . 'Sections');
        $category = $catModel->getById($in_cat);
        if (!$category) $error = $error.'<li>' . __('Can not find category') . '</li>'."\n";
		
		
		
		// Delete attached file if an exists and we get flag from editor
		if (!empty($_POST['delete_file']) || !empty($_FILES['attach']['name'])) {
			if ($target->getDownload() && file_exists($this->attached_files_path . $target->getDownload())) {
				_unlink($this->attached_files_path . $target->getDownload());
			}
		}
		
		//Проверяем прикрепленный файл...
		$file = '';
		$filename = '';
		if (!empty($_FILES['attach']['name'])) {
			$file = $this->__saveFile($_FILES['attach']);
		}

		if ($file!='') {
			$filename = $_FILES['attach']['name'];
		}

        // Check attaches size and format
		$max_attach = $this->Register['Config']->read('max_attaches', $this->module);
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
		if (!empty($error)) {
			$_SESSION['FpsForm'] = array_merge(array('title' => null, 'mainText' => null, 
				'description' => null, 'tags' => null, 'sourse' => null, 'sourse_email' => null, 'in_cat' => $in_cat,
				'sourse_site' => null, 'download_url' => null, 'download_url_size' => null, 'commented' => null, 'available' => null), $_POST);
			$_SESSION['FpsForm']['error'] = '<p class="errorMsg">' . __('Some error in form') 
				. '</p>' . "\n" . '<ul class="errorMsg">'."\n".$error.'</ul>'."\n";
			redirect($this->getModuleURL('edit_form/' . $id ));
		}
		
		
		// Auto tags generation
		if (empty($tags)) {
			$TagGen = new MetaTags;
			$tags = $TagGen->getTags($editLoad);
			$tags = (!empty($tags) && is_array($tags)) ? implode(',', array_keys($tags)) : '';
		}
		

		// Запрос на обновление файла
		$data = array(
			'id' 		   => $id,
			'title' 	   => $title,
			'main' 		   => $editLoad,
			'category_id'  => $in_cat,
			'description'  => $description,
			'tags'         => $tags,
			'sourse'  	   => $sourse,
			'sourse_email' => $sourse_email,
			'sourse_site'  => $sourse_site,
			'download_url'  => $download_url,
			'download_url_size'  => $download_url_size,
			'commented'    => $commented,
			'available'    => $available,
		);
		if (!empty($file)) {
			$data['download'] = $file;
			$data['filename'] = $filename;
		}
        $target->set($data);
        $target->save();

		// Save additional fields if they is active
		if (is_object($this->AddFields)) {
			$this->AddFields->save($id, $_addFields);
		}
		
		//clear cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('record_id_' . $id, 'module_loads'));
		$this->DB->cleanSqlCache();
		if ($this->Log) $this->Log->write('editing load', 'ent. id(' . $id . ')');
		return $this->showInfoMessage(__('Operation is successful'), getReferer());
	}



	// Функция удаляет тему; ID темы передается методом GET
	function delete($id = null)
    {
		$id = (int)$id;
		if ($id < 1) redirect('/');


        $target = $this->Model->getById($id);
        if (!$target) redirect('/');


        //turn access
		if (!$this->ACL->turn(array($this->module, 'delete_materials'), false)
            && (!empty($_SESSION['user']['id']) && $target->getAuthor_id() == $_SESSION['user']['id']
				&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false)) === false) {
			return $this->showInfoMessage(__('Permission denied'), $this->getModuleURL());
        }


        //remove cache
        $this->Cache->clean(CACHE_MATCHING_TAG, array('module_loads', 'record_id_' . $id));
        $this->Register['DB']->cleanSqlCache();

        $target->delete();

        $user_id = (!empty($_SESSION['user']['id'])) ? intval($_SESSION['user']['id']) : 0;
        if ($this->Log) $this->Log->write('delete loads', 'ent. id(' . $id . ') user id('.$user_id.')');
        return $this->showInfoMessage(__('Operation is successful'), getReferer());
	}




    /**
     * add comment to stat
     *
     * @id (int)    stat ID
     * @return      info message
     */
    public function add_comment($id = null)
    {
        include_once(ROOT . '/sys/inc/includes/add_comment.php');
    }


    /**
     * add comment form to stat
     *
     * @id (int)    stat ID
     * @return      html form
     */
    private function _add_comment_form($id = null)
    {
        include_once(ROOT . '/sys/inc/includes/_add_comment_form.php');
        return $html;
    }



    /**
     * edit comment form to stat
     *
     * @id (int)    comment ID
     * @return      html form
     */
    public function edit_comment_form($id = null)
    {
        include_once(ROOT . '/sys/inc/includes/edit_comment_form.php');
    }



    /**
     * update comment
     *
     * @id (int)    comment ID
     * @return      info message
     */
    public function update_comment($id = null)
    {
        include_once(ROOT . '/sys/inc/includes/update_comment.php');
    }



    /**
     * get comments for stat
     *
     * @id (int)    stat ID
     * @return      html comments list
     */
    private function _get_comments($entity = null)
    {
        include_once(ROOT . '/sys/inc/includes/_get_comments.php');
        return $html;
    }



    /**
     * delete comment
     *
     * @id (int)    comment ID
     * @return      info message
     */
    public function delete_comment($id = null)
    {
        include_once(ROOT . '/sys/inc/includes/delete_comment.php');
    }



    /**
     * @param int $id - record ID
     *
     * update date by record also up record in recods list
     */
    public function upper($id)
    {
        //turn access
		$this->ACL->turn(array($this->module, 'up_materials'));
        $id = (int)$id;
		if ($id < 1) redirect($this->getModuleURL());


        $entity = $this->Model->getById($id);
		if (!$entity) redirect($this->getModuleURL());

        $entity->setDate(date("Y-m-d H-i-s"));
        $entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
    }



    /**
     * @param int $id - record ID
     *
     * allow record be on home page
     */
    public function on_home($id)
    {
        //turn access
		$this->ACL->turn(array($this->module, 'on_home'));
        $id = (int)$id;
		if ($id < 1) redirect($this->getModuleURL());


        $entity = $this->Model->getById($id);
		if (!$entity) redirect($this->getModuleURL());

        $entity->setView_on_home('1');
        $entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
    }



    /**
     * @param int $id - record ID
     *
     * denied record be on home page
     */
    public function off_home($id)
    {
        //turn access
		$this->ACL->turn(array($this->module, 'on_home'));
        $id = (int)$id;
		if ($id < 1) redirect($this->getModuleURL());


        $entity = $this->Model->getById($id);
		if (!$entity) redirect($this->getModuleURL());

        $entity->setView_on_home('0');
        $entity->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
    }



    /**
     * @param int $id - record ID
     *
     * fix or unfix record on top on home page
     */
    public function fix_on_top($id)
    {
		$this->ACL->turn(array($this->module, 'on_home'));
        $id = (int)$id;
		if ($id < 1) redirect($this->getModuleURL());

        $target = $this->Model->getById($id);
        if (!$target) redirect('/');

        $curr_state = $target->getOn_home_top();
        $dest = ($curr_state) ? '0' : '1';
        $target->setOn_home_top($dest);
        $target->save();
		return $this->showInfoMessage(__('Operation is successful'), $this->getModuleURL());
    }




    function download_file($id = null, $mimetype = 'application/octet-stream')
    {
		
		if (empty($id)) redirect('/');
		$id = intval($id);
		//clear cache
		$this->Cache->clean(CACHE_MATCHING_TAG, array('record_id_' . $id, 'module_load'));


        $entity = $this->Model->getById($id);
		if (!$entity) return $this->showInfoMessage(__('File not found'), $this->getModuleURL() );

        $entity->setDownloads($entity->getDownloads() + 1);
        $entity->save();
        $this->Register['DB']->cleanSqlCache();

		if ($this->Register['Config']->read('filename_from_title', $this->module)) {
			$ext = strrchr($entity->getDownload(), ".");
			$name = $entity->getTitle() . $this->Register['Config']->read('filename_postfix', $this->module) . (empty($ext) ? '' : $ext);
		} else {
			$name = $entity->getFilename();
			if (!empty($name)) {
				if ($this->Register['Config']->read('filename_postfix', $this->module)) {
					$pos = strrpos($entity->getFilename(), ".");
					if ($pos === false) {
						$name = $name . $this->Register['Config']->read('filename_postfix', $this->module);
					} else {
						$ext = strrchr($name, ".");
						$name = substr($name, 0, $pos) . $this->Register['Config']->read('filename_postfix', $this->module) . $ext;
					}
				}
			} else {
				$name = $entity->getDownload();
				if ($this->Register['Config']->read('filename_postfix', $this->module)) {
					$pos = strrpos($name, ".");
					if ($pos === false) {
						$name = $name . $this->Register['Config']->read('filename_postfix', $this->module);
					} else {
						$ext = strrchr($name, ".");
						$name = substr($name, 0, $pos) . $this->Register['Config']->read('filename_postfix', $this->module) . $ext;
					}
				}
			}
		}
		$filename = ROOT . $this->getFilesPath($entity->getDownload());


        if (!file_exists($filename))
			return $this->showInfoMessage(__('File not found'), $this->getModuleURL() );
        $from = 0;
        $size = filesize($filename);
        $to = $size;


		if (isset($_SERVER['HTTP_RANGE'])) {
			if (preg_match('#bytes=-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {// если указан отрезок от конца файла
				$from = $size-$range[1];
				$to = $size;
			} elseif (preg_match('#bytes=([0-9]*)-#', $_SERVER['HTTP_RANGE'], $range)) {// если указана только начальная метка

				$from = $range[1];
				$to = $size;
			} elseif (preg_match('#bytes=([0-9]*)-([0-9]*)#', $_SERVER['HTTP_RANGE'], $range)) {// если указан отрезок файла

				$from = $range[1];
				$to = $range[2];
			}
			header('HTTP/1.1 206 Partial Content');

			$cr='Content-Range: bytes ' . $from .'-' . $to . '/' . $size;
		} else
			header('HTTP/1.1 200 Ok');
		
		$etag = md5($filename);
		$etag = substr($etag, 0, 8) . '-' . substr($etag, 8, 7) . '-' . substr($etag, 15, 8);
		header('ETag: "' . $etag . '"');
		header('Accept-Ranges: bytes');
		header('Content-Length: ' . ($to-$from));
		if (isset($cr))header($cr);
		header('Connection: close');
			
		header('Content-Type: ' . $mimetype);
		header('Last-Modified: ' . gmdate('r', filemtime($filename)));
		header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime($filename))." GMT");
		header("Expires: ".gmdate("D, d M Y H:i:s", time() + 3600)." GMT");
		$f=fopen($filename, 'rb');


		if (preg_match('#^image/#',$mimetype))
			header('Content-Disposition: filename="' . $name . '";');
		else
			header('Content-Disposition: attachment; filename="' . $name . '";');

		fseek($f, $from, SEEK_SET);
		$size = $to;
		$downloaded = 0;
		while(!feof($f) and ($downloaded<$size)) {
			$block = min(1024*8, $size - $downloaded);
			echo fread($f, $block);
			$downloaded += $block;
			flush();
		}
		fclose($f);
	}


	function download_file_url($id = null, $mimetype = 'application/octet-stream')
    {
	    $entity = $this->Model->getById($id);
		if (!$entity) return $this->showInfoMessage(__('File not found'), $this->getModuleURL() );

        $entity->setDownloads($entity->getDownloads() + 1);
        $entity->save();
		$this->Register['DB']->cleanSqlCache();
	
		header('Location: ' . $entity->getDownload_url());
	}
	

	
	/**
	* @param array $record - assoc record array
	* @return string - admin buttons
	*
	* create and return admin bar
	*/
	protected function _getAdminBar($record)
    {
		$moder_panel = '';
        $uid = $record->getAuthor_id();
        $id = $record->getId();


		if ($this->ACL->turn(array($this->module, 'edit_materials'), false) 
		|| (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
		&& $this->ACL->turn(array($this->module, 'edit_mine_materials'), false))) {
			$moder_panel .= get_link(get_img('/sys/img/edit_16x16.png'), $this->getModuleURL('edit_form/' . $id)) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'up_materials'), false)) {
			$moder_panel .= get_link(get_img('/sys/img/star.png'), $this->getModuleURL('fix_on_top/' . $id),
				array('onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
			$moder_panel .= get_link(get_img('/sys/img/up_arrow_16x16.png'), $this->getModuleURL('upper/' . $id),
				array('onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		if ($this->ACL->turn(array($this->module, 'on_home'), false)) {
				if ($record->getvView_on_home() == 1) {
					$moder_panel .= get_link(get_img('/sys/img/round_ok.png', array('alt' => __('On home'), 'title' => __('On home'))), 
						$this->getModuleURL('off_home/' . $id), array('onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
				} else {
					$moder_panel .= get_link(get_img('/sys/img/round_not_ok.png', array('alt' => __('On home'), 'title' => __('On home'))), 
						$this->getModuleURL('on_home/' . $id), array('onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
				}
		}
		if ($this->ACL->turn(array($this->module, 'delete_materials'), false) 
		|| (!empty($_SESSION['user']['id']) && $uid == $_SESSION['user']['id']
		&& $this->ACL->turn(array($this->module, 'delete_mine_materials'), false))) {
			$moder_panel .= get_link(get_img('/sys/img/delete_16x16.png'), $this->getModuleURL('delete/' . $id),
				array('onClick' => "return confirm('" . __('Are you sure') . "')")) . '&nbsp;';
		}
		return $moder_panel;
	}
	

	
	/**
	 * Try Save file
	 * 
	 * @param array $file (From POST request)
	 */
	private function __saveFile($file)
    {
		// Извлекаем из имени файла расширение
		$ext = strrchr( $file['name'], "." );
		
		// Формируем путь к файлу
		if (!isPermittedFile($ext)) $path = md5(uniqid(rand(), true)) . '-' . date("YmdHis", time()) . '.txt';
		else $path = md5(uniqid(rand(), true)) . '-' . date("YmdHis", time()) . $ext;
		// Перемещаем файл из временной директории сервера в директорию files
		if (move_uploaded_file($file['tmp_name'], ROOT . $this->getFilesPath($path))) {
			chmod( ROOT . $this->getFilesPath($path, 0644 ));
		}
		
		return $path;
	}
	


    /**
     * RSS
	 *
     */
    function rss()
    {
		include_once ROOT . '/sys/inc/includes/rss.php';
    }
	
}


