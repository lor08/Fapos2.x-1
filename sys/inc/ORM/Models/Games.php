<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.0                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Games Model                    |
| @copyright     ©Andrey Brykin 2010-2012      |
| @last mod      2012/04/25                    |
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
class GamesModel extends FpsModel
{
	public $Table = 'games';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'author_id',
      	),
        'category' => array(
            'model' => 'GamesSections',
            'type' => 'has_one',
            'foreignKey' => 'category_id',
        ),
        'comments_' => array(
            'model' => 'GamesComments',
            'type' => 'has_many',
            'foreignKey' => 'entity_id',
        ),
        'attaches' => array(
            'model' => 'GamesAttaches',
            'type' => 'has_many',
            'foreignKey' => 'entity_id',
        ),
    );

	function getUserStatistic($user_id) {
		$user_id = intval($user_id);
		if ($user_id > 0) {
			$result = $this->getDbDriver()->select($this->Table, DB_FIRST, array('cond' => array('`author_id`' => $user_id), 'fields' => array('COUNT(*) as cnt'), 'limit' => 1));
			if (is_array($result) && count($result) > 0 && $result[0]['cnt'] > 0) {
				$res = array(
					array(
						'text' => 'Игр',
						'count' => $result[0]['cnt'],
						'url' => get_url('/games/user/' . $user_id),
					),
				);
				return $res;
			}
		}
		return false;
	}
}