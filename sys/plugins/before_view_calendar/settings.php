<?php
/*-----------------------------------------------\
|                                                |
|  @Author:       Alexander Verenik (Wasja)      |
|  @Version:      0.1                            |
|  @Last mod.     2013/05/19                     |
|                                                |
\-----------------------------------------------*/

$markers = array(
	'day' => array(
		'title' => 'Число',
		'length' => 2,
		'min' => 1,
		'max' => 31,
	),
	'month' => array(
		'title' => 'Месяц',
		'length' => 2,
		'min' => 1,
		'max' => 12,
	),
	'year' => array(
		'title' => 'Год',
		'length' => 4,
		'min' => date("Y"),
		'max' => date("Y") + 10,
	),
	'hour' => array(
		'title' => 'Час',
		'length' => 2,
		'min' => 0,
		'max' => 23,
	),
	'minute' => array(
		'title' => 'Минута',
		'length' => 2,
		'min' => 0,
		'max' => 59,
	),
);

$days = array(
	'1' => 'Пн.',
	'2' => 'Вт.',
	'3' => 'Ср.',
	'4' => 'Чт.',
	'5' => 'Пт.',
	'6' => 'Сб.',
	'0' => 'Вс.',
);

$calendar_set = array();

include 'config.php';

if (isset($_POST['ac'])) {
	$calendar = array('text' => isset($_POST['text']) ? trim($_POST['text']) : '');
	if (isset($_POST['wday'])) {
		$wdays = array();
		foreach ($days as $wday => $value) {
			if (isset($_POST['wday_' . $wday])) {
				$wdays[] = intval($wday);
			}
		}
		if (count($wdays)) {
			$calendar['wday'] = implode(',', $wdays);
		}
	}
	if (isset($_POST['dday'])) {
		$calendar['period'] = (isset($_POST['period']) && $_POST['period'] ? true : false);
		foreach ($markers as $marker => $value) {
			if (isset($_POST['active_' . $marker])) {
				if (isset($_POST[$marker])) {
					$calendar[$marker] = intval(trim($_POST[$marker]));
				}
				if (isset($_POST['period']) && isset($_POST[$marker . '_2'])) {
					$calendar[$marker . '_2'] = intval(trim($_POST[$marker . '_2']));
				}
			}
		}
		if (isset($calendar['year_2']) && intval($calendar['year_2']) < intval($calendar['year']))
			$calendar['year_2'] = intval($calendar['year']);
	}

	switch (strtolower(trim($_POST['ac']))) {
		case 'set':
			$number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
			if (isset($number) && $calendar_set && is_array($calendar_set) && isset($calendar_set[$number])) {
				$active = (isset($calendar_set[$number]['active'])) ? $calendar_set[$number]['active'] : false;
				$calendar_set[$number]['active'] = !$active;
			}
			break;
		case 'new':
			$calendar['active'] = true;
			$calendar_set[] = $calendar;
			break;
		case 'edit':
			$number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
			if (isset($number)) {
				if (isset($calendar_set[$number]))
					$calendar['active'] = $calendar_set[$number]['active'];
				$calendar_set[$number] = $calendar;
			} else {
				$calendar_set[] = $calendar;
			}
			break;
		case 'del':
			$number = isset($_POST['number']) ? intval(trim($_POST['number'])) : null;
			if (isset($number)) {
				unset($calendar_set[$number]);
			}
			break;
		default:
	}
	$fopen = @fopen(dirname(__FILE__) . '/config.php', 'w');
	if ($fopen) {
		fputs($fopen, '<?php ' . "\n" . '$calendar_set = ' . var_export($calendar_set, true) . ";\n" . '?>');
		fclose($fopen);
	}
}

$popups_content = '<div class="popup" id="addCalendar">
	<div class="top">
		<div class="title">Добавление события</div>
		<div class="close" onclick="closePopup(\'addCalendar\'); resetPopup(\'addCalendar\');"></div>
	</div>
	<form method="POST" action="">
	<input type="hidden" name="ac" value="new" />
	<div class="items">';
$popups_content .= '	<input type="checkbox" name="wday" value="1" onclick="checkBlock(\'addCalendar\', \'wday\');" />День недели</label><br />
	<table class="wday_block" cellspacing="0" cellpadding="0" style="display:none;"><tr>';
foreach ($days as $wday => $value) {
	$popups_content .= '<td width="50"><div class="item">
		<label><input type="checkbox" name="wday_' . $wday . '" value="1" />' . $value . '</label><br />';
	$popups_content .= '<div class="clear"></div></div></td>';
}
$popups_content .= '</tr></table><hr />';
$popups_content .= '	<input type="checkbox" name="dday" value="1" onclick="checkBlock(\'addCalendar\', \'dday\');" />Дата или период</label><br />
	<div class="dday_block" style="display:none;">';
