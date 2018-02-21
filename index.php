<?php
	require_once './cc-charts/ajax.php';
	include_once './admin/includes.php';


	$donor_lists = array();
	$donor_list_names = Config::$donor_pages;

	$links_list = array();


	$donor = new Donor();
	$config = new Config();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
		<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-113139169-3"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-113139169-3');
</script>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Title</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->


    <!-- FONT AWESOME -->

    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">


    <!-- GOOGLE FONTS ################### -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700,900" rel="stylesheet">

    <!-- MY CSS STYLE ###################### -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="cc-charts/assets/vendor/select2/css/select2.min.css" />
<link rel="stylesheet" type="text/css" href="cc-charts/assets/vendor/amstock/plugins/export/export.css" />
<link rel="stylesheet" type="text/css" href="cc-charts/assets/css/style.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.js"></script>
<script src="cc-charts/assets/vendor/amstock/amcharts.js"></script>
<script src="cc-charts/assets/vendor/amstock/serial.js"></script>
<script src="cc-charts/assets/vendor/amstock/amstock.js"></script>
<script src="cc-charts/assets/vendor/amstock/plugins/export/export.min.js"></script>
<script src="cc-charts/assets/vendor/select2/js/select2.full.js"></script>
<script src="cc-charts/assets/js/app.min.js"></script>
<!-- Facebook Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '370992336698309');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=370992336698309&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->

	</head>
  <body>
    <header class="main-nav">
    	<div class="container">
    		<div class="row">
    			<div class="logo col-md-2 col-sm-2"><img src="img/logo.png" alt=""></div>
    			<nav class="col-md-7 col-sm-10">
    				<ul>
    					<li><a href="#one">Индексы</a></li>
    					<li><a href="#five">Экспертный совет</a></li>
    					<li><a href="#six">Контакты</a></li>
    					<li><a href="documents.html">Документы</a></li>
    				</ul>
    			</nav>
    		</div>
    	</div>
    </header>

    <section id="one">
    	<div class="container">
    		<div class="row mb">
    			<div class="one__box col-md-12">
    				<div class="one__header">индексы</div>
    			</div>
    		</div>
    		<div class="row">
<? $date_finish = explode('.',date('d.m.Y.H.i', time()));

	$sec = $date_finish[3]*60*60+$date_finish[4]*60;

	if ($sec<=(7*60*60)) {
		$date_finish = mktime (23, 59, 0, $date_finish[1], $date_finish[0]-2, $date_finish[2]);

	}
	ELSE {
		$date_finish = mktime (23, 59, 0, $date_finish[1], $date_finish[0]-1, $date_finish[2]);
	}

	$date_start = $date_finish - 40*24*60*60; // - 40 дней

	$date_calculate = $date_finish; // strtotime('02.07.2017');
	$index1 = $donor->Index1Calculate24h($config::get_connect(), $config::db_main, $date_calculate);
	$dynamic_ind = $donor->GetCacheindexCalc($config::get_connect(), $config::db_main, $date_calculate);
	//echo('<pre>');print_r($dynamic_ind);echo('</pre>');
	

	?>
    			<div class="one__table mmr">
    					<div class="one__table_header">
    						Динамика индексов на <? echo (date('d.m.Y',$date_calculate)); ?>
    					</div>
    					<table class="table">
						  <thead>
						  	<!-- <div class="yellow__line"></div> -->
						    <tr class="my__theader">
						      <th scope="col">Название</th>
						      <th scope="col">Текущее значение</th>
						      <th scope="col">1 день</th>
						      <th scope="col">7 дней</th>
						      <th scope="col">14 дней</th>
						      <th scope="col">1 месяц</th>
						      <th scope="col">3 месяца</th>
						    </tr>
						  </thead>
						  <tbody>
