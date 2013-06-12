<?php
/*-----------------------------------------------\
|                                                |
|  @Author:       Alexander Verenik (Wasja)      |
|  @Version:      0.1                            |
|  @Last mod.     2013/05/19                     |
|                                                |
\-----------------------------------------------*/

class Calendar {

	private function check($stack, $calendar, $current, $cmp = 0, $left = true) {
		if (count($stack)) {
			$name = array_shift($stack);
			$item = intval($calendar[$name]);
			$item_2 = isset($calendar[$name . '_2']) ? intval($calendar[$name . '_2']) : $item;
			
			if ($cmp == 0) {
				if ($item > $item_2) { 
					if ($current[$name] < $item && $current[$name] > $item_2) return false;
					elseif ($current[$name] == $item) return $this->check($stack, $calendar, $current, -1, true);
					elseif ($current[$name] == $item_2) return $this->check($stack, $calendar, $current, -1, false);
				} elseif ($item < $item_2) {
					if (($current[$name] < $item || $current[$name] > $item_2)) return false;
					elseif ($current[$name] == $item) return $this->check($stack, $calendar, $current, 1, true);
					elseif ($current[$name] == $item_2) return $this->check($stack, $calendar, $current, 1, false);
				} elseif ($item == $item_2) {
					if ($current[$name] != $item) return false;
					else return $this->check($stack, $calendar, $current, 0);
				}
			} elseif ($cmp < 0) {
				if ($left && $current[$name] > $item) return false;
				elseif (!$left && $current[$name] < $item_2) return false;
				elseif ($current[$name] == $item) return $this->check($stack, $calendar, $current, -1, true);
				elseif ($current[$name] == $item_2) return $this->check($stack, $calendar, $current, -1, false);
			} elseif ($cmp > 0) {
				if ($left && $current[$name] < $item) return false;
				elseif (!$left && $current[$name] > $item_2) return false;
				elseif ($current[$name] == $item) return $this->check($stack, $calendar, $current, 1, true);
				elseif ($current[$name] == $item_2) return $this->check($stack, $calendar, $current, 1, false);
			}
		}
		return true;
	}

	public function common($params) {
		if (strpos($params, 'calendar_')) {
			$calendar_set = array();

			include 'config.php';

			$current = array();
			$current['wday'] = intval(date('w'));
			$current['day'] = intval(date('j'));
			$current['month'] = intval(date('n'));
			$current['year'] = intval(date('Y'));
			$current['hour'] = intval(date('G'));
			$current['minute'] = intval(date('i'));

			$items = array('year', 'month', 'day', 'hour', 'minute');
			if ($calendar_set && is_array($calendar_set) && count($calendar_set)) {
				foreach ($calendar_set as $index => $calendar) {
					if (isset($calendar['active']) && $calendar['active']) {
						$wday_ok = true;
						if (isset($calendar['wday'])) {
							$wdays = explode(',', $calendar['wday']);
							if (count($wdays) && !in_array($current['wday'], $wdays))
								$wday_ok = false;
						}
						$dday_ok = true;
						if (isset($calendar['period']) && $calendar['period']) {
							$stack = array();
							foreach ($items as $item) {
								if (isset($calendar[$item])) {
									$stack[] = $item;
								} elseif (count($stack)) {
									if (!$this->check($stack, $calendar, $current)) {
										$dday_ok = false;
										break;
									}
									$stack = array();
								}
							}
							if (count($stack) && !$this->check($stack, $calendar, $current)) {
								$dday_ok = false;
							}
						} else {
							foreach ($items as $item) {
								if (isset($calendar[$item]) && isset($current[$item]) && intval($calendar[$item]) != $current[$item]) {
									$dday_ok = false;
									break;
								}
							}
						}
						$value = ($wday_ok && $dday_ok && isset($calendar['text']) ? $calendar['text'] : '');
						$params = preg_replace('#{{\s*calendar_' . $index . '\s*}}#i', $value, $params);
					}
				}
			}
		} 
		return $params;
	}
}
