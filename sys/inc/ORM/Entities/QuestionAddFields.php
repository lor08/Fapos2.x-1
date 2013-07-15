<?php
/*---------------------------------------------\
|											   |
| @Author:       Andrey Brykin (Drunya)        |
| @Version:      1.2                           |
| @Project:      CMS                           |
| @package       CMS Fapos                     |
| @subpackege    QuestionAddFields Entity          |
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
class QuestionAddFieldsEntity extends FpsEntity
{
	
	protected $id;
	protected $type;
	protected $name;
	protected $label;
	protected $size;
	protected $params;
	protected $content;



    /**
     * @param $content
     */
	public function setContent($content)
    {
        $this->content = $content;
    }



    /**
     * @return array
     */
    public function getContent()
   	{

        $this->checkProperty('content');
   		return $this->content;
   	}
}