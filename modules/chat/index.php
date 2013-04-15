<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.4.9                         |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Chat Module                   |
| @copyright     ©Andrey Brykin 2010-2013      |
| @last mod      2013/01/22                    |
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



class ChatModule extends Module {

	/**
	 * @template  layout for module
	 */
	public static $_template = 'chat';
	public $template = 'chat';

	/**
	 * @module_title  title of module
	 */
	public $module_title = 'Чат';

	/**
	 * @module module indentifier
	 */
	public static $_module = 'chat';
	public $module = 'chat';

	/**
	 * default action ( show add form and iframe for messages )
	 *
	 * @return view content
	 */
	public function index() {
		$content = '';
		if ($this->ACL->turn(array($this->module, 'add_materials'), false)) {
			$content = $this->add_form();
		} else {
			$content = __('Permission denied');
		}
		return $this->_view($content);
	}

	/**
	 *  show messages list
	 *
	 * @return view content
	 */
	public function view_messages() {
		$content = '';
		$chatDataPath = ROOT . $this->getTmpPath('messages.dat');


		if (file_exists($chatDataPath)) {
			$data = unserialize(file_get_contents($chatDataPath));
			if (!empty($data)) {
				$data = array_reverse($data);


				foreach ($data as $key => &$record) {

					$record['message'] = $this->Textarier->print_page($record['message']);
					/* view ip adres if admin */
					if ($this->ACL->turn(array($this->module, 'delete_materials'), false)) {
						$record['ip'] = '<a target="_blank" href="https://apps.db.ripe.net/search/query.html?searchtext=' . $record['ip'] . '" class="fps-ip" title="IP: ' . $record['ip'] . '"></a>';
					} else {
						$record['ip'] = '';
					}
				}

				$content = $this->render('list.html', array('messages' => $data));
			}
		}

//		header('Refresh: 10; url=' . get_url($this->getModuleURL('view_messages/')));
		echo $content;
	}

	/**
	 * add message form
	 *
	 * @return  none
	 */
	public function add() {
		if (!$this->ACL->turn(array($this->module, 'add_materials'), false)) {
			return;
		}
		if (!isset($_POST['message'])) {
			die(__('Needed fields are empty'));
		}

		/* cut and trim values */
		$user_id = (!empty($_SESSION['user']['id'])) ? $_SESSION['user']['id'] : '0';
		$name = (!empty($_SESSION['user']['name'])) ? trim($_SESSION['user']['name']) : __('Guest');
		$message = trim(mb_substr($_POST['message'], 0, $this->Register['Config']->read('max_lenght', $this->module)));
		$ip = (!empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
		$keystring = (isset($_POST['captcha_keystring'])) ? trim($_POST['captcha_keystring']) : '';


		// Check fields
		$error = '';
		$valobj = $this->Register['Validate'];
		if (!empty($name) && !$valobj->cha_val($name, V_TITLE))
			$error = $error . '<li>' . __('Wrong chars in field "login"') . '</li>' . "\n";
		if (empty($message))
			$error = $error . '<li>' . __('Empty field "text"') . '</li>' . "\n";



		// Check captcha if need exists
		if (!$this->ACL->turn(array('other', 'no_captcha'), false)) {
			if (empty($keystring))
				$error = $error . '<li>' . __('Empty field "code"') . '</li>' . "\n";


			// Проверяем поле "код"
			if (!empty($keystring)) {
				// Проверяем поле "код" на недопустимые символы
				if (!$valobj->cha_val($keystring, V_CAPTCHA))
					$error = $error . '<li>' . __('Wrong chars in field "code"') . '</li>' . "\n";
				if (!isset($_SESSION['captcha_keystring'])) {
					if (file_exists(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat')) {
						$_SESSION['captcha_keystring'] = file_get_contents(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat');
						@_unlink(ROOT . '/sys/logs/captcha_keystring_' . session_id() . '-' . date("Y-m-d") . '.dat');
					}
				}
				if (!isset($_SESSION['captcha_keystring']) || $_SESSION['captcha_keystring'] != $keystring)
					$error = $error . '<li>' . __('Wrong protection code') . '</li>' . "\n";
			}
			unset($_SESSION['captcha_keystring']);
		}

		/* if an errors */
		if (!empty($error)) {
			$_SESSION['addForm'] = array();
			$_SESSION['addForm']['error'] = '<p class="errorMsg">' . __('Some error in form') . '</p>' .
					"\n" . '<ul class="errorMsg">' . "\n" . $error . '</ul>' . "\n";
			$_SESSION['addForm']['message'] = $message;
			die($_SESSION['addForm']['error']);
		}

		/* create dir for chat tmp file if not exists */
		$tmp = ROOT . $this->getTmpPath();
		if (!file_exists($tmp))
			mkdir($tmp, 0777, true);
		/* get data */
		if (file_exists($tmp . 'messages.dat')) {
			$data = unserialize(file_get_contents($tmp . 'messages.dat'));
		} else {
			$data = array();
		}


		/* cut data (no more 50 messages */
		while (count($data) > 50) {
			array_shift($data);
		}
		$data[] = array(
			'user_id' => $user_id,
			'name' => $name,
			'message' => $message,
			'ip' => $ip,
			'date' => date("Y-m-d"),
			'time' => date("h:i"),
		);


		/* save messages */
		$file = fopen($tmp . 'messages.dat', 'w+');
		fwrite($file, serialize($data));
		fclose($file);
		die('ok');
	}

	/**
	 * view add message form
	 *
	 * @return (str)  add form
	 */
	public static function add_form() {
		$Register = Register::getInstance();


		$Parser = $Register['DocParser'];
		$Parser->templateDir = ChatModule::$_template;
		$ACL = $Register['ACL'];
		if (!$ACL->turn(array(ChatModule::$_module, 'add_materials'), false))
			return __('Dont have permission to write post');


		$markers = array();

		/* if an errors */
		if (isset($_SESSION['addForm'])) {
			$message = $_SESSION['addForm']['message'];
			unset($_SESSION['addForm']);
		} else {
			$message = '';
		}


		$kcaptcha = '';
		if (!$ACL->turn(array('other', 'no_captcha'), false)) {
			$kcaptcha = getCaptcha();
		}
		$markers['action'] = get_url('/' . ChatModule::$_module . '/add/');
		$markers['message'] = h($message);
		$markers['captcha'] = $kcaptcha;


		$View = new Fps_Viewer_Manager();
		$View->setModuleTitle(ChatModule::$_module);
		$View->setLayout(ChatModule::$_module);
		$source = $View->view('addform.html', array('data' => $markers));


		return $source;
	}

}

