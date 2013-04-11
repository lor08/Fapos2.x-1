<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.1                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Foto Entity                   |
| @copyright     ©Andrey Brykin 2010-2013      |
| @last mod      2013/04/03                    |
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
class FotoEntity extends FpsEntity
{
	
	protected $id;
	protected $title;
	protected $description;
	protected $views;
	protected $date;
	protected $category_id;
	protected $category = null;
	protected $author_id;
	protected $author = null;
	protected $comments;
	protected $commented;
	protected $filename = null;

	
	
	public function save()
	{
		$params = array(
			'title' => $this->title,
			'description' => $this->description,
			'views' => intval($this->views),
			'date' => $this->date,
			'category_id' => intval($this->category_id),
			'author_id' => intval($this->author_id),
			'comments' => (!empty($this->comments)) ? intval($this->comments) : 0,
			'commented' => (!empty($this->commented)) ? 1 : 0,
			'filename' => $this->filename,
		);
		if ($this->id) $params['id'] = $this->id;
		$Register = Register::getInstance();
		return ($Register['DB']->save('foto', $params));
	}
	
	
	
	public function delete()
	{ 
		$Register = Register::getInstance();
		$path = ROOT . '/sys/files/foto/full/' . $this->filename;
		$path2 = ROOT . '/sys/files/foto/preview/' . $this->filename;
		if (file_exists($path)) unlink($path);
		if (file_exists($path2)) unlink($path2);
		$Register['DB']->delete('foto', array('id' => $this->id));
	}


    /**
     * @param $author
     */
    public function setAuthor($author)
   	{
   		$this->author = $author;
   	}



    /**
     * @return object
     */
	public function getAuthor()
	{
        if (!$this->checkProperty('author')) {
			if (!$this->getAuthor_id()) {
                $Register = Register::getInstance();
				$this->author = $Register['ModManager']->getEntityInstance('users');
			} else {
                $Register = Register::getInstance();
				$usersModel = $Register['ModManager']->getModelInstance('Users');
				$this->author = $usersModel->getById($this->author_id);
				// $Model = new FotoModel('foto');
				// $this->author = $Model->getAuthorByEntity($this); // TODO (function is not exists)
			}
        }
		return $this->author;
	}
	
	

    /**
     * @param $category
     */
    public function setCategory($category)
   	{
   		$this->category = $category;
   	}



	/**
     * @return object
     */
	public function getCategory()
	{
        if (!$this->checkProperty($this->category)) {
			if (!$this->getCategory_id()) {
                $Register = Register::getInstance();
				$this->category = $Register['ModManager']->getEntityInstance('FotoSections');
			} else {
                $Register = Register::getInstance();
				$catsModel = $Register['ModManager']->getModelInstance('FotoSections');
				$this->category = $catsModel->getById($this->category_id);
				// $Model = new FotoModel('foto');
				// $this->category = $Model->getCategoryByEntity($this);  // TODO (function is not exists)
			}
        }
		return $this->category;
	}

}