$popups_content .= '	<input type="checkbox" name="period" value="1" onclick="checkBlock(\'addCalendar\', \'period\');" />Период</label><br />
	<table cellspacing="0" cellpadding="0"><tr>';
foreach ($markers as $marker => $value) {
	$popups_content .= '<td width="75"><div class="item">
		<label><input type="checkbox" name="active_' . $marker . '" value="1" onclick="checkBlock(\'addCalendar\', \'active_' . $marker . '\');" />' . $value['title'] . '</label><br />';
	$popups_content .= '<div class="active_' . $marker . '_block" style="display:none;"><select name="' . $marker . '" style="width:70px">';
	for ($i = $value['min']; $i <= $value['max']; $i++) {
		$popups_content .= '<option value="' . $i . '">' . sprintf('%0' . $value['length'] . 'd', $i) . '</option>';
	}
	$popups_content .= '</select>';
	$popups_content .= '<div class="period_block" style="display:none;"><select name="' . $marker . '_2" style="width:70px">';
	for ($i = $value['min']; $i <= $value['max']; $i++) {
		$popups_content .= '<option value="' . $i . '">' . sprintf('%0' . $value['length'] . 'd', $i) . '</option>';
	}
	$popups_content .= '</select>';
	$popups_content .= '</div>';
	$popups_content .= '</div>';
	$popups_content .= '<div class="clear"></div></div></td>';
}
$popups_content .= '</tr></table></div><hr />
		<div class="item">Сообщение:<br />
			<textarea name="text" cols="30" rows="3" style="width:95%" /></textarea>
		<div class="clear"></div></div>
		<div class="item submit">
			<div class="left"></div>
			<div style="float:left;" class="right">
				<input type="submit" class="save-button" name="send" value="Сохранить">
			</div>
			<div class="clear"></div>
		</div>
	</div>
	</form>
</div>';

