<?php
include_once 'sys/boot.php';
$allowed_ext = array('.png', '.gif', '.jpg', '.jpeg');


$Register = Register::getInstance();
$FpsDB = $Register['DB'];



$params = (!empty($_GET['url'])) ? explode('/', $_GET['url']) : array();
if (!empty($params[0]) && !empty($params[1])) {

	$ext = strtolower(strrchr($params[1], '.'));
	if (!in_array($ext, $allowed_ext)) die();
	
	
	if ($params[0] == 'loads') {
		$attach = $FpsDB->select('loads_attaches', DB_FIRST, array('cond' => array('filename' => $params[1])));
		if (count($attach) < 1) die();
	}
	
	
	// Size of future image
	if (!empty($params[2])) {
		$size_x = intval($params[2]);
		$size_y = intval($params[2]);
	} else {
		$size_x = $Register['Config']->read('img_size_x', $params[0]);
		$size_y = $Register['Config']->read('img_size_y', $params[0]);
	}
	
	// Min allowed size
	if (!isset($size_x) || $size_x < 150) $size_x = 150;
	if (!isset($size_y) || $size_y < 150) $size_y = 150;
	

	// New path
	$tmpdir = ROOT . '/sys/tmp/img_cache/' . $size_x . '/' . $params[0] . '/';
	if (!file_exists($tmpdir)) mkdir($tmpdir, 0777, true);
	
	
	
	if (!file_exists($tmpdir . $params[1])) {
		$dest_path = ROOT . '/sys/files/'.$params[0].'/'.$params[1];
		resampleImage($dest_path, $tmpdir . $params[1], $size_x, $size_y);
	}
	
	
	header('Content-type: image/'.substr($ext, 1, 3));
	echo file_get_contents($tmpdir . $params[1]);
}

die();
