<?php
	class CurrencyLoader
	{
		const USD_URL = 'http://www.cbr.ru/scripts/XML_daily.asp';
		const USD_INFLATE = 3;
		
		public $usd_currency = 0;
		
		public function __construct()
		{
			$this->getUsdCurrency();
		}
		
		public function getUsdCurrency()
		{
			$content = str_replace(array(' ', "\r", "\n", "\t"), '', file_get_contents(self::USD_URL));
			
			$parts = explode('USD', $content);
			$parts = explode('/Name', $parts[1], 2);
			$parts = explode('/Value', $parts[1], 2);
						
			$this->usd_currency = (float)str_replace(',', '.', preg_replace('/[^0-9,]/', '', $parts[0])) + self::USD_INFLATE;
		}
	}
	
?>