<?


			foreach ($dynamic_ind as $key => $value) { ?>
						    <tr class="my__line">
						      <th scope="row"> <? echo ($value['NameIndex']); ?></th>
						      <td <? echo (($value['ValueIndexUP']=='1')  ? '' : 'class="my__line_red"'); ?> > <? echo (number_format($value['ValueIndex'],2,'.','')); echo (($value['ValueIndexUP']=='1')  ? '<i class="fa fa-arrow-up" aria-hidden="true"></i>' : '<i class="fa fa-arrow-down" aria-hidden="true"></i>');?>  </td>
						      <td <? echo (($value['PersentDay1UP']=='1')  ? '' : 'class="my__line_red"'); ?> > <? echo (number_format($value['PersentDay1'],2,'.','').'%'); echo (($value['PersentDay1UP']=='1')  ? '<i class="fa fa-arrow-up" aria-hidden="true"></i>' : '<i class="fa fa-arrow-down" aria-hidden="true"></i>');?> </td>
						      <td <? echo (($value['PersentDay7UP']=='1')  ? '' : 'class="my__line_red"'); ?> ><? echo (number_format($value['PersentDay7'],2,'.','').'%'); echo (($value['PersentDay7UP']=='1')  ? '<i class="fa fa-arrow-up" aria-hidden="true"></i>' : '<i class="fa fa-arrow-down" aria-hidden="true"></i>');?>  </td>
						      <td <? echo (($value['PersentDay14UP']=='1')  ? '' : 'class="my__line_red"'); ?> ><? echo (number_format($value['PersentDay14'],2,'.','').'%'); echo (($value['PersentDay14UP']=='1')  ? '<i class="fa fa-arrow-up" aria-hidden="true"></i>' : '<i class="fa fa-arrow-down" aria-hidden="true"></i>');?></td>
						      <td <? echo (($value['PersentDay31UP']=='1')  ? '' : 'class="my__line_red"'); ?> ><? echo (number_format($value['PersentDay31'],2,'.','').'%'); echo (($value['PersentDay31UP']=='1')  ? '<i class="fa fa-arrow-up" aria-hidden="true"></i>' : '<i class="fa fa-arrow-down" aria-hidden="true"></i>');?></td>
						      <td <? echo (($value['PersentDay90UP']=='1')  ? '' : 'class="my__line_red"'); ?> ><? echo (number_format($value['PersentDay90'],2,'.','').'%'); echo (($value['PersentDay90UP']=='1')  ? '<i class="fa fa-arrow-up" aria-hidden="true"></i>' : '<i class="fa fa-arrow-down" aria-hidden="true"></i>');?> </td>
						    </tr>
<? 			} ?>

						  </tbody>
						</table>
    			</div>
    		</div>
    	</div>
    </section>

    <section id="two" class="section__same">
    	<div class="container">
    		<div class="row">
    			<div class="two__header">
    				<p><span>Индекс ТОП-10</span>  Индекс расчитываеться в долларах США на основе ежедневной динамики десяти крупнейших по капитализации криптовалют<br></p>
    			</div>
    		</div>
    		<div class="row">
    			<div class="section__same_box col-md-8 col-md-offset-2">
    			<div id="my-cc-chart" class="ccc-chart-container"></div>
<script>
  cryptocurrencyChartsPlugin.buildChart(
    'my-cc-chart', // HTML container ID
    7777, // ID of cryptocurrency (see full list below)
    '', // display currency
    {
      primaryChartType: "smoothedLine",
      secondaryChartType: "column",
      primaryLineColor: "red",
      primaryPanelTitle: "Index TOP10",
      secondaryPanelTitle: "The total capitalization of the index, bln. USD:",
      width: "100%",
      height: "100%",
      exportEnabled: false
    }, // settings
    'assets/images/coins/7777-ATOP.png' // path to background image logo
  );
