<?php
	
	class Receiver
	{
		private $page_hash = '';
		
		private $sess_id = '';
		private $sess_hash = '';
		
		private $version = '';
		
		private $property_ids;
		private $sizes_equality;
		
		private $trash_list;
		
		public function __construct($version, $property_ids = null, $sizes_equality = null)
		{
			$this->version = $version;
			$this->property_ids = $property_ids;
			$this->sizes_equality = $sizes_equality;
		}
		
		public function initHash($url, $try_count = 0)
		{
			$result = $this->makeQuery($url);
			
			preg_match('/hash\" value\=\"(.*?)\"/', $result, $hash);
			preg_match('/window\.location=\"(.*?)\"/', $result, $redirect);
			
			//	die();
			if(empty($redirect[1]))
			{
				$redirect = array('', '');
			}
			
		//		echo htmlentities($result);					
			
			
			if(empty($hash[1]))
			{
				sleep(0.7);				
				
				if($try_count > 10)
				{
					die('Storeland fucked up!');
				}
				
				$url_parts = explode('?', $url);
				
				return $this->initHash($url_parts[0].@$redirect[1], $try_count + 1);
			}
			
			$this->page_hash = $hash[1];			
		}
		
		public function authAdmin($url, $login, $pass, $try_count = 0)
		{
			$result = $this->makeQuery($url, 'act=login&action_to=http%3A%2F%2Fstoreland.ru%2F&site_id=&to=&hash='.$this->page_hash.'&form%5Buser_mail%5D='.$login.'&form%5Buser_pass%5D='.$pass);
			
			
			preg_match('/form action\=\"(.*?)\"/', $result, $link);
			preg_match('/sess_id\" value\=\"(.*?)\"/', $result, $id);
			preg_match('/sess_hash\" value\=\"(.*?)\"/', $result, $hash);
			
			if(empty($link[1]))
			{
				if($try_count < 20)
				{
					sleep(0.5);
					echo 'Got 403, retrying. </br>';
					return $this->authAdmin($url, $login, $pass, $try_count + 1);
				}
				else
				{
					die('Fucking Storeland, always returns error');
				}
			}
			
			$this->makeQuery($link[1], 'sess_id='.$id[1].'&sess_hash='.$hash[1]); //авторизация на самом хосте сайта, а не storeland, чтобы куки были на домене сайта оригинального
		}
		
		public function initTrash($url)
		{
			$this->trash_list = $this->loadGoodies($url, 'trash');
		}
		
		
		public function loadGoodies($url, $category_id)
		{
			//http://myaussie.ru/admin/store_catalog?method=cat-data&request_type=store_catalog&per_page=100&search_q=&page=0&id=cid_3519639&version=ef0a39&ajax_q=1
			
			$loaded = 0;
			$count = 0;
			$page = 1;
			$list = array();//id, article, sizes
			$this->page_hash = ''; //сбрасываем, чтобы было условие для проверки в цикле.
			
			while($loaded == 0 || $loaded < $count)
			{//цикл идет или пока вообще ничего не загрузили, или если уже начали загружать, но не догрузили
				
				$category_name = ($category_id == 'trash')?'trash':('cid_'.$category_id);
				
				$result = $this->makeQuery($url, 'method=cat-data&request_type=store_catalog&per_page=200&search_q=&page='.$page.'&id='.$category_name.'&version='.$this->version.'&ajax_q=1');
				$json = @json_decode($result);
				
				
				if(!$json)
				{
					var_dump($result);
					die();
				}
				
				if($json->status != 'ok')
				{//если версия когда-нибудь обновится
					
					$this->version = $json->version;
					return $this->loadGoodies($url, $category_id);
				}
				$count = (int) $json->nb_goods->$category_name;
				$loaded += 200;
				$page++;
				
				if(empty($this->page_hash))
				{
					preg_match('/hash\" value\=\"(.*?)\"/', $json->data, $hash);
					$this->page_hash = $hash[1];
				}
				
				preg_match_all('/tbody id\=\"gl(.*)\"/', $json->data, $ids); 
				$ids_count = count($ids[1]);
				
				preg_match_all('/art_number\"><.*>(.*)<\/.*></', $json->data, $articles);
				$articles_count = count($articles[1]);
				
				$parts = explode('tbody id="gl', $json->data); //делим на куски по блокам товаров, чтобы спарсить размеры не затрагивая каждый раз весь html 
				for($i = 0; $i < $ids_count; $i++)
				{
					preg_match_all('/rel\=\".*<\/td>.*<td>(.*?)<\/td>/', $parts[$i + 1], $sizes);		
					preg_match_all('/form\[mod\]\[(.*?)\]\[rest_value\]/', $parts[$i + 1], $size_ids);	
					preg_match('/br num cost rest.*value=\"(.*?)\"/', $parts[$i + 1], $quantity);
					preg_match('/br num cost now.*value=\"(.*?)\"/', $parts[$i + 1], $price);
					preg_match('/br img.*style=\"(.*?)\"/', $parts[$i + 1], $style); //чтобы если картинка в прошлый раз не загрузилась
					
					$sizes_assoc = array();
					$sizes_count = count($sizes[1]); 
					
					for($j = 0; $j < $sizes_count; $j++)
					{	//чтобы проще в будущем проверять соотвествие размеров -- делаем ассоцитивный массив, где название размера - ключ
						
						$size = $sizes[1][$j];
						$sizes_assoc[$size] = (int)$size_ids[1][$j];
					}
					$list[(int)$articles[1][$i]] = array('id' => (int)$ids[1][$i], 'category_id' => $category_id,
					'article' => (int)$articles[1][$i], 'is_no_image' => strpos($style[1], 'no-photo') !== false,
					'quantity' => (int)$quantity[1], 'price' => ceil((float)str_replace(array(' ', ','), array('', '.'), $price[1])), 'sizes' => $sizes_assoc);
				}
			}
			return $list;
		}

        public function get_page_hash($url, $category_id)
		{
			//http://myaussie.ru/admin/store_catalog?method=cat-data&request_type=store_catalog&per_page=100&search_q=&page=0&id=cid_3519639&version=ef0a39&ajax_q=1
			
			$loaded = 0;
			$count = 0;
			$page = 1;
			$list = array();//id, article, sizes
//			$this->page_hash = ''; //сбрасываем, чтобы было условие для проверки в цикле.
			
			//while($loaded == 0 || $loaded < $count)
			{//цикл идет или пока вообще ничего не загрузили, или если уже начали загружать, но не догрузили
				
				$category_name = ($category_id == 'trash')?'trash':('cid_'.$category_id);
				
				$result = $this->makeQuery($url, 'method=cat-data&request_type=store_catalog&per_page=10&search_q=&page='.$page.'&id='.$category_name.'&version='.$this->version.'&ajax_q=1');
				$json = @json_decode($result);
				
				
				if(!$json)
				{
					var_dump($result);
					die();
				}
				
				if($json->status != 'ok')
				{//если версия когда-нибудь обновится
					
					$this->version = $json->version;
			//		return $this->get_page_hash($url, $category_id);
				}
//				$count = (int) $json->nb_goods->$category_name;
//				$loaded += 200;
//				$page++;
				
				$hash = array(1 => $this->page_hash);
				if (isset($json->data)) {
				    preg_match('/hash\" value\=\"(.*?)\"/', $json->data, $hash);
				    $this->page_hash = $hash[1];
				    return $hash[1];
				}
				else {
				    return '';
				};
				
			}
		}
		
		private function loadGoodie($url)
		{
			$result = $this->makeQuery($url);
			$data = array();
			
			preg_match('/form\[current_version_of_record\].*value=\"(.*?)\"/', $result, $version);
			$data['version'] = $version[1];
			
			preg_match('/form\[goods_name\].*value=\"(.*?)\"/', $result, $name);
			$data['name'] = $name[1];
			
			//preg_match('/form\[goods_desc_short\].*>(.*?)<\/textarea>/s', $result, $short_description);
			//$data['short_description'] = $short_description[1];
			
			$temp_parts = explode('form[goods_desc_short]', $result, 2);
			$temp_parts = explode('>', $temp_parts[1], 2);
			$temp_parts = explode('</textarea>', $temp_parts[1], 2);
			$data['short_description'] = html_entity_decode($temp_parts[0]);
			
			//preg_match('/form\[goods_desc_large\].*>(.*?)<\/textarea>/', $result, $large_description);
			//$data['large_description'] = $large_description[1];
			
			$temp_parts = explode('form[goods_desc_large]', $result, 2);
			$temp_parts = explode('>', $temp_parts[1], 2);
			$temp_parts = explode('</textarea>', $temp_parts[1], 2);
			$data['large_description'] = html_entity_decode($temp_parts[0]);
			
			preg_match('/form\[goods_title\].*value=\"(.*?)\"/', $result, $title);
			$data['title'] = $title[1];
			
			preg_match('/form\[goods_path\].*value=\"(.*?)\"/', $result, $path);
			$data['path'] = $path[1];
			
			//preg_match('/form\[goods_description\].*>(.*?)<\/textarea>/s', $result, $seo_description);
			//$data['seo_description'] = $seo_description[1];
			
			$temp_parts = explode('form[goods_description]', $result, 2);
			$temp_parts = explode('>', $temp_parts[1], 2);
			$temp_parts = explode('</textarea>', $temp_parts[1], 2);
			$data['seo_description'] = html_entity_decode($temp_parts[0]);
			
			preg_match_all('/form\[images_data\]\[(.*?)\]\[name\]/', $result, $images);
			$data['images'] = $images[1];
			
			//var_dump($data);
			return $data;
		}
		
		
		public function updateGoodies($url, $goodies)
		{
			$this->page_hash = $this->get_page_hash(Config::RECEIVER_HOST.'/admin/store_catalog','all_goods');
			$vars = array(
			'hash' => $this->page_hash, 'version' => $this->version, 'ajax_q' => 1, 
			'form' => array('method' => 0, 'goods' => array(), 'mod' => array())
			);
			foreach($goodies as $article => $goodie)
			{
				$vars['form']['goods'][$goodie['id']] = array('check' => 1);
				foreach($goodie['sizes'] as $size_id)
				{
					$vars['form']['mod'][$size_id] = array('cost_now' => $goodie['donor']['new_price']);
				}
			}
			return $this->makeQuery($url, $vars);
			/*
			foreach($goodies as $article => $goodie)
			{
			    $vars = array(
			'search_q' => '',
			'hash' => $this->page_hash, 'version' => $this->version,
			'form' => array('category_id' => 'all_goods', 'search_query' => '', 'select_all' => 0, 'goods' => array($goodie['id'] => array('check' => 1)), 'method' => 14, 'goods_values' => array('action_type' => 1, 'relatively' => 1, 'field_goods_mod_cost_now' => 1, 'new_value' => $goodie['donor']['new_price'], 'new_value_is_percent' => 1)),'ajax_q' => 1
			);
				$result_var = $this->makeQuery($url, $vars);
				
				$json = @json_decode($result_var);
				
				
				echo('hash='.$this->page_hash.'<br>');
				echo('<pre>');
			    print_r($vars);
			    echo('</pre>');
				echo('<pre>');
			    print_r($json);
			    echo('</pre>');
				
				
				if(!$json)
				{
					var_dump($result_var);
					die();
				}
				
				if($json->status != 'ok')
				{//если версия когда-нибудь обновится
					
					echo('Поменяли версию '.$json->version.'<br>');
					$this->version = $json->version;
					return $this->updateGoodies($url, $goodies);
				}
				//if (isset($result_var[''])
				
			}
//			echo('<pre>');
//			print_r($vars);
//			echo('</pre>');
			return $result_var;*/
		}

		public function updateGoodies_new($url, $goodies)
		{
			$this->page_hash = $this->get_page_hash(Config::RECEIVER_HOST.'/admin/store_catalog','all_goods');
			foreach($goodies as $article => $goodie)
			{
			   $count=1;
			   $vars = array(
    	    		'hash' => $this->page_hash,
    		    	'goods_id' => 0,
    			    'form' => array('property' => array())
	    		);
				$vars['goods_id'] = $goodie['id'];
				
				foreach($goodie['sizes'] as $size_id)
				{
				    $vars['form']['property'][$count] = array();
				    $vars['form']['property'][$count]['id'] = $size_id;
				    $vars['form']['property'][$count]['cost_now'] = $goodie['donor']['new_price'];
					//$vars['form']['property'][$count][$size_id] = array('cost_now' => $goodie['donor']['new_price']);
					$count = $count +1;
				}
				echo('<pre>');
			    print_r($vars);
			    echo('</pre>');
				return $this->makeQuery($url, $vars);
			}
			
		}

		
		public function removeGoodies($url, $goodies)
		{
		    $this->page_hash = $this->get_page_hash(Config::RECEIVER_HOST.'/admin/store_catalog','all_goods');
		    $vars = array(
			'hash' => $this->page_hash, 'version' => $this->version, 'ajax_q' => 1, 
			'form' => array('method' => 2, 'goods' => array())
			);
			foreach($goodies as $article => $goodie)
			{
				$vars['form']['goods'][$goodie['id']] = array('check' => 1);
				
			}
			return $this->makeQuery($url, $vars);
		    
			/*$vars = array(
			'ajax_q' => 1
			);
			return $this->makeQuery($url, $vars);*/
		}
		
		public function getTrashId($article)
		{
			if(!empty($this->trash_list[$article]))
			{
				return $this->trash_list[$article]['id'];
			}
			return 0;
		}
		
		public function restoreGoodie($url, $id)
		{
		    $this->page_hash = $this->get_page_hash(Config::RECEIVER_HOST.'/admin/store_catalog','all_goods');
			$vars = array(
			'hash' => $this->page_hash, 'version' => $this->version, 'ajax_q' => 1, 
			'form' => array('method' => 13, 'goods' => array($id => array('check' => 1)), 'category_id' => 'trash')
			);
			return $this->makeQuery($url, $vars);
		}
		
		public function updateGoodie($url, $category_id, $receiver_id, $data)
		{
		    $this->page_hash = $this->get_page_hash(Config::RECEIVER_HOST.'/admin/store_catalog','all_goods');
			if($receiver_id > 0)
			{
				$full_data = $this->loadGoodie($url);
				
				$vars = array('hash' => $this->page_hash, 'goods_id' => $receiver_id);
				$vars['form'] = array('current_version_of_record' => $full_data['version'], 
				'open_seo' => 0, 'open_main' => 1, 'open_attr' => -1, 'open_mod' => 1, 'open_placement' => 1, 'open_images' => 1, 'open_related_goods' => 1, 'open_other_actions' => 1,
				'goods_name' => $full_data['name'], 'goods_desc_short' => $full_data['short_description'], 'goods_desc_large' => $full_data['large_description'],
				'goods_title' => $full_data['title'], 'goods_path' => $full_data['path'], 'goods_subdomain' => '', 'goods_description' => $full_data['seo_description'],
				'goods_keywords' => '', 'goods_seo_desc_short' => '', 'goods_seo_desc_large' => '', 'goods_cat_id' => ',cid_'.$category_id);
				if($data['is_new'])
				{
					$vars['form']['goods_cat_id'] .= ',cid_new';
				}
				if($data['is_sale'])
				{
					$vars['form']['goods_cat_id'] .= ',cid_favorites';
				}
				
				$vars['form']['property'] = array();
				
				foreach($data['sizes'] as $size => $value)
				{
					if(!empty($this->sizes_equality[$size]))
					{
						$size_id = $this->sizes_equality[$size];
						
						$temp_key1 = $this->makeRandomHash(8);
						$temp_key2 = $this->makeRandomHash(8);
						
						$vars['form']['property'][$temp_key1] = array();
						$property = &$vars['form']['property'][$temp_key1];
						
						$property['prop'] = array();
						$property['prop'][$temp_key2] = array();
						
						$prop = &$property['prop'][$temp_key2];
						
						$prop['name'] = $this->property_ids['size'];
						$prop['new_name'] = '';
						$prop['value'] = $size_id;
						$prop['new_value'] = '';
						
						$property['description'] = '';
						$property['art_number'] = $data['article'];
						$property['cost_now'] = $data['new_price'];
						$property['cost_old'] = '0';
						$property['cost_supplier'] = '0';
						$property['rest_value'] = '1000';
						$property['rest_value_measure_id'] = '1';
					}
				}
				
				$vars['form']['images_data'] = array();
				for($i = 0; $i < count($full_data['images']); $i++)
				{
					$image_id = ($full_data['images'][$i]);
					$vars['form']['images_data'][$image_id] = array('name' => '', 'main' => (int)($i == 0));
				}
			}
			
			$this->makeQuery($url, $vars);
			return true;
		}
		
		public function addGoodie($url, $category_id, $data, $images)
		{
		    $this->page_hash = $this->get_page_hash(Config::RECEIVER_HOST.'/admin/store_catalog','all_goods');
			$vars = array('hash' => $this->page_hash, 'continue' => 0);
			$vars['form'] = array('open_seo' => 0, 'open_main' => 1, 'open_attr' => -1, 'open_mod' => 1, 'open_placement' => 1, 'open_images' => 1,
			'goods_name' => $data['title'].' [eng]', 'goods_desc_short' => $data['description'], 'goods_desc_large' => $data['description'].'</br></br>'.$data['description2'],
			'goods_title' => $data['title'], 'goods_path' => $this->makeUrlName($data['list_name'].' '.$data['article'].' '.$data['model_row_name']), 'goods_subdomain' => '', 
			'goods_description' => $data['description'], 'goods_keywords' => '', 'goods_seo_desc_short' => '', 'goods_seo_desc_large' => '', 'goods_cat_id' => ',cid_'.$category_id
			);
			
			if($data['is_new'])
			{
				$vars['form']['goods_cat_id'] .= ',cid_new';
			}
			if($data['is_sale'])
			{
				$vars['form']['goods_cat_id'] .= ',cid_favorites';
			}
			
			
			//var_dump($vars);
			$vars['form']['property'] = array();
			foreach($data['sizes'] as $size => $value)
			{
				if(!empty($this->sizes_equality[$size]))
				{
					$size_id = $this->sizes_equality[$size];
					
					$temp_key1 = $this->makeRandomHash(8);
					$temp_key2 = $this->makeRandomHash(8);
					
					$vars['form']['property'][$temp_key1] = array();
					$property = &$vars['form']['property'][$temp_key1];
					
					$property['prop'] = array();
					$property['prop'][$temp_key2] = array();
					
					$prop = &$property['prop'][$temp_key2];
					
					$prop['name'] = $this->property_ids['size'];
					$prop['new_name'] = '';
					$prop['value'] = $size_id;
					$prop['new_value'] = '';
					
					$property['description'] = '';
					$property['art_number'] = $data['article'];
					$property['cost_now'] = $data['new_price'];
					$property['cost_old'] = '0';
					$property['cost_supplier'] = '0';
					$property['rest_value'] = '1000';
					$property['rest_value_measure_id'] = '1';
				}
			}
			
			$vars['form']['images_data_by_id'] = array();
			for($i = 0; $i < count($images); $i++)
			{
				$image = $images[$i];
				
				$vars['form']['images_data_by_id'][($image['image_cond_id'] == 0)?($image['image_id']):($image['image_cond_id'])] = array('desc' => '', 'main' => (int)($i == 0), 'id' => $image['image_id']);
			}
			
			$this->makeQuery($url, $vars);
			return true;
		}
		
		
		public function loadImage($url, $image_url, $goodie_id = 0, $reconnect_attempt = 0)
		{
			$image_rand_id = $this->makeRandomHash(16);
			$vars = array('form[ajax_images][0]' => '', 'form[images_ids][0]' => $image_rand_id, 'form[goods_id]' => empty($goodie_id)?'NaN':$goodie_id, 'ajax_q' => 1);
			
			$image_data = file_get_contents($image_url);
			if(!$image_data)
			{
				die('Connection error at function <b>loadImage</b></br>');
				if($reconnect_attempt < 4)
				{
					sleep(0.1);
					echo 'Trying to reconnect.</br>';
					return $this->loadImage($url, $image_url, $goodie_id, $reconnect_attempt + 1);
				}
				else
				{
					return array('image_id' => 0, 'image_cond_id' => 0);
				}
			}
			
			$temp_file_name = dirname(__FILE__).'/temp_image.jpeg';
			$file = fopen($temp_file_name, 'w');
			fwrite($file, $image_data);
			fclose($file);
			
			$vars['form[ajax_images][0]'] = new CurlFile($temp_file_name, 'image/jpeg', 'temp_image.jpeg');
			
			
			$json = @json_decode($this->makeQuery($url, $vars, true));
			if($json)
			{
				if(empty($json->result->$image_rand_id->error_text))
				{
					return array('image_id' => $json->result->$image_rand_id->image_id, 'image_cond_id' => $json->result->$image_rand_id->image_cond_id);
				}
			}
			else
			{
				echo $this->makeQuery($url, $vars, true);
			}
			
			//var_dump($json);
			return array('image_id' => 0, 'image_cond_id' => 0);
		}
		
		
		private function makeQuery($url, $post = '', $is_file = false, $reconnect_attempt = 0)
		{
			if(is_array($post) && !$is_file)
			{
				$post = http_build_query($post);
			}
			
			$interfaces = array(/*'', */ '31.31.196.177');
			$interface = $interfaces[mt_rand(0, count($interfaces) - 1)];
			
			
			$ch = curl_init();  
			curl_setopt($ch, CURLOPT_URL, $url); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 7);
			if(!empty($post))
			{
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			}
			if(!empty($interface))
			{
				curl_setopt($ch, CURLOPT_INTERFACE, $interface);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Upgrade-Insecure-Requests: 1', 
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36',
			''
			));
			curl_setopt($ch, CURLOPT_COOKIEJAR, (__DIR__).'/cookies.txt');
			curl_setopt($ch, CURLOPT_COOKIEFILE, (__DIR__).'/cookies.txt');
			//curl_setopt($ch, CURLOPT_INTERFACE, '217.23.6.129');
			
			$result = curl_exec($ch);
			
			if(!$result)
			{
				echo 'Connection error till requesting <u>'.$url.'</u></br>Error: <b>'.curl_error($ch).'</b></br>';
				if($reconnect_attempt < 3)
				{
					sleep(1 + $reconnect_attempt);
					echo 'Trying to reconnect.</br>';
					return $this->makeQuery($url, $post, $is_file, $reconnect_attempt + 1);
				}
				else
				{
					//die();
				}
			}
			curl_close($ch);  
			
			return $result;
		}
		
		private function makeRandomHash($length)
		{
			$str = '';
			$chars = 'abcdef0123456789';
			$num_chars = strlen($chars);
			
			for ($i = 0; $i < $length; $i++) 
			{
				$str .= substr($chars, rand(1, $num_chars) - 1, 1);
			}
			return $str;
		}
		
		private function makeUrlName($str)
		{
			$str = str_replace(array("\n", "\r"), ' ', $str);
			$str = preg_replace('/\s+/', ' ', $str);
			$str = trim($str); 
			$str = strtolower($str);
			$str = strtr($str, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
			$str = preg_replace('/[^0-9a-z-_ ]/i', '', $str);
			$str = str_replace(array(' ', '_'), '-', $str);
			
			return $str;
		}
	}
	