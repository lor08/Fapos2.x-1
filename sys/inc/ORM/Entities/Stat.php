<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.1                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Stat Entity                   |
| @copyright     ©Andrey Brykin 2010-2013      |
| @last mod      2013/01/30                    |
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
class StatEntity extends FpsEntity
{
	
	protected $id;
	protected $title;
	protected $main;
	protected $views;
	protected $date;
	protected $category_id;
	protected $category = null;
	protected $author_id;
	protected $author = null;
	protected $comments;
	protected $comments_ = null;
	protected $attaches = null;
	protected $tags;
	protected $description;
	protected $sourse;
	protected $sourse_email;
	protected $sourse_site;
	protected $commented;
	protected $available;
	protected $view_on_home;
	protected $on_home_top;
	protected $add_fields = null;
	
	
	
	public function save()
	{
		$params = array(
			'title' => $this->title,
			'main' => $this->main,
			'views' => intval($this->views),
			'date' => $this->date,
			'category_id' => $this->category_id,
			'author_id' => $this->author_id,
			'comments' => (!empty($this->comments)) ? intval($this->comments) : 0,
			'tags' => (is_array($this->tags)) ? implode(',', $this->tags) : $this->tags,
			'description' => $this->description,
			'sourse' => $this->sourse,
			'sourse_email' => $this->sourse_email,
			'sourse_site' => $this->sourse_site,
			'commented' => (!empty($this->commented)) ? '1' : new Expr("'0'"),
			'available' => (!empty($this->available)) ? '1' : new Expr("'0'"),
			'view_on_home' => (!empty($this->view_on_home)) ? '1' : new Expr("'0'"),
			'on_home_top' => (!empty($this->on_home_top)) ? '1' : new Expr("'0'"),
		);
		if ($this->id) $params['id'] = $this->id;
		$Register = Register::getInstance();
		return ($Register['DB']->save('stat', $params));
	}
	
	
	
	public function delete()
	{ 
		$Register = Register::getInstance();
		
		$attachesModel = $Register['ModManager']->getModelInstance('StatAttaches');
		$commentsModel = $Register['ModManager']->getModelInstance('StatComments');
		$addContentModel = $Register['ModManager']->getModelInstance('StatAddContent');
		
		$attachesModel->deleteByParentId($this->id);
		$commentsModel->deleteByParentId($this->id);
		$addContentModel->deleteByParentId($this->id);
		

		$Register['DB']->delete('stat', array('id' => $this->id));
	}



    /**
     * @param $comments
     */
	public function setComments_($comments)
    {
        $this->comments_ = $comments;
    }



    /**
     * @return array
     */
    public function getComments_()
   	{

        $this->checkProperty('comments_');
   		return $this->comments_;
   	}



    /**
     * @param $comments
     */
	public function setAttaches($attaches)
    {
        $this->attaches = $attaches;
    }



    /**
     * @return array
     */
    public function getAttaches()
   	{

        $this->checkProperty('attaches');
   		return $this->attaches;
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
				// $Model = new StatModel('stat');
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
				$this->category = $Register['ModManager']->getEntityInstance('StatSections');
			} else {
                $Register = Register::getInstance();
				$catsModel = $Register['ModManager']->getModelInstance('StatSections');
				$this->category = $catsModel->getById($this->category_id);
				// $Model = new StatModel('stat');
				// $this->category = $Model->getCategoryByEntity($this);  // TODO (function is not exists)
			}
        }
		return $this->category;
	}

}
