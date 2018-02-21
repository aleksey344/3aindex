<?php
	include_once './admin/includes.php';
	
	
	$donor_lists = array();
	$donor_list_names = Config::$donor_pages;
	
	$links_list = array();
	
	
	$donor = new Donor();
	$config = new Config();

	$date_finish = explode('.',date('d.m.Y.H.i', time()));
	
	$sec = $date_finish[3]*60*60+$date_finish[4]*60;
	
	if ($sec<=(6*60*60)) {
		$date_finish = mktime (23, 59, 0, $date_finish[1], $date_finish[0]-2, $date_finish[2]);
		
	}
	ELSE {
		$date_finish = mktime (23, 59, 0, $date_finish[1], $date_finish[0]-1, $date_finish[2]);
	}
	
	$date_start = mktime (23, 59, 0, date('m',$date_finish), date('d',$date_finish)-7, date('Y',$date_finish)); // - 7 дней
//	$date_start = strtotime('01.01.2016');

//	$date_finish = time(); // текущее время
	
	$date_calculate = $date_finish; // strtotime('02.07.2017');

	$donor->ParseDonorPoloniex($config::get_connect(), $config::db_main);
	$donor->ParseDonorCBR($config::get_connect(), $config::db_main,$date_start, $date_finish);
	$donor->ParseDonorCoinmarketcap($config::get_connect(), $config::db_main,$date_start, $date_finish, 86400);
	$donor->ParseDonorRBC($config::get_connect(), $config::db_main);
	
	$donor->CreateListIndexUSD1($config::get_connect(), $config::db_main);
	
//	$donor->Index1Calculate24h($config::get_connect(), $config::db_main, $date_calculate);
	
	$donor->CreateCacheindexCalc($config::get_connect(), $config::db_main);
//	$res = $donor->GetCacheindexCalc($config::get_connect(), $config::db_main, $date_calculate);
	
	
/*	echo '<pre>';
	print_r($res);
	echo '</pre>';*/ 
?>