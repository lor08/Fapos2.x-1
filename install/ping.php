<?php
@ini_set('display_errors', 0);
@ini_set('default_socket_timeout', 5);

if (empty($_GET['type'])) $_GET['type'] = true;

if ($_GET['type'] === true) {
	checkRequest();
} else {
	checkUpdate();
}


function checkUpdate() {
	@$b = file_get_contents('http://home.develdo.com/cdn/versions.txt');
	@$m = file_get_contents('http://fapos.modostroi.ru/last.php?host=' . $_SERVER['HTTP_HOST']);
	if ($b || $m) {
		if ($m && preg_match('#[^></]+#i', $m)) {
			echo '<a href="https://github.com/modos189/Fapos2.x/">Последняя модифицированная версия ' . trim($m) . '</a>';
		}
		if ($b && preg_match('#[^></]+#i', $b)) {
			if ($w) echo '<br />';
			echo '<a href="http://home.develdo.com/downloads.php">Последняя официальная версия ' . trim($b) . '</a>';
		}
	} else {
		echo 'Не удалось узнать';
	}
}

function checkRequest() {
	@$b = file_get_contents('http://home.develdo.com/check.php?v=2.2RC1&d=' . $_SERVER['HTTP_HOST']);
	@$w = file_get_contents('http://fapos.modostroi.ru/last.php?host=' . $_SERVER['HTTP_HOST']);
}
?>
