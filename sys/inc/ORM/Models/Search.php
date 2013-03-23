<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.2                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    News Model                    |
| @copyright     ©Andrey Brykin 2010-2012      |
| @last mod      2012/06/04                    |
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
class SearchModel extends FpsModel
{
	public $Table = 'search_index';

    protected $RelatedEntities = array();


    public function truncateTable()
    {
        $this->getDbDriver()->query("TRUNCATE `" . $this->getDbDriver()->getFullTableName('search_index') . "`");
    }
	
	
	public function getSearchResults($search, $limit, $modules)
	{
		$lmsql = '';
		if (is_array($modules)) {
			$lmsql .= '(';
			foreach ($modules as $module) {
				if ($module != $modules[0]) {
					$lmsql .= ' OR ';
				}
				$lmsql .= '`module` = \''.$module.'\'';
			}
			$lmsql .= ') AND';
		}
		$results = $this->getDbDriver()->query("
			SELECT * FROM `" . $this->getDbDriver()->getFullTableName('search_index') . "`
			WHERE ".$lmsql." MATCH (`index`) AGAINST ('" . $search . "' IN BOOLEAN MODE)
			ORDER BY MATCH (`index`) AGAINST ('" . $search . "' IN BOOLEAN MODE) DESC LIMIT " . $limit);
		if ($results) {
			foreach ($results as $key => $res) {
				$results[$key] = new SearchEntity($res);
			}
		}
			
		return $results;
	}

	public function getTitleName($module, $id)
	{
		if ($module!='forum') {
			$res = $this->getDbDriver()->query("SELECT `title` FROM `" . $this->getDbDriver()->getFullTableName($module) . "` WHERE `id` LIKE '".$id."'");
		} else {
			$idtheme = $this->getDbDriver()->query("SELECT `id_theme` FROM `" . $this->getDbDriver()->getFullTableName('posts') . "` WHERE `id` LIKE '".$id."'");
			$res = $this->getDbDriver()->query("SELECT `title` FROM `" . $this->getDbDriver()->getFullTableName('themes') . "` WHERE `id` LIKE '".$idtheme[0]['id_theme']."'");
		}
		return (!empty($res[0]) && !empty($res[0]['title'])) ? (string)$res[0]['title'] : 0;
	}
}