$output = '<script type="text/javascript">
function checkBlock(popupName, chbName) {
	var popup = $(\'#\' + popupName);
	if (popup.find(\'input[name="\' + chbName + \'"]\').is(\':checked\')) popup.find(\'.\' + chbName + \'_block\').show(); else popup.find(\'.\' + chbName + \'_block\').hide();
}
function resetPopup(popupName) {
	$(\'#\' + popupName).find(\'form\').each(function() {
		this.reset();
	});
	checkBlock(popupName, "wday"); checkBlock(popupName, "dday"); checkBlock(popupName, "period");';
foreach ($markers as $marker => $value) {
	$output .= ' checkBlock(popupName, "active_' . $marker . '");';
}
$output .= '}
</script>';
$output .= '
	<div class="list">
		<div class="title">Управление событиями</div>
		<div onclick="openPopup(\'addCalendar\');" class="add-cat-butt"><div class="add"></div>Добавить событие</div>';

if ($calendar_set && is_array($calendar_set) && count($calendar_set)) {
	$output .= '
		<form name="deleteCalendar" action="" method="POST">
			<input type="hidden" name="ac" value="del" />
			<input type="hidden" name="number" value="" />
		</form>
		<form name="setCalendar" action="" method="POST">
			<input type="hidden" name="ac" value="set" />
			<input type="hidden" name="number" value="" />
		</form>
		<div class="level1">
			<div class="items">';
	foreach ($calendar_set as $index => $calendar) {
		$wday_a = array();
		if (isset($calendar['wday'])) {
			$wdays = explode(',', $calendar['wday']);
			foreach ($days as $wday => $value) {
				if (in_array($wday, $wdays)) {
					$wday_a[] = $value;
				}
			}
		}
		foreach ($markers as $marker => $value) {
			$$marker = isset($calendar[$marker]) ? sprintf('%0' . $value['length'] . 'd', $calendar[$marker]) : '*';
		}
		$date = (count($wday_a) ? ('[' . implode(', ', $wday_a) . '] ') : '') . sprintf('%s.%s.%s %s:%s', $day, $month, $year, $hour, $minute);
		if (isset($calendar['period']) && $calendar['period']) {
			foreach ($markers as $marker => $value) {
				$$marker = isset($calendar[$marker . '_2']) ? sprintf('%0' . $value['length'] . 'd', $calendar[$marker . '_2']) : '*';
			}
			$date .= sprintf(' - %s.%s.%s %s:%s', $day, $month, $year, $hour, $minute);
		}

		$output .= '<div class="level2">
					<div class="title">{{ calendar_' . $index . ' }}</div>
					<div class="title">' . $date . '</div>
					<div class="buttons">
						<a class="edit" onclick="openPopup(\'editCalendar' . $index . '\')" href="javascript://"></a>
						<a class="' . ((isset($calendar['active']) && $calendar['active']) ? 'on' : 'off') . '" onclick="document.forms[\'setCalendar\'].number.value=' . $index . ';document.forms[\'setCalendar\'].submit();" href="javascript://"></a>
						<a class="delete" onclick="if (_confirm()) {document.forms[\'deleteCalendar\'].number.value=' . $index . ';document.forms[\'deleteCalendar\'].submit();};" href="javascript://"></a>
					</div>
				</div>';

		$popups_content .= '<div class="popup" id="editCalendar' . $index . '">
	<div class="top">
		<div class="title">Настройка события</div>
		<div class="close" onclick="closePopup(\'editCalendar' . $index . '\'); resetPopup(\'editCalendar' . $index . '\');"></div>
	</div>
	<form method="POST" action="">
	<input type="hidden" name="ac" value="edit" />
	<input type="hidden" name="number" value="' . $index . '" />
	<div class="items">';

		$wday_a = array();
		if (isset($calendar['wday'])) {
			$wdays = explode(',', $calendar['wday']);
			foreach ($days as $wday => $value) {
				if (in_array($wday, $wdays)) {
					$wday_a[] = $wday;
				}
			}
		}
		$popups_content .= '	<input type="checkbox" name="wday"' . (count($wday_a) ? ' checked="checked"' : '') . ' value="1" onclick="checkBlock(\'editCalendar' . $index . '\', \'wday\');" />День недели</label><br />
	<table class="wday_block" cellspacing="0" cellpadding="0"' . (!count($wday_a) ? ' style="display:none;"' : '') . '><tr>';
		foreach ($days as $wday => $value) {
			$popups_content .= '<td width="50"><div class="item">
		<label><input type="checkbox" name="wday_' . $wday . '" value="1"' . (in_array($wday, $wday_a) ? ' checked="checked"' : '') . ' />' . $value . '</label><br />';
			$popups_content .= '<div class="clear"></div></div></td>';
		}
		$popups_content .= '</tr></table><hr />';

		$dday = isset($calendar['period']);
		foreach ($markers as $marker => $value) {
			$dday |= isset($calendar[$marker]) || isset($calendar[$marker . '_2']);
		}
		$popups_content .= '	<input type="checkbox" name="dday"' . ($dday ? ' checked="checked"' : '') . ' value="1" onclick="checkBlock(\'editCalendar' . $index . '\', \'dday\');" />Дата или период</label><br />
	<div class="dday_block"' . (!$dday ? ' style="display:none;"' : '') . '>';
		$period = isset($calendar['period']) && $calendar['period'];
		$popups_content .= '	<input type="checkbox" name="period"' . ($period ? ' checked="checked"' : '') . ' value="1" onclick="checkBlock(\'editCalendar' . $index . '\', \'period\');" />Период</label><br />
	<table cellspacing="0" cellpadding="0"><tr>';
		foreach ($markers as $marker => $value) {
			$popups_content .= '<td width="75"><div class="item">
		<label><input type="checkbox" name="active_' . $marker . '"' . (isset($calendar[$marker]) ? ' checked="checked"' : '') . ' value="1" onclick="checkBlock(\'editCalendar' . $index . '\', \'active_' . $marker . '\');" />' . $value['title'] . '</label><br />';
			$popups_content .= '<div class="active_' . $marker . '_block"' . (!isset($calendar[$marker]) ? ' style="display:none;"' : '') . '><select name="' . $marker . '" style="width:70px">';
			for ($i = $value['min']; $i <= $value['max']; $i++) {
				$popups_content .= '<option value="' . $i . '"' . (isset($calendar[$marker]) && $calendar[$marker] == $i ? ' selected="selected"' : '') . '>' . sprintf('%0' . $value['length'] . 'd', $i) . '</option>';
			}
			$popups_content .= '</select>';
			$popups_content .= '<div class="period_block"' . (!$period ? ' style="display:none;"' : '') . '><select name="' . $marker . '_2" style="width:70px">';
			for ($i = $value['min']; $i <= $value['max']; $i++) {
				$popups_content .= '<option value="' . $i . '"' . (isset($calendar[$marker . '_2']) && $calendar[$marker . '_2'] == $i ? ' selected="selected"' : '') . '>' . sprintf('%0' . $value['length'] . 'd', $i) . '</option>';
			}
			$popups_content .= '</select>';
			$popups_content .= '</div>';
			$popups_content .= '</div>';
			$popups_content .= '<div class="clear"></div></div></td>';
		}
		$popups_content .= '</tr></table></div><hr />
		<div class="item">Сообщение:<br />
			<textarea name="text" cols="30" rows="3" style="width:95%" />' . (isset($calendar['text']) ? htmlspecialchars($calendar['text']) : '') . '</textarea>
		<div class="clear"></div></div>
		<div class="item submit">
			<div class="left"></div>
			<div style="float:left;" class="right">
				<input type="submit" class="save-button" name="send" value="Сохранить">
			</div>
			<div class="clear"></div>
		</div>
	</div>
	</form>
</div>';
	}
}
$output .= '</div></div>';
$output = $popups_content . $output;
?>