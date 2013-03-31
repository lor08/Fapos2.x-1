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
class BlogAddFieldsModel extends FpsModel
{
	
    public $Table = 'blog_add_fields';

	
    protected $RelatedEntities = array(
        'content' => array(
            'model' => 'BlogAddContent',
            'type' => 'has_many',
            'foreignKey' => 'field_id',
      	),
    );
}