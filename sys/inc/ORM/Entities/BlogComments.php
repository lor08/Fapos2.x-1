<?php
/*---------------------------------------------\
|											   |
| @Author:       Alexandr Danilow (modos189)   |
| @Email:        modos189@ya.ru                |
| @Package       CMS Fapos                     |
| @Subpackege    Blog Module                   |
|											   |
\---------------------------------------------*/



/**
 *
 */
class BlogCommentsEntity extends FpsEntity
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
		return ($Register['DB']->save('blog_comments', $data));
	}
	
	
	public function delete()
	{
		$Register = Register::getInstance();
		$Register['DB']->delete('blog_comments', array('id' => $this->id));
	}
}