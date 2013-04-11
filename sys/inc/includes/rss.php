<?php

$cache_key = $this->module . '_rss';
$cache_tags = array(
	'module_' . $this->module,
	'action_rss',
);


$check = Config::read('rss_' . $this->module, 'common');
if (!$check) {
	$html = '<?xml version="1.0" encoding="UTF-8"?>';
	$html .= '<rss version="2.0" />';
} else {
	if ($this->cached && $this->Cache->check($cache_key)) {
		$html = $this->Cache->read($cache_key);
	} else {
		$sitename = '';
		if (!empty($_SERVER['SERVER_NAME'])) {
			$sitename = 'http://' . $_SERVER['SERVER_NAME'] . '';
		}

		$html = '<?xml version="1.0" encoding="UTF-8"?>';
		$html .= '<rss version="2.0">';		
		$html .= '<channel>';
		$html .= '<title>' . h(Config::read('title', $this->module)) . '</title>';
		$html .= '<link>' . $sitename . get_url($this->getModuleURL()) . '</link>';
		$html .= '<description>' . h(Config::read('description', $this->module)) . '</description>';
		$html .= '<pubDate>' . date('r') . '</pubDate>';
		$html .= '<generator>FPS RSS Generator (Fapos CMS)</generator>';

		if ($this->module == 'forum') {
			$themeModel = $this->Register['ModManager']->getModelInstance('Themes');
			$themeModel->bindModel('forum');
			$themeModel->bindModel('author');
			$themeModel->bindModel('last_author');
			$records = $themeModel->getCollection(array(), 
				array(
					'limit' => Config::read('rss_cnt', 'common'),
					'order' => 'last_post DESC',
				)
			);
		} else {
			$where = array();
			$this->Model->bindModel('category');
			$this->Model->bindModel('author');
			if ($this->module != 'foto') {
				$this->Model->bindModel('attaches');
				$where['available'] = '1';
			}
			$records = $this->Model->getCollection($where, array(
					'limit' => Config::read('rss_cnt', 'common'),
					'order' => 'date DESC',
				)
			);
		}

		if (!empty($records) && is_array($records) && count($records)) {
			if ($this->module == 'forum') {
				$last_date = $records[0]->getLast_post();
			} else {
				$last_date = $records[0]->getDate();
			}
			$html .= '<lastBuildDate>' . date('r', strtotime($last_date)) . '</lastBuildDate>';
			foreach ($records as $record) { 
				$announce = '';
				if ($this->module == 'forum') {
					if ($record->getForum() != null) {
						$announce = 'Форум: <a href="' . $sitename . get_url($this->module . '/view_forum/' . $record->getForum()->getId()) . '">' . h($record->getForum()->getTitle()) . '</a><br />';
						$category = $record->getForum()->getTitle();
					}
					if ($record->getAuthor() != null) {
						$announce .= 'Автор темы: ' . $record->getAuthor()->getName() . '<br />';
					}
					if ($record->getLast_author() != null) {
						$announce .= 'Автор сообщения: ' . $record->getLast_author()->getName() . '<br />';
					}
					$announce .= 'Количество ответов: ' . $record->getPosts();
					$record_date = $record->getLast_post();
					$url = get_url($this->module . '/view_theme/' . $record->getId());
				} else {
					if ($this->module == 'foto') {
						$announce = '<img src="' . $this->getFilesPath('preview/' . $record->getFilename()) . '" />';
					} else {
						$announce = $this->Textarier->getAnnounce($record->getMain(), null, 0, Config::read('rss_lenght', 'common'), $record);
						$atattaches = ($record->getAttaches() && count($record->getAttaches())) ? $record->getAttaches() : array();
						if (count($atattaches) > 0) {
							foreach ($atattaches as $attach) {
								if ($attach->getIs_image() == '1') {
									$announce = $this->insertImageAttach($announce, $attach->getFilename(), $attach->getAttach_number(), $record->getSkey());
								}
							}
						}
					}
					$announce .= '<br />';
					if ($record->getAuthor() != null) {
						$announce .= 'Автор: ' . $record->getAuthor()->getName() . '<br />';
					}
					$record_date = $record->getDate();
					if ($record->getCategory() != null) {
						$category = $record->getCategory()->getTitle();
					}
					$url = get_url(entryUrl($record, $this->module));
				}
				$html .= '<item>';
				$html .= '<link>' . $sitename . $url . '</link>';
				$html .= '<pubDate>' . date('r', strtotime($record_date)) . '</pubDate>';
				$html .= '<title>' . $record->getTitle() . '</title>';
				$html .= '<description><![CDATA[' . $announce . ']]></description>';
				if (!empty($category)) {
					$html .= '<category>' . $category . '</category>';
				}
				$html .= '<guid>' . $sitename . $url . '</guid>';
				$html .= '</item>';
			}
		}
		$html .= '</channel>';
		$html .= '</rss>';

		$this->Cache->write($html, $cache_key, $cache_tags);
	}
}

echo $html; 

?>