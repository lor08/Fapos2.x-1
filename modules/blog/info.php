<?php


$menuInfo = array(
	'url' => 'settings.php?m=blog',
	'ankor' => 'Блог',
	'sub' => array(
		'settings.php?m=blog' => 'Настройки',
		'design.php?m=blog' => 'Дизайн',
		'category.php?mod=blog' => 'Управление категориями',
		'additional_fields.php?m=blog' => 'Дополнительные поля',
	),
);




$settingsInfo = array(
	'title' => array(
		'type' => 'text',
		'title' => 'Заголовок',
	),
	'description' => array(
		'type' => 'text',
		'title' => 'Описание',
	),

	'max_lenght' => array(
		'type' => 'text',
		'title' => 'Максимальная длина описания',
	),
	'announce_lenght' => array(
		'type' => 'text',
		'title' => 'Длина анонса',
	),
	'per_page' => array(
		'type' => 'text',
		'title' => 'Материалов на странице',
	),

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
	'max_attaches_size' => array(
		'type' => 'text',
		'title' => 'Максимальный "вес"',
		'description' => 'Определяет максимальный возможный размер изображения в килобайтах.',
		'help' => 'КиБ',
		'onview' => array(
			'division' => 1024,
		),
		'onsave' => array(
			'multiply' => 1024,
		),
	),
	'max_attaches' => array(
		'type' => 'text',
		'title' => 'Максимальное количество вложений',
	),

	'fields_cat' => array(
		'type' => 'checkbox',
		'title' => 'Категория',
		'attr' => array(
			'disabled' => 'disabled',
			'checked' => 'checked',
		),
	),
	'fields_title' => array(
		'type' => 'checkbox',
		'title' => 'Заголовок',
		'attr' => array(
			'disabled' => 'disabled',
			'checked' => 'checked',
		),
	),
	'fields_main' => array(
		'type' => 'checkbox',
		'title' => 'Текст материала',
		'attr' => array(
			'disabled' => 'disabled',
			'checked' => 'checked',
		),
	),
	'sub_description' => array(
		'type' => 'checkbox',
		'title' => 'Краткое описание',
		'value' => 'description',
		'fields' => 'fields',
		'checked' => '1',
	),
	'sub_tags' => array(
		'type' => 'checkbox',
		'title' => 'Теги',
		'value' => 'tags',
		'fields' => 'fields',
		'checked' => '1',
	),

	'comment_active' => array(
		'type' => 'checkbox',
		'title' => 'Разрешить использование комментариев',
		'description' => '',
        'value' => '1',
        'checked' => '1',
	),
	'comment_per_page' => array(
		'type' => 'text',
		'title' => 'Комментариев на странице',
		'description' => '',
		'help' => '',
	),
    'comment_lenght' => array(
        'type' => 'text',
        'title' => 'Максимальный размер',
		'description' => '',
		'help' => 'Символов',
    ),
	'comments_order' => array(
		'type' => 'checkbox',
		'title' => 'Новые сверху',
		'description' => '',
        'value' => '1',
        'checked' => '1',
	),

	'active' => array(
		'type' => 'checkbox',
		'title' => 'Статус',
		'checked' => '1',
		'value' => '1',
		'description' => '(Активирован/Деактивирован)',
	),
);