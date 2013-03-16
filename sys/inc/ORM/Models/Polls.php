<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.0                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    Polls Model                   |
| @copyright     ©Andrey Brykin 2010-2013      |
| @last mod      2013/01/24                    |
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
class PollsModel extends FpsModel
{
	public $Table = 'polls';

    protected $RelatedEntities;


	public function add($params)
	{
		if (isset($params['id'])) unset($params['id']);
		$id = $this->getDbDriver()->save($this->Table, $params);
		return !empty($id) ? $id : false;
	}
	
	
	public function delete($id)
	{
		$entity = $this->getById($id);
		if (!empty($entity)) {
			$this->getDbDriver()->delete($this->Table, array("`id` = '" . $id . "'"));
			return true;
		}
		throw new Exception('Entity not found');
	}
}