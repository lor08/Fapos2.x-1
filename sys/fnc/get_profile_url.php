<?php
##################################################
##												##
## Author:       Andrey Brykin (Drunya)         ##
## Version:      0.7                            ##
## Project:      CMS                            ##
## package       CMS Fapos                      ##
## subpackege    Geting profile url function    ##
## copyright     ©Andrey Brykin 2010-2011       ##
##################################################


##################################################
##												##
## any partial or not partial extension         ##
## CMS Fapos,without the consent of the         ##
## author, is illegal                           ##
##################################################
## Любое распространение                        ##
## CMS Fapos или ее частей,                     ##
## без согласия автора, является не законным    ##
##################################################


function getProfileUrl($user_id) {
    $Register = Register::getInstance();

	if (!$Register['ACL']->turn(array('users', 'view_users'), false)) {
		$url = '#';
	} else {
		$url = '/users/info/' . $user_id . '/' ;
	}
	return $url;
}

?>