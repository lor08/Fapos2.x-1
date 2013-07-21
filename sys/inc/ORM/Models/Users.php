<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.1                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Users Model                   |
| @copyright     ©Andrey Brykin 2010-2013      |
| @last mod      2013/04/25                    |
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



/**
 *
 */
class UsersModel extends FpsModel
{
	public $Table  = 'users';

    protected $RelatedEntities = array(
        'inpm' => array(
            'model' => 'Messages',
            'type' => 'has_many',
            'foreignKey' => 'to',
        ),
        'outpm' => array(
            'model' => 'Messages',
            'type' => 'has_many',
            'foreignKey' => 'from',
        ),
    );



    public function getSameNics($nick)
    {
        $Register = Register::getInstance();
        // kirilic
        $rus = array( "А","а","В","Е","е","К","М","Н","О","о","Р","р","С","с","Т","Х","х" );
        // latin
        $eng = array( "A","a","B","E","e","K","M","H","O","o","P","p","C","c","T","X","x" );
        // Заменяем русские буквы латинскими
        $eng_new_name = str_replace( $rus, $eng, $nick );
        // Заменяем латинские буквы русскими
        $rus_new_name = str_replace( $eng, $rus, $nick );
        // Формируем SQL-запрос
        $res = $Register['DB']->query("SELECT * FROM `" . $Register['DB']->getFullTableName('users') . "`
			WHERE name LIKE '".mysql_real_escape_string( $nick )."' OR
			name LIKE '".mysql_real_escape_string( $eng_new_name )."' OR
			name LIKE '".mysql_real_escape_string( $rus_new_name )."';");
        return $res;
    }


    public function getMessage($id)
    {
        $Register = Register::getInstance();

        $messagesModel = $Register['ModManager']->getModelInstance('Messages');
        $message = $messagesModel->getById($id);

        if ($message) {
            $to = $this->getById($message->getTo_user());
            $from = $this->getById($message->getFrom_user());
            $message->setToUser($to);
            $message->setFromUser($from);
            return $message;
        }
        return null;
    }


    public function getInputMessages()
    {
        // Запрос на выборку входящих сообщений
        // id_rmv - это поле указывает на то, что это сообщение уже удалил
        // один из пользователей. Т.е. сначала id_rmv=0, после того, как
        // сообщение удалил один из пользователей, id_rmv=id_user. И только после
        // того, как сообщение удалит второй пользователь, мы можем удалить
        // запись в таблице БД TABLE_MESSAGES
        $Register = Register::getInstance();
        $messagesModel = $Register['ModManager']->getModelInstance('Messages');
        $messagesModel->bindModel('fromuser');
        $messagesModel->bindModel('touser');

        $messages = $messagesModel->getCollection(array(
            'to_user' => $_SESSION['user']['id'],
            "id_rmv <> '" . $_SESSION['user']['id'] . "'",
        ), array(
            'order' => 'sendtime DESC'
        ));

        return $messages;
    }


    public function getOutputMessages()
    {
        // Запрос на выборку входящих сообщений
        // id_rmv - это поле указывает на то, что это сообщение уже удалил
        // один из пользователей. Т.е. сначала id_rmv=0, после того, как
        // сообщение удалил один из пользователей, id_rmv=id_user. И только после
        // того, как сообщение удалит второй пользователь, мы можем удалить
        // запись в таблице БД TABLE_MESSAGES
        $Register = Register::getInstance();
        $messagesModel = $Register['ModManager']->getModelInstance('Messages');
        $messagesModel->bindModel('touser');
        $messagesModel->bindModel('fromuser');

        $messages = $messagesModel->getCollection(array(
            'from_user' => $_SESSION['user']['id'],
            "id_rmv <> '" . $_SESSION['user']['id'] . "'",
        ), array(
            'order' => 'sendtime DESC'
        ));

        return $messages;
    }


	public function getByName($name)
	{
        $Register = Register::getInstance();
		$entities = $this->getDbDriver()->select($this->Table, DB_FIRST, array(
			'cond' => array(
				'name' => $name
			)
		));

		if ($entities && count($entities)) {
            $entities = $this->getAllAssigned($entities);
			$entityClassName = $Register['ModManager']->getEntityNameFromModel(get_class($this));
			$entity = new $entityClassName($entities[0]);
			return (!empty($entity)) ? $entity : false;
		}
		return false;
	}


	public function getByNamePass($name, $password)
	{
        $Register = Register::getInstance();
		$entities = $Register['DB']->query("SELECT *, UNIX_TIMESTAMP(last_visit) as unix_last_visit
			FROM `" . $Register['DB']->getFullTableName('users') . "`  WHERE name='"
			.mysql_real_escape_string( $name )."' LIMIT 1");

		$check_password = false;
		if (count($entities) > 0 && !empty($entities[0])) {
			$check_password = checkPassword($entities[0]['passw'], $password);
		}

		if (count($entities) > 0 && !empty($entities[0]) && $check_password) {
            $entities = $this->getAllAssigned($entities);
			$entityClassName = $Register['ModManager']->getEntityNameFromModel(get_class($this));
			$entity = new $entityClassName($entities[0]);
			return (!empty($entity)) ? $entity : false;
		}
		return false;
	}


	public function getNewPmMessages($uid)
	{
		$res = $this->getDbDriver()->query("SELECT COUNT(*) as cnt
				FROM `" . $this->getDbDriver()->getFullTableName('messages') . "`
				WHERE `to_user` = ".$uid."
				AND `viewed` = 0 AND `id_rmv` <> ".$uid);

		return (!empty($res[0]) && !empty($res[0]['cnt'])) ? (string)$res[0]['cnt'] : 0;
	}

	function getCountComments($user_id = null) {
		$Register = Register::getInstance();
		$commentsModel = $Register['ModManager']->getModelInstance('Comments');
		$cond = array();
		if ($user_id) {
			$cond['user_id'] = $user_id;
		}
		$cnt = $commentsModel->getTotal(array('cond' => $cond));
		
		return ($cnt) ? $cnt : false;
	}

	function getUserStatistic($user_id) {
		$user_id = intval($user_id);
		if ($user_id > 0) {
			$cnt = $this->getCountComments($user_id);
			if ($cnt && $cnt > 0) {
				$res = array(
					array(
						'text' => 'Комментариев',
						'count' => $cnt,
						'url' => get_url('/users/comments/' . $user_id),
					),
				);
				return $res;
			}
		}
		return false;
	}

    public function getMessages()
    {
        $Register = Register::getInstance();
        $messagesModel = $Register['ModManager']->getModelInstance('Messages');
        $messagesModel->bindModel('fromuser');
        $messagesModel->bindModel('touser');

        $messages = $messagesModel->getCollection(array(
            '(to_user = ' . $_SESSION['user']['id'] . ' OR from_user = ' . $_SESSION['user']['id'] . ')',
            "id_rmv <> '" . $_SESSION['user']['id'] . "'",
        ), array(
            'order' => 'sendtime DESC'
        ));

        if (!$messages || (is_array($messages) && count($messages) == 0)) {
        	return;
        }

        $users = array();
        foreach ($messages as $i => $message) {
            if ($message->getFrom_user() != $_SESSION['user']['id']) {
                if (array_search($message->getFrom_user(), $users) !== FALSE) {
                    unset($messages[$i]);
                } else {
                    $message->setDirection('in');
                    array_push($users, $message->getFrom_user());
                }
            } else {
                if (array_search($message->getTo_user(), $users) !== FALSE) {
                    unset($messages[$i]);
                } else {
                    $message->setDirection('out');
                    array_push($users, $message->getTo_user());
                }
            }
        }
        return $messages;
    }

    public function getUserMessages($id)
    {
        $Register = Register::getInstance();
        $messagesModel = $Register['ModManager']->getModelInstance('Messages');
        $messagesModel->bindModel('fromuser');
        $messagesModel->bindModel('touser');

        $messages = $messagesModel->getCollection(array(
            '((to_user = '.$_SESSION['user']['id'].' and from_user = '.$id.') OR 
              (to_user = '.$id.' and from_user = '.$_SESSION['user']['id'].'))',
            "id_rmv <> '".$_SESSION['user']['id']."'",
        ), array(
            'order' => 'sendtime DESC'
        ));

        foreach ($messages as $message) {
            if ($message->getFrom_user() != $_SESSION['user']['id']) {
                $message->setDirection('in');
            } else {
                $message->setDirection('out');
            }
        }
        return $messages;
    }
}