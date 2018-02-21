<?php

	class Donor
	{
		//const mount = array("Jan" => "1", "Feb" => "2", "Mar" => "3", "Apr" => "4", "May" => "5", "Jun" => "6", "Jul" => "7", "Aug" => "8", "Sep" => "9", "Oct" => "10", "Nov" => "11", "Dec" => "12");

		public function CreateListUsers($connect,$db_main) // Создание таблицы ListUsers в БД. Вернет True если таблица создана или существовала
		{
				// @mysqli_query ($connect,"SET NAMES `cp1251`");
	  		$res=mysqli_query($connect,"SHOW TABLES FROM {$db_main} LIKE 'ListUsers';");
        if(mysqli_num_rows($res)==0){
          $sql = "CREATE TABLE {$db_main}.ListUsers (
					id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
					date TIMESTAMP, /* дата создания пользователя */
					shortName VARCHAR(50), /* короткое имя пользователя (про запас)*/
					longName VARCHAR(100), /* длинное имя пользователя (про запас)*/
					email VARCHAR(100), /* электронная почта пользователя */
					currentCookies VARCHAR(100), /* кука */
					tel VARCHAR(50), /* номера телефонов (про запас)*/
					password VARCHAR(50)/* пароль пользователя (про запас)*/
          )";

          if(!mysqli_query($connect,$sql)) {
          	return False;
          }
        }


				$res=mysqli_query($connect,"SHOW TABLES FROM {$db_main} LIKE 'ListVersionPortfelCurrencies';");
        if(mysqli_num_rows($res)==0){
          $sql = "CREATE TABLE {$db_main}.ListVersionPortfelCurrencies (
						id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
						IdUsers INT(6), /* ссылка на пользователя */
						date TIMESTAMP, /* информация на дата создания порфеля */
						active tinyint(1) default 1 /* Флаг активности портфеля 1 - активен, 0 неактивен */
          )";

          if(!mysqli_query($connect,$sql)) {
          	return False;
          }
        }



				$res=mysqli_query($connect,"SHOW TABLES FROM {$db_main} LIKE 'ListPortfelCurrencies';");
        if(mysqli_num_rows($res)==0){
          $sql = "CREATE TABLE {$db_main}.ListPortfelCurrencies (
						id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
						IdPortfel INT(6), /* ссылка на портфель */
						IdCurrenciesPair INT(6), /* ссылка на валюту */
						countCurrencies DECIMAL (30,15) /* кол-во валюты в кошельке */
          )";

          if(!mysqli_query($connect,$sql)) {
          	return False;
          }
        }
			return True;
		}

		public function CreateNewListUsers($connect,$db_main, $shortName, $longName, $email, $currentCookies, $tel, $password) // Создание нового пользователя. Все параметры в закодированы base64_encode . Уникальность по email. Вернет ID добавленого пользователя
		{
			if (!$this->CreateListUsers($connect,$db_main)) {
				return False;
			}
			Else {
				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListUsers WHERE email='".$email."'";
				$res=mysqli_query($connect,$sql);
				$num_parts=mysqli_result($res,0,'num');
							//echo ($num_parts.' '.$shortName.'<br>');
				if ($num_parts>0) {
					$sql="SELECT * FROM {$db_main}.ListUsers WHERE email='".$email."'";
					$res=mysqli_query($connect,$sql);
					$id=mysqli_result($res,0,'id');

					// Обновляем информацию
					$sql = "UPDATE {$db_main}.ListUsers SET shortName = '".$shortName."', longName = '".$longName."', currentCookies = '".$currentCookies."',
					tel = '".$tel."', password = '".$password."'  WHERE id=".$id;
					$res=mysqli_query($connect,$sql);
					return $id;
				}
				else {
					// Создаем нового пользователя
					$sql = "INSERT INTO {$db_main}.ListUsers (date, shortName, longName, currentCookies, tel, password, email)
									VALUES ('".date('Y-m-d H:i')."', '".$shortName."', '".$longName."', '".$currentCookies."', '".$tel."', '".$password."', '".$email."')";
					$res=mysqli_query($connect,$sql);
										//echo ($sql.'<br>');
					$sql="SELECT * FROM {$db_main}.ListUsers WHERE email='".$email."'";
					$res=mysqli_query($connect,$sql);
					$id=mysqli_result($res,0,'id');
					return $id;
				}
			}
		}

		public function CreateNewPortfel($connect,$db_main, $email, $listCurrentANDCount) // Создание нового портфеля с валютами. $listCurrentANDCount = array(IdCurrenciesPair => countCurrencies). Вернет ID портфеля
		{
			if (!$this->CreateListUsers($connect,$db_main)) {
				return False;
			}
			Else {
				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListUsers WHERE email='".$email."'";
				$res=mysqli_query($connect,$sql);
				$num_parts=mysqli_result($res,0,'num');
							//echo ($num_parts.' '.$shortName.'<br>');
				if ($num_parts>0) {
					$sql="SELECT * FROM {$db_main}.ListUsers WHERE email='".$email."'";
					$res=mysqli_query($connect,$sql);
					$id=mysqli_result($res,0,'id');

					// Отключаем все портфели пользователя
					$sql = "UPDATE {$db_main}.ListVersionPortfelCurrencies SET active = 0 WHERE IdUsers=".$id." AND active=1";
					$res=mysqli_query($connect,$sql);

					// Создаем новый портфель пользователя
					$temp_date=date('Y-m-d H:i');
					$sql = "INSERT INTO {$db_main}.ListVersionPortfelCurrencies (IdUsers, date, active)
									VALUES (".$id.", '".$temp_date."', 1)";
					$res=mysqli_query($connect,$sql);
										//echo ($sql.'<br>');
					$sql="SELECT * FROM {$db_main}.ListVersionPortfelCurrencies WHERE IdUsers=".$id." AND  date='".$temp_date."' AND active=1";
					$res=mysqli_query($connect,$sql);
					$id_portfel=mysqli_result($res,0,'id');

					foreach ($listCurrentANDCount as $key => $value) {
						$sql = "INSERT INTO {$db_main}.ListPortfelCurrencies (IdPortfel, IdCurrenciesPair, countCurrencies)
										VALUES (".$id_portfel.", ".$key.", '".$value."')";
						$res=mysqli_query($connect,$sql);
					}
					return $id_portfel;
				}
			}
		}

		public function GetUserEMAIL($connect,$db_main, $currentCookies) // Загрузить email по куку. Если такого пользователя нет, то вернем пустую строку
		{
			$email ='';
			if (!$this->CreateListUsers($connect,$db_main)) {
				return $email;
			}
			Else {
				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListUsers WHERE currentCookies='".$currentCookies."'";
				$res=mysqli_query($connect,$sql);
				$num_parts=mysqli_result($res,0,'num');
							//echo ($num_parts.' '.$shortName.'<br>');
				if ($num_parts>0) {
					$sql="SELECT * FROM {$db_main}.ListUsers WHERE currentCookies='".$currentCookies."'";
					$res=mysqli_query($connect,$sql);
					$email=mysqli_result($res,0,'email');

				}
				return $email;
			}
		}


		public function GetCalcPortfel($connect,$db_main, $email) // Возвращает портфель и стоимость портфеля, а также список валют. array(portfel => array(), ListCurrency => array(id => NameCurrency), email =>$email ).
		{
			// array(portfel => array(), ListCurrency => array(id => NameCurrency), currentCookies =>$currentCookies )

			$id_usd = $this->CreateNewCurrencies($connect,$db_main, 'USD', 'US Dollar', 0);
			$id_btc = $this->CreateNewCurrencies($connect,$db_main, 'BTC', 'Bitcoin', 1);

			$date_finish = explode('.',date('d.m.Y.H.i', time()));

			$sec = $date_finish[3]*60*60+$date_finish[4]*60;

			if ($sec<=(6*60*60)) {
				$date_finish = mktime (23, 59, 0, $date_finish[1], $date_finish[0]-2, $date_finish[2]);

			}
			ELSE {
				$date_finish = mktime (23, 59, 0, $date_finish[1], $date_finish[0]-1, $date_finish[2]);
			}
			$cache_exist = false;
			if (file_exists('./cache_curs.json')) {
				$handle = fopen('./cache_curs.json','r');
				$contents = fread($handle, filesize('./cache_curs.json'));
				fclose($handle);
				$json = json_decode($contents,true);
/*				echo('<pre>');
				print_r($json);
				echo('</pre>'); */
				if ($json['date']==$date_finish) {
					$KursBTC = $json['kursBTCinUSD'];
					$result = array('date'=> $date_finish ,'portfel' => array(), 'ListCurrency' => $json['ListCurrency'], 'email' => $email,'kursBTCinUSD' => $json['kursBTCinUSD']);
					$cache_exist = true;
				}
			}

			if (!$cache_exist) {
				// Выгружаем курс BTC в USD для пересчета всех валют в BTC
				$result_cur = array($date_finish => array());
				$result_cur = $this -> GetListPairCurrencies24h($connect,$db_main, $id_usd, $id_btc, $result_cur);
	/*					echo('Курс BTC в USD = <pre>');
				print_r($result_cur);
				echo('</pre>');*/
				$KursBTC = 0;
				foreach ($result_cur[$date_finish] as $key => $value) {
					$KursBTC = $value['close'];
					break;
				}

				$result = array('date'=> $date_finish ,'portfel' => array(), 'ListCurrency' => array(), 'email' => $email,'kursBTCinUSD' => $KursBTC);
			}


			if (!$this->CreateListUsers($connect,$db_main)) {
				return $result;
			}
			Else {
				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListUsers WHERE email='".$email."'";
				$res=mysqli_query($connect,$sql);
				$num_parts=mysqli_result($res,0,'num');
							//echo ($num_parts.' '.$shortName.'<br>');
				if ($num_parts>0) {
					$sql="SELECT * FROM {$db_main}.ListUsers WHERE email='".$email."'";
					$res=mysqli_query($connect,$sql);
					$id=mysqli_result($res,0,'id');

					$sql="SELECT * FROM {$db_main}.ListVersionPortfelCurrencies  WHERE active = 1 AND IdUsers=".$id;
					$res=mysqli_query($connect,$sql);
					$id_portfel=mysqli_result($res,0,'id');

					$sql="SELECT * FROM {$db_main}.ListPortfelCurrencies  WHERE IdPortfel='".$id_portfel."'";
					$res=mysqli_query($connect,$sql);
					$SumUSD = 0;
					$SumBTC = 0;

//					echo('Курс BTC в USD ='.$KursBTC.'<br>');

						while($arr=mysqli_fetch_assoc($res)){
							//$namecurrency = $this->GetLongNameCurrencies($connect,$db_main,$arr['IdCurrenciesPair']).' ('.$this->GetShortNameCurrencies($connect,$db_main,$arr['IdCurrenciesPair']).')';
							$namecurrency = $this->GetShortNameCurrencies($connect,$db_main,$arr['IdCurrenciesPair']);

							$CostUSD = 0;
							$CostBTC = 0;
							//$result_cur = array($date_finish => array());
							if ($id_usd == $arr['IdCurrenciesPair']) {
								$CostUSD = 1*$arr['countCurrencies'];
								$CostBTC = $CostUSD/$KursBTC;
							}
							else {
								// вычисляем курс валюты
								$result_cur = array($date_finish => array());
								$result_cur = $this -> GetListPairCurrencies24h($connect,$db_main, $id_usd, $arr['IdCurrenciesPair'],$result_cur);
	/*							echo('<pre>');
	    					print_r($result_cur);
	    					echo('</pre>');*/
								foreach ($result_cur[$date_finish] as $key => $value) {
									$CostUSD = $value['close']*$arr['countCurrencies'];
									break;
								}
								$CostBTC = $CostUSD/$KursBTC;
							}

	/*						if ($id_btc == $arr['IdCurrenciesPair']) {
								$CostBTC = 1*$arr['countCurrencies'];
							}
							else {
								// вычисляем курс валюты
								$result_cur = array($date_finish => array());
								$result_cur = $this -> GetListPairCurrencies24h($connect,$db_main, $id_btc, $arr['IdCurrenciesPair'],$result_cur);
								echo('<pre>');
	    					print_r($result_cur);
	    					echo('</pre>');

								foreach ($result_cur[$date_finish] as $key => $value) {
									$CostBTC = $value['close']*$arr['countCurrencies'];
									break;
								}
							}*/
							$SumUSD += $CostUSD;
							$SumBTC += $CostBTC;
							$result['portfel'][$arr['id']] = array('IdPortfel' => $arr['IdPortfel'], 'IdCurrenciesPair' => $arr['IdCurrenciesPair'], 'countCurrencies' => $arr['countCurrencies'], 'nameCurrency' => $namecurrency, 'CostBTC' => $CostBTC, 'CostUSD' => $CostUSD);
						}


					// Пишем стоимость портфеля
					$result['portfel']['SumUSD'] = $SumUSD;
					$result['portfel']['SumBTC'] = $SumBTC;



				}

			}

			if (!$cache_exist) {
				// пишем в массив список доступных валют
				$result['ListCurrency'] = $this->GetListCurrencies($connect,$db_main);
				foreach ($result['ListCurrency'] as $key => $value) {
					$result_cur = array($date_finish => array());
					$result_cur = $this -> GetListPairCurrencies24h($connect,$db_main, $id_usd, $key, $result_cur);
					foreach ($result_cur[$date_finish] as $key2 => $value2) {
						$result['ListCurrency'][$key]['KursInUSD']=$value2['close'];
						break;
					}
				}
				$json = array('date' => $date_finish, 'ListCurrency' => $result['ListCurrency'], 'kursBTCinUSD' => $KursBTC);
				$contents = json_encode($json);
				$handle = fopen('./cache_curs.json','w');
				fwrite ($handle,$contents);
				fclose($handle);
			}
			return $result;
		}

		public function CreateListPair($connect,$db_main) // Создание таблицы ListPair в БД. Вернет True если таблица создана или существовала
		{
			// @mysqli_query ($connect,"SET NAMES `cp1251`");
	        $res=mysqli_query($connect,"SHOW TABLES FROM {$db_main} LIKE 'ListPair';");
            $counter=0;
            if(mysqli_num_rows($res)==0){
                $sql = "CREATE TABLE {$db_main}.ListPair (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                idDonor INT(6), -- с какого донора брать
				IdCurrenciesPair1 INT(6), -- первая валюта в паре
				IdCurrenciesPair2 INT(6), -- вторая валюта в паре
				nameTableSavePair VARCHAR (100), -- название таблиц в которой храняться значения пар
				namePairForDonor VARCHAR (100), -- название пары для запросов на сайте Донора
				periodSec INT(6), -- период между замерами значений пар
				INDEX ListPairIdDonor (idDonor), /* Индекс к полю NameDonor */
				INDEX ListPairperiodSec (periodSec), /* Индекс к полю NameDonor */
				INDEX (idDonor, IdCurrenciesPair1, IdCurrenciesPair2, periodSec) /* Индекс по полям idDonor, IdCurrenciesPair1, IdCurrenciesPair2, periodSec */
                )";

                if(!mysqli_query($connect,$sql)) {
                    return False;
                }
                else {
		            return True;
                }
            }
        	Else {
        		return True;
        	}
		}

		public function CreateCacheindexCalc($connect,$db_main) // Создание таблицы CacheindexCalc в БД. Вернет True если таблица создана или существовала
		{
			// @mysqli_query ($connect,"SET NAMES `cp1251`");
	        $res=mysqli_query($connect,"SHOW TABLES FROM {$db_main} LIKE 'CacheindexCalc';");
            $counter=0;
            if(mysqli_num_rows($res)==0){
                $sql = "CREATE TABLE {$db_main}.CacheindexCalc (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				date TIMESTAMP, /* информация на дату и время */
				NameIndex VARCHAR (100), /* название индекса */
				priority INT(6), /* Приоритет вывода индекса на сайте */
				ValueIndex DECIMAL (30,15), /* фактическое значение индекса */
				ValueIndexUP INT(1), /* если 1 значит стрелку вверх, если 0 то стрелку вниз */
				PersentDay1 DECIMAL (30,15), /* процент роста индекса за 1 день */
				PersentDay1UP INT(1), /* если 1 значит стрелку вверх, если 0 то стрелку вниз */
				PersentDay7 DECIMAL (30,15), /* процент роста индекса за 7 дней */
				PersentDay7UP INT(1), /* если 1 значит стрелку вверх, если 0 то стрелку вниз */
				PersentDay14 DECIMAL (30,15), /* процент роста индекса за 14 дней */
				PersentDay14UP INT(1), /* если 1 значит стрелку вверх, если 0 то стрелку вниз */
				PersentDay31 DECIMAL (30,15), /* процент роста индекса за 1 месяц */
				PersentDay31UP INT(1), /* если 1 значит стрелку вверх, если 0 то стрелку вниз */
				PersentDay90 DECIMAL (30,15), /* процент роста индекса за 3 месяца */
				PersentDay90UP INT(1), /* если 1 значит стрелку вверх, если 0 то стрелку вниз */
				NameCurrenciesTOP1 VARCHAR (100),  /*  Название Валюты ТОП 1  */
				NameCurrenciesTOP2 VARCHAR (100), /* Название Валюты ТОП 2  */
                NameCurrenciesTOP3 VARCHAR (100), /* Название Валюты ТОП 3  */
                NameCurrenciesTOP4 VARCHAR (100), /* Название Валюты ТОП 4  */
                NameCurrenciesTOP5 VARCHAR (100), /* Название Валюты ТОП 5  */
                NameCurrenciesTOP6 VARCHAR (100), /* Название Валюты ТОП 6  */
                NameCurrenciesTOP7 VARCHAR (100), /* Название Валюты ТОП 7  */
                NameCurrenciesTOP8 VARCHAR (100), /* Название Валюты ТОП 8  */
                NameCurrenciesTOP9 VARCHAR (100), /* Название Валюты ТОП 9  */
                NameCurrenciesTOP10 VARCHAR (100), /* Название Валюты ТОП 10  */
                VESmarketCapTOP1 DECIMAL (30,15), /* Вес валюты TOP1 по всем биржам  */
                VESmarketCapTOP2 DECIMAL (30,15), /* Вес валюты TOP2 по всем биржам  */
                VESmarketCapTOP3 DECIMAL (30,15), /* Вес валюты TOP3 по всем биржам  */
                VESmarketCapTOP4 DECIMAL (30,15), /* Вес валюты TOP4 по всем биржам  */
                VESmarketCapTOP5 DECIMAL (30,15), /* Вес валюты TOP5 по всем биржам  */
                VESmarketCapTOP6 DECIMAL (30,15), /* Вес валюты TOP6 по всем биржам  */
                VESmarketCapTOP7 DECIMAL (30,15), /* Вес валюты TOP7 по всем биржам  */
                VESmarketCapTOP8 DECIMAL (30,15), /* Вес валюты TOP8 по всем биржам  */
                VESmarketCapTOP9 DECIMAL (30,15), /* Вес валюты TOP9 по всем биржам  */
                VESmarketCapTOP10 DECIMAL (30,15), /* Вес валюты TOP10 по всем биржам  */
                CurrentCloseTOP1 VARCHAR (50), /* Курс валюты TOP1 и прирост */
                CurrentCloseTOP2 VARCHAR (50), /* Курс валюты TOP2  и прирост */
                CurrentCloseTOP3 VARCHAR (50), /* Курс валюты TOP3  и прирост */
                CurrentCloseTOP4 VARCHAR (50), /* Курс валюты TOP4  и прирост */
                CurrentCloseTOP5 VARCHAR (50), /* Курс валюты TOP5  и прирост */
                CurrentCloseTOP6 VARCHAR (50), /* Курс валюты TOP6  и прирост */
                CurrentCloseTOP7 VARCHAR (50), /* Курс валюты TOP7  и прирост */
                CurrentCloseTOP8 VARCHAR (50), /* Курс валюты TOP8  и прирост */
                CurrentCloseTOP9 VARCHAR (50), /* Курс валюты TOP9  и прирост */
                CurrentCloseTOP10 VARCHAR (50), /* Курс валюты TOP10 и прирост */
								INDEX (date), /* Индекс к полю date */
								INDEX  (date, NameIndex) /* Индекс к полю date, NameIndex */
                )";



                if(!mysqli_query($connect,$sql)) {
                    return False;
                }
                else {
		            return True;
                }
            }
        	Else {
        		return True;
        	}
		}

		public function CreateListIndexUSD1($connect,$db_main) // Создание таблицы ListIndexUSD1 в БД. Вернет True если таблица создана или существовала
		{
			// @mysqli_query ($connect,"SET NAMES `cp1251`");
	        $res=mysqli_query($connect,"SHOW TABLES FROM {$db_main} LIKE 'ListIndexUSD1';");
            $counter=0;
            if(mysqli_num_rows($res)==0){
                $sql = "CREATE TABLE {$db_main}.ListIndexUSD1 (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				date TIMESTAMP, /* информация на дату и время */
				IdCurrenciesTOP1 INT(6), /* id Валюты ТОП 1  */
				IdCurrenciesTOP2 INT(6), /* id Валюты ТОП 2  */
				IdCurrenciesTOP3 INT(6), /* id Валюты ТОП 3  */
				IdCurrenciesTOP4 INT(6), /* id Валюты ТОП 4  */
				IdCurrenciesTOP5 INT(6), /* id Валюты ТОП 5  */
				IdCurrenciesTOP6 INT(6), /* id Валюты ТОП 6  */
				IdCurrenciesTOP7 INT(6), /* id Валюты ТОП 7  */
				IdCurrenciesTOP8 INT(6), /* id Валюты ТОП 8  */
				IdCurrenciesTOP9 INT(6), /* id Валюты ТОП 9  */
				IdCurrenciesTOP10 INT(6), /* id Валюты ТОП 10  */
				marketCapTOP1 DECIMAL (30,15), /* капитализация TOP1 по всем магазинам */
				marketCapTOP2 DECIMAL (30,15), /* капитализация TOP2 по всем магазинам  */
				marketCapTOP3 DECIMAL (30,15), /* капитализация TOP3 по всем магазинам  */
				marketCapTOP4 DECIMAL (30,15), /* капитализация TOP4 по всем магазинам  */
				marketCapTOP5 DECIMAL (30,15), /* капитализация TOP5 по всем магазинам  */
				marketCapTOP6 DECIMAL (30,15), /* капитализация TOP6 по всем магазинам  */
				marketCapTOP7 DECIMAL (30,15), /* капитализация TOP7 по всем магазинам  */
				marketCapTOP8 DECIMAL (30,15), /* капитализация TOP8 по всем магазинам  */
				marketCapTOP9 DECIMAL (30,15), /* капитализация TOP9 по всем магазинам  */
				marketCapTOP10 DECIMAL (30,15), /* капитализация TOP10 по всем магазинам  */
				SUMmarketCapTOP DECIMAL (30,15), /* сумма капитализаций TOP10 по всем магазинам  */
				VESmarketCapTOP1 DECIMAL (30,15), /* Вес валюты TOP1 по всем магазинам  */
				VESmarketCapTOP2 DECIMAL (30,15), /* Вес валюты TOP2 по всем магазинам  */
				VESmarketCapTOP3 DECIMAL (30,15), /* Вес валюты TOP3 по всем магазинам  */
				VESmarketCapTOP4 DECIMAL (30,15), /* Вес валюты TOP4 по всем магазинам  */
				VESmarketCapTOP5 DECIMAL (30,15), /* Вес валюты TOP5 по всем магазинам  */
				VESmarketCapTOP6 DECIMAL (30,15), /* Вес валюты TOP6 по всем магазинам  */
				VESmarketCapTOP7 DECIMAL (30,15), /* Вес валюты TOP7 по всем магазинам  */
				VESmarketCapTOP8 DECIMAL (30,15), /* Вес валюты TOP8 по всем магазинам  */
				VESmarketCapTOP9 DECIMAL (30,15), /* Вес валюты TOP9 по всем магазинам  */
				VESmarketCapTOP10 DECIMAL (30,15), /* Вес валюты TOP10 по всем магазинам  */
				PrirostTOP1 DECIMAL (30,15), /* Дневной прирост цены валюты TOP1  */
				PrirostTOP2 DECIMAL (30,15), /* Дневной прирост цены валюты TOP2  */
				PrirostTOP3 DECIMAL (30,15), /* Дневной прирост цены валюты TOP3  */
				PrirostTOP4 DECIMAL (30,15), /* Дневной прирост цены валюты TOP4  */
				PrirostTOP5 DECIMAL (30,15), /* Дневной прирост цены валюты TOP5  */
				PrirostTOP6 DECIMAL (30,15), /* Дневной прирост цены валюты TOP6  */
				PrirostTOP7 DECIMAL (30,15), /* Дневной прирост цены валюты TOP7  */
				PrirostTOP8 DECIMAL (30,15), /* Дневной прирост цены валюты TOP8  */
				PrirostTOP9 DECIMAL (30,15), /* Дневной прирост цены валюты TOP9  */
				PrirostTOP10 DECIMAL (30,15), /* Дневной прирост цены валюты TOP10  */
				NakopPrirostTOP1 DECIMAL (30,15), /* Накопительный прирост позиции TOP1  */
				NakopPrirostTOP2 DECIMAL (30,15), /* Накопительный прирост позиции TOP2  */
				NakopPrirostTOP3 DECIMAL (30,15), /* Накопительный прирост позиции TOP3  */
				NakopPrirostTOP4 DECIMAL (30,15), /* Накопительный прирост позиции TOP4  */
				NakopPrirostTOP5 DECIMAL (30,15), /* Накопительный прирост позиции TOP5  */
				NakopPrirostTOP6 DECIMAL (30,15), /* Накопительный прирост позиции TOP6  */
				NakopPrirostTOP7 DECIMAL (30,15), /* Накопительный прирост позиции TOP7  */
				NakopPrirostTOP8 DECIMAL (30,15), /* Накопительный прирост позиции TOP8  */
				NakopPrirostTOP9 DECIMAL (30,15), /* Накопительный прирост позиции TOP9  */
				NakopPrirostTOP10 DECIMAL (30,15), /* Накопительный прирост позиции TOP10  */
				Index1 DECIMAL (30,15) /* ИНДЕКС ТОП-10 по ТОП 10 с 01.07.2017  */
                )";

                if(!mysqli_query($connect,$sql)) {
                    return False;
                }
                else {
                	$sql = "INSERT INTO {$db_main}.ListIndexUSD1 (date, PrirostTOP1, PrirostTOP2, PrirostTOP3, PrirostTOP4, PrirostTOP5, PrirostTOP6, PrirostTOP7, PrirostTOP8,
                	PrirostTOP9, PrirostTOP10, NakopPrirostTOP1, NakopPrirostTOP2, NakopPrirostTOP3, NakopPrirostTOP4, NakopPrirostTOP5, NakopPrirostTOP6, NakopPrirostTOP7, NakopPrirostTOP8, NakopPrirostTOP9, NakopPrirostTOP10, Index1)
                    VALUES ('2017-07-1 23:59', 1, 1, 1, 1, 1, 1, 1, 1,
                	1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1000)";
                    // date('Y-m-d 23:59',$date)
                    $res=mysqli_query($connect,$sql);
		            return True;

                }
            }
        	Else {
        		return True;
        	}
		}

		public function CreateNewConfigPair($connect,$db_main, $idDonor, $IdCurrenciesPair1, $IdCurrenciesPair2, $namePairForDonor, $periodSec) // Создание настройки конкретной пары валют. Вернет ID настроеной пары, если настройка пары существовала, то меняет настройки этой пары валют и вернет id настройки пары
		{
			if (!$this->CreateListPair($connect,$db_main)) {
				return False;
			}
			Else {
				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListPair WHERE idDonor=".$idDonor." AND IdCurrenciesPair1=".$IdCurrenciesPair1." AND IdCurrenciesPair2=".$IdCurrenciesPair2." AND periodSec=".$periodSec;
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');
	            //echo ($num_parts.' '.$shortName.'<br>');
	            if ($num_parts>0) {
	            	$sql="SELECT *  FROM {$db_main}.ListPair WHERE idDonor=".$idDonor." AND IdCurrenciesPair1=".$IdCurrenciesPair1." AND IdCurrenciesPair2=".$IdCurrenciesPair2." AND periodSec=".$periodSec;
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
	            	$result['id'] = $id;
	            	$result['idDonor'] = mysqli_result($res,0,'idDonor');
	            	$result['IdCurrenciesPair1'] = mysqli_result($res,0,'IdCurrenciesPair1');
	            	$result['IdCurrenciesPair2'] = mysqli_result($res,0,'IdCurrenciesPair2');
	            	$result['nameTableSavePair'] = mysqli_result($res,0,'nameTableSavePair');
	            	$result['namePairForDonor'] = mysqli_result($res,0,'namePairForDonor');
	            	$result['periodSec'] = mysqli_result($res,0,'periodSec');

	            	//echo ($id.' | '.longName_old.' | '.' | '.$CiberCurrencies_old.'<br>');
	                // Обновляем информацию
	                $sql = "UPDATE {$db_main}.ListPair SET namePairForDonor = ".$namePairForDonor." WHERE idDonor=".$idDonor." AND IdCurrenciesPair1=".$IdCurrenciesPair1." AND IdCurrenciesPair2=".$IdCurrenciesPair2." AND periodSec=".$periodSec;
                    $res=mysqli_query($connect,$sql);
                    return $result;
	            }
	            else {
	                // Формируем название таблицы для пары валют
	                $nameTableSavePair = 'Pair_'.$idDonor.'_'.$IdCurrenciesPair1.'_'.$IdCurrenciesPair2; //тут
	                // Создаем новую строку
	                $sql = "INSERT INTO {$db_main}.ListPair (idDonor, IdCurrenciesPair1, IdCurrenciesPair2, nameTableSavePair, namePairForDonor, periodSec)
                    VALUES (".$idDonor.", ".$IdCurrenciesPair1.", ".$IdCurrenciesPair2.", '".$nameTableSavePair."', '".$namePairForDonor."', ".$periodSec.")";
                    $res=mysqli_query($connect,$sql);
                    //echo ($sql.'<br>');
                    $sql="SELECT * FROM {$db_main}.ListPair WHERE idDonor=".$idDonor." AND IdCurrenciesPair1=".$IdCurrenciesPair1." AND IdCurrenciesPair2=".$IdCurrenciesPair2." AND periodSec=".$periodSec;
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
	            	$result['id'] = $id;
	            	$result['idDonor'] = $idDonor;
	            	$result['IdCurrenciesPair1'] = $IdCurrenciesPair1;
	            	$result['IdCurrenciesPair2'] = $IdCurrenciesPair2;
	            	$result['nameTableSavePair'] = $nameTableSavePair;
	            	$result['namePairForDonor'] = $namePairForDonor;
	            	$result['periodSec'] = $periodSec;

	            	$sql = "CREATE TABLE {$db_main}.{$nameTableSavePair} (
                	id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                	date TIMESTAMP, /* информация на дату и время */
					high DECIMAL (30,15), /* максимальное значение за период */
					low DECIMAL (30,15), /* минимальное значение за период */
					open DECIMAL (30,15), /* курс на начало дня */
					close DECIMAL (30,15), /* курс на конец дня */
					volume DECIMAL (30,15), /* объем торгов */
					quoteVolume DECIMAL (30,15),
					weightedAverage DECIMAL (30,15),
					marketCap DECIMAL (30,15), /* капитализация по всем магазинам */
					INDEX (date) /* Индекс к полю date */
                	)";
                	$res=mysqli_query($connect,$sql);
	            	return $result;
	            }
			}
		}

		public function CreateNewPairCoinmarketcap($connect,$db_main, $nameTable, $date, $high, $low, $open, $close, $volume, $MarketCap) // Создание строки содержащей информацию о  соотношении цен 2-х пар валют.
		{

				//$date YY-MM-DD HH:MM

				$sql="SELECT COUNT(*) AS num FROM {$db_main}.{$nameTable} WHERE date='".date('Y-m-d 23:59',$date)."'";
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');
	            //echo ($num_parts.' '.$shortName.'<br>');
	            if ($num_parts>0) {
	            	// Обновляем данные о паре валют в БД
	            	$sql = "UPDATE {$db_main}.{$nameTable} SET high = '".$high."', low = '".$low."', open = '".$open."', close = '".$close."', volume = '".$volume."', marketCap = '".$MarketCap."' where date='".date('Y-m-d 23:59',$date)."'";
                	$res=mysqli_query($connect,$sql);
	            }
	            else {
	            	// Создаем новую строку с информацией о паре валют

	                $sql = "INSERT INTO {$db_main}.{$nameTable} (date, high, low, open, close, volume, marketCap)
                    VALUES ('".date('Y-m-d 23:59',$date)."','".$high."', '".$low."', '".$open."', '".$close."', '".$volume."','".$MarketCap."')";
                    //echo($sql.'<br>');
                    $res=mysqli_query($connect,$sql);
	            }
		}
		public function CreateNewPairPoloniex($connect,$db_main, $nameTable, $date, $high, $low, $open, $close, $volume, $quoteVolume, $weightedAverage) // Создание строки содержащей информацию о  соотношении цен 2-х пар валют.
		{

				//$date YY-MM-DD HH:MM

				$sql="SELECT COUNT(*) AS num FROM {$db_main}.{$nameTable} WHERE date='".date('Y-m-d H:i',$date)."'";
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');
	            //echo ($num_parts.' '.$shortName.'<br>');
	            if ($num_parts>0) {
	            	// Обновляем данные о паре валют в БД
	            	$sql = "UPDATE {$db_main}.{$nameTable} SET high = '".$high."', low = '".$low."', open = '".$open."', close = '".$close."', volume = '".$volume."', quoteVolume = '".$quoteVolume."', weightedAverage = '".$weightedAverage."' where date='".date('Y-m-d H:i',$date)."'";
                	$res=mysqli_query($connect,$sql);
	            }
	            else {
	            	// Создаем новую строку с информацией о паре валют

	                $sql = "INSERT INTO {$db_main}.{$nameTable} (date, high, low, open, close, volume, quoteVolume, weightedAverage)
                    VALUES ('".date('Y-m-d H:i',$date)."','".$high."', '".$low."', '".$open."', '".$close."', '".$volume."', '".$quoteVolume."','".$weightedAverage."')";
                    //echo($sql.'<br>');
                    $res=mysqli_query($connect,$sql);
	            }
		}

		public function CreateNewPairCBR($connect,$db_main, $nameTable, $date, $open, $close) // Создание строки содержащей информацию о  соотношении цен 2-х пар валют.
		{

				//$date YY-MM-DD HH:MM

				$sql="SELECT COUNT(*) AS num FROM {$db_main}.{$nameTable} WHERE date='".date('Y-m-d 23:59',$date)."'";
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');
	            //echo ($num_parts.' '.$shortName.'<br>');
	            if ($num_parts>0) {
	            	// Обновляем данные о паре валют в БД
	            	$sql = "UPDATE {$db_main}.{$nameTable} SET open = '".$open."', close = '".$close."' where date='".date('Y-m-d 23:59',$date)."'";
                	$res=mysqli_query($connect,$sql);
	            }
	            else {
	            	// Создаем новую строку с информацией о паре валют

	                $sql = "INSERT INTO {$db_main}.{$nameTable} (date, open, close)
                    VALUES ('".date('Y-m-d 23:59',$date)."', '".$open."', '".$close."')";
                    //echo($sql.'<br>');
                    $res=mysqli_query($connect,$sql);
	            }
		}

		public function CreateNewPairRBC($connect,$db_main, $nameTable, $date, $Low, $High, $Open, $Last) // Создание строки содержащей информацию о  соотношении цен 2-х пар валют.
		{

				//$date YY-MM-DD HH:MM

				$sql="SELECT COUNT(*) AS num FROM {$db_main}.{$nameTable} WHERE date='".date('Y-m-d 23:59',$date)."'";
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');
	            //echo ($num_parts.' '.$shortName.'<br>');
	            if ($num_parts>0) {
	            	// Обновляем данные о паре валют в БД
	            	$sql = "UPDATE {$db_main}.{$nameTable} SET high = '".$High."', low = '".$Low."', open = '".$Open."', close = '".$Last."' where date='".date('Y-m-d 23:59',$date)."'";
                	$res=mysqli_query($connect,$sql);
	            }
	            else {
	            	// Создаем новую строку с информацией о паре валют

	                $sql = "INSERT INTO {$db_main}.{$nameTable} (date, high, low, open, close)
                    VALUES ('".date('Y-m-d 23:59',$date)."','".$High."', '".$Low."', '".$Open."', '".$Last."')";
                    //echo($sql.'<br>');
                    $res=mysqli_query($connect,$sql);
	            }
		}

		public function CreateListCurrencies($connect,$db_main) // Создание таблицы ListCurrencies в БД. Вернет True если таблица создана или существовала
		{
			// @mysqli_query ($connect,"SET NAMES `cp1251`");
	        $res=mysqli_query($connect,"SHOW TABLES FROM {$db_main} LIKE 'ListCurrencies';");
            $counter=0;
            if(mysqli_num_rows($res)==0){
                $sql = "CREATE TABLE {$db_main}.ListCurrencies (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				shortName VARCHAR(50), /* короткое имя валюты */
				longName VARCHAR(100), /* длинное имя валюты */
				CiberCurrencies tinyint(1) default 1, /* если 1, то кибервалюта */
				INDEX ListCurrenciesLongName (longName), /* Индекс к полю NameDonor */
				INDEX ListCurrenciesShortName (shortName) /* Индекс к полю NameDonor */
                )";

                if(!mysqli_query($connect,$sql)) {
                    return False;
                }
                else {
		            return True;
                }
            }
        	Else {
        		return True;
        	}
		}

		public function CreateNewCurrencies($connect,$db_main, $shortName, $longName, $CiberCurrencies) // Создание записи валюты в таблице
		{
			if (!$this->CreateListCurrencies($connect,$db_main)) {
				return False;
			}
			Else {
				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListCurrencies WHERE longName='".$longName."'";
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');
	            //echo ($num_parts.' '.$shortName.'<br>');
	            if ($num_parts>0) {
	            	$sql="SELECT id, shortName, longName, CiberCurrencies FROM {$db_main}.ListCurrencies WHERE longName='".$longName."'";
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
                    return $id;
	            }
	            else {
	                // Создаем новую строку
	                $sql = "INSERT INTO {$db_main}.ListCurrencies (shortName, longName, CiberCurrencies)
                    VALUES ('".$shortName."', '".$longName."', ".$CiberCurrencies.")";
                    $res=mysqli_query($connect,$sql);
                    //echo ($sql.'<br>');
                    $sql="SELECT id FROM {$db_main}.ListCurrencies WHERE shortName='".$shortName."'";
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
	            	return $id;
	            }
			}

		}

		public function GetListCurrencies($connect,$db_main) // Возвращает список валют в массив
		{

			$result = array(); //array('id' => array(shortName, longName, CiberCurrencies))

			if (!$this->CreateListCurrencies($connect,$db_main)) {
				return $result;
			}
			Else {
	            $sql="SELECT id, shortName, longName, CiberCurrencies FROM {$db_main}.ListCurrencies order by shortName";
                $res=mysqli_query($connect,$sql);
	            if(mysqli_num_rows($res)){
					while($arr=mysqli_fetch_assoc($res)){
						$result[$arr['id']]['shortName'] = $arr['shortName'];
						$result[$arr['id']]['longName'] = $arr['longName'];
						$result[$arr['id']]['CiberCurrencies'] = $arr['CiberCurrencies'];

					}
				}
                return $result;;
	        }

		}

		public function GetCurrencies($connect,$db_main,$nameTable,$date_get) // Возвращает значение валюты на конкретную дату в массив
		{

			$result = array(); //array('id' => array(shortName, longName, CiberCurrencies))

	        $sql="SELECT * FROM {$db_main}.{$nameTable} WHERE date='".date('Y-m-d 23:59',$date_get)."'";
            $res=mysqli_query($connect,$sql);
	        if(mysqli_num_rows($res)){
				while($arr=mysqli_fetch_assoc($res)){
					$result['high'] = number_format($arr['high'],15,',','');
					$result['low'] = number_format($arr['low'],15,',','');;
					$result['open'] = number_format($arr['open'],15,',','');;
					$result['close'] = number_format($arr['close'],15,',','');;
					$result['volume'] = number_format($arr['volume'],15,',','');;
					$result['quoteVolume'] = number_format($arr['quoteVolume'],15,',','');;
					$result['weightedAverage'] = number_format($arr['weightedAverage'],15,',','');;
					$result['marketCap'] = number_format($arr['marketCap'],15,',','');;
					break;
				}
			}
            return $result;

		}

		public function GetCurrenciesPoint($connect,$db_main,$nameTable,$date_get) // Возвращает значение валюты на конкретную дату в массив
		{

			$result = array(); //array('id' => array(shortName, longName, CiberCurrencies))

	        $sql="SELECT * FROM {$db_main}.{$nameTable} WHERE date='".date('Y-m-d 23:59',$date_get)."'";
            $res=mysqli_query($connect,$sql);
	        if(mysqli_num_rows($res)){
				while($arr=mysqli_fetch_assoc($res)){
					$result['high'] = number_format($arr['high'],15,'.','');
					$result['low'] = number_format($arr['low'],15,'.','');;
					$result['open'] = number_format($arr['open'],15,'.','');;
					$result['close'] = number_format($arr['close'],15,'.','');;
					$result['volume'] = number_format($arr['volume'],15,'.','');;
					$result['quoteVolume'] = number_format($arr['quoteVolume'],15,'.','');;
					$result['weightedAverage'] = number_format($arr['weightedAverage'],15,'.','');;
					$result['marketCap'] = number_format($arr['marketCap'],15,'.','');;
					break;
				}
			}
            return $result;

		}

		public function GetShortNameCurrencies($connect,$db_main,$id_currency) // Возвращает короткое название валюты
		{

			$result = '';

	        $sql="SELECT * FROM {$db_main}.ListCurrencies WHERE id=".$id_currency;
            $res=mysqli_query($connect,$sql);
	        if(mysqli_num_rows($res)){
				while($arr=mysqli_fetch_assoc($res)){
					$result = $arr['shortName'];
					break;
				}
			}
            return $result;

		}

		public function GetLongNameCurrencies($connect,$db_main,$id_currency) // Возвращает длинное название валюты
		{

			$result = '';

	        $sql="SELECT * FROM {$db_main}.ListCurrencies WHERE id=".$id_currency;
            $res=mysqli_query($connect,$sql);
	        if(mysqli_num_rows($res)){
				while($arr=mysqli_fetch_assoc($res)){
					$result = $arr['longName'];
					break;
				}
			}
            return $result;

		}

		public function GetListPairCurrencies24h($connect,$db_main, $id_currency_1, $id_currency_2,$result) // Вписывает список курсов валюты $id_currency_2 в номинале $id_currency_1 в массив $result.
		{

			$period = 86400; // период сутки

			$b = true;

			$shortname_main = $this->GetShortNameCurrencies($connect,$db_main,$id_currency_1);
			$shortname2 = $this->GetShortNameCurrencies($connect,$db_main,$id_currency_2);
			$full_name = $shortname2.' in '.$shortname_main;

			// Ищем подходящие пары валют

			$sql = "SELECT a.nameTableSavePair, b.Priority FROM {$db_main}.ListPair a, {$db_main}.ListDonor b WHERE a.IdCurrenciesPair1 = ".$id_currency_1." AND a.IdCurrenciesPair2 = ".$id_currency_2." AND a.periodSec = ".$period." AND a.idDonor = b.id ORDER BY b.Priority";
			$res=mysqli_query($connect,$sql);
	        if(mysqli_num_rows($res)){
	        	/*
	        	echo '<pre>';
	        	print_r(mysqli_fetch_assoc($res));
	        	echo '</pre>';
	        	*/
				while($arr=mysqli_fetch_assoc($res)){
					$nameTableSavePair = $arr['nameTableSavePair'];
					//echo $nameTableSavePair.'<br>';
					$b = false;
					break;
				}
				// Выгружаем необходимые данные из обнаруженной таблицы
				if (!$b) {
					foreach ($result as $key => $value) {
						// Выгружаем валюты по порядку
						//GetCurrencies($connect,$db_main,$nameTable,$date_get)
						$res_cur = $this->GetCurrencies($connect,$db_main,$nameTableSavePair,$key);
						if (count($res_cur)<2) continue;

						$result[$key][$full_name]['high'] = $res_cur['high'];
						$result[$key][$full_name]['low'] = $res_cur['low'];
						$result[$key][$full_name]['open'] = $res_cur['open'];
						$result[$key][$full_name]['close'] = $res_cur['close'];
						$result[$key][$full_name]['volume'] = $res_cur['volume'];
						$result[$key][$full_name]['quoteVolume'] = $res_cur['quoteVolume'];
						$result[$key][$full_name]['weightedAverage'] = $res_cur['weightedAverage'];
						$result[$key][$full_name]['marketCap'] = $res_cur['marketCap'];
					}
				}
			}

			return $result;
		}

		public function GetCacheindexCalc($connect,$db_main, $date_calculate) // рассчитывает и возращает параметры индексов на конкретную дату. возвращаем массив значений
		{
			$date_calculate = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate), date('Y',$date_calculate));
			$DateStart = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate)-3, date('Y',$date_calculate));
			$DateYesterday = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate)-1, date('Y',$date_calculate));
