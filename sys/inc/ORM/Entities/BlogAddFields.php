<?php
/*---------------------------------------------\
|                                              |
| @Author:       Alexandr Danilow (modos189)   |
| @Email:        modos189@ya.ru                |
| @Package       CMS Fapos                     |
| @Subpackege    Blog Module                   |
|                                              |
\---------------------------------------------*/



/**
 *
 */
class BlogAddFieldsEntity extends FpsEntity
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