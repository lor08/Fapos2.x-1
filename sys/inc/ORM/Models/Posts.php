<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.0                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Posts Model                   |
| @copyright     ©Andrey Brykin 2010-2012      |
| @last mod      2012/04/28                    |
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
class PostsModel extends FpsModel
{
	public $Table = 'posts';

    protected $RelatedEntities = array(
        'author' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'id_author',
      	),
        'editor' => array(
            'model' => 'Users',
            'type' => 'has_one',
            'foreignKey' => 'id_editor',
      	),
        'theme' => array(
            'model' => 'Themes',
            'type' => 'has_one',
            'foreignKey' => 'id_theme',
      	),
        'attacheslist' => array(
            'model' => 'ForumAttaches',
            'type' => 'has_many',
            'foreignKey' => 'post_id',
      	),
    );

	
	
	public function deleteByTheme($theme_id)
	{
		$this->getDbDriver()->query("DELETE FROM `" . $this->getDbDriver()->getFullTableName('posts') . "` WHERE `id_theme` = '" . $theme_id . "'");
	}

	public function moveToTheme($theme_id, $posts_id)
	{
		$post = $this->getDbDriver()->select('posts', DB_FIRST, array('cond' => array('`id_theme`' => $theme_id), 'limit' => 1, 'order' => 'time ASC'));
		$this->getDbDriver()->query("UPDATE `" . $this->getDbDriver()->getFullTableName('posts') . "` SET `id_theme` = " . $theme_id . " WHERE `id` IN (" . implode(',', (array)$posts_id) . ")");
		if (!empty($post) && is_array($post) && count($post) > 0) {
			$time = strtotime($post[0]['time']);
			$new_time = $time + 1;
			$this->getDbDriver()->query("UPDATE `" . $this->getDbDriver()->getFullTableName('posts') . "` SET `time` = '" . date("Y-m-d H:i:s", $new_time) . "' WHERE `id` IN (" . implode(',', (array)$posts_id) . ") AND `time` < '" . date("Y-m-d H:i:s", $time) . "'");
		}
	}
}