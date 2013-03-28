<?php

function cmpTitle($a, $b) {
	$a = isset($a['title']) ? $a['title'] : '';
	$b = isset($b['title']) ? $b['title'] : '';
    if ($a === $b) return 0;
    return ($a < $b) ? -1 : 1;
}

$markers = array(
	'editor_head' => 'HTML-код, добавляемый в &lt;HEAD&gt;',
	'editor_body' => 'HTML-код, добавляемый в &lt;BODY&gt;',
	'editor_buttons' => 'HTML-код панели кнопок',
	'editor_text' => 'HTML-код поля ввода',
	'editor_forum_text' => 'HTML-код поля ввода для форума',
	'editor_forum_quote' => 'HTML-код кнопки "цитировать" для форума',
	'editor_forum_name' => 'HTML-код кнопки добавления имени пользователя',
);

$editor_set = array();

include 'config.php';

if (isset($_POST['ac'])) {
	$editor = array('title' => isset($_POST['title']) ? trim($_POST['title']) : '');
	foreach ($markers as $marker => $value) {
		$editor[$marker] = isset($_POST[$marker]) ? trim($_POST[$marker]) : '';
	}
	
	switch (strtolower(trim($_POST['ac']))) {
		case 'set':
			$number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
			if (isset($number) && $editor_set && is_array($editor_set)) {
				foreach ($editor_set as $index => $editor) {
					$editor['default'] = ($index == $number);
					$editor_set[$index] = $editor;
				}
			}
			break;
		case 'new':
			$editor_set[] = $editor;
			break;
		case 'edit':
			$number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
			if (isset($number)) {
				$editor_set[$number] = $editor;
			} else {
				$editor_set[] = $editor;
			}
			break;
		case 'del':
			$number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
			if (isset($number)) {
				unset($editor_set[$number]);
			}
			break;
		default:
	}
	usort($editor_set, "cmpTitle");
	$fopen = @fopen(dirname(__FILE__) . '/config.php', 'w');
	if ($fopen) {
		fputs($fopen, '<?php ' . "\n" . '$editor_set = ' . var_export($editor_set, true) . ";\n" . '?>');
		fclose($fopen);
	}
}

$output = '';

$output .= '<table width="100%"><tr>
	<td><input type="button" name="add" value="Добавить редактор" onClick="wiOpen(\'new\');" /></td>
	<td align="right"><div align="right" class="topButtonL" id="cat_view"><input type="button" name="set" value="Выбрать редактор" onClick="wiOpen(\'set\');" /></div></td>
	</tr></table>';

$output .= '<div id="new_dWin" class="fps-win" style="position:absolute;top:100px;left:30%;width:40%;display:none">
	<div class="xw-tl"><div class="xw-tr"><div class="xw-tc xw-tsps"></div>
	</div></div><div class="xw-ml"><div class="xw-mr"><div align="center" class="xw-mc">
	<form action="" method="POST">

	<div class="form-item2">
	' . __('Title') . ':<br />
	<input type="text" name="title" style="width:95%" />
	<input type="hidden" name="ac" value="new" />
	<div style="clear:both;"></div></div>';

foreach ($markers as $marker => $value) {
	$output .= '<div class="form-item2">
		Маркер {{ ' . $marker . ' }} - 
		' . $value . ':<br />';
	$output .= ($marker == 'editor_text' || $marker == 'editor_forum_text' || $marker == 'editor_forum_quote' || $marker == 'editor_forum_name') ? 
		'<input type="text" name="' . $marker . '" style="width:95%" />' :
		'<textarea name="' . $marker . '" cols="30" rows="3" style="width:95%" /></textarea>';
	$output .= '<br /><div style="clear:both;"></div></div>';
}

$output .= '<div class="form-item2">
	<input type="submit" name="send" value="' . __('Save') . '" />
	<input type="button" onClick="hideWin(\'new\')" value="' . __('Cancel') . '" />
	<div style="clear:both;"></div></div>
	</form>
	</div></div></div><div class="xw-bl"><div class="xw-br"><div class="xw-bc">
	<div class="xw-footer"></div></div></div></div>
	</div>';

