<?php

$code='{sgXdYvgL';

ini_set('memory_limit', '-1');
set_time_limit(0);
error_reporting(E_ALL);

if(empty($_GET['code']) || $_GET['code'] != $code){
		die('Access denied!!');
		exit;
	}

$fsym=$_GET['fsym'];
//$tsym=$_GET['tsym'];
$tsym='USD';


if ($tsym!='USD'){
	die('Error tsym param!!');
		exit;
};

if (!in_array($fsym,array('ATOP','BTC'))){
	die('Error fsym param!!');
	exit;
};

	include_once './../../admin/includes.php';
	
	
	$donor_lists = array();
	
	$links_list = array();
	
	
	$donor = new Donor();
	$config = new Config();
	$connect = $config::get_connect();
	$db_main = $config::db_main;

	$date_finish = explode('.',date('d.m.Y.H.i', time()));
	
	$sec = $date_finish[3]*60*60+$date_finish[4]*60;
	
	if ($sec<=(3*60*60)) {
		$date_finish = mktime (23, 59, 0, $date_finish[1], $date_finish[0]-2, $date_finish[2]);
		
	}
	ELSE {
		$date_finish = mktime (23, 59, 0, $date_finish[1], $date_finish[0]-1, $date_finish[2]);
	}
	
//	$date_start = $date_finish - 40*24*60*60; // - 40 дней
	$date_start = mktime (23, 59, 0, 01, 07, 2017);// strtotime('01.07.2017');

	header("Content-type: application/json; charset=utf-8");
	echo('{"Response":"Success","Type":100,"Aggregated":false,"Data":[');

//выгружаем все необходимые данные
	if ($fsym=='ATOP') {
		$b = false;
		$sql = "SELECT date, SUMmarketCapTOP, Index1 FROM {$db_main}.ListIndexUSD1 WHERE date >= '".date('Y-m-d 23:59',$date_start)."' AND date <= '".date('Y-m-d 23:59',$date_finish)."' ORDER BY date";
		$res=mysqli_query($connect,$sql);
		if(mysqli_num_rows($res)){
			while($arr=mysqli_fetch_assoc($res)){
				$str = $b ?',':'';
				$str = $str.'{"time":'.strtotime($arr['date']).',"close":'.number_format($arr['Index1'],2,'.','').',"high":'.number_format($arr['Index1'],2,'.','').',"low":'.number_format($arr['Index1'],2,'.','').',"open":'.number_format($arr['Index1'],2,'.','').',"volumefrom":'.number_format($arr['SUMmarketCapTOP']/1000000000,2,'.','').',"volumeto":'.number_format($arr['SUMmarketCapTOP']/1000000000,2,'.','').'}';
				$b = true;
				echo ($str."\n");
			}
		}	
	}
	ELSEIF ($fsym=='BTC') {
		$id_usd = $donor->CreateNewCurrencies($connect,$db_main, 'USD', 'US Dollar', 0);
		$id_btc = $donor->CreateNewCurrencies($connect,$db_main, 'BTC', 'Bitcoin', 1);
		$sql2 = "SELECT a.nameTableSavePair, b.Priority FROM {$db_main}.ListPair a, {$db_main}.ListDonor b WHERE a.IdCurrenciesPair1 = ".$id_usd." AND a.IdCurrenciesPair2 = ".$id_btc." AND a.idDonor = b.id ORDER BY b.Priority";
		$res2=mysqli_query($connect,$sql2);
		$b=false;
		if(mysqli_num_rows($res2)){
			while($arr2=mysqli_fetch_assoc($res2)){
				if ($b) break;
				$nameTableSavePair = $arr2['nameTableSavePair'];
				
				$sql="SELECT * FROM {$db_main}.{$nameTableSavePair} WHERE date >= '".date('Y-m-d 23:59',$date_start)."' AND date <= '".date('Y-m-d 23:59',$date_finish)."' ORDER BY date";
            	$res=mysqli_query($connect,$sql);
	        	if(mysqli_num_rows($res)){
					while($arr=mysqli_fetch_assoc($res)){
						if ($arr['marketCap']>0) {
							$str = $b ?',':'';
							$str = $str.'{"time":'.strtotime($arr['date']).',"close":'.number_format($arr['close'],2,'.','').',"high":'.number_format($arr['high'],2,'.','').',"low":'.number_format($arr['low'],2,'.','').',"open":'.number_format($arr['open'],2,'.','').',"volumefrom":'.number_format($arr['marketCap']/1000000000,2,'.','').',"volumeto":'.number_format($arr['marketCap']/1000000000,2,'.','').'}';
							$b = true;
							echo ($str."\n");
						}
						else break;

				}
			}
			
	}
		}
	}


	echo('],"TimeTo":'.$date_finish.',"TimeFrom":'.$date_start.',"FirstValueInArray":true,"ConversionType":{"type":"direct","conversionSymbol":""}}'."\n");