</script>
</div>
    		</div>
    		<div class="row">
    			<div class="one__table one__table_f col-md-8 col-md-offset-2">
    					<table class="table borderless">
						  <thead>
						  	<!-- <div class="yellow__line"></div> -->
						    <tr class="my__theader">
						      <th scope="col"></th>
					<? 		for ($i = 1; $i <= 5; $i++) { ?>
						      <th scope="col"><? echo ($dynamic_ind[0]['NameCurrenciesTOP'][$i]);  ?> </th>
						      <? } ?>
						    </tr>
						  </thead>
						  <tbody>
						    <tr class="my__line">
						      <th scope="row">ЦЕНА В USD </th>
					 <? 		for ($i = 1; $i <= 5; $i++) { ?>
						      <td><? echo ($dynamic_ind[0]['CurrentCloseTOP'][$i]);  ?> </td>
						      <? } ?>
						    </tr>
						    <tr class="my__line">
						      <th scope="row">Доля в индексе</th>
						       <? 		for ($i = 1; $i <= 5; $i++) { ?>
						      <td><? echo (number_format($dynamic_ind[0]['VESmarketCapTOP'][$i]*100,2,'.','').'%');  ?> </td>
						      <? } ?>
						    </tr>
						  </tbody>
						</table>
    			</div>
    			<div class="one__table one__table_f one__table_b col-md-8  kkz col-md-offset-2">
    					<table class="table borderless">
						  <thead>
						  	<!-- <div class="yellow__line"></div> -->
						    <tr class="my__theader">
						      <th scope="col"></th>
						      <? 		for ($i = 6; $i <= 10; $i++) { ?>
						      <th scope="col"><? echo ($dynamic_ind[0]['NameCurrenciesTOP'][$i]); ?> </th>
						      <? } ?>

						    </tr>
						  </thead>
						  <tbody>
						    <tr class="my__line">
						      <th scope="row">ЦЕНА В USD </th>
				<? 		for ($i = 6; $i <= 10; $i++) { ?>
						      <td><? echo ($dynamic_ind[0]['CurrentCloseTOP'][$i]);  ?> </td>
						      <? } ?>
						    </tr>
						    <tr class="my__line">
						      <th scope="row">Доля в индексе</th>
				 <? 		for ($i = 6; $i <= 10; $i++) { ?>
						      <td><? echo (number_format($dynamic_ind[0]['VESmarketCapTOP'][$i]*100,2,'.','').'%');  ?> </td>
						      <? } ?>
						    </tr>
						  </tbody>
						</table>
    			</div>

    	</div>
    </section>
