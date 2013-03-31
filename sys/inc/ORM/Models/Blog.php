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
class BlogModel extends FpsModel
{
	public $Table = 'blog';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'author_id',
      	),
        'category' => array(
            'model' => 'BlogSections',
            'type' => 'has_one',
            'foreignKey' => 'category_id',
        ),
        'comments_' => array(
            'model' => 'BlogComments',
            'type' => 'has_many',
            'foreignKey' => 'entity_id',
        ),
        'attaches' => array(
            'model' => 'BlogAttaches',
            'type' => 'has_many',
            'foreignKey' => 'entity_id',
        ),
    );

	function getUserStatistic($user_id) {
		$result = $this->getDbDriver()->select($this->Table, DB_FIRST, array('cond' => array('`author_id`' => $user_id), 'fields' => array('COUNT(*) as cnt'), 'limit' => 1));
		if (is_array($result) && count($result) > 0 && $result[0]['cnt'] > 0) {
			$res = array(
				array(
					'text' => 'Статей',
					'count' => $result[0]['cnt'],
					'url' => get_url('/blog'),
				),
			);
			return $res;
		}
		return false;
	}
}