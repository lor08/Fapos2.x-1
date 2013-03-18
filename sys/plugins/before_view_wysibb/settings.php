<?php

function config_write($set) {
	if ($fopen=@fopen(ROOT . '/sys/plugins/before_view_wysibb/config.php', 'w')) {
		$data = '<?php ' . "\n" . '$conf = ' . var_export($set, true) . "\n" . '?>';
		fputs($fopen, $data);
		fclose($fopen);
	}
}

if (isset($_POST['send'])) {
	$TempSet['style'] = $_POST['style'];
	config_write($TempSet);
}

include ('config.php');

$output = '';

$output .= '<table class="settings-tb">';
$output .= '<form action="" method="post">';

$output .= '<tr>';
$output .= '	<td class="left">Цветовая схема редактора:<br>';
$output .= '        <small>white или black</small></td>';
$output .= '	<td>';
$output .= '			<input type="text" size="100" name="style" value="' . $conf['style'] . '">';
$output .= '	</td>';
$output .= '</tr>';

$output .= '<tr>';
$output .= '	<td colspan="2" align="center">';
$output .= '		<input name="send" type="submit" value="Записать">';
$output .= '	</td>';
$output .= '</tr>';

$output .= '</form>';
$output .= '</table>';

?>