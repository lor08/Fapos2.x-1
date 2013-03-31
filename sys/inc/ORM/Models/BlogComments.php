<?php
/*---------------------------------------------\
|                                              |
| @Author:       Alexandr Danilow (modos189)   |
| @Email:        modos189@ya.ru                |
| @Package       CMS Fapos                     |
| @Subpackege    Blog Module                   |
|                                              |
\---------------------------------------------*/



/**
 *
 */
class BlogCommentsModel extends FpsModel
{
	
    public $Table = 'blog_comments';
	
    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'user_id',
      	),
        'parent_entity' => array(
            'model' => 'Blog',
            'type' => 'has_one',
            'foreignKey' => 'entity_id',
        ),
    );

	
	
	public function getByEntity($entity)
	{
		$this->bindModel('Users');
		$params['entity_id'] = $entity->getId();
		$news = $this->getCollection($params);
		return $news;
	}
	
}