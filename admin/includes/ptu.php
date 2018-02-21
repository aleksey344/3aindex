<?php 
	
	/*
		10.12.2013
		
		Класс, который содержит в себе статические методы для тестирования производительности. На 08.12.2013 в разработке
		
		Пример:
		for($i = 0; $i < 100; $i++)
		{
		PerfomanceTestUtils::initRecord('aaa');
		//действия
		PerfomanceTestUtils::logRecord('aaa');
		}
		var_dump(PerfomanceTestUtils::getResult('aaa', false));		
		//или PerfomanceTestUtils::getResult('aaa')
	*/
	
	class PerfomanceTestUtils
	{
		private static $records = array();
		private static $stats = array();
		
		public static function initRecord($key = null)
		{
			self::$records[($key == null)?'---last---':$key] = microtime(true);
		}
		
		public static function logRecord($key = null)
		{
			$key = ($key == null)?'---last---':$key;			
			$result = microtime(true) - self::$records[$key];
			$average = $result;
			
			if(!@is_array(self::$stats[$key]))
			{
				self::$stats[$key] = array();	
			}
			self::$stats[$key][] = $result;
			
			return $result;
		}
		
		public static function getResult($key = null, $is_var_dump = true)
		{
			$key = ($key == null)?'---last---':$key;
			$average = 0;
			
			if(is_array(self::$stats[$key]))
			{				
				$average = array_sum(self::$stats[$key])/count(self::$stats[$key]);
			}
			$is_var_dump?var_dump($average):null;
			return $average;
		}
	}
	
?>