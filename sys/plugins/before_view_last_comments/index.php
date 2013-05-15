<?php


class LastComments {
	
	// How match comments to view
	private $limit = 10;
	
	// Wraper for comments
	//private $wrap = '<li class="point"><b>%s</b> <span style="color:#D6C39B;">Написал в</span><br /> %s</li>';
	private $wrap;
	
	// Marker for plugin
	private $marker = '#{{\s*last_comments\s*}}#i';
	
	
	private $DB;

	public function __construct($params) {
		$Register = Register::getInstance();
		$this->DB = $Register['DB'];
		$this->wrap = '<div class="conmenu2 lastqq">' . "\n" .
			'<div class="lastcomm">' . "\n" .
			'<img class="lastimg" alt="" src="' . get_url('/template/' . Config::read('template')) . 
			'/img/listlast.png">Автор: <span style="color:#587803;">%s</span>, %s:' . "\n" .
			'<p>%s</p>' . "\n" .
			'</div>' . "\n" .
			'</div>' . "\n";
	}
	
	
	public function common($params) {
		$output = '';
		
		if (preg_match($this->marker, $params) == 0) return $params;
		
		$Cache = new Cache;
		$Cache->lifeTime = 600;
		if ($Cache->check('pl_last_comments')) {
			$comments = $Cache->read('pl_last_comments');
			$comments = unserialize($comments);
		} else {
			$sql = "(SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`message`, b.`title`, a.`module`
				FROM `" . $this->DB->getFullTableName('comments') . "` a 
				JOIN `" . $this->DB->getFullTableName('news') . "` b ON b.`id` = a.`entity_id` WHERE a.`module` = 'news')
				UNION (SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`message`, b.`title`, a.`module`
				FROM `" . $this->DB->getFullTableName('comments') . "` a 
				JOIN `" . $this->DB->getFullTableName('stat') . "` b ON b.`id` = a.`entity_id` WHERE a.`module` = 'stat')
				UNION (SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`message`, b.`title`, a.`module`
				FROM `" . $this->DB->getFullTableName('comments') . "` a 
				JOIN `" . $this->DB->getFullTableName('loads') . "` b ON b.`id` = a.`entity_id` WHERE a.`module` = 'loads')
				UNION (SELECT a.`date`, a.`id`, a.`entity_id`, a.`name`, a.`message`, b.`title`, a.`module`
				FROM `" . $this->DB->getFullTableName('comments') . "` a 
				JOIN `" . $this->DB->getFullTableName('foto') . "` b ON b.`id` = a.`entity_id` WHERE a.`module` = 'foto')
				ORDER BY `date` DESC LIMIT " . $this->limit;
			$comments = $this->DB->query($sql);
			$Cache->write(serialize($comments), 'pl_last_comments', array());
		}
		
		if (!empty($comments)) {
			foreach ($comments as $key => $comm) {
				$str = 'к материалу';
				switch ($comm['module']) {
					case 'foto': $str = 'к фотографии'; break;
					case 'loads': $str = 'к загрузке'; break;
					case 'news': $str = 'к новости'; break;
					case 'stat': $str = 'к статье'; break;
				}
				$link = get_link($comm['title'], '/' . $comm['module'] . '/view/' . $comm['entity_id']);
				$output .= sprintf($this->wrap, $comm['name'], $str, $link);
			}
		}
			
		
		return preg_replace($this->marker, $output, $params);
	}

}
