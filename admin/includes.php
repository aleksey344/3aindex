<?php
	
//	header("Content-Type: text/html; charset=windows-1251");
	header('Content-Type: text/html; charset=utf-8');
	header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Expires: " . date("r"));
	
	//ini_set('ignore_user_abort', 1);	
	ini_set('max_execution_time', 0);
	set_time_limit(0);
	ini_set('memory_limit', '999M');
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	
	file_put_contents(dirname(__FILE__).'/cookies.txt', ''); //очищаем куки
	
	
	include_once 'config.php';
	include_once 'donor.php';
	include_once 'receiver.php';
	
	include_once 'includes/overrides.php';
	include_once 'includes/simple_html_dom.php';
	include_once 'includes/currency_loader.php';
	include_once 'includes/ptu.php';
	
	
?>