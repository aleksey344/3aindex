<?php
	
	function file_get_curl($url, $post_params = '') 
	{
		
		//$interfaces = array('217.23.6.151', '217.23.6.158');
		$interfaces = array('10.1.77.47');
		$interface = $interfaces[mt_rand(0, count($interfaces) - 1)];
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);      
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		
		curl_setopt($ch, CURLOPT_COOKIEJAR, (__DIR__).'/cookies.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, (__DIR__).'/cookies.txt');
		
		if(!empty($post_params))
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
		}
		
		if(!empty($interface))
		{
			curl_setopt($ch, CURLOPT_INTERFACE, $interface);
		}
		
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;
	}
	
?>