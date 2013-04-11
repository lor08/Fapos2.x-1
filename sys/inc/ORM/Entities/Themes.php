<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.2                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Themes Entity                 |
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
class ThemesEntity extends FpsEntity
{
	
	protected $id;
	protected $title;
	protected $id_author;
	protected $time;
	protected $id_last_author;
	protected $last_post;
	protected $id_forum;
	protected $locked;
	protected $posts;
	protected $views;
	protected $important;
	protected $description;
	protected $group_access;
	protected $first_top;

	
	
	
	public function save()
	{
		$params = array(
			'title' 			=> $this->title,
			'id_author' 		=> intval($this->id_author),
			'time' 				=> $this->time,
			'id_last_author' 	=> intval($this->id_last_author),
			'last_post' 		=> $this->last_post,
			'id_forum' 			=> intval($this->id_forum),
			'locked' 			=> intval($this->locked),
			'posts' 			=> intval($this->posts),
			'views' 			=> intval($this->views),
			'important' 		=> (!empty($this->important)) ? '1' : new Expr("'0'"),
			'description' 		=> $this->description,
			'group_access' 		=> (is_array($this->group_access) && count($this->group_access) == 1 && $this->group_access[0] !== '') 
									? intval($this->group_access[0])
									: implode('.', (array)$this->group_access),
			'first_top' 		=> (!empty($this->first_top)) ? '1' : new Expr("'0'"),
		);
		if ($this->id) $params['id'] = $this->id;
		$Register = Register::getInstance();
		return ($Register['DB']->save('themes', $params));
	}
	
	
	public function getGroup_access()
	{
		$out = (is_array($this->group_access)) ? $this->group_access : explode('.', $this->group_access);
		foreach ($out as $k => $v) if ('' === $v) unset($out[$k]);
		return $out;
	}

	
	public function getId_last_post()
	{
		if ($this->id) {
			$Register = Register::getInstance();
			$res = $Register['DB']->query("SELECT id FROM `" . $Register['DB']->getFullTableName('posts') . "`
				WHERE id_theme = " . intval($this->id) . " ORDER BY time DESC, id DESC LIMIT 1;");
			if ($res && is_array($res) && count($res) > 0 && isset($res[0]['id'])) return $res[0]['id'];
		}
		return null;
	}

	
	public function delete()
	{ 
		$Register = Register::getInstance();
		$Register['DB']->delete('themes', array('id' => $this->id));
	}

}