//			echo ('$date_calculate= '.date('d.m.Y H:i',$date_calculate).'<br>');
//			echo ('$DateYesterday= '.date('d.m.Y H:i',$DateYesterday).'<br>');

			$period = 86400;
			$this->CreateCacheindexCalc($connect,$db_main);
			$result=array();

			$b = true;
			$icomplite = 0;
			$resultComplite = array();
			$sql = "SELECT * FROM {$db_main}.CacheindexCalc WHERE date = '".date('Y-m-d 23:59',$date_calculate)."' AND NameIndex='ИНДЕКС ТОП-10'";
			$res=mysqli_query($connect,$sql);
		    if(mysqli_num_rows($res)){
		    	while($arr=mysqli_fetch_assoc($res)){
					$resultComplite[$icomplite] = array();
					$resultComplite[$icomplite]['date'] = $arr['date'];
					$resultComplite[$icomplite]['NameIndex'] = $arr['NameIndex'];
					$resultComplite[$icomplite]['priority'] = $arr['priority'];
					$resultComplite[$icomplite]['ValueIndex'] = $arr['ValueIndex'];
					$resultComplite[$icomplite]['ValueIndexUP'] = $arr['ValueIndexUP'];
					$resultComplite[$icomplite]['PersentDay1'] = $arr['PersentDay1'];
					$resultComplite[$icomplite]['PersentDay1UP'] = $arr['PersentDay1UP'];
					$resultComplite[$icomplite]['PersentDay7'] = $arr['PersentDay7'];
					$resultComplite[$icomplite]['PersentDay7UP'] = $arr['PersentDay7UP'];
					$resultComplite[$icomplite]['PersentDay14'] = $arr['PersentDay14'];
					$resultComplite[$icomplite]['PersentDay14UP'] = $arr['PersentDay14UP'];
					$resultComplite[$icomplite]['PersentDay31'] = $arr['PersentDay31'];
					$resultComplite[$icomplite]['PersentDay31UP'] = $arr['PersentDay31UP'];
					$resultComplite[$icomplite]['PersentDay90'] = $arr['PersentDay90'];
					$resultComplite[$icomplite]['PersentDay90UP'] = $arr['PersentDay90UP'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'] = array();
					$resultComplite[$icomplite]['NameCurrenciesTOP'][1] =$arr['NameCurrenciesTOP1'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][2] =$arr['NameCurrenciesTOP2'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][3] =$arr['NameCurrenciesTOP3'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][4] =$arr['NameCurrenciesTOP4'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][5] =$arr['NameCurrenciesTOP5'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][6] =$arr['NameCurrenciesTOP6'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][7] =$arr['NameCurrenciesTOP7'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][8] =$arr['NameCurrenciesTOP8'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][9] =$arr['NameCurrenciesTOP9'];
					$resultComplite[$icomplite]['NameCurrenciesTOP'][10] =$arr['NameCurrenciesTOP10'];
					$resultComplite[$icomplite]['VESmarketCapTOP'] = array();
					$resultComplite[$icomplite]['VESmarketCapTOP'][1] = $arr['VESmarketCapTOP1'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][2] = $arr['VESmarketCapTOP2'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][3] = $arr['VESmarketCapTOP3'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][4] = $arr['VESmarketCapTOP4'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][5] = $arr['VESmarketCapTOP5'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][6] = $arr['VESmarketCapTOP6'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][7] = $arr['VESmarketCapTOP7'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][8] = $arr['VESmarketCapTOP8'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][9] = $arr['VESmarketCapTOP9'];
					$resultComplite[$icomplite]['VESmarketCapTOP'][10] = $arr['VESmarketCapTOP10'];
					$resultComplite[$icomplite]['CurrentCloseTOP'] = array();
					$resultComplite[$icomplite]['CurrentCloseTOP'][1] = $arr['CurrentCloseTOP1'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][2] = $arr['CurrentCloseTOP2'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][3] = $arr['CurrentCloseTOP3'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][4] = $arr['CurrentCloseTOP4'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][5] = $arr['CurrentCloseTOP5'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][6] = $arr['CurrentCloseTOP6'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][7] = $arr['CurrentCloseTOP7'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][8] = $arr['CurrentCloseTOP8'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][9] = $arr['CurrentCloseTOP9'];
					$resultComplite[$icomplite]['CurrentCloseTOP'][10] = $arr['CurrentCloseTOP10'];

					$b = false;
					$icomplite = $icomplite +1;
					return $resultComplite;

				}
		    }

			if ($b) {
				$cache = array();
				for ($i = 1; $i <= 3; $i++) {
					$DateStart = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate)-($i-1), date('Y',$date_calculate));
					$cache[$i-1] = $this -> Index1Calculate24h($connect,$db_main, $DateStart);
//					echo (date('d.m.Y', $DateStart).' | '.$cache[$i-1][['Index1']].'<br>');
				}

				$resultComplite[$icomplite]['date'] = $date_calculate;
				$resultComplite[$icomplite]['NameIndex'] = 'ИНДЕКС ТОП-10';
				$resultComplite[$icomplite]['priority'] = 1;

				// рассчитываем показатели за предыдущий день

				$resultComplite[$icomplite]['ValueIndex'] = $cache[0]['Index1'];
				$resultComplite[$icomplite]['ValueIndexUP'] = ($cache[0]['Index1']>=$cache[1]['Index1']) ? 1 : 0;

				$resultComplite[$icomplite]['PersentDay1'] = ($cache[0]['Index1']/$cache[1]['Index1'] - 1)*100;
				$resultComplite[$icomplite]['PersentDay1UP'] = (($cache[0]['Index1']/$cache[1]['Index1'])>($cache[1]['Index1']/$cache[2]['Index1'])) ? 1 : 0;

				$resultComplite[$icomplite]['NameCurrenciesTOP'] = array ();
				$resultComplite[$icomplite]['VESmarketCapTOP'] = array();
				$resultComplite[$icomplite]['CurrentCloseTOP'] = array();

				$sqlfrag1 ='';
				$sqlfrag2 ='';

				$id_usd = $this->CreateNewCurrencies($connect,$db_main, 'USD', 'US Dollar', 0);

				for ($i = 1; $i <= 10; $i++) {
					$resultComplite[$icomplite]['CurrentCloseTOP'][$i] = '-';
					$resultComplite[$icomplite]['NameCurrenciesTOP'][$i] = $this->GetLongNameCurrencies($connect,$db_main,$cache[0]['IdCurrenciesTOP'][$i]).'('.$this->GetShortNameCurrencies($connect,$db_main,$cache[0]['IdCurrenciesTOP'][$i]).')';

					$resultComplite[$icomplite]['VESmarketCapTOP'][$i] = $cache[0]['VESmarketCapTOP'][$i];



					$sql2 = "SELECT a.nameTableSavePair, b.Priority FROM {$db_main}.ListPair a, {$db_main}.ListDonor b WHERE a.IdCurrenciesPair1 = ".$id_usd." AND a.IdCurrenciesPair2 = ".$cache[0]['IdCurrenciesTOP'][$i]." AND a.periodSec = ".$period." AND a.idDonor = b.id ORDER BY b.Priority";
					//echo ($i.' '.$sql2.'<br>');
					$res2=mysqli_query($connect,$sql2);
					if(mysqli_num_rows($res2)){
						while($arr2=mysqli_fetch_assoc($res2)){
							$nameTableSavePair = $arr2['nameTableSavePair'];
							// читаем информацию курсах за текущую дату
							$res_cur = $this->GetCurrenciesPoint($connect,$db_main,$nameTableSavePair,$date_calculate);
						/*	if ($i==4) {
								echo('<pre>');
								print_r($res_cur);
								echo('</pre>');

							}*/
							if (!isset($res_cur['marketCap'])) continue;

							$close1 = $res_cur['close'];

							$res_cur = $this->GetCurrenciesPoint($connect,$db_main,$nameTableSavePair,$DateYesterday);
						/*	if ($i==4) {
								echo('<pre>');
								print_r($res_cur);
								echo('</pre>');

							}*/
							if (!isset($res_cur['marketCap'])) continue;

							$close2 = $res_cur['close'];

						    $resultComplite[$icomplite]['CurrentCloseTOP'][$i] = number_format($close1,2,'.','').' ('.number_format(($close1/$close2-1)*100,2,'.','').'%)';



							break;
						}
					}

					$sqlfrag1 = $sqlfrag1 . ", " . 'NameCurrenciesTOP'.$i.", " . 'VESmarketCapTOP'.$i.", ".'CurrentCloseTOP'.$i;
					$sqlfrag2 = $sqlfrag2 . ", '" . $resultComplite[$icomplite]['NameCurrenciesTOP'][$i]."', '" . $resultComplite[$icomplite]['VESmarketCapTOP'][$i]."', '".$resultComplite[$icomplite]['CurrentCloseTOP'][$i]."'";


//					$resultComplite[$icomplite]['CurrentCloseTOP'][$i] =

//					$cache[0]
//					echo (date('d.m.Y', $DateStart).' | '.$cache[$i-1][['Index1']].'<br>');
				}

				// Расчитываем показатели за неделю

				$cache = array();
				for ($i = 1; $i <= 3; $i++) {
					$DateStart = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate)-(1+($i-1)*7), date('Y',$date_calculate));
					$cache[$i-1] = $this -> Index1Calculate24h($connect,$db_main, $DateStart);
				}

				$resultComplite[$icomplite]['PersentDay7'] = ($cache[0]['Index1']/$cache[1]['Index1'] - 1)*100;
				$resultComplite[$icomplite]['PersentDay7UP'] = (($cache[0]['Index1']/$cache[1]['Index1'])>($cache[1]['Index1']/$cache[2]['Index1'])) ? 1 : 0;

				// Расчитываем показатели за 2 недели

				$cache = array();
				for ($i = 1; $i <= 3; $i++) {
					$DateStart = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate)-(1+($i-1)*14), date('Y',$date_calculate));
					$cache[$i-1] = $this -> Index1Calculate24h($connect,$db_main, $DateStart);
				}

				$resultComplite[$icomplite]['PersentDay14'] = ($cache[0]['Index1']/$cache[1]['Index1'] - 1)*100;
				$resultComplite[$icomplite]['PersentDay14UP'] = (($cache[0]['Index1']/$cache[1]['Index1'])>($cache[1]['Index1']/$cache[2]['Index1'])) ? 1 : 0;

				// Расчитываем показатели за месяц

				$cache = array();
				for ($i = 1; $i <= 3; $i++) {
					$DateStart = mktime (23, 59, 0, date('m',$date_calculate)-($i-1), date('d',$date_calculate)-1, date('Y',$date_calculate));
					$cache[$i-1] = $this -> Index1Calculate24h($connect,$db_main, $DateStart);
				}

				$resultComplite[$icomplite]['PersentDay31'] = ($cache[0]['Index1']/$cache[1]['Index1'] - 1)*100;
				$resultComplite[$icomplite]['PersentDay31UP'] = (($cache[0]['Index1']/$cache[1]['Index1'])>($cache[1]['Index1']/$cache[2]['Index1'])) ? 1 : 0;

				// Расчитываем показатели за квартал

				$cache = array();
				for ($i = 1; $i <= 3; $i++) {
					$DateStart = mktime (23, 59, 0, date('m',$date_calculate)-($i-1)*3, date('d',$date_calculate)-1, date('Y',$date_calculate));
					$cache[$i-1] = $this -> Index1Calculate24h($connect,$db_main, $DateStart);
				}

				$resultComplite[$icomplite]['PersentDay90'] = ($cache[0]['Index1']/$cache[1]['Index1'] - 1)*100;
				$resultComplite[$icomplite]['PersentDay90UP'] = (($cache[0]['Index1']/$cache[1]['Index1'])>($cache[1]['Index1']/$cache[2]['Index1'])) ? 1 : 0;

				$sql = "INSERT INTO {$db_main}.CacheindexCalc (date, NameIndex, priority, ValueIndex, ValueIndexUP, PersentDay1, PersentDay1UP,
					PersentDay7,PersentDay7UP,PersentDay14,PersentDay14UP,PersentDay31,PersentDay31UP,PersentDay90,PersentDay90UP".$sqlfrag1.")
                    VALUES ('".date('Y-m-d 23:59',$resultComplite[$icomplite]['date'])."', '".$resultComplite[$icomplite]['NameIndex']."', ".$resultComplite[$icomplite]['priority'].",
                    '".$resultComplite[$icomplite]['ValueIndex']."', '".$resultComplite[$icomplite]['ValueIndexUP']."', '".$resultComplite[$icomplite]['PersentDay1']."', '".$resultComplite[$icomplite]['PersentDay1UP']."',
                    '".$resultComplite[$icomplite]['PersentDay7']."', '".$resultComplite[$icomplite]['PersentDay7UP']."', '".$resultComplite[$icomplite]['PersentDay14']."', '".$resultComplite[$icomplite]['PersentDay14UP']."',

                    '".$resultComplite[$icomplite]['PersentDay31']."', '".$resultComplite[$icomplite]['PersentDay31UP']."', '".$resultComplite[$icomplite]['PersentDay90']."', '".$resultComplite[$icomplite]['PersentDay90UP']."'".$sqlfrag2.")";
                    // date('Y-m-d 23:59',$date)
                    $res=mysqli_query($connect,$sql);
		//			echo($sql);
					$icomplite++;


				return $resultComplite;

			}



		}

		public function Index1Calculate24h($connect,$db_main, $date_calculate) // рассчитывает ИНДЕКС ТОП-10 на конкретную дату. возвращаем массив со строкой индекса на конретную дату
		{
			$date_calculate = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate), date('Y',$date_calculate));
			$DateYesterday = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate)-1, date('Y',$date_calculate));
