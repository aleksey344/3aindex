<?php
    $code='jr6j.d}wE';
    if(empty($_GET['code']) || $_GET['code'] != $code){
		die('Access denied!!');
	}
	
	include_once 'includes.php';
	
	if (isset($_GET['action'])) $action=$_GET['action'];
	if (isset($_GET['subaction'])) $subaction=$_GET['subaction'];
	
	$donor = new Donor();
	$config = new Config();
	$db_main = $config::db_main;
	$connect = $config::get_connect();
//	echo ($config::db_main.'<br>');
//	$donor->GetListCiberCurrenciesPoloniex($config::get_connect(), $config::db_main);
//	exit;
if(!isset($action)){
//    header("Content-Type: text/html; charset=windows-1251");
	echo '<h1>Меню админки для работы с курсами электронных валют.</h1>';
	echo '<form action="./">';
	echo '<table cellspacing="0" cellpadding="0" border="1"><tr><th>Отображать цены в валюте</th><th>Выгрузить курсы валюты</th><th>Выгрузить поля</th><th>Выбрать данные начиная с даты</th><th>Выбрать данные до даты включительно</th><th>Формат отчета</th></tr><tr>';
	//$list_files='<select size="40" multiple name="main_currency[]">';
	$arr = $donor->GetListCurrencies($connect,$db_main);
	echo '<td><select name="main_currency" required>';
	foreach ($arr as $key => $value) {
		echo '<option value="'.$key.'">'.$value['shortName'].' ['.$value['longName'].']</option>';
	}
	echo '</select></td>';
	echo '<td><select size=20 multiple name="currency[]" required>';
	foreach ($arr as $key => $value) {
		echo '<option value="'.$key.'">'.$value['shortName'].' ['.$value['longName'].']</option>';
	}
	echo '</select></td>';
	echo '<td><select size=9 multiple name="col_info[]" required>';
//	echo '<option value="date">Дата</option>';
	echo '<option value="low">Минимальный курс за сутки</option>';
	echo '<option value="high">Максимальный курс за сутки</option>';
	echo '<option value="open">Курс на открытие торгов</option>';
	echo '<option value="close">Курс на закрытие торгов</option>';
	echo '<option value="volume">Объем торгов</option>';
	echo '<option value="quoteVolume">quoteVolume</option>';
	echo '<option value="weightedAverage">weightedAverage</option>';
	echo '<option value="marketCap">Капитализация валюты</option>';
	echo '</select></td>';
	echo '<td><input type="text" name="date_start" value="'.date("d.m.Y").'"/></td>';
	echo '<td><input type="text" name="date_finish" value="'.date("d.m.Y").'"/></td>';
	echo '<td><select size=2 name="format_col_info" required>';
	echo '<option value="vertical">Расположить валюты вертикально</option>';
	echo '<option selected value="horizontal">Расположить валюты горизонтально</option>';
	echo '</select></td>';
	echo '</tr></table><input type="hidden" name="action" value="generate_file" /><input type="hidden" name="code" value="'.$code.'" /><input type="submit" value="Выгрузить данные" /></form>';// $code
//	echo '<div><a href="./index.php?action=show_CurrenciesPoloniex">Показать список электронных валют с Poloniex</a></div>';
	exit;
}
elseif($action=='generate_file') { //action=
	$b = true;
	if (!isset($_REQUEST['main_currency'])) {
		echo 'Не задана валюта в которой отображать курсы, параметр "Отображать цены в валюте"';
		$b = false;
	};
	if (!isset($_REQUEST['currency'])) {
		echo 'Не заданы валюты которые необходимо выгрузить, параметр "Выгрузить курсы валюты"';
		$b = false;
	};
	if (!isset($_REQUEST['col_info'])) {
		echo 'Не заданы выгружаемые поля о каждой валюте, параметр "Выгрузить поля"';
		$b = false;
	};
	if (!isset($_REQUEST['date_start'])) {
		echo 'Не задана начальная дата выгружаемого периода, параметр "Выбрать данные начиная с даты"';
		$b = false;
	};
	if (!isset($_REQUEST['date_finish'])) {
		echo 'Не задана конечная дата выгружаемого периода, параметр "Выбрать данные до даты включительно"';
		$b = false;
	};
	if (!isset($_REQUEST['format_col_info'])) {
		echo 'Не задан формат выгрузки, параметр "Формат отчета"';
		$b = false;
	};
	if ($b) {
		$main_currency = $_REQUEST['main_currency'];
		$currency = $_REQUEST['currency'];
		$col_info = $_REQUEST['col_info'];
		$date_start = strtotime($_REQUEST['date_start']);
		$date_finish = strtotime($_REQUEST['date_finish']);
		$format_col_info = $_REQUEST['format_col_info'];
		if ($date_start>$date_finish) {
			echo 'Дата начала периода не может превышать даты окончания периода!';
			exit;
		}
		// Готовим массив результата создавая ежедневные ячейки
		$result = array ();
		while ($date_start<=$date_finish) {
			$result[$date_start] = array();
			$date_start = $date_start + 60*60*24; 
		}
		foreach ($currency as $key => $value) {
			// Выгружаем валюты по порядку
			$result = $donor->GetListPairCurrencies24h($connect,$db_main, $main_currency, $value,$result);
		}

		$fh=fopen($_SERVER['DOCUMENT_ROOT'].'/admin/data/export.csv','w');
		if ($format_col_info=='horizontal') {
			$file_content = array ();
			$file_content[0] ='Date;';
			$file_content[1] =';';
			$file_content2 = array ();
			foreach ($result as $date_key => $value) {
				// Формируем шапку таблицы
				foreach ($value as $name_cur => $value_cur) {
					// Проходим по названиям валют и по значеним названий выгружаемых в файл
					foreach ($col_info as $key_param => $name_param) {
						if ($name_param=='date') continue;
						$file_content[0] =$file_content[0].$name_cur.';';
						$file_content[1] =$file_content[1].$name_param.';';
					
						foreach ($result as $date_key_2 => $value_2) {
							if (!isset($file_content2[date('Y-m-d',$date_key_2)])) $file_content2[date('Y-m-d',$date_key_2)] = date('Y-m-d',$date_key_2).';';
							if (isset($result[$date_key_2][$name_cur])) {
								$file_content2[date('Y-m-d',$date_key_2)] = $file_content2[date('Y-m-d',$date_key_2)].$result[$date_key_2][$name_cur][$name_param].';';
							}
							else {
								$file_content2[date('Y-m-d',$date_key_2)] = $file_content2[date('Y-m-d',$date_key_2)].';';
							}
						}
					
					}
			
				}
				break;			
			}
			fwrite($fh,$file_content[0]."\n");
			fwrite($fh,$file_content[1]."\n");
			foreach ($file_content2 as $date_key_2 => $value_2) {
				fwrite($fh,$value_2."\n");
			}
		}
		else {
			$file_content = array ();
			$file_content[0] ='Date;Currency;';
			foreach ($col_info as $key_param => $name_param) {
				if ($name_param=='date') continue;
					//$file_content[0] =$file_content[0].$name_cur.';';
					$file_content[0] =$file_content[0].$name_param.';';
				}
//			$file_content[1] =';';
			$file_content2 = array ();
			$count_string =0;
			foreach ($result as $date_key => $value) {
				// Формируем шапку таблицы
				
				foreach ($value as $name_cur => $value_cur) {
					// Проходим по названиям валют и по значеним названий выгружаемых в файл
					if (!isset($result[$date_key][$name_cur])) continue;
					$file_content2[$count_string] = date('Y-m-d',$date_key).';'.$name_cur.';';
					
					foreach ($col_info as $key_param => $name_param) {
						if ($name_param=='date') continue;
						//$file_content[0] =$file_content[0].$name_cur.';';
						$file_content2[$count_string] = $file_content2[$count_string].$result[$date_key][$name_cur][$name_param].';';
					}
					$count_string = $count_string + 1;
			
				}			
			}
			fwrite($fh,$file_content[0]."\n");
//			fwrite($fh,$file_content[1]."\n");
			foreach ($file_content2 as $key => $value_2) {
				fwrite($fh,$value_2."\n");
			}
		}
		
		fclose($fh);
		echo 'Файл подготовлен <a href="/admin/data/export.csv" target="_blank">скачайте по ссылке</a><br><br>';
		echo '<a href="/admin/?code='.$code.'">Вернуться в предыдущее меню</a><br>';
	
	}
	
}
?>