<? /*
    <section id="three" class="section__same">
    	<div class="container">
    		<div class="row">
    			<div class="two__header">
    				<p><span>  Индекс 2 </span>     СРО НФА рассчитывает финансовый ииндикатор  	MosPrime Rate СРО НФА рассчитывае <br>финансовый индикатор MosPrime Rate</p>
    			</div>
    		</div>
    		<div class="row">
    			<div class="section__same_box col-md-8 col-md-offset-2"></div>
    		</div>
    		<div class="row">
    			<div class="one__table one__table_nb col-md-8 col-md-offset-2">
    					<div class="one__table_header">
    						Состав индекса на  28/12/2017
    					</div>
    					<table class="table borderless">
						  <thead>
						  	<!-- <div class="yellow__line"></div> -->
						    <tr class="my__theader">
						      <th scope="col"></th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						    </tr>
						  </thead>
						  <tbody>
						    <tr class="my__line">
						      <th scope="row">ЦЕНА В USD </th>
						      <td>151181.00 (-5.60%) </td>
						      <td> 151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						    </tr>
						    <tr class="my__line">
						      <th scope="row">Доля в индексе</th>
						      <td>35.89% </td>
						      <td> 35.89% </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						    </tr>
						  </tbody>
						</table>
    			</div>
    			<div class="one__table one__table_b col-md-8 col-md-offset-2 mmr">
    				<div class="yellow__line"></div>
    					<table class="table borderless">
						  <thead>
						  	<!-- <div class="yellow__line"></div> -->
						    <tr class="my__theader">
						      <th scope="col"></th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						    </tr>
						  </thead>
						  <tbody>
						    <tr class="my__line">
						      <th scope="row">ЦЕНА В USD </th>
						      <td>151181.00 (-5.60%) </td>
						      <td> 151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						    </tr>
						    <tr class="my__line">
						      <th scope="row">Доля в индексе</th>
						      <td>35.89% </td>
						      <td> 35.89% </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						    </tr>
						  </tbody>
						</table>
    			</div>
    		</div>
    	</div>
    </section>

	<section id="four" class="section__same">
    	<div class="container">
    		<div class="row">
    			<div class="two__header">
    				<p><span>Индекс 3</span>  СРО НФА рассчитывает финансовый ииндикатор  	MosPrime Rate СРО НФА рассчитывает <br>финансовый индикатор MosPrime Rate</p>
    			</div>
    		</div>
    		<div class="row">
    			<div class="section__same_box col-md-8 col-md-offset-2"></div>
    		</div>
    		<div class="row">
    			<div class="one__table one__table_f mmr col-md-8 col-md-offset-2">
    					<div class="one__table_header">
    						Состав индекса на  28/12/2017
    					</div>
    					<table class="table borderless">
						  <thead>
						  	<!-- <div class="yellow__line"></div> -->
						    <tr class="my__theader">
						      <th scope="col"></th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						    </tr>
						  </thead>
						  <tbody>
						    <tr class="my__line">
						      <th scope="row">ЦЕНА В USD </th>
						      <td>151181.00 (-5.60%) </td>
						      <td> 151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						    </tr>
						    <tr class="my__line">
						      <th scope="row">Доля в индексе</th>
						      <td>35.89% </td>
						      <td> 35.89% </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						    </tr>
						  </tbody>
						</table>
    			</div>
    			<div class="one__table one__table_f one__table_b col-md-8 col-md-offset-2">
    					<table class="table borderless">
						  <thead>
						  	<!-- <div class="yellow__line"></div> -->
						    <tr class="my__theader">
						      <th scope="col"></th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						    </tr>
						  </thead>
						  <tbody>
						    <tr class="my__line">
						      <th scope="row">ЦЕНА В USD </th>
						      <td>151181.00 (-5.60%) </td>
						      <td> 151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						    </tr>
						    <tr class="my__line">
						      <th scope="row">Доля в индексе</th>
						      <td>35.89% </td>
						      <td> 35.89% </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						    </tr>
						  </tbody>
						</table>
    			</div>
    			<div class="one__table one__table_f col-md-8 col-md-offset-2">
    					<table class="table borderless">
						  <thead>
						  	<!-- <div class="yellow__line"></div> -->
						    <tr class="my__theader">
						      <th scope="col"></th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						      <th scope="col">Bitcoin(BTC) </th>
						    </tr>
						  </thead>
						  <tbody>
						    <tr class="my__line">
						      <th scope="row">ЦЕНА В USD </th>
						      <td>151181.00 (-5.60%) </td>
						      <td> 151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						      <td>151181.00 (-5.60%) </td>
						    </tr>
						    <tr class="my__line">
						      <th scope="row">Доля в индексе</th>
						      <td>35.89% </td>
						      <td> 35.89% </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						      <td>35.89%  </td>
						    </tr>
						  </tbody>
						</table>
    			</div>
    		</div>
    	</div>
    </section>
*/ ?>
    <section id="five">
    	<div class="container">
    		<div class="one__box one__box_expert col-md-12">
    				<div class="one__header one__header_expert">Экпертный совет</div>
    			</div>
    		<div class="row">
    			<div class="five__box col-md-8 col-md-offset-2">
    				<div class="five__box_child">
    					<div class="child__img">

    					</div>
    					<div class="child__contact">Фамилия Имя Отчество</div>
    					<div class="child__social">
    						<a href="#">
    							<i class="fa fa-facebook" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-twitter" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-envelope" aria-hidden="true"></i>
    						</a>
    					</div>
    					<div class="child__specialist">Председатль экспертного совета</div>
    					<div class="child__text">описание <br>описание<br>описание</div>
    				</div>

					<div class="five__box_child col-md-4">
    					<div class="child__img">

    					</div>
    					<div class="child__contact">Фамилия Имя Отчество</div>
    					<div class="child__social">
    						<a href="#">
    							<i class="fa fa-facebook" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-twitter" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-envelope" aria-hidden="true"></i>
    						</a>
    					</div>
    					<div class="child__specialist">Заместитель Председателя совета</div>
    					<div class="child__text">описание <br>описание<br>описание</div>
    				</div>

    				<div class="five__box_child col-md-4">
    					<div class="child__img">
