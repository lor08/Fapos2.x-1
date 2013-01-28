<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.2                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    LoadsAddContent Entity        |
| @copyright     ©Andrey Brykin 2010-2013      |
| @last mod      2013/01/28                    |
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
class LoadsAddContentEntity extends FpsEntity
{
	
	protected $id;
	protected $field_id;
	protected $entity_id;
	protected $content;

	
	
	public function save()
	{
		$params = array(
			'entity_id' => $this->entity_id,
			'field_id' => $this->field_id,
			'content' => $this->content,
		);
		if ($this->id) $params['id'] = $this->id;
		$Register = Register::getInstance();
		return ($Register['DB']->save('loads_add_content', $params));
	}
	
	
	
	public function delete()
	{
		$Register = Register::getInstance();
		$Register['DB']->delete('loads_add_content', array('id' => $this->id));
	}

}
