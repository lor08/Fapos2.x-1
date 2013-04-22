<?php





$menuInfo = array(
    'url' => 'settings.php?m=statistics',
    'ankor' => 'Статистика',
	'sub' => array(
        'settings.php?m=statistics' => 'Настройки',
        'statistic.php' => 'Просмотр статистики',
	),
);







$settingsInfo = array(
    'show_bots' => array(
        'type' => 'checkbox',
        'title' => 'Отображать ботов как пользователей',
        'checked' => '1',
        'value' => '1',
        'description' => 'Позволяет в списке пользователей онлайн отображать поисковых роботов',
    ),
    'active' => array(
        'type' => 'checkbox',
        'title' => 'Статус',
        'checked' => '1',
        'value' => '1',
        'description' => '(Активирован/Деактивирован)',
    ),
);