<img src="img/petrov.jpg" alt="">
    					</div>
    					<div class="child__contact">Петров Алексей Викторович</div>
    					<div class="child__social">
    						<a href="#">
    							<i class="fa fa-facebook" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-twitter" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-envelope" aria-hidden="true"></i>
    						</a>
    					</div>
    					<div class="child__specialist">Секретарь экспертного совета</div>
    					<div class="child__text">Эксперт <br>Консультант <br>Криптоинвестор </div>
    				</div>

    				<div class="five__box_child col-md-4">
    					<div class="child__img">

    					</div>
    					<div class="child__contact">Фамилия Имя Отчество</div>
    					<div class="child__social">
    						<a href="#">
    							<i class="fa fa-facebook" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-twitter" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-envelope" aria-hidden="true"></i>
    						</a>
    					</div>
    					<div class="child__specialist">Член экспертного совета</div>
    					<div class="child__text">описание <br>описание<br>описание</div>
    				</div>

    				<div class="five__box_child col-md-4">
    					<div class="child__img">

    					</div>
    					<div class="child__contact">Фамилия Имя Отчество</div>
    					<div class="child__social">
    						<a href="#">
    							<i class="fa fa-facebook" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-twitter" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-envelope" aria-hidden="true"></i>
    						</a>
    					</div>
    					<div class="child__specialist">Член экспертного совета</div>
    					<div class="child__text">описание <br>описание<br>описание</div>
    				</div>

    				<div class="five__box_child col-md-4">
    					<div class="child__img">

    					</div>
    					<div class="child__contact">Фамилия Имя Отчество</div>
    					<div class="child__social">
    						<a href="#">
    							<i class="fa fa-facebook" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-twitter" aria-hidden="true"></i>
    						</a>
    						<a href="#">
    							<i class="fa fa-envelope" aria-hidden="true"></i>
    						</a>
    					</div>
    					<div class="child__specialist">Член экспертного совета</div>
    					<div class="child__text">описание <br>описание<br>описание</div>
    				</div>
    			</div>
    		</div>
    	</div>
    </section>

    <section id="six">
    	<div class="container">
    		<div class="row">
    			<div class="row mb">
    			<div class="one__box col-md-12">
    				<div class="one__header">контакты</div>
    			</div>
    			<div class="six__contacts">
    				<div class="contacts__phone">
    					<p>Телефон:</p><p> +7(495) 123-45-67</p>
    				</div>
    				<div class="contacts__mail">
    					<p>Email:</p><p>S@3aIndex.ru</p>
    				</div>
    			</div>
    		</div>
    		</div>
    	</div>
    </section>

    <footer>
    	<div class="container">
    		<div class="row">
    			<div class="logo col-md-2"><img src="img/logo.png" alt=""></div>
    			<nav class="col-md-7 ">
    				<ul>
    					<li><a href="#one">Индексы</a></li>
    					<li><a href="#five">Экспертный совет</a></li>
    					<li><a href="#six">Контакты</a></li>
    					<li><a href="documents.html">Документы</a></li>
    				</ul>
    			</nav>
    		</div>
    	</div>
    </footer>

    <script src="js/main.scroll.js"></script>
	<script src="js/jquery.scroller.js"></script>
	<script src="js/demo.js"></script>
  </body>
</html>