//			echo ('$date_calculate= '.date('d.m.Y H:i',$date_calculate).'<br>');
//			echo ('$DateYesterday= '.date('d.m.Y H:i',$DateYesterday).'<br>');

			$period = 86400;
			$this->CreateListIndexUSD1($connect,$db_main);
			$result=array();

			$result['id']=0;
			$result['date']=$date_calculate;
			$result['IdCurrenciesTOP']['1']=0;
			$result['IdCurrenciesTOP']['2']=0;
			$result['IdCurrenciesTOP']['3']=0;
			$result['IdCurrenciesTOP']['4']=0;
			$result['IdCurrenciesTOP']['5']=0;
			$result['IdCurrenciesTOP']['6']=0;
			$result['IdCurrenciesTOP']['7']=0;
			$result['IdCurrenciesTOP']['8']=0;
			$result['IdCurrenciesTOP']['9']=0;
			$result['IdCurrenciesTOP']['10']=0;
			$result['marketCapTOP']['1']=0;
			$result['marketCapTOP']['2']=0;
			$result['marketCapTOP']['3']=0;
			$result['marketCapTOP']['4']=0;
			$result['marketCapTOP']['5']=0;
			$result['marketCapTOP']['6']=0;
			$result['marketCapTOP']['7']=0;
			$result['marketCapTOP']['8']=0;
			$result['marketCapTOP']['9']=0;
			$result['marketCapTOP']['10']=0;
			$result['SUMmarketCapTOP']=0;
			$result['VESmarketCapTOP']['1']=0;
			$result['VESmarketCapTOP']['2']=0;
			$result['VESmarketCapTOP']['3']=0;
			$result['VESmarketCapTOP']['4']=0;
			$result['VESmarketCapTOP']['5']=0;
			$result['VESmarketCapTOP']['6']=0;
			$result['VESmarketCapTOP']['7']=0;
			$result['VESmarketCapTOP']['8']=0;
			$result['VESmarketCapTOP']['9']=0;
			$result['VESmarketCapTOP']['10']=0;
			$result['PrirostTOP']['1']=1;
			$result['PrirostTOP']['2']=1;
			$result['PrirostTOP']['3']=1;
			$result['PrirostTOP']['4']=1;
			$result['PrirostTOP']['5']=1;
			$result['PrirostTOP']['6']=1;
			$result['PrirostTOP']['7']=1;
			$result['PrirostTOP']['8']=1;
			$result['PrirostTOP']['9']=1;
			$result['PrirostTOP']['10']=1;
			$result['NakopPrirostTOP']['1']=1;
			$result['NakopPrirostTOP']['2']=1;
			$result['NakopPrirostTOP']['3']=1;
			$result['NakopPrirostTOP']['4']=1;
			$result['NakopPrirostTOP']['5']=1;
			$result['NakopPrirostTOP']['6']=1;
			$result['NakopPrirostTOP']['7']=1;
			$result['NakopPrirostTOP']['8']=1;
			$result['NakopPrirostTOP']['9']=1;
			$result['NakopPrirostTOP']['10']=1;
			$result['Index1']=1000;

			if ($date_calculate<=strtotime('01.07.2017 23:59')) {
//				echo (date('d.m.Y H:i',$date_calculate).'<br>');
				return $result;
			}
			ELSE {
				$b = true;
				$sql = "SELECT * FROM {$db_main}.ListIndexUSD1 WHERE date = '".date('Y-m-d 23:59',$date_calculate)."'";
				$res=mysqli_query($connect,$sql);
		        if(mysqli_num_rows($res)){
	        		/*
		        	echo '<pre>';
		        	print_r(mysqli_fetch_assoc($res));
	    	    	echo '</pre>';
	        		*/
					while($arr=mysqli_fetch_assoc($res)){

						//$nameTableSavePair = $arr['nameTableSavePair'];
						//echo $nameTableSavePair.'<br>';
						$b = false;
						$result['id']=$arr['id'];
						$result['date']=strtotime($arr['date']);
						$result['IdCurrenciesTOP']['1']=$arr['IdCurrenciesTOP1'];
						$result['IdCurrenciesTOP']['2']=$arr['IdCurrenciesTOP2'];
						$result['IdCurrenciesTOP']['3']=$arr['IdCurrenciesTOP3'];
						$result['IdCurrenciesTOP']['4']=$arr['IdCurrenciesTOP4'];
						$result['IdCurrenciesTOP']['5']=$arr['IdCurrenciesTOP5'];
						$result['IdCurrenciesTOP']['6']=$arr['IdCurrenciesTOP6'];
						$result['IdCurrenciesTOP']['7']=$arr['IdCurrenciesTOP7'];
						$result['IdCurrenciesTOP']['8']=$arr['IdCurrenciesTOP8'];
						$result['IdCurrenciesTOP']['9']=$arr['IdCurrenciesTOP9'];
						$result['IdCurrenciesTOP']['10']=$arr['IdCurrenciesTOP10'];
						$result['marketCapTOP']['1']=$arr['marketCapTOP1'];
						$result['marketCapTOP']['2']=$arr['marketCapTOP2'];
						$result['marketCapTOP']['3']=$arr['marketCapTOP3'];
						$result['marketCapTOP']['4']=$arr['marketCapTOP4'];
						$result['marketCapTOP']['5']=$arr['marketCapTOP5'];
						$result['marketCapTOP']['6']=$arr['marketCapTOP6'];
						$result['marketCapTOP']['7']=$arr['marketCapTOP7'];
						$result['marketCapTOP']['8']=$arr['marketCapTOP8'];
						$result['marketCapTOP']['9']=$arr['marketCapTOP9'];
						$result['marketCapTOP']['10']=$arr['marketCapTOP10'];
						$result['SUMmarketCapTOP']=$arr['SUMmarketCapTOP'];
						$result['VESmarketCapTOP']['1']=$arr['VESmarketCapTOP1'];
						$result['VESmarketCapTOP']['2']=$arr['VESmarketCapTOP2'];
						$result['VESmarketCapTOP']['3']=$arr['VESmarketCapTOP3'];
						$result['VESmarketCapTOP']['4']=$arr['VESmarketCapTOP4'];
						$result['VESmarketCapTOP']['5']=$arr['VESmarketCapTOP5'];
						$result['VESmarketCapTOP']['6']=$arr['VESmarketCapTOP6'];
						$result['VESmarketCapTOP']['7']=$arr['VESmarketCapTOP7'];
						$result['VESmarketCapTOP']['8']=$arr['VESmarketCapTOP8'];
						$result['VESmarketCapTOP']['9']=$arr['VESmarketCapTOP9'];
						$result['VESmarketCapTOP']['10']=$arr['VESmarketCapTOP10'];
						$result['PrirostTOP']['1']=$arr['PrirostTOP1'];
						$result['PrirostTOP']['2']=$arr['PrirostTOP2'];
						$result['PrirostTOP']['3']=$arr['PrirostTOP3'];
						$result['PrirostTOP']['4']=$arr['PrirostTOP4'];
						$result['PrirostTOP']['5']=$arr['PrirostTOP5'];
						$result['PrirostTOP']['6']=$arr['PrirostTOP6'];
						$result['PrirostTOP']['7']=$arr['PrirostTOP7'];
						$result['PrirostTOP']['8']=$arr['PrirostTOP8'];
						$result['PrirostTOP']['9']=$arr['PrirostTOP9'];
						$result['PrirostTOP']['10']=$arr['PrirostTOP10'];
						$result['NakopPrirostTOP']['1']=$arr['NakopPrirostTOP1'];
						$result['NakopPrirostTOP']['2']=$arr['NakopPrirostTOP2'];
						$result['NakopPrirostTOP']['3']=$arr['NakopPrirostTOP3'];
						$result['NakopPrirostTOP']['4']=$arr['NakopPrirostTOP4'];
						$result['NakopPrirostTOP']['5']=$arr['NakopPrirostTOP5'];
						$result['NakopPrirostTOP']['6']=$arr['NakopPrirostTOP6'];
						$result['NakopPrirostTOP']['7']=$arr['NakopPrirostTOP7'];
						$result['NakopPrirostTOP']['8']=$arr['NakopPrirostTOP8'];
						$result['NakopPrirostTOP']['9']=$arr['NakopPrirostTOP9'];
						$result['NakopPrirostTOP']['10']=$arr['NakopPrirostTOP10'];
						$result['Index1']=$arr['Index1'];
						return $result;
						break;
					}
				}

				if ($b) {
					$DateYesterday = mktime (23, 59, 0, date('m',$date_calculate), date('d',$date_calculate)-1, date('Y',$date_calculate));
					$resultYesterday = $this -> Index1Calculate24h($connect,$db_main, $DateYesterday); // берем данные за вчерашний день

					// Загружаем данные по валютам за дату и выбираем ТОП 10 валют по капитализации
					$id_usd = $this->CreateNewCurrencies($connect,$db_main, 'USD', 'US Dollar', 0);
					$result2 = array();
					$result2[$date_calculate] = array();
					$result2[$DateYesterday] = array();

					//GetListPairCurrencies24h($connect,$db_main, $id_currency_1, $id_currency_2,$result)
					$sql = "SELECT * FROM {$db_main}.ListCurrencies";
					$res=mysqli_query($connect,$sql);
					if(mysqli_num_rows($res)){
						while($arr=mysqli_fetch_assoc($res)){
							$idCurrency = $arr['id'];
							//$full_name = $this->GetShortNameCurrencies($connect,$db_main,$idCurrency);
							$CurrencyShortName = $arr['shortName'];
							$full_name = $arr['shortName'];
							$sql2 = "SELECT a.nameTableSavePair, b.Priority FROM {$db_main}.ListPair a, {$db_main}.ListDonor b WHERE a.IdCurrenciesPair1 = ".$id_usd." AND a.IdCurrenciesPair2 = ".$idCurrency." AND a.periodSec = ".$period." AND a.idDonor = b.id ORDER BY b.Priority";
							$res2=mysqli_query($connect,$sql2);
							if(mysqli_num_rows($res2)){
								while($arr2=mysqli_fetch_assoc($res2)){
									$nameTableSavePair = $arr2['nameTableSavePair'];
									// читаем информацию курсах за текущую дату
									$res_cur = $this->GetCurrenciesPoint($connect,$db_main,$nameTableSavePair,$date_calculate);
									if (!isset($res_cur['marketCap'])) continue;

									$result2[$date_calculate][$full_name]['id'] = $idCurrency;
									$result2[$date_calculate][$full_name]['high'] = $res_cur['high'];
									$result2[$date_calculate][$full_name]['low'] = $res_cur['low'];
									$result2[$date_calculate][$full_name]['open'] = $res_cur['open'];
									$result2[$date_calculate][$full_name]['close'] = $res_cur['close'];
									$result2[$date_calculate][$full_name]['volume'] = $res_cur['volume'];
									$result2[$date_calculate][$full_name]['quoteVolume'] = $res_cur['quoteVolume'];
									$result2[$date_calculate][$full_name]['weightedAverage'] = $res_cur['weightedAverage'];
									$result2[$date_calculate][$full_name]['marketCap'] = $res_cur['marketCap'];

									// читаем информацию курсах за предыдущую дату
									$res_cur = $this->GetCurrenciesPoint($connect,$db_main,$nameTableSavePair,$DateYesterday);
									if (!isset($res_cur['marketCap'])) continue;

									$result2[$DateYesterday][$full_name]['id'] = $idCurrency;
									$result2[$DateYesterday][$full_name]['high'] = $res_cur['high'];
									$result2[$DateYesterday][$full_name]['low'] = $res_cur['low'];
									$result2[$DateYesterday][$full_name]['open'] = $res_cur['open'];
									$result2[$DateYesterday][$full_name]['close'] = $res_cur['close'];
									$result2[$DateYesterday][$full_name]['volume'] = $res_cur['volume'];
									$result2[$DateYesterday][$full_name]['quoteVolume'] = $res_cur['quoteVolume'];
									$result2[$DateYesterday][$full_name]['weightedAverage'] = $res_cur['weightedAverage'];
									$result2[$DateYesterday][$full_name]['marketCap'] = $res_cur['marketCap'];


									break;
								}
							}
						}
					}

/*					echo('$result<br><pre>');
					print_r($result);
					echo('</pre>');


					echo('$result2<br><pre>');
					print_r($result2);
					echo('</pre>');
*/

					// Ищем ТОП 10
					foreach ($result2[$date_calculate] as $key => $value) {
						//$result2[$date_calculate]
						for ($i = 1; $i <= 10; $i++) {
							if ((float)$result['marketCapTOP'][$i]<(float)$value['marketCap']) {
								//echo('$key='.$key.'  marketcap='.(float)$value['marketCap'].' marketCapTOP='.(float)$result['marketCapTOP'][$i].' closeyesterday='.(float)$result2[$DateYesterday][$key]['close'].' i='.$i.'<br>');
								// нашли новый максимум, вписываем его в ТОП
								if ((float)$result2[$DateYesterday][$key]['close']==0) continue;
								// двигаем старые элементы
								for ($j = 10; $j > $i; $j--) {
									$result['marketCapTOP'][$j] = $result['marketCapTOP'][$j-1];
									$result['IdCurrenciesTOP'][$j] = $result['IdCurrenciesTOP'][$j-1];
									$result['PrirostTOP'][$j] = $result['PrirostTOP'][$j-1];
								}

								$result['marketCapTOP'][$i] = $value['marketCap'];
								$result['IdCurrenciesTOP'][$i] = $value['id'];

								$result['PrirostTOP'][$i] = 1+((float)$value['close']-(float)$result2[$DateYesterday][$key]['close'])/(float)$result2[$DateYesterday][$key]['close'];
								break;
							}
						}
					}

					// расчитываем сумму капитализации
					foreach ($result['marketCapTOP'] as $key => $value) {
						$result['SUMmarketCapTOP'] = $result['SUMmarketCapTOP'] + $value;
					}

					// Расчитываем вес каждого ТОП
					foreach ($result['marketCapTOP'] as $key => $value) {
						$result['VESmarketCapTOP'][$key] = $result['marketCapTOP'][$key]/$result['SUMmarketCapTOP'];
					}

					// вычисляем накопительный прирост относительно вчерашнего дня
					foreach ($result['PrirostTOP'] as $key => $value) {
						$result['NakopPrirostTOP'][$key] = $value * $resultYesterday['NakopPrirostTOP'][$key];
					}

					// Расчитываем индекс
					$result['Index1']=0;

					foreach ($result['NakopPrirostTOP'] as $key => $value) {
						$result['Index1'] = $result['Index1'] + $result['VESmarketCapTOP'][$key] * $value;
					}
					$result['Index1']=$result['Index1']*1000;

					$sql = "INSERT INTO {$db_main}.ListIndexUSD1 (date, IdCurrenciesTOP1, IdCurrenciesTOP2, IdCurrenciesTOP3, IdCurrenciesTOP4, IdCurrenciesTOP5, IdCurrenciesTOP6, IdCurrenciesTOP7, IdCurrenciesTOP8, IdCurrenciesTOP9, IdCurrenciesTOP10,
					marketCapTOP1,marketCapTOP2,marketCapTOP3,marketCapTOP4,marketCapTOP5,marketCapTOP6,marketCapTOP7,marketCapTOP8,marketCapTOP9,marketCapTOP10,
					SUMmarketCapTOP, VESmarketCapTOP1,VESmarketCapTOP2,VESmarketCapTOP3,VESmarketCapTOP4,VESmarketCapTOP5,VESmarketCapTOP6,VESmarketCapTOP7,VESmarketCapTOP8,VESmarketCapTOP9,VESmarketCapTOP10,
					PrirostTOP1, PrirostTOP2, PrirostTOP3, PrirostTOP4, PrirostTOP5, PrirostTOP6, PrirostTOP7, PrirostTOP8,
                	PrirostTOP9, PrirostTOP10, NakopPrirostTOP1, NakopPrirostTOP2, NakopPrirostTOP3, NakopPrirostTOP4, NakopPrirostTOP5, NakopPrirostTOP6, NakopPrirostTOP7, NakopPrirostTOP8, NakopPrirostTOP9, NakopPrirostTOP10, Index1)
                    VALUES ('".date('Y-m-d 23:59',$date_calculate)."', '".$result['IdCurrenciesTOP'][1]."', '".$result['IdCurrenciesTOP'][2]."', '".$result['IdCurrenciesTOP'][3]."', '".$result['IdCurrenciesTOP'][4]."', '".$result['IdCurrenciesTOP'][5]."', '".$result['IdCurrenciesTOP'][6]."',
                    '".$result['IdCurrenciesTOP'][7]."', '".$result['IdCurrenciesTOP'][8]."', '".$result['IdCurrenciesTOP'][9]."', '".$result['IdCurrenciesTOP'][10]."',
                    '".$result['marketCapTOP'][1]."', '".$result['marketCapTOP'][2]."', '".$result['marketCapTOP'][3]."', '".$result['marketCapTOP'][4]."', '".$result['marketCapTOP'][5]."', '".$result['marketCapTOP'][6]."',
                    '".$result['marketCapTOP'][7]."', '".$result['marketCapTOP'][8]."', '".$result['marketCapTOP'][9]."', '".$result['marketCapTOP'][10]."',
                    '".$result['SUMmarketCapTOP']."', '".$result['VESmarketCapTOP'][1]."', '".$result['VESmarketCapTOP'][2]."', '".$result['VESmarketCapTOP'][3]."', '".$result['VESmarketCapTOP'][4]."', '".$result['VESmarketCapTOP'][5]."', '".$result['VESmarketCapTOP'][6]."',
                    '".$result['VESmarketCapTOP'][7]."', '".$result['VESmarketCapTOP'][8]."', '".$result['VESmarketCapTOP'][9]."', '".$result['VESmarketCapTOP'][10]."',
                    '".$result['PrirostTOP'][1]."', '".$result['PrirostTOP'][2]."', '".$result['PrirostTOP'][3]."', '".$result['PrirostTOP'][4]."', '".$result['PrirostTOP'][5]."', '".$result['PrirostTOP'][6]."',
                    '".$result['PrirostTOP'][7]."', '".$result['PrirostTOP'][8]."', '".$result['PrirostTOP'][9]."', '".$result['PrirostTOP'][10]."',
                    '".$result['NakopPrirostTOP'][1]."', '".$result['NakopPrirostTOP'][2]."', '".$result['NakopPrirostTOP'][3]."', '".$result['NakopPrirostTOP'][4]."', '".$result['NakopPrirostTOP'][5]."', '".$result['NakopPrirostTOP'][6]."',
                    '".$result['NakopPrirostTOP'][7]."', '".$result['NakopPrirostTOP'][8]."', '".$result['NakopPrirostTOP'][9]."', '".$result['NakopPrirostTOP'][10]."',
                     '".$result['Index1']."')";
                    // date('Y-m-d 23:59',$date)
                    $res=mysqli_query($connect,$sql);


					return $result;

				}

			}

		}

		public function UpdateCurrencies($connect,$db_main, $shortName, $longName, $CiberCurrencies) // Изменение записи валюты в таблице
		{

				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListCurrencies WHERE longName='".$longName."'";
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');
	            //echo ($num_parts.' '.$shortName.'<br>');
	            if ($num_parts>0) {
	            	$sql="SELECT id, shortName, longName, CiberCurrencies FROM {$db_main}.ListCurrencies WHERE longName='".$longName."'";
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
	            	$shortName_old=mysqli_result($res,0,'shortName');
	            	$CiberCurrencies_old=mysqli_result($res,0,'CiberCurrencies');
	            	//echo ($id.' | '.longName_old.' | '.' | '.$CiberCurrencies_old.'<br>');
	            	if (($shortName_old!=$shortName)and($CiberCurrencies_old!=$CiberCurrencies)) {
	                	// Обновляем информацию
	                	$sql = "UPDATE {$db_main}.ListCurrencies SET shortName = '".$shortName."', CiberCurrencies = ".$CiberCurrencies." where longName='".$longName."'";
                    	$res=mysqli_query($connect,$sql);
                    }
                    return $id;
	            }
	            else {
	                // Создаем новую строку
	                $sql = "INSERT INTO {$db_main}.ListCurrencies (shortName, longName, CiberCurrencies)
                    VALUES ('".$shortName."', '".$longName."', ".$CiberCurrencies.")";
                    $res=mysqli_query($connect,$sql);
                    //echo ($sql.'<br>');
                    $sql="SELECT id FROM {$db_main}.ListCurrencies WHERE shortName='".$shortName."'";
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
	            	return $id;
	            }

		}

		public function CreateListDonor($connect,$db_main) // Создание таблицы ListDonor в БД. Вернет True если таблица создана или существовала
		{
			// @mysqli_query ($connect,"SET NAMES `cp1251`");
	        $res=mysqli_query($connect,"SHOW TABLES FROM {$db_main} LIKE 'ListDonor';");
            $counter=0;
            if(mysqli_num_rows($res)==0){
                $sql = "CREATE TABLE {$db_main}.ListDonor (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				NameDonor VARCHAR(100), /* наименование донора */
				Site VARCHAR(400), /* URL донора */
				Priority tinyint(1) default 10, /* Приоритет использования информации от Донора */
				INDEX ListDonorNameDonor (NameDonor) /* Индекс к полю NameDonor */
                )";

                if(!mysqli_query($connect,$sql)) {
                    return False;
                }
                else {
		            return True;
                }
            }
        	Else {
        		return True;
        	}
		}

		public function UpdateNewDonor($connect,$db_main, $NameDonor, $Site, $Priority = 10) // Изменение записи донора в таблице
		{
			if (!$this->CreateListDonor($connect,$db_main)) {
				return False;
			}
			Else {
				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListDonor WHERE NameDonor='".$NameDonor."'";
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');

	            if ($num_parts>0) {
	            	$sql="SELECT id, Site, Priority FROM {$db_main}.ListDonor WHERE NameDonor='".$NameDonor."'";
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
	            	$Site_old=mysqli_result($res,0,'Site');
	            	$Priority_old=mysqli_result($res,0,'Priority');

	            	if (($Site_old!=$Site)and($Priority_old!=$Priority)) {
	                	// Обновляем информацию
	                	$sql = "UPDATE {$db_main}.ListDonor SET Site = '".$Site."', Priority = ".$Priority." where shortName='".$NameDonor."'";
                    	$res=mysqli_query($connect,$sql);
                    }
                    return $id;
	            }
	            else {
	                // Создаем новую строку
	                $sql = "INSERT INTO {$db_main}.ListDonor (NameDonor, Site, Priority)
                    VALUES ('".$NameDonor."', '".$Site."', ".$Priority.")";
                    $res=mysqli_query($connect,$sql);
                    $sql="SELECT id FROM {$db_main}.ListDonor WHERE NameDonor='".$NameDonor."'";
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
	            	return $id;
	            }
			}

		}

		public function CreateNewDonor($connect,$db_main, $NameDonor, $Site, $Priority = 10) // Создание записи донора в таблице
		{
			if (!$this->CreateListDonor($connect,$db_main)) {
				return False;
			}
			Else {
				$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListDonor WHERE NameDonor='".$NameDonor."'";
                $res=mysqli_query($connect,$sql);
	            $num_parts=mysqli_result($res,0,'num');

	            if ($num_parts>0) {
	            	$sql="SELECT id, Site, Priority FROM {$db_main}.ListDonor WHERE NameDonor='".$NameDonor."'";
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');

                    return $id;
	            }
	            else {
	                // Создаем новую строку
	                $sql = "INSERT INTO {$db_main}.ListDonor (NameDonor, Site, Priority)
                    VALUES ('".$NameDonor."', '".$Site."', ".$Priority.")";
                    $res=mysqli_query($connect,$sql);
                    $sql="SELECT id FROM {$db_main}.ListDonor WHERE NameDonor='".$NameDonor."'";
                	$res=mysqli_query($connect,$sql);
	            	$id=mysqli_result($res,0,'id');
	            	return $id;
	            }
			}

		}

		public function ParseDonorPoloniex($connect,$db_main, $date_start = 1483272000, $date_finish = 1514775600, $period = 86400, $reconnect_attempt = 0) // Парсинг информации с сайта Донора poloniex.com , вернут false если парсинг не пройдет
		{
			// Проверяем наличие необходимых записей в БД
			$id_Polonex = $this->CreateNewDonor($connect,$db_main, 'Poloniex', 'https://poloniex.com', 1);
			//echo ('id_Polonex='.$id_Polonex.'<br>');
			if ($id_Polonex===false) {
				return False;
			}
			Else {
				// Проверяем наличие таблиц ListPair, ListCurrencies
				$this->CreateListCurrencies($connect,$db_main);
				$this->CreateListPair($connect,$db_main);
				// Парсим список валют
				$json_text = file_get_curl('https://poloniex.com/public?command=returnCurrencies');
				if(empty($json_text))
				{
					if ($reconnect_attempt<10) {
						//echo ('Ошибка загрузки списка валют! Попытка № '.$reconnect_attempt.'<br>');
						sleep(2);
						return $this->ParseDonorPoloniex($connect,$db_main,$date_start,$date_finish,$period,$reconnect_attempt+1);
						exit;
					}
					else {
						die('Connection error at function <b>loadListCurrencies</b></br>');
					}

				}
				$json = json_decode($json_text);
				if($json)
				{
					$json_count = count($json);
					if($json_count > 0)
					{
			    		foreach ($json as $element => $item)
			    		{
							if (($item->delisted==1)or($item->disabled==1)) continue;
							//echo ($element.'<br>');
							$this->CreateNewCurrencies($connect,$db_main, $element, $item->name, 1);
	                	}

	                	//Создаем настройки пар

	                	// Ищем ключевую валюту Биткойн
	                	$sql="SELECT COUNT(*) AS num FROM {$db_main}.ListCurrencies WHERE longName='Bitcoin'";
            			$res=mysqli_query($connect,$sql);
	        			$num_parts=mysqli_result($res,0,'num');

	        			if ($num_parts>0) {
	        				$sql="SELECT * FROM {$db_main}.ListCurrencies WHERE longName='Bitcoin'";
            				$res=mysqli_query($connect,$sql);
	        				$id_BTC=mysqli_result($res,0,'id');
	        				$sql="SELECT * FROM {$db_main}.ListCurrencies WHERE longName != 'Bitcoin' ";
	        				//echo ($sql);
							$res=mysqli_query($connect,$sql);
							$counter=0;
							if(mysqli_num_rows($res)){
								while($arr=mysqli_fetch_assoc($res)){
									$IdCurrenciesPair2 = $arr['id'];
									//echo('BTC_'.$arr['shortName'].'<br>');
									$ConfigPair = $this->CreateNewConfigPair($connect,$db_main, $id_Polonex, $id_BTC, $IdCurrenciesPair2, 'BTC_'.$arr['shortName'], $period);

									$this->loadChartDataPoloniexToDB($connect, $db_main, $ConfigPair['nameTableSavePair'], $ConfigPair['namePairForDonor'], 1483272000, 1514775600, $period);
								}
							}

	        			}

					}
				}
			}
		}


		public function loadChartDataPoloniexToDB($connect, $db_main, $nameTable, $currencyPair = 'BTC_DGB', $date_start = 1483272000, $date_finish = 1514775600, $period = 86400, $reconnect_attempt = 0)
		{  //1483315140&end=1514505600&period=   1483315199
			// @mysqli_query ($connect,"SET NAMES `cp1251`");

			$json_text = file_get_curl('https://poloniex.com/public?command=returnChartData&currencyPair='.$currencyPair.'&start='.$date_start.'&end='.$date_finish.'&period='.$period.'&ajxop=1');
	        //echo ($url.'/?ajxop=1<br>');
	        //print_r ($json_text);
			if(empty($json_text))
			{
				//die('Connection error at function <b>loadList</b></br>');
				if($reconnect_attempt < 10)
				{
					sleep(4);
					echo 'Trying to reconnect.</br>';
					return $this->loadChartDataPoloniexToDB($connect, $db_main, $nameTable, $currencyPair, $date_start, $date_finish, $period,  $reconnect_attempt + 1);
				}
				else
				{
					die();
				}
			}
			//echo('1.<br>');
			if (strpos($json_text, 'Invalid currency pair.')===true) exit;
			//echo('2.<br>');
			$json = json_decode($json_text);

			if($json and true)
			{
				$json_count = count($json);

				if($json_count > 0)
				{
					//echo('3.<br>');
				    foreach ($json as $element => $item)
				    {

							//$date_cur = date('d.m.Y H:i', $item->date-3*60*60-1);
							//is_date
							if (!is_object($item)) continue;
							if (!isset($item->date) and !is_int($item->date)) continue;
							$date_cur = date('Y-m-d H:i', $item->date-3*60*60-1);
							$high = $item->high;
							$low = $item->low;
							$open = $item->open;

							$close = number_format($item->close,15,'.','');
							$volume = number_format($item->volume,10,'.','');
							$quoteVolume = $item->quoteVolume;
							$weightedAverage = $item->weightedAverage;

							$this->CreateNewPairPoloniex($connect,$db_main, $nameTable, $item->date-3*60*60-1, $high, $low, $open, $close, $volume, $quoteVolume, $weightedAverage);
					}
				}
			}
		}


		public function ParseDonorRBC($connect,$db_main, $reconnect_attempt = 0) // Парсинг информации с сайта Донора RBC , вернут false если парсинг не пройдет
		{
			// Проверяем наличие необходимых записей в БД
			$id_RBC = $this->CreateNewDonor($connect,$db_main, 'РБК', 'https://quote.rbc.ru/data/export/eod/ticker/157694', 5);

			if ($id_RBC===false) {
				return False;
			}
			Else {
				// Проверяем наличие таблиц ListPair, ListCurrencies
				$this->CreateListCurrencies($connect,$db_main);
				$this->CreateListPair($connect,$db_main);
				// Создаем 1 валюты
				$id_btc = $this->CreateNewCurrencies($connect,$db_main, 'BTC', 'Bitcoin', 1);
				$id_usd = $this->CreateNewCurrencies($connect,$db_main, 'USD', 'US Dollar', 0);

	            //Создаем настройку BTC_USD
	            $ConfigPair_BTC_USD = $this->CreateNewConfigPair($connect,$db_main, $id_RBC, $id_btc, $id_usd, 'BTC_USD', 86400);

	            //Создаем настройку EURO_RUB
	            $ConfigPair_USD_BTC = $this->CreateNewConfigPair($connect,$db_main, $id_RBC, $id_usd, $id_btc, 'USD_BTC', 86400);

				$this->loadChartDataRBCToDB($connect, $db_main, $ConfigPair_BTC_USD['nameTableSavePair'], 'BTC_USD', $ConfigPair_USD_BTC['nameTableSavePair'], 'USD_BTC');


			}
		}

		public function loadChartDataRBCToDB($connect, $db_main, $invertNameTable, $invertCurrencyPair, $nameTable, $currencyPair, $reconnect_attempt = 0)
		{

			$csv_text = file_get_curl('https://quote.rbc.ru/data/export/eod/ticker/157694');


			// Разбиваем курсы на дни
			$parts = explode("\n", $csv_text);
//			echo ('Кол-во курсов ='.count($parts).'<br>');
			foreach ($parts as $value) {
				if (strpos($value,'Low,High,Open,')>0) continue;

				$value_cur = explode(",", $value);
				$value_cur[0] = str_replace('"', '', $value_cur[0]);
				$value_date = explode("-", $value_cur[0]);
				if (count($value_date)<3) continue;
				// Выделяем дату
				$datecurrency=strtotime($value_date[2].'.'.$value_date[1].'.'.$value_date[0]);



				//echo('currency='.$cur_value[0].'<br>');
				$this->CreateNewPairRBC($connect,$db_main, $nameTable, $datecurrency, $value_cur[1], $value_cur[2], $value_cur[3], $value_cur[4]);
				$this->CreateNewPairRBC($connect,$db_main, $invertNameTable, $datecurrency, 1/$value_cur[1], 1/$value_cur[2], 1/$value_cur[3], 1/$value_cur[4]);


			}

		}

		public function ParseDonorCBR($connect,$db_main, $date_start = 1483272000, $date_finish = 1514775600, $period = 86400, $reconnect_attempt = 0) // Парсинг информации с сайта Донора CBR , вернут false если парсинг не пройдет
		{
			// Проверяем наличие необходимых записей в БД
			$id_CBR = $this->CreateNewDonor($connect,$db_main, 'CBR', 'http://www.cbr.ru/scripts/XML_val.asp', 1);

			if ($id_CBR===false) {
				return False;
			}
			Else {
				// Проверяем наличие таблиц ListPair, ListCurrencies
				$this->CreateListCurrencies($connect,$db_main);
				$this->CreateListPair($connect,$db_main);
				// Создаем 3 валюты
				$id_rub = $this->CreateNewCurrencies($connect,$db_main, 'RUB', 'Российский рубль', 0);
	            $id_usd = $this->CreateNewCurrencies($connect,$db_main, 'USD', 'US Dollar', 0);
	            $id_euro = $this->CreateNewCurrencies($connect,$db_main, 'EURO', 'euro', 0);

	            //Создаем настройку USD_RUB
	            $ConfigPair_USD_RUB = $this->CreateNewConfigPair($connect,$db_main, $id_CBR, $id_usd, $id_rub, 'USD_RUB', $period);

	            //Создаем настройку EURO_RUB
	            $ConfigPair_EURO_RUB = $this->CreateNewConfigPair($connect,$db_main, $id_CBR, $id_euro, $id_rub, 'EURO_RUB', $period);

	            //Создаем настройку RUB_USD
	            $ConfigPair_RUB_USD = $this->CreateNewConfigPair($connect,$db_main, $id_CBR, $id_rub, $id_usd, 'RUB_USD', $period);

	            //Создаем настройку RUB_EURO
	            $ConfigPair_RUB_EURO = $this->CreateNewConfigPair($connect,$db_main, $id_CBR, $id_rub, $id_euro, 'RUB_EURO', $period);


				$this->loadChartDataCBRToDB($connect, $db_main, $ConfigPair_USD_RUB['nameTableSavePair'], $ConfigPair_RUB_USD['nameTableSavePair'], 'USD_RUB',$date_start,$date_finish,$period);
				$this->loadChartDataCBRToDB($connect, $db_main, $ConfigPair_EURO_RUB['nameTableSavePair'], $ConfigPair_RUB_EURO['nameTableSavePair'], 'EURO_RUB',$date_start,$date_finish,$period);

				//loadChartDataPoloniexToDB($connect, $db_main, $ConfigPair['nameTableSavePair'], $ConfigPair['namePairForDonor'], 1483272000, 1514775600, $period);


			}
		}

		public function loadChartDataCBRToDB($connect, $db_main, $invertNameTable, $nameTable, $currencyPair = 'USD_RUB', $date_start = 1483272000, $date_finish = 1514775600, $period = 86400, $reconnect_attempt = 0)
		{  //1483315140&end=1514505600&period=   1483315199
			// @mysqli_query ($connect,"SET NAMES `cp1251`");

			// разбираем стартовую дату
			$start_date_str = date('d/m/Y',$date_start);

			// разбираем конечную дату
			$finish_date_str = date('d/m/Y',$date_finish);

			if ($currencyPair=='USD_RUB') {
				$xml_text = file_get_curl('http://www.cbr.ru/scripts/XML_dynamic.asp?date_req1='.$start_date_str.'&date_req2='.$finish_date_str.'&VAL_NM_RQ=R01235');
			//	echo('Закачали файл для USD_RUB<br>');
			//	echo('http://www.cbr.ru/scripts/XML_dynamic.asp?date_req1='.$start_date_str.'&date_req2='.$finish_date_str.'&VAL_NM_RQ=R01235 <br>');
			}

			if ($currencyPair=='EURO_RUB') {
				$xml_text = file_get_curl('http://www.cbr.ru/scripts/XML_dynamic.asp?date_req1='.$start_date_str.'&date_req2='.$finish_date_str.'&VAL_NM_RQ=R01239');
			//	echo('Закачали файл для EURO_RUB<br>');
			//	echo('http://www.cbr.ru/scripts/XML_dynamic.asp?date_req1='.$start_date_str.'&date_req2='.$finish_date_str.'&VAL_NM_RQ=R01239 <br>');
			}

			if (!isset($xml_text)) {
				return false;
				exit;
			}
			//echo ($currencyPair.'<br>');

			// Убираем табуляцию и переносы строк
			$content = str_replace(array(' ', "\r", "\n", "\t"), '', $xml_text);

			// Разбиваем курсы на дни
			$parts = explode('<Record', $content);
			//echo ('Кол-во курсов ='.count($parts).'<br>');
			foreach ($parts as $value) {
				// Выделяем дату
				if (strpos($value,'Date="')===false) continue;
				if (strpos($value,'<Value>')===false) continue;

				//echo($value.'<br>');

				$cur_date = explode('Date="', $value, 2);
				$cur_date = explode('"Id="', $cur_date[1], 2);
				$datecurrency=strtotime($cur_date[0]);

				//echo('datecurrency='.$cur_date[0].'<br>');


				// Выделяем курс
				$cur_value = explode('<Value>', $value, 2);
				$cur_value = explode('</Value>', $cur_value[1], 2);
				$currency = str_replace(array(','), '.', $cur_value[0]);

				//echo('currency='.$cur_value[0].'<br>');

				$this->CreateNewPairCBR($connect,$db_main, $nameTable, $datecurrency, $currency, $currency);
				$this->CreateNewPairCBR($connect,$db_main, $invertNameTable, $datecurrency, 1/$currency, 1/$currency);

			}

		}

		public function ParseDonorCoinmarketcap($connect,$db_main, $date_start = 1483272000, $date_finish = 1514775600, $period = 86400, $reconnect_attempt = 0) // Парсинг информации с сайта Донора coinmarketcap.com, вернут false если парсинг не пройдет
		{
			// Проверяем наличие необходимых записей в БД
			$id_Coinmarketcap = $this->CreateNewDonor($connect,$db_main, 'Coinmarketcap', 'https://coinmarketcap.com', 3);
			//echo ('id_Polonex='.$id_Polonex.'<br>');
			if ($id_Coinmarketcap===false) {
				return False;
			}
			Else {
				// Проверяем наличие таблиц ListPair, ListCurrencies
				$this->CreateListCurrencies($connect,$db_main);
				$this->CreateListPair($connect,$db_main);
				// Парсим список валют
				$html_text = file_get_curl('https://coinmarketcap.com/all/views/all/');
				if(empty($html_text))
				{
					if ($reconnect_attempt<10) {
						sleep(2);
						//echo ('Ошибка загрузки списка валют! Попытка № '.$reconnect_attempt.'<br>');
						return $this->ParseDonorCoinmarketcap($connect,$db_main,$date_start,$date_finish,$period,$reconnect_attempt+1);
						exit;
					}
					else {
						die('Connection error at function <b>loadListCurrencies</b></br>');
					}

				}
				$content = str_replace(array(' ', "\r", "\n", "\t"), '', $html_text);
				$parts = explode('id="currencies-all"', $content,2);
				$parts = explode('<tbody>', $parts[1],2);
				$parts = explode('</tbody>', $parts[1],2);
				$parts = explode('<tr', $parts[0]);

				$id_usd = $this->CreateNewCurrencies($connect,$db_main, 'USD', 'US Dollar', 0);
				$count_currency = 0;
				foreach ($parts as $value) {
					if (strpos($value,'<td')===false) continue;
					if (strpos($value,'class="currency-name-container"')===false) continue;

					if ($count_currency>100) break;

					$parts_td = explode("<td", $value);
					$short_name = '';
					$long_name = '';
					$url_detail_page ='';

					foreach ($parts_td as $value_td) {
						if (strpos($value_td,'</td')===false) continue;
						if (strpos($value_td,'class="currency-name-container"')>0) {
							$cut_text=$value_td;
							// Вытаскиваем url
							$cut_text = explode('class="currency-name-container"', $cut_text,2);
							$cut_text = explode('</a>', $cut_text[1],2);
							$cut_text = explode('href="', $cut_text[0],2);
							$cut_text = explode('">', $cut_text[1],2);
							//$url_detail_page ='https://coinmarketcap.com'.$cut_text[0].'historical-data/';  //УРЛ
							$url_detail_page =$cut_text[0].'historical-data/';  //УРЛ

							// Вытаскиваем длиное имя криптовалюты
							$long_name = $cut_text[1];  //Длинное имя криптовалюты
						}
						if (strpos($value_td,'class="currency-symbol"')>0) {
							$cut_text=$value_td;
							// Вытаскиваем url
							$cut_text = explode('class="currency-symbol"', $cut_text,2);
							$cut_text = explode('</a>', $cut_text[1],2);
							$cut_text = explode('href="', $cut_text[0],2);
							$cut_text = explode('">', $cut_text[1],2);
							//$url_detail_page ='https://coinmarketcap.com'.$cut_text[0].'historical-data/';  //УРЛ
							$url_detail_page =$cut_text[0].'historical-data/';  //УРЛ

							// Вытаскиваем короткое имя криптовалюты
							$short_name = $cut_text[1];  //Длинное имя криптовалюты
						}
					}

					if (($short_name != '') and ($long_name != '') and ($url_detail_page != '')) {
						// Создаем валюту
						$id_new_currency = $this->CreateNewCurrencies($connect,$db_main, $short_name, $long_name, 1);

						//$this->CreateNewPairRBC($connect,$db_main, $nameTable, $datecurrency, $value_cur[1], $value_cur[2], $value_cur[3], $value_cur[4]);
					 	// Кибервалюта к USD
					 	$ConfigPair = $this->CreateNewConfigPair($connect,$db_main, $id_Coinmarketcap, $id_new_currency, $id_usd, $url_detail_page, $period);

				 		// USD к кибервалюта
				 		$ConfigPairRevers = $this->CreateNewConfigPair($connect,$db_main, $id_Coinmarketcap, $id_usd, $id_new_currency, $url_detail_page, $period);


					 	// Вызываем синхронизацию и получение курсов валют за период
						$this->loadChartDataCoinmarketcapToDB($connect, $db_main, $ConfigPairRevers['nameTableSavePair'], $ConfigPair['nameTableSavePair'], $ConfigPair['namePairForDonor'], $date_start, $date_finish, $period);
						sleep(0.7);
//						echo ($count_currency.'<br>');
						$count_currency = $count_currency +1;

					}

				}
			}
		}


		public function loadChartDataCoinmarketcapToDB($connect, $db_main, $nameTableRevers, $nameTable, $currencyPair = 'BTC_DGB', $date_start = 1483272000, $date_finish = 1514775600, $period = 86400, $reconnect_attempt = 0)
		{  //1483315140&end=1514505600&period=   1483315199
			// @mysqli_query ($connect,"SET NAMES `cp1251`");

			// разбираем стартовую дату
			$start_date_str = date('Ymd',$date_start);

			// разбираем конечную дату
			$finish_date_str = date('Ymd',$date_finish);

			$html_text = file_get_curl('https://coinmarketcap.com'.$currencyPair.'?start='.$start_date_str.'&end='.$finish_date_str);
	        //echo ($url.'/?ajxop=1<br>');
	        //print_r ($json_text);
			if(empty($html_text))
			{
				//
				if($reconnect_attempt < 10)
				{
					sleep(2);
					echo 'Trying to reconnect.</br>';
					return $this->loadChartDataCoinmarketcapToDB($connect, $db_main, $nameTable, $currencyPair, $date_start, $date_finish, $period,  $reconnect_attempt + 1);
				}
				else
				{
					die('Connection error at function <b>loadList</b></br>');
				}
			}

			$content = str_replace(array("\r", "\n"), '', $html_text);
			$parts = explode('class="table"', $content,2);
			$parts = explode('<tbody>', $parts[1],2);
			$parts = explode('</tbody>', $parts[1],2);
			$parts = explode('<tr', $parts[0]);

			$id_usd = $this->CreateNewCurrencies($connect,$db_main, 'USD', 'US Dollar', 0);
			$count_currency = 0;
			foreach ($parts as $value) {
				if (strpos($value,'<td')===false) continue;
//				if (strpos($value,'class="currency-name-container"')===false) continue;

//				if ($count_currency>100) break;

				$parts_td = explode("<td", $value);

/*				<th class="text-left">Date</th>			- 0
                <th class="text-right">Open</th>		- 1
                <th class="text-right">High</th>		- 2
                <th class="text-right">Low</th>			- 3
                <th class="text-right">Close</th>		- 4
                <th class="text-right">Volume</th>		- 5
                <th class="text-right">Market Cap</th>	- 6
                */
				//strtotime
				$i =1; // Date
				$parts_td[$i]  = explode('>', $parts_td[$i],2);
				$parts_td[$i]  = explode('<', $parts_td[$i][1],2);
				$parts_td[$i] = $parts_td[$i][0];
				$date_cur = strtotime($parts_td[$i]);
//				echo('data='.$parts_td[$i].'='.date('Y-m-d',$date_cur).'<br>');
//				$parts_td[$i] = str_replace(',','',$parts_td[$i]);
//				$parts_date = explode(" ", $parts_td[$i]);

//				echo($parts_td[$i].'<br>'.$parts_date[1].'.'.self::mount[$parts_date[0]].'.'.$parts_date[2].'<br>');

//				$date_cur = strtotime($parts_date[1].'.'.self::mount[$parts_date[0]].'.'.$parts_date[2]);

				//echo($parts_td[$i].'='.date('Y-m-d',$date_cur).'<br>');



				$i =2; // Open
				$parts_td[$i]  = explode('>', $parts_td[$i],2);
				$parts_td[$i]  = explode('<', $parts_td[$i][1],2);
				$parts_td[$i] = str_replace(array(',', " "), '', $parts_td[$i][0]);
				//$open = number_format($parts_td[$i],15,'.','');
				$open = (float)$parts_td[$i];
//				echo($open.'<br>');

				$i =3; // High
				$parts_td[$i]  = explode('>', $parts_td[$i],2);
				$parts_td[$i]  = explode('<', $parts_td[$i][1],2);
				$parts_td[$i] = str_replace(array(',', " "), '', $parts_td[$i][0]);
				$high = (float)$parts_td[$i];
//				echo($high.'<br>');

				$i =4; // Low
				$parts_td[$i]  = explode('>', $parts_td[$i],2);
				$parts_td[$i]  = explode('<', $parts_td[$i][1],2);
				$parts_td[$i] = str_replace(array(',', " "), '', $parts_td[$i][0]);
				$low = (float)$parts_td[$i];
//				echo($low.'<br>');

				$i =5; // Close
				$parts_td[$i]  = explode('>', $parts_td[$i],2);
				$parts_td[$i]  = explode('<', $parts_td[$i][1],2);
				$parts_td[$i] = str_replace(array(',', " "), '', $parts_td[$i][0]);
				$close = (float)$parts_td[$i];
//				echo($close.'<br>');

				$i =6; // Volume
				$parts_td[$i]  = explode('>', $parts_td[$i],2);
				$parts_td[$i]  = explode('<', $parts_td[$i][1],2);
				$parts_td[$i] = str_replace(array(',', " "), '', $parts_td[$i][0]);
				$volume = (float)$parts_td[$i];
//				echo($volume.'<br>');

				$i =7; // Market Cap
				$parts_td[$i]  = explode('>', $parts_td[$i],2);
				$parts_td[$i]  = explode('<', $parts_td[$i][1],2);
				$parts_td[$i] = str_replace(array(',', " "), '', $parts_td[$i][0]);
				$MarketCap = (float)$parts_td[$i];
//				echo($MarketCap.'<br>');


				$this->CreateNewPairCoinmarketcap($connect,$db_main, $nameTableRevers, $date_cur, $high, $low, $open, $close, $volume, $MarketCap);

				$MarketCap = $MarketCap / $close;
				$volume = $volume / $close;
				$open = 1/$open;
				$high = 1/$high;
				$low = 1/$low;
				$close = 1/$close;

				$this->CreateNewPairCoinmarketcap($connect,$db_main, $nameTable, $date_cur, $high, $low, $open, $close, $volume, $MarketCap);


			}
		}

	}
