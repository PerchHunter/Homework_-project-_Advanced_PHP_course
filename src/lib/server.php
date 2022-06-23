<?php
	require_once '../configurations/autoload.php';

	$model = '';
	$action = $_POST['action'] ?? null;


	if ($_POST['c'] === 'Cart' ){
		$model = new M_Cart;
	}
	elseif ($_POST['c'] === 'User') {
		$model = new M_User;
	}
	elseif ($_POST['c'] === 'Page') {
		$model = new M_Page;
	}
	elseif ($_POST['c'] === 'Admin') {
		$model = new M_Admin;
	}
	else {
		die('Что-то не то :/');
	}

	echo $model->$action() ?? die("AJAX сломался :(");