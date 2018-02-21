<?php
        function mysqli_result($res,$row=0,$col=0){ 
            $numrows = mysqli_num_rows($res); 
            if ($numrows && $row <= ($numrows-1) && $row >=0){
                mysqli_data_seek($res,$row);
                $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
                if (isset($resrow[$col])){
                    return $resrow[$col];
                }
            }
            return false;
        }
        
	class Config
	{  
	    public static $db_user='aindexcom_admin';
        public static $db_password='as%7Assc';
//$connect=mysql_connect('localhost',$db_user,$db_password);
        const db_main="`aindexcom_db`";
        public static $connect=false;
//$connect=mysqli_connect('88.99.124.81',$db_user,$db_password);

        public static function get_connect() {
            if(!static::$connect) 
                static::$connect=mysqli_connect('aindexcom.mysql',static::$db_user,static::$db_password,'aindexcom_db',3306);
            if(!static::$connect)
	            die('Not connected to db!');
            @mysqli_query (static::$connect,"SET NAMES `utf8`");
            return static::$connect;
        }


		const DONOR_HOST = ''; //сайт донор
		const STORELAND_HOST = ''; //сайт для авторизации
		const RECEIVER_HOST = ''; //ваш сайт. БЕЗ СЛЕША В КОНЦЕ
		
        
		const RECEIVER_LOGIN = ''; //логин от админки
		const RECEIVER_PASS = ''; //пароль от админки
		const PRICE_COEFF = 1.49; //коэффициент умножения цены, через точку
		
		
		const RECEIVER_VERSION = 'd83ecd'; // не трогать 
//		const RECEIVER_VERSION = '404a62'; // не трогать 
		
//		public static $donor_pages = array('underwear', 'swimwear', 'surfwear', 't_shirts'); // не трогать старые привязки
		public static $donor_pages = array('underwear', 'swimwear', 'menswear'); // не трогать новые привязки
//		public static $receiver_categories = array(3517616, 3517621, 3519639, 3519637); // не трогать старые привязки
		public static $receiver_categories = array(3517616, 3517621, 3519637); // не трогать новые привязки 
		//public static $donor_pages = array('surfwear'); // не трогать
		//public static $receiver_categories = array(3519639); // не трогать.
		//public static $donor_pages = array('swimwear'); // не трогать		
		//public static $receiver_categories = array(3517621); // не трогать
		//public static $donor_pages = array('surfwear'); // не трогать
		//public static $receiver_categories = array(3519639); // не трогать
		
		public static $property_ids = array('mod' => 1178157, 'size' => 1178158); // не трогать
		public static $sizes_equality = array('40' => 5032455, '41' => 5032456, 'L' => 5032461, 'L-XL' => 5032465, 'M' => 5032460, 'Out of stock - Click for notification' => 5032467, 'S' => 5032459, 'S-M' => 5032464, 'XL' => 5032462, 'XS' => 5032458, 'XXL' => 5032463, 'XXXL' => 5032466); // не трогать
		
//		public static $categories_equality = array('underwear' => 3517616, 'swimwear' => 3517621, 'surfwear' => 3519639, 't_shirts' => 3519637); // не трогать старые значения
//		public static $names_equality = array(3517616 => 'underwear', 3517621 => 'swimwear', 3519639 => 'surfwear', 3519637 => 't_shirts'); // не трогать старые значения
        public static $categories_equality = array('underwear' => 3517616, 'swimwear' => 3517621, 'menswear' => 3519637); // не трогать новые значения
		public static $names_equality = array(3517616 => 'underwear', 3517621 => 'swimwear', 3519637 => 'menswear'); // не трогать новые значения
		
	}
?>