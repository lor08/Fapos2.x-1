<?php


class UsersRating {
	
	// How match comments to view
	private $limit = 5;
	
	// Wraper for comments
	private $wrap;
	
	// Marker for plugin
	private $marker = '#{{\s*users_rating\s*}}#i';
	
	
	private $DB;

	public function __construct($params) {
		$Register = Register::getInstance();
		$this->DB = $Register['DB'];
		$this->wrap = '<div class="etopu">' . "\n" .
						'<img alt="" src="%s">' . "\n" .
						'<div class="ttop">' . "\n" .
							'%s<br>' . "\n" .
							'Репутация: %d<br>' . "\n" .
							'Сообщений: %d<br>' . "\n" .
						'</div>' . "\n" .
					'</div>' . "\n";
	}
	
	
	public function common($params) {
		$output = '';
		
		if (preg_match($this->marker, $params) == 0) return $params;
		
		$Cache = new Cache;
		$Cache->lifeTime = 600;
		if ($Cache->check('pl_users_rating')) {
			$users = $Cache->read('pl_users_rating');
			$users = unserialize($users);
		} else {
			$users = $this->DB->select('users', DB_ALL, array(
				'order' => '`rating` DESC',
				'limit' => $this->limit,
			));
			//$users = $this->DB->query($sql);
			$Cache->write(serialize($users), 'pl_users_rating', array());
		}
		
		if (!empty($users)) {
			foreach ($users as $key => $user) {
				$link = get_link($user['name'], '/users/info/' . $user['id']);
				$ava = getAvatar($user['id']);
				
				$output .= sprintf($this->wrap, $ava, $link, $user['rating'], $user['posts']);
			}
		}
		$output .= 	'<div class="etopu">' . get_link('Весь рейтинг', '/users/index?order=rating') . '</div>';	
			
			
		return preg_replace($this->marker, $output, $params);
	}

}
