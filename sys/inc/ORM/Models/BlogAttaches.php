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
class BlogAttachesModel extends FpsModel
{
	
    public $Table = 'blog_attaches';

	
	
	public function getByEntity($entity)
	{
		$params['entity_id'] = $entity->getId();
		$data = $this->getMapper()->getCollection($params);
		return $data;
	}
	

}