if ($editor_set && is_array($editor_set) && count($editor_set)) {
	$output .= '<div id="set_dWin" class="fps-win" style="position:absolute;top:200px;left:40%;display:none">
		<div class="xw-tl"><div class="xw-tr"><div class="xw-tc xw-tsps"></div>
		</div></div><div class="xw-ml"><div class="xw-mr"><div align="center" class="xw-mc">
		<form action="" method="POST">
		<div class="form-item2">
		Выберите редактор:<br />';

	$output .= '<select style="width:95%;" name="number">';
	foreach ($editor_set as $index => $editor) {
		$output .= '<option value="' . $index . '"' . ((isset($editor['default']) && $editor['default']) ? 'selected="selected"' : '') . '>' . h($editor['title']) . '</option>';
	}
	$output .= '</select>';

	$output .= '<input type="hidden" name="ac" value="set" />
		<div style="clear:both;"></div></div>';

	$output .= '<div class="form-item2">
		<input type="submit" name="send" value="' . __('Save') . '" />
		<input type="button" onClick="hideWin(\'set\')" value="' . __('Cancel') . '" />
		<div style="clear:both;"></div></div>
		</form>
		</div></div></div><div class="xw-bl"><div class="xw-br"><div class="xw-bc">
		<div class="xw-footer"></div></div></div></div>
		</div>';


	$output .= '<div class="cat_list_container">';
	foreach ($editor_set as $index => $editor) {
		$output .= '
			<div class="category_row">
			<b>' . (isset($editor['title']) ? $editor['title'] : 'Редактор ' . $index) . '</b>
			<div class="tools">
			<form action="" method="POST">
			<a href="javascript://" onClick="wiOpen(\'' . $index . '_editor\')"><img src="template/img/edit_16x16.png"  /></a>
			<input type="hidden" name="ac" value="del" />
			<input type="hidden" name="number" value="' . $index . '" />
			<input type="image" src="template/img/del.png" onClick="return _confirm();" />
			</form>';
		$output .= '		
			<div id="' . $index . '_editor_dWin" class="fps-win" style="position:absolute;top:100px;left:30%;width:40%;display:none">
			<div class="xw-tl"><div class="xw-tr"><div class="xw-tc xw-tsps"></div>
			</div></div><div class="xw-ml"><div class="xw-mr"><div align="center" class="xw-mc">
			<form action="" method="POST">

			<div class="form-item2">
			' . __('Title') . ':<br />
			<input type="text" name="title" value="' . (isset($editor['title']) ? htmlspecialchars($editor['title']) : '') . '" style="width:95%" />
			<input type="hidden" name="ac" value="edit" />
			<input type="hidden" name="number" value="' . $index . '" />
			<div style="clear:both;"></div></div>';

		foreach ($markers as $marker => $value) {
			$output .= '<div class="form-item2">
				Маркер {{ ' . $marker . ' }} - 
				' . $value . ':<br />';
			$output .= ($marker == 'editor_text' || $marker == 'editor_forum_text' || $marker == 'editor_forum_quote' || $marker == 'editor_forum_name') ? 
				'<input type="text" name="' . $marker . '" style="width:95%" value="' . (isset($editor[$marker]) ? htmlspecialchars($editor[$marker]) : '') . '" />' :
				'<textarea name="' . $marker . '" cols="30" rows="3" style="width:95%" />' . (isset($editor[$marker]) ? htmlspecialchars($editor[$marker]) : '') . '</textarea>';
			$output .= '<br /><div style="clear:both;"></div></div>';
		}

		$output .= '<div class="form-item2 center">
			<input type="submit" name="send" value="' . __('Save') . '" />
			<input type="button" onClick="hideWin(\'' . $index . '_editor\')" value="' . __('Cancel') . '" />
			<div style="clear:both;"></div></div>
			</form>
			</div></div></div><div class="xw-bl"><div class="xw-br"><div class="xw-bc">
			<div class="xw-footer"></div></div></div></div>
			</div>
			</div></div>';
	}
	$output .= '</div>';
}
?>