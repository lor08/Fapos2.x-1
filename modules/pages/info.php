<?php


$menuInfo = array(
    'url' => 'settings.php?m=pages',
    'ankor' => 'Страницы',
	'sub' => array(
        'settings.php?m=pages' => 'Настройки',
        'page.php' => 'Управление страницами',
	),
);



$settingsInfo = array(
	'Изображения' => 'Изображения',
	'use_preview' => array(
		'type' => 'checkbox',
		'title' => 'Использовать эскизы инображений',
		'description' => 'Возможность автоматического создания эскизов для больших изображений.',
		'value' => '1',
		'checked' => '1',
	),
	'img_size_x' => array(
		'type' => 'text',
		'title' => 'Ширина эскиза',
		'description' => 'Максимально допустимый размер эскиза по горизонтали.',
		'help' => 'px',
	),
	'img_size_y' => array(
		'type' => 'text',
		'title' => 'Высота эскиза',
		'description' => 'Максимально допустимый размер эскиза по вертикали.',
		'help' => 'px',
	),
	
	'Прочее' => 'Прочее',
    'active' => array(
        'type' => 'checkbox',
        'title' => 'Статус',
        'checked' => '1',
        'value' => '1',
        'description' => '(Активирован/Деактивирован)',
    ),
);