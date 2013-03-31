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
class BlogAttachesEntity extends FpsEntity
{
	
	protected $id;
	protected $entity_id;
	protected $user_id;
	protected $attach_number;
	protected $filename ;
	protected $size;
	protected $date;
	protected $is_image;

	
	public function save()
	{
		$params = array(
			'entity_id' => $this->entity_id,
			'user_id' => $this->user_id,
			'attach_number' => $this->attach_number,
			'filename' => $this->filename,
			'size' => $this->size,
			'date' => $this->date,
			'is_image' => (!empty($this->is_image)) ? '1' : new Expr("'0'"),
		);
		if($this->id) $params['id'] = $this->id;
		$Register = Register::getInstance();
		return ($Register['DB']->save('blog_attaches', $params));
	}
	
	
	
	public function delete()
	{
		$path = ROOT . '/sys/files/blog/' . $this->filename;
		if (file_exists($path)) unlink($path);
		$Register = Register::getInstance();
		$Register['DB']->delete('blog_attaches', array('id' => $this->id));
	}
}