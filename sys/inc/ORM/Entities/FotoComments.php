<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.1                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    FotoComments Entity           |
| @copyleft      сообщество FaposCMS           |
| @last mod      2012/08/04                    |
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
class FotoCommentsEntity extends FpsEntity
{
	
	protected $id;
	protected $entity_id;
	protected $user_id;
	protected $name;
	protected $message;
	protected $ip;
	protected $mail;
	protected $date;
	protected $editdate;


	public function save()
	{
		$data = array(
			'id' => $this->id,
			'entity_id' => $this->entity_id,
			'user_id' => $this->user_id,
			'name' => $this->name,
			'message' => $this->message,
			'ip' => $this->ip,
			'mail' => $this->mail,
			'date' => $this->date,
			'editdate' => $this->editdate,
		);
		
		$Register = Register::getInstance();
		$Register['DB']->save('foto_comments', $data);
	}
	
	
	public function delete()
	{
		$Register = Register::getInstance();
		$Register['DB']->delete('foto_comments', array('id' => $this->